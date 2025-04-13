<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Inertia\Inertia;
use App\Services\DocumentUploadService;
use App\Services\OcrService;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::orderBy('created_at', 'desc')->get();

        return Inertia::render('Documents', [
            'documents' => $documents,
        ]);
    }

    public function show(Document $document)
    {
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
        $type = $request->input('type', 'school');

        if ($type === 'student') {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
                'categories' => 'required|array',
                'lrns' => 'required|array',
                'types' => 'required|array',
            ]);
        } else {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
            ]);
        }

        foreach ($request->file('files') as $index => $file) {
            $category = $request->input('categories')[$index] ?? 'Uncategorized';
            $lrn = $request->input('lrns')[$index] ?? null;
            $docType = $request->input('types')[$index] ?? 'school';

            $uploadService->handle($file, $category, $lrn, $docType, $ocrService);
        }

        return back()->with('message', 'Files uploaded successfully with OCR!');
    }
}
