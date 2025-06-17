<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Category;
use App\Models\Teacher;
use App\Models\User;
use App\Models\SchoolPropertyDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class DocumentUploadService
{
    protected $schoolPropertyCategories = ['ICS', 'RIS'];

    public function handle(
        UploadedFile $file,
        ?int $teacherId,
        ?int $categoryId,
        User $user
    ) {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $size = $file->getSize();

        // 🔐 Encrypt and store
        $encryptedContents = $this->encryptFileContents($file->getRealPath());
        $filename = $file->hashName();
        $path = "documents/{$filename}";
        Storage::disk('public')->put($path, $encryptedContents, 'public');

        // 🔓 Decrypt temporarily
        $decryptedPath = storage_path("app/public/temp-decrypted-{$filename}");
        file_put_contents($decryptedPath, $this->decryptFileContents($encryptedContents));

        $ocrText = null;
        $autoCategoryName = null;
        $pdfPreviewPath = null;

        try {
            // ✅ Convert to PDF for preview (Word/Excel)
            if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                $outputDir = storage_path('app/public/converted');
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }

                // Use default LibreOffice detection
                $command = "soffice --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($decryptedPath);
                exec($command);

                $baseName = pathinfo($decryptedPath, PATHINFO_FILENAME);
                $convertedPdf = $outputDir . '/' . $baseName . '.pdf';

                // Fallback: check for any matching PDF
                if (!file_exists($convertedPdf)) {
                    $matches = glob($outputDir . '/' . $baseName . '*.pdf');
                    if (count($matches)) {
                        $convertedPdf = $matches[0];
                    }
                }

                if (file_exists($convertedPdf)) {
                    $relativePath = 'converted/' . basename($convertedPdf);
                    Storage::disk('public')->put($relativePath, file_get_contents($convertedPdf));
                    $pdfPreviewPath = $relativePath;
                }
            }

            // ✅ If already a PDF
            if ($extension === 'pdf') {
                $pdfPreviewPath = $path;
            }

            // ✅ OCR + Classification via Python microservice
            try {
                $response = Http::timeout(5)->attach(
                    'file',
                    file_get_contents($decryptedPath),
                    $originalName
                )->post('http://127.0.0.1:5000/extract-and-classify');

                if ($response->successful()) {
                    $data = $response->json();
                    $ocrText = $data['text'] ?? '';
                    $autoCategoryName = $data['subcategory'] ?? null;
                    Log::info("✅ OCR + Classification success via Flask: {$autoCategoryName}");
                } else {
                    Log::warning("⚠️ Flask responded but failed to classify: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Flask server not reachable: " . $e->getMessage());
            }

            // ✅ Auto-assign category
            if ($autoCategoryName) {
                $category = Category::where('name', $autoCategoryName)->first();
                if ($category) {
                    $categoryId = $category->id;
                }
            }

            // ✅ Auto-assign teacher if OCR matches
            if (!empty($autoCategoryName) && is_null($teacherId) && !$this->isSchoolProperty($autoCategoryName)) {
                $teachers = Teacher::all();
                foreach ($teachers as $teacher) {
                    if (stripos(strtolower($ocrText), strtolower($teacher->full_name)) !== false) {
                        $teacherId = $teacher->id;
                        Log::info("👨‍🏫 Auto-detected Teacher: '{$teacher->full_name}' (ID: {$teacher->id})");
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('❌ Upload failed: ' . $e->getMessage());
            throw $e;
        } finally {
            if (file_exists($decryptedPath)) {
                unlink($decryptedPath);
            }
        }

        // ✅ Determine if School Property
        $categoryName = Category::where('id', $categoryId)->value('name');
        $isSchoolProperty = $this->isSchoolProperty($categoryName);

        if ($isSchoolProperty) {
            return SchoolPropertyDocument::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'property_type' => $categoryName,
                'document_no' => null,
                'issued_date' => null,
                'received_by' => null,
                'received_date' => null,
                'description' => null,
                'name' => $originalName,
                'path' => $path,
                'mime_type' => $mime,
                'size' => $size,
                'pdf_preview_path' => $pdfPreviewPath,
                'extracted_text' => $ocrText,
            ]);
        }

        if (is_null($teacherId)) {
            throw new \Exception('❌ No teacher detected. Please manually select a teacher before uploading.');
        }

        return Document::create([
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'category_id' => $categoryId,
            'name' => $originalName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'pdf_preview_path' => $pdfPreviewPath,
            'extracted_text' => $ocrText,
        ]);
    }


    protected function isSchoolProperty($categoryName): bool
    {
        return in_array($categoryName, $this->schoolPropertyCategories);
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
