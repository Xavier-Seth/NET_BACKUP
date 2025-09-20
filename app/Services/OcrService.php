<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OcrService
{
    protected $tesseractPath = 'tesseract';
    protected $pdftoppmPath = 'pdftoppm';
    protected $libreOfficePath = 'soffice';

    public function extractText(string $filePath): string
    {
        $hash = md5_file($filePath);
        $cacheKey = "ocr_cache_{$hash}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $text = match (true) {
            in_array($extension, ['png', 'jpg', 'jpeg']) => $this->extractFromImage($filePath),
            $extension === 'pdf' => $this->extractFromPdf($filePath),
            in_array($extension, ['doc', 'docx']) => $this->extractFromDocOrExcel($filePath, $extension),
            in_array($extension, ['xls', 'xlsx']) => $this->extractFromExcelDirect($filePath) ?: $this->extractFromDocOrExcel($filePath, $extension),
            default => '',
        };

        Cache::put($cacheKey, $text, now()->addDay());
        return $text;
    }

    protected function extractFromImage(string $imagePath): string
    {
        $outputPath = storage_path('app/ocr_output_' . uniqid());
        $command = "{$this->tesseractPath} " . escapeshellarg($imagePath) . " " . escapeshellarg($outputPath) . " --psm 6 -l eng";
        exec($command . " 2>&1", $log, $exitCode);

        $outputTextFile = $outputPath . '.txt';

        if ($exitCode !== 0 || !file_exists($outputTextFile)) {
            Log::error("Tesseract failed: " . implode("\n", $log));
            return '';
        }

        $text = file_get_contents($outputTextFile);
        $this->safeUnlink($outputTextFile);

        return trim($text);
    }

    protected function extractFromPdf(string $pdfPath): string
    {
        $outputPrefix = storage_path('app/pdf_images_' . uniqid());
        exec("{$this->pdftoppmPath} -png " . escapeshellarg($pdfPath) . " " . escapeshellarg($outputPrefix) . " 2>&1", $log, $exitCode);

        if ($exitCode !== 0) {
            Log::error("pdftoppm failed: " . implode("\n", $log));
            return '';
        }

        $text = '';
        foreach (glob($outputPrefix . '-*.png') as $img) {
            $text .= ' ' . $this->extractFromImage($img);
            $this->safeUnlink($img);
        }

        return trim($text);
    }

    protected function extractFromDocOrExcel(string $filePath, string $extension): string
    {
        $outputDir = storage_path('app/doc_text');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $convertTo = in_array($extension, ['xls', 'xlsx']) ? 'pdf:calc_pdf_Export' : 'pdf:writer_pdf_Export';
        $outputCommand = "{$this->libreOfficePath} --headless --invisible --convert-to {$convertTo} --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($filePath);

        exec($outputCommand . " 2>&1", $log, $exitCode);

        $convertedPdf = $outputDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.pdf';

        if ($exitCode !== 0 || !file_exists($convertedPdf)) {
            Log::error("LibreOffice conversion failed: " . implode("\n", $log));
            return '';
        }

        $text = $this->extractFromPdf($convertedPdf);
        $this->safeUnlink($convertedPdf);

        return $text;
    }

    /**
     * Direct Excel text extraction using PhpSpreadsheet
     */
    protected function extractFromExcelDirect(string $filePath): string
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $text = '';

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                foreach ($sheet->toArray() as $row) {
                    $text .= implode(' ', array_filter($row)) . ' ';
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::warning("⚠️ PhpSpreadsheet failed for Excel: " . $e->getMessage());
            return '';
        }
    }

    protected function safeUnlink(string $filePath)
    {
        for ($i = 0; $i < 5; $i++) {
            if (!file_exists($filePath)) {
                return;
            }
            if (@unlink($filePath)) {
                return;
            }
            usleep(100000);
        }

        Log::warning("❗ Failed to delete file after multiple attempts: {$filePath}");
    }
}
