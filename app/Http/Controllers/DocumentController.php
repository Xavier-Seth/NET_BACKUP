<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentUploadService;
use App\Services\OcrService;
use App\Services\CategorizationService;
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

    public function upload(Request $request, DocumentUploadService $uploadService, OcrService $ocrService)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $uploadedDocuments = [];
        $user = User::findOrFail(auth()->id());

        foreach ($request->file('files') as $file) {
            $document = $uploadService->handle(
                $file,
                $request->teacher_id,
                null,
                $ocrService,
                $user
            );

            $uploadedDocuments[] = [
                'file_name' => $document->name,
                'teacher' => optional($document->teacher)->full_name ?? 'N/A',
                'category' => optional($document->category)->name ?? 'N/A',
            ];
        }

        return back()->with('uploadedDocuments', $uploadedDocuments);
    }

    public function scan(Request $request, OcrService $ocrService)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
        ]);

        $file = $request->file('file');
        $tempPath = $file->store('temp-scan', 'public');
        $fullPath = storage_path('app/public/' . $tempPath);

        $ocrText = $ocrService->extractText($fullPath);
        $matchedTeacher = null;

        if (!empty($ocrText)) {
            $teachers = Teacher::all();
            foreach ($teachers as $teacher) {
                if (Str::contains(strtolower($ocrText), strtolower($teacher->full_name))) {
                    $matchedTeacher = $teacher->full_name;
                    break;
                }
            }
        }

        $categorizationService = new CategorizationService();
        $matchedCategory = $categorizationService->categorize($ocrText ?? '', $file->getClientOriginalName());

        $belongsToTeacher = false;
        if ($matchedCategory) {
            $teacherDocumentTypes = $categorizationService->getTeacherDocumentTypes();
            $belongsToTeacher = in_array($matchedCategory, $teacherDocumentTypes);
        }

        if (Storage::disk('public')->exists($tempPath)) {
            Storage::disk('public')->delete($tempPath);
        }

        return response()->json([
            'teacher' => $matchedTeacher,
            'category' => $matchedCategory,
            'belongs_to_teacher' => $belongsToTeacher,
        ]);
    }
}
