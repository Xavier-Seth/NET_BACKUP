<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Inertia\Inertia;
use App\Services\DocumentUploadService;

class DocumentController extends Controller
{
    // ✅ Show documents page
    public function index()
    {
        $documents = Document::orderBy('created_at', 'desc')->get();

        return Inertia::render('Documents', [
            'documents' => $documents,
        ]);
    }

    // ✅ Serve file for viewing
    public function show(Document $document)
    {
        $path = storage_path("app/public/{$document->filepath}");

        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->file($path);
    }

    // ✅ Handle file uploads
    public function upload(Request $request, DocumentUploadService $uploadService)
    {
        $type = $request->input('type', 'school'); // default to 'school'

        // ✅ Validation
        if ($type === 'student') {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
                'lrn' => 'required|string|max:20',
                'category' => 'required|string|max:50',
            ]);
        } else {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
            ]);
        }

        // ✅ Handle each file using the upload service
        foreach ($request->file('files') as $file) {
            $uploadService->handle(
                $file,
                $request->input('category'),  // may be null for school
                $request->input('lrn'),       // may be null for school
                $type
            );
        }

        return back()->with('message', 'Files uploaded successfully!');
    }
}
