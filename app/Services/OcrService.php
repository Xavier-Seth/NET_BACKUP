<?php

namespace App\Services;

class OcrService
{
    public function extractText(string $imagePath): string
    {
        // Escape input and output paths
        $safePath = escapeshellarg($imagePath);
        $outputPath = storage_path('app/ocr_output_' . uniqid());
        $safeOutputPath = escapeshellarg($outputPath);

        // Use full path to Windows Tesseract binary
        $tesseractPath = '"C:\\Program Files\\Tesseract-OCR\\tesseract.exe"';

        // Run the Tesseract command and capture errors
        exec("$tesseractPath $safePath $safeOutputPath 2>&1", $outputLog, $exitCode);

        $outputTextFile = $outputPath . '.txt';

        if ($exitCode !== 0 || !file_exists($outputTextFile)) {
            \Log::error("❌ Tesseract failed: " . implode("\n", $outputLog));
            return 'OCR failed or no text found.';
        }

        $text = trim(file_get_contents($outputTextFile));

        // Clean up temp output file
        if (file_exists($outputTextFile)) {
            unlink($outputTextFile);
        }

        return $text ?: 'OCR found no readable text.';
    }
}
