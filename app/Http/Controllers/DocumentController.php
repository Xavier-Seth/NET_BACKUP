<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;
use Dompdf\Dompdf;

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

        return response()->file($path);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,docx,xlsx,xls,png,jpg|max:20480',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store('documents', 'public');
            $pdfPreviewPath = null;

            $extension = $file->getClientOriginalExtension();

            try {
                if ($extension === 'docx') {
                    $phpWord = WordIOFactory::load(storage_path("app/public/{$path}"));
                    $htmlPath = storage_path('app/public/temp-docx.html');

                    $htmlWriter = WordIOFactory::createWriter($phpWord, 'HTML');
                    $htmlWriter->save($htmlPath);
                }

                if (in_array($extension, ['xls', 'xlsx'])) {
                    $spreadsheet = ExcelIOFactory::load(storage_path("app/public/{$path}"));
                    $htmlPath = storage_path('app/public/temp-excel.html');

                    $htmlWriter = ExcelIOFactory::createWriter($spreadsheet, 'Html');
                    $htmlWriter->save($htmlPath);
                }

                // If .docx or .xlsx was processed and $htmlPath is defined
                if (isset($htmlPath) && file_exists($htmlPath)) {
                    $dompdf = new Dompdf();
                    $dompdf->loadHtml(file_get_contents($htmlPath));
                    $dompdf->setPaper('A4', 'portrait');
                    $dompdf->render();

                    $pdfFileName = 'converted/' . pathinfo($file->hashName(), PATHINFO_FILENAME) . '.pdf';
                    Storage::disk('public')->put($pdfFileName, $dompdf->output());

                    if (Storage::disk('public')->exists($pdfFileName)) {
                        $pdfPreviewPath = $pdfFileName;
                    } else {
                        Log::error("❌ PDF not saved to storage: {$pdfFileName}");
                    }
                }
            } catch (\Exception $e) {
                Log::error("❌ Conversion failed for {$file->getClientOriginalName()}: " . $e->getMessage());
            }

            Log::info('📦 Saving document:', [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'category' => $request->category,
                'lrn' => $request->lrn,
                'pdf_preview_path' => $pdfPreviewPath,
            ]);

            Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'category' => $request->category,
                'lrn' => $request->lrn,
                'pdf_preview_path' => $pdfPreviewPath,
            ]);
        }

        return back()->with('message', 'Files uploaded successfully!');
    }
}
