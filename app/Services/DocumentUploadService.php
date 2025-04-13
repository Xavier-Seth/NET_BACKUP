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
        string $type = 'school',
        ?\App\Services\OcrService $ocrService = null
    ): ?Document {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $encryptedContents = $this->encryptFileContents($file->getRealPath());

        $filename = $file->hashName();
        $path = "documents/{$filename}";
        Storage::disk('public')->put($path, $encryptedContents);

        $decryptedPath = storage_path("app/public/temp-decrypted-{$filename}");
        file_put_contents($decryptedPath, $this->decryptFileContents($encryptedContents));

        $pdfPreviewPath = null;
        $ocrText = null;

        try {
            $htmlPath = null;

            if ($extension === 'docx') {
                $phpWord = WordIOFactory::load($decryptedPath);
                $htmlPath = storage_path('app/public/temp-docx.html');
                WordIOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);
            }

            if (in_array($extension, ['xls', 'xlsx'])) {
                $spreadsheet = ExcelIOFactory::load($decryptedPath);
                $htmlPath = storage_path('app/public/temp-excel.html');
                ExcelIOFactory::createWriter($spreadsheet, 'Html')->save($htmlPath);
            }

            if ($htmlPath && file_exists($htmlPath)) {
                $dompdf = new Dompdf();
                $dompdf->loadHtml(file_get_contents($htmlPath));
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $pdfFileName = 'converted/' . pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
                Storage::disk('public')->put($pdfFileName, $dompdf->output());

                if (Storage::disk('public')->exists($pdfFileName)) {
                    $pdfPreviewPath = $pdfFileName;
                }
            }

            // ✅ Run OCR if needed
            if ($ocrService && in_array($extension, ['png', 'jpg', 'jpeg'])) {
                $ocrText = $ocrService->extractText($decryptedPath);
                \Log::info("📄 OCR Text for '{$originalName}': " . $ocrText);
            }

        } catch (\Exception $e) {
            Log::error("❌ Conversion/OCR failed for {$originalName}: " . $e->getMessage());
        } finally {
            if (file_exists($decryptedPath)) {
                unlink($decryptedPath);
            }
        }

        return Document::create([
            'user_id' => auth()->id(),
            'name' => $originalName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'type' => $type,
            'category' => $type === 'student' ? $category : null,
            'lrn' => $type === 'student' ? $lrn : null,
            'pdf_preview_path' => $pdfPreviewPath,
            'extracted_text' => $ocrText,
        ]);
    }

    public function encryptFileContents(string $filePath): string
    {
        $key = base64_decode(env('FILE_ENCRYPTION_KEY'));
        $iv = random_bytes(16);
        $data = file_get_contents($filePath);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public function decryptFileContents(string $base64): string
    {
        $key = base64_decode(env('FILE_ENCRYPTION_KEY'));
        $data = base64_decode($base64);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
}
