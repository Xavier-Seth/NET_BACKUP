<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Str;

use App\Models\Document;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\User;

use App\Services\DocumentUploadService;
use App\Services\CategorizationService;
use App\Services\LogService;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with(['teacher', 'category', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $teachers = Teacher::orderBy('full_name')->get();
        $categories = Category::orderBy('name')->get();

        $categorizationService = new CategorizationService();
        $teacherDocumentTypes = $categorizationService->getTeacherDocumentTypes();

        return Inertia::render('Teacher/TeacherProfile', [
            'documents' => $documents,
            'teachers' => $teachers,
            'categories' => $categories,
            'teacherDocumentTypes' => $teacherDocumentTypes,
        ]);
    }

    public function preview(Document $document)
    {
        if ($document->pdf_preview_path && Storage::disk('public')->exists($document->pdf_preview_path)) {
            $fullPath = Storage::disk('public')->path($document->pdf_preview_path);
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . pathinfo($document->name, PATHINFO_FILENAME) . '.pdf"',
            ]);
        }

        $path = storage_path("app/public/{$document->path}");
        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        $encrypted = file_get_contents($path);
        $decrypted = (new DocumentUploadService)->decryptFileContents($encrypted);

        return response($decrypted)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $document->name . '"');
    }

    public function download(Document $document)
    {
        $path = storage_path("app/public/{$document->path}");
        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        $encrypted = file_get_contents($path);
        $decrypted = (new DocumentUploadService)->decryptFileContents($encrypted);

        return response($decrypted)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'attachment; filename="' . $document->name . '"');
    }

    public function upload(Request $request, DocumentUploadService $uploadService)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            // removed: 'action'
            'allow_duplicate' => 'nullable|boolean',
            'skip_ocr' => 'nullable|boolean',
            'scanned_text' => 'nullable|string',
        ]);

        $user = User::findOrFail(auth()->id());

        // Normalize incoming IDs (treat "" as null)
        $teacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $allowDuplicate = (bool) $request->boolean('allow_duplicate'); // true when Continue(Add Another)

        // ── Duplicate gate (teacher documents only; by teacher_id + category_id) ─────────
        // We check once before processing any files. If blocked, UI shows the modal.
        $existing = null;
        if ($teacherId && $categoryId) {
            $existing = Document::where('teacher_id', $teacherId)
                ->where('category_id', $categoryId)
                ->latest('id')
                ->first();
        }

        // If duplicate exists and UI did NOT allow duplicate → notify UI
        if ($existing && !$allowDuplicate) {
            return response()->json([
                'duplicate' => true,
                'existing_document_name' => $existing->name,
            ]);
        }

        // ── Save all files ─────────────────────────────────────────────────────
        $uploadedDocuments = [];

        foreach ($request->file('files') as $file) {
            $document = $uploadService->handle(
                $file,
                $teacherId,
                $categoryId,
                $user,
                [
                    // removed: 'action'
                    'allow_duplicate' => $allowDuplicate,
                    'skip_ocr' => (bool) $request->boolean('skip_ocr'),
                    'scanned_text' => $request->input('scanned_text'),
                ]
            );

            $uploadedDocuments[] = [
                'file_name' => $document->name,
                'teacher' => optional($document->teacher)->full_name ?? 'N/A',
                'category' => optional($document->category)->name ?? 'N/A',
            ];

            LogService::record("Uploaded document '{$document->name}'"
                . ($document->teacher ? " for teacher {$document->teacher->full_name}" : ''));
        }

        return response()->json([
            'success' => true,
            'uploadedDocuments' => $uploadedDocuments,
        ]);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:102400',
        ]);

        $file = $request->file('file');
        $tempPath = $file->store('temp-scan', 'public');
        $fullPath = storage_path('app/public/' . $tempPath);

        $ocrText = '';
        $matchedTeacher = null;
        $matchedCategory = null;
        $belongsToTeacher = false;
        $categoryId = null;

        try {
            $response = Http::attach('file', file_get_contents($fullPath), $file->getClientOriginalName())
                ->post('http://127.0.0.1:5000/extract-and-classify');

            if ($response->successful()) {
                $data = $response->json();
                $ocrText = $data['text'] ?? $data['text_preview'] ?? '';
                $matchedCategory = $data['subcategory'] ?? null;
            } else {
                LaravelLog::error("Flask scan OCR failed: " . $response->body());
            }

            // Normalize category (TOR -> Transcript of Records, etc.)
            $matchedCategory = $this->normalizeCategoryName($matchedCategory);

            // Resolve to category_id if possible (case-insensitive)
            if ($matchedCategory) {
                $categoryId = Category::whereRaw('LOWER(name) = ?', [strtolower($matchedCategory)])->value('id');
            }

            // Try to find teacher name inside OCR text
            if (!empty($ocrText)) {
                foreach (Teacher::all() as $teacher) {
                    if (Str::contains(strtolower($ocrText), strtolower($teacher->full_name))) {
                        $matchedTeacher = $teacher->full_name;
                        break;
                    }
                }
            }

            if ($matchedCategory) {
                $teacherDocumentTypes = (new CategorizationService())->getTeacherDocumentTypes();
                $belongsToTeacher = collect($teacherDocumentTypes)
                    ->map(fn($n) => strtolower($n))
                    ->contains(strtolower($matchedCategory));
            }
        } catch (\Exception $e) {
            LaravelLog::error("Exception during scan: " . $e->getMessage());
        } finally {
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
        }

        return response()->json([
            'teacher' => $matchedTeacher,
            'category' => $matchedCategory,  // normalized canonical name
            'category_id' => $categoryId,       // helps the UI map directly
            'belongs_to_teacher' => $belongsToTeacher,
            'text' => $ocrText,          // expose text for reuse
        ]);
    }

    public function destroy(Document $document)
    {
        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }
        if ($document->pdf_preview_path && Storage::disk('public')->exists($document->pdf_preview_path)) {
            Storage::disk('public')->delete($document->pdf_preview_path);
        }

        $teacherName = optional($document->teacher)->full_name ?? 'N/A';
        $docName = $document->name;

        $document->delete();

        LogService::record(
            "Deleted document '{$docName}'"
            . ($teacherName !== 'N/A' ? " belonging to teacher {$teacherName}" : '')
        );

        return back()->with('success', 'Document deleted successfully.');
    }

    public function dtrIndex(Request $request)
    {
        $query = Document::with(['teacher', 'category'])
            ->whereHas('category', fn($q) => $q->where('name', 'Daily Time Record'));

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->teacher) {
            $query->whereHas('teacher', fn($q) => $q->where('full_name', $request->teacher));
        }

        return Inertia::render('Teacher/DtrDocuments', [
            'documents' => $query->latest()->paginate(10)->withQueryString(),
            'teachers' => Teacher::orderBy('full_name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'filters' => $request->only(['search', 'teacher']),
        ]);
    }

    public function updateMetadata(Request $request, Document $document)
    {
        $request->validate([
            'teacher_id' => 'nullable|exists:teachers,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'nullable|string|max:255',
        ]);

        $document->update([
            'teacher_id' => $request->teacher_id,
            'category_id' => $request->category_id,
            'name' => $request->name ?? $document->name,
        ]);

        LogService::record("Updated metadata for document '{$document->name}'");

        return back()->with('success', 'Document metadata updated.');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────
    private function normalizeCategoryName(?string $name): ?string
    {
        if (!$name)
            return null;
        $map = [
            'TOR' => 'Transcript of Records',
            'PDS' => 'Personal Data Sheet',
            'COAD' => 'Certification of Assumption to Duty',
            'WES' => 'Work Experience Sheet',
            'DTR' => 'Daily Time Record',
            'APPOINTMENT' => 'Appointment Form',
        ];
        $key = trim($name);
        return $map[$key] ?? $map[strtoupper($key)] ?? $key;
    }
}
