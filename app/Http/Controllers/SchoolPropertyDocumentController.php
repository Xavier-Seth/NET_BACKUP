<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolPropertyDocument;
use App\Models\Category;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use App\Services\LogService; // ✅ Import the log service
use Symfony\Component\HttpFoundation\Response;

class SchoolPropertyDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolPropertyDocument::with(['user', 'category'])->orderBy('created_at', 'desc');

        // Filter by Category (ICS / RIS)
        if ($request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Search by Document No or Name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('document_no', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $documents = $query->paginate(20);
        $categories = Category::whereIn('name', ['ICS', 'RIS'])->get();

        return Inertia::render('DocumentsSchoolProperties', [
            'documents' => $documents,
            'categories' => $categories,
            'filters' => $request->only('category', 'search'),
        ]);
    }

    public function download(SchoolPropertyDocument $schoolDocument)
    {
        $encryptedPath = $schoolDocument->path;

        if (!$encryptedPath) {
            abort(404, 'Missing file path.');
        }

        if (!Storage::disk('public')->exists($encryptedPath)) {
            abort(404, 'Encrypted file not found.');
        }

        try {
            $encryptedContent = Storage::disk('public')->get($encryptedPath);

            $decryptedContent = app(\App\Services\DocumentUploadService::class)
                ->decryptFileContents($encryptedContent);

            $tempFilePath = tempnam(sys_get_temp_dir(), 'doc_');
            file_put_contents($tempFilePath, $decryptedContent);

            return response()->download($tempFilePath, $schoolDocument->name)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error("❌ Download error: " . $e->getMessage());
            return back()->with('error', 'Failed to decrypt or download.');
        }
    }

    public function destroy(SchoolPropertyDocument $schoolDocument)
    {
        try {
            // Store details before delete
            $docName = $schoolDocument->name;
            $categoryName = optional($schoolDocument->category)->name ?? 'N/A';
            $uploadedBy = optional($schoolDocument->user)->name ?? 'Unknown';

            Storage::disk('public')->delete($schoolDocument->path);
            $schoolDocument->delete();

            // ✅ Log the deletion
            LogService::record("Deleted school property document '{$docName}' under category '{$categoryName}' uploaded by '{$uploadedBy}'");

            return redirect()->back()->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            \Log::error("Delete failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Delete failed.');
        }
    }
}
