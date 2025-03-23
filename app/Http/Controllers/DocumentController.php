<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    // Display all stored documents
    public function index()
    {
        $documents = Document::orderBy('created_at', 'desc')->get();

        return Inertia::render('Documents', [
            'documents' => $documents,
        ]);
    }

    // View a single document in browser
    public function show(Document $document)
    {
        $path = storage_path("app/public/{$document->path}");

        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->file($path);
    }

    // Your existing upload method stays unchanged:
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,docx,png,jpg|max:20480',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store('documents', 'public');

            Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'category' => $request->category,
                'lrn' => $request->lrn,
            ]);
        }

        return back()->with('message', 'Your files have been uploaded successfully!');
    }
}
