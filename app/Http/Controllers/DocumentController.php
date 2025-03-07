<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:20480', // 20MB max
        ]);

        $uploadedFiles = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('documents', 'public'); // Store in 'storage/app/public/documents'

            $document = Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $uploadedFiles[] = $document;
        }

        return Inertia::render('Upload', [
            'message' => 'Files uploaded successfully!',
            'documents' => $uploadedFiles,
        ]);
    }
}

