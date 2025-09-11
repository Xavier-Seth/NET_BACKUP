<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolPropertyDocument;
use App\Models\Category;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Services\LogService; // ✅ Import LogService

class SchoolPropertyDocumentController extends Controller
{
    /**
     * Display a listing of the school property documents.
     */
    public function index(Request $request)
    {
        $query = SchoolPropertyDocument::with(['user', 'category'])
            ->orderBy('created_at', 'desc');

        // 🔎 Filter by category
        if ($request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // 🔎 Search by document_no or file name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('document_no', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $documents = $query->paginate(20)->withQueryString();
        $categories = Category::whereIn('name', ['ICS', 'RIS'])->get();

        return Inertia::render('DocumentsSchoolProperties', [
            'documents' => $documents,
            'categories' => $categories,
            'filters' => $request->only('category', 'search'),
        ]);
    }

    /**
     * Download and decrypt the given document.
     */
    public function download(SchoolPropertyDocument $schoolDocument)
    {
        $encryptedPath = $schoolDocument->path;

        if (!$encryptedPath || !Storage::disk('public')->exists($encryptedPath)) {
            abort(404, 'File not found.');
        }

        try {
            $encryptedContent = Storage::disk('public')->get($encryptedPath);

            $decryptedContent = app(\App\Services\DocumentUploadService::class)
                ->decryptFileContents($encryptedContent);

            $tempFilePath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($tempFilePath, $decryptedContent);

            return response()->download($tempFilePath, $schoolDocument->name)
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error("❌ Download error: " . $e->getMessage());
            return back()->with('error', 'Failed to decrypt or download the file.');
        }
    }

    /**
     * Delete the given document and log the action.
     */
    public function destroy(SchoolPropertyDocument $schoolDocument)
    {
        try {
            // Capture details before deletion
            $docName = $schoolDocument->name;
            $categoryName = optional($schoolDocument->category)->name ?? 'N/A';
            $uploadedBy = optional($schoolDocument->user)->name ?? 'Unknown';

            // Delete file + DB record
            Storage::disk('public')->delete($schoolDocument->path);
            $schoolDocument->delete();

            // Log deletion
            LogService::record("Deleted school property document '{$docName}' under category '{$categoryName}' uploaded by '{$uploadedBy}'");

            return redirect()->back()->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            \Log::error("❌ Delete failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete document.');
        }
    }
}
