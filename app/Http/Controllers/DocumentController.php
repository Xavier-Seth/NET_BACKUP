<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LaravelLog;
use App\Services\DocumentUploadService;
use App\Services\CategorizationService;
use App\Services\LogService;
use Illuminate\Support\Str;

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

        return Inertia::render('Documents', [
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
                'Content-Disposition' => 'inline; filename="' . pathinfo($document->name, PATHINFO_FILENAME) . '.pdf"'
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
            'teacher_id' => 'nullable|exists:teachers,id',
            'category_id' => 'nullable|exists:categories,id',
            'action' => 'nullable|in:upload,replace'
        ]);

        $user = User::findOrFail(auth()->id());
        $uploadedDocuments = [];

        // Check only first file for duplicate check logic
        $file = $request->file('files')[0];
        $teacherId = $request->teacher_id;
        $categoryId = $request->category_id;

        // ✅ Check for existing document for same teacher + category
        $existing = Document::where('teacher_id', $teacherId)
            ->where('category_id', $categoryId)
            ->first();

        if ($existing && $request->input('action') !== 'replace') {
            return response()->json([
                'duplicate' => true,
                'existing_document_name' => $existing->name,
            ]);
        }

        // ✅ If replacing, delete the old document first and log it
        if ($existing && $request->input('action') === 'replace') {
            // Delete the file from storage
            if ($existing->path && Storage::disk('public')->exists($existing->path)) {
                Storage::disk('public')->delete($existing->path);
            }

            if ($existing->pdf_preview_path && Storage::disk('public')->exists($existing->pdf_preview_path)) {
                Storage::disk('public')->delete($existing->pdf_preview_path);
            }

            // Log the replacement activity
            LogService::record(
                "Replaced document '{$existing->name}'"
                . ($existing->teacher ? " for teacher {$existing->teacher->full_name}" : "")
            );

            // Delete the document record
            $existing->delete();
        }

        // ✅ Proceed with actual upload
        foreach ($request->file('files') as $file) {
            $document = $uploadService->handle(
                $file,
                $teacherId,
                $categoryId,
                $user
            );

            $uploadedDocuments[] = [
                'file_name' => $document->name,
                'teacher' => optional($document->teacher)->full_name ?? 'N/A',
                'category' => optional($document->category)->name ?? 'N/A',
            ];

            // Log upload
            LogService::record(
                "Uploaded document '{$document->name}'"
                . ($document->teacher ? " for teacher {$document->teacher->full_name}" : "")
            );
        }

        return response()->json([
            'success' => true,
            'uploadedDocuments' => $uploadedDocuments,
        ]);
    }



    public function scan(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
        ]);

        $file = $request->file('file');
        $tempPath = $file->store('temp-scan', 'public');
        $fullPath = storage_path('app/public/' . $tempPath);
        $ocrText = '';
        $matchedTeacher = null;
        $matchedCategory = null;
        $belongsToTeacher = false;

        try {
            $response = Http::attach('file', file_get_contents($fullPath), $file->getClientOriginalName())
                ->post('http://127.0.0.1:5000/extract-and-classify');

            if ($response->successful()) {
                $data = $response->json();
                $ocrText = $data['text'] ?? '';
                $matchedCategory = $data['subcategory'] ?? null;
            } else {
                LaravelLog::error("❌ Flask scan OCR failed: " . $response->body());
            }

            if (!empty($ocrText)) {
                $teachers = Teacher::all();
                foreach ($teachers as $teacher) {
                    if (Str::contains(strtolower($ocrText), strtolower($teacher->full_name))) {
                        $matchedTeacher = $teacher->full_name;
                        break;
                    }
                }
            }

            if ($matchedCategory) {
                $teacherDocumentTypes = (new CategorizationService())->getTeacherDocumentTypes();
                $belongsToTeacher = in_array($matchedCategory, $teacherDocumentTypes);
            }

        } catch (\Exception $e) {
            LaravelLog::error("❌ Exception during scan: " . $e->getMessage());
        } finally {
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
        }

        return response()->json([
            'teacher' => $matchedTeacher,
            'category' => $matchedCategory,
            'belongs_to_teacher' => $belongsToTeacher,
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
            "Deleted document '{$docName}'" .
            ($teacherName !== 'N/A' ? " belonging to teacher {$teacherName}" : "")
        );

        return back()->with('success', 'Document deleted successfully.');
    }
}
