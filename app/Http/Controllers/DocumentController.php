<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Inertia\Inertia;
use App\Services\DocumentUploadService;

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
        $path = storage_path("app/public/{$document->filepath}");

        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->file($path);
    }

    public function upload(Request $request, DocumentUploadService $uploadService)
    {
        /** @var string|null $type */
        $type = $request->input('type', 'school'); // default to 'school' if not set

        // Validation
        if ($type === 'student') {
            $request->validate([
                'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
                'lrn' => 'required|string|max:20',
                'category' => 'required|string|max:50',
            ]);
        } else {
            $request->validate([
                'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
            ]);
        }

        // Upload each file
        foreach ($request->file('files') as $file) {
            $uploadService->handle(
                $file,
                $request->input('category'),
                $request->input('lrn'),
                $type // ✅ passed here
            );
        }

        return back()->with('message', 'Files uploaded successfully!');
    }
}
