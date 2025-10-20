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

    /**
     * Multi-upload entry for Teacher docs AND ICS/RIS school-property docs.
     * Uses DocumentUploadService::handle() which returns either Document
     * or SchoolPropertyDocument instance. We log safely for both.
     */
    public function upload(Request $request, DocumentUploadService $uploadService)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
            'meta' => 'nullable|string',
            'allow_duplicate' => 'nullable|boolean',
            'skip_ocr' => 'nullable|boolean',
            'scanned_text' => 'nullable|string',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $user = $request->user() ?? User::findOrFail(auth()->id());

        // Parse meta (if posted)
        $meta = [];
        if ($request->filled('meta')) {
            try {
                $meta = json_decode($request->input('meta'), true) ?: [];
                if (!is_array($meta))
                    $meta = [];
            } catch (\Throwable $e) {
                $meta = [];
            }
        }

        // Lookup by filename
        $byName = [];
        foreach ($meta as $m) {
            if (!empty($m['name']) && is_string($m['name'])) {
                $byName[$m['name']] = $m;
            }
        }

        $skipOcr = (bool) $request->boolean('skip_ocr');
        $globalAllowDuplicate = (bool) $request->boolean('allow_duplicate');

        // Legacy fallback
        $legacyTeacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $legacyCategoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $legacyScannedText = $request->input('scanned_text');

        $results = [];

        foreach ($request->file('files') as $file) {
            $orig = $file->getClientOriginalName();
            $m = $byName[$orig] ?? [];

            // Effective per-file values
            $categoryId = isset($m['category_id']) ? (int) $m['category_id'] : $legacyCategoryId;
            $teacherId = isset($m['teacher_id']) ? (int) $m['teacher_id'] : $legacyTeacherId;
            $override = (bool) ($m['override'] ?? $request->boolean('override'));
            $scanned = $m['scanned_text'] ?? $legacyScannedText ?? '';

            // Duplicate rules (only matter for teacher docs)
            if ($teacherId && !$globalAllowDuplicate) {
                $sameCat = $categoryId ? $this->isDuplicate($teacherId, $categoryId) : false;
                $sameName = $this->hasSameNameForTeacher($teacherId, $orig, $categoryId);
                if ($sameCat || $sameName) {
                    $results[] = [
                        'name' => $orig,
                        'success' => false,
                        'duplicate' => true,
                        'message' => $sameName ? 'Duplicate filename detected' : 'Duplicate document type detected',
                    ];
                    continue;
                }
            }

            try {
                // Save file (service returns Document or SchoolPropertyDocument)
                $doc = $uploadService->handle(
                    $file,
                    $teacherId,
                    $categoryId,
                    $user,
                    [
                        'allow_duplicate' => $globalAllowDuplicate,
                        'skip_ocr' => $skipOcr,
                        'scanned_text' => $scanned,
                        'override' => $override,
                    ]
                );

                // --- Safe logging for both models ---
                $relations = [];
                if (method_exists($doc, 'teacher'))
                    $relations[] = 'teacher';
                if (method_exists($doc, 'category'))
                    $relations[] = 'category';
                if ($relations)
                    $doc->loadMissing($relations);

                $typeLabel = $doc instanceof \App\Models\SchoolPropertyDocument ? 'school property document' : 'document';
                $teacherPart = (method_exists($doc, 'teacher') && $doc->relationLoaded('teacher') && $doc->teacher)
                    ? " for teacher {$doc->teacher->full_name}" : '';
                $categoryPart = (method_exists($doc, 'category') && $doc->relationLoaded('category') && $doc->category)
                    ? " under category '{$doc->category->name}'" : '';

                LogService::record("Uploaded {$typeLabel} '{$doc->name}'{$teacherPart}{$categoryPart}.");
                // ------------------------------------

                $results[] = [
                    'name' => $orig,
                    'success' => true,
                    'duplicate' => false,
                    'id' => $doc->id,
                ];
            } catch (\Throwable $e) {
                report($e);
                $results[] = [
                    'name' => $orig,
                    'success' => false,
                    'duplicate' => false,
                    'message' => 'Upload error',
                ];
            }
        }

        return response()->json([
            'results' => $results,
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
        $fallbackUsed = false;

        try {
            $response = Http::attach('file', file_get_contents($fullPath), $file->getClientOriginalName())
                ->post('http://127.0.0.1:5000/extract-and-classify');

            if ($response->successful()) {
                $data = $response->json();
                $ocrText = $data['text'] ?? $data['text_preview'] ?? '';
                $matchedCategory = $data['subcategory'] ?? null;
            } else {
                $fallbackUsed = true;
                LaravelLog::error("Flask scan OCR failed: " . $response->body());
            }

            // Normalize to canonical category names
            $matchedCategory = $this->normalizeCategoryName($matchedCategory);

            // Map to category_id
            if ($matchedCategory) {
                $categoryId = Category::whereRaw('LOWER(name) = ?', [strtolower($matchedCategory)])->value('id');
            }

            // Try to detect teacher in OCR text
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
            $fallbackUsed = true;
            LaravelLog::error("Exception during scan: " . $e->getMessage());
        } finally {
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
        }

        return response()->json([
            'teacher' => $matchedTeacher,
            'category' => $matchedCategory,
            'category_id' => $categoryId,
            'belongs_to_teacher' => $belongsToTeacher,
            'text' => $ocrText,
            'fallback_used' => $fallbackUsed,
        ]);
    }

    /** DTR page (list & filter) */
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

    /** Edit metadata modal (name, teacher, category) */
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

        $document->loadMissing(['teacher', 'category']);
        $teacherPart = $document->teacher ? " for teacher {$document->teacher->full_name}" : '';
        $categoryPart = $document->category ? " under category '{$document->category->name}'" : '';

        LogService::record("Updated metadata for document '{$document->name}'{$teacherPart}{$categoryPart}.");

        return back()->with('success', 'Document metadata updated.');
    }

    public function destroy(Document $document)
    {
        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }
        if ($document->pdf_preview_path && Storage::disk('public')->exists($document->pdf_preview_path)) {
            Storage::disk('public')->delete($document->pdf_preview_path);
        }

        $document->loadMissing(['teacher', 'category']);

        $teacherPart = $document->teacher ? " belonging to teacher {$document->teacher->full_name}" : '';
        $categoryPart = $document->category ? " under category '{$document->category->name}'" : '';
        $docName = $document->name;

        $document->delete();

        LogService::record("Deleted document '{$docName}'{$teacherPart}{$categoryPart}.");

        return back()->with('success', 'Document deleted successfully.');
    }

    // ─────────── Helpers ───────────

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

    /** Teacher-doc duplicate check by (teacher_id, category_id) */
    private function isDuplicate(?int $teacherId, ?int $categoryId): bool
    {
        if (!$teacherId || !$categoryId)
            return false;

        return Document::where('teacher_id', $teacherId)
            ->where('category_id', $categoryId)
            ->exists();
    }

    /** Same-name check for a teacher (optionally scoped to category) */
    private function hasSameNameForTeacher(int $teacherId, string $originalName, ?int $categoryId = null): bool
    {
        $q = Document::where('teacher_id', $teacherId)
            ->where('name', $originalName);

        if (!is_null($categoryId)) {
            $q->where('category_id', $categoryId);
        }

        return $q->exists();
    }
}
