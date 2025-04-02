<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;
use Dompdf\Dompdf;
use Illuminate\Http\UploadedFile;

class DocumentUploadService
{
    public function handle(
        UploadedFile $file,
        ?string $category = null,
        ?string $lrn = null,
        string $type = 'school' // default to 'school'
    ): ?Document {
        $path = $file->store('documents', 'public');
        $pdfPreviewPath = null;

        $extension = $file->getClientOriginalExtension();

        try {
            $htmlPath = null;

            // Convert .docx to HTML
            if ($extension === 'docx') {
                $phpWord = WordIOFactory::load(storage_path("app/public/{$path}"));
                $htmlPath = storage_path('app/public/temp-docx.html');
                WordIOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);
            }

            // Convert Excel to HTML
            if (in_array($extension, ['xls', 'xlsx'])) {
                $spreadsheet = ExcelIOFactory::load(storage_path("app/public/{$path}"));
                $htmlPath = storage_path('app/public/temp-excel.html');
                ExcelIOFactory::createWriter($spreadsheet, 'Html')->save($htmlPath);
            }

            // Convert HTML to PDF
            if ($htmlPath && file_exists($htmlPath)) {
                $dompdf = new Dompdf();
                $dompdf->loadHtml(file_get_contents($htmlPath));
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $pdfFileName = 'converted/' . pathinfo($file->hashName(), PATHINFO_FILENAME) . '.pdf';
                Storage::disk('public')->put($pdfFileName, $dompdf->output());

                if (Storage::disk('public')->exists($pdfFileName)) {
                    $pdfPreviewPath = $pdfFileName;
                }
            }
        } catch (\Exception $e) {
            Log::error("❌ Conversion failed for {$file->getClientOriginalName()}: " . $e->getMessage());
        }

        return Document::create([
            'user_id' => auth()->id(),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $type,
            'category' => $type === 'student' ? $category : null,
            'lrn' => $type === 'student' ? $lrn : null,
            'pdf_preview_path' => $pdfPreviewPath,
        ]);
    }
}
