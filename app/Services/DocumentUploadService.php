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
use Illuminate\Validation\ValidationException;

class DocumentUploadService
{
    protected $schoolPropertyCategories = ['ICS', 'RIS'];

    public function handle(
        UploadedFile $file,
        ?int $teacherId,
        ?int $categoryId,
        User $user,
        array $options = [] // ['skip_ocr' => bool, 'scanned_text' => string|null]
    ) {
        // Normalize ID inputs
        $teacherId = $teacherId ?: null;
        $categoryId = $categoryId ?: null;

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $size = $file->getSize();

        // Encrypt and store
        $encryptedContents = $this->encryptFileContents($file->getRealPath());
        $filename = $file->hashName();
        $path = "documents/{$filename}";
        Storage::disk('public')->put($path, $encryptedContents, 'public');

        // Decrypt temporarily
        $decryptedPath = storage_path("app/public/temp-decrypted-{$filename}");
        file_put_contents($decryptedPath, $this->decryptFileContents($encryptedContents));

        $ocrText = $options['scanned_text'] ?? null;
        $autoCategoryName = null;
        $pdfPreviewPath = null;

        try {
            // Convert Office → PDF for preview
            if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                $outputDir = storage_path('app/public/converted');
                if (!is_dir($outputDir))
                    mkdir($outputDir, 0755, true);

                $command = "soffice --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($decryptedPath);
                @exec($command);

                $baseName = pathinfo($decryptedPath, PATHINFO_FILENAME);
                $convertedPdf = $outputDir . '/' . $baseName . '.pdf';

                if (!file_exists($convertedPdf)) {
                    $matches = glob($outputDir . '/' . $baseName . '*.pdf');
                    if (count($matches))
                        $convertedPdf = $matches[0];
                }

                if (file_exists($convertedPdf)) {
                    $relativePath = 'converted/' . basename($convertedPdf);
                    Storage::disk('public')->put($relativePath, file_get_contents($convertedPdf));
                    $pdfPreviewPath = $relativePath;
                }
            }

            if ($extension === 'pdf') {
                $previewPath = "previews/{$filename}";
                Storage::disk('public')->put($previewPath, file_get_contents($decryptedPath));
                $pdfPreviewPath = $previewPath;
            }

            // OCR + Classification via Python (only if needed and not skipped)
            $skipOcr = (bool) ($options['skip_ocr'] ?? false);
            if (!$skipOcr && (is_null($teacherId) || is_null($categoryId))) {
                try {
                    $response = Http::timeout(5)->attach(
                        'file',
                        file_get_contents($decryptedPath),
                        $originalName
                    )->post('http://127.0.0.1:5000/extract-and-classify');

                    if ($response->successful()) {
                        $data = $response->json();
                        $ocrText = $ocrText ?: ($data['text'] ?? $data['text_preview'] ?? '');
                        $autoCategoryName = $this->normalizeCategoryName($data['subcategory'] ?? null);
                        Log::info("✅ OCR + Classification via Flask during upload: {$autoCategoryName}");
                    } else {
                        Log::warning("⚠️ Flask responded but failed to classify: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::warning("⚠️ Flask server not reachable during upload: " . $e->getMessage());
                }
            }

            // Auto-assign category (case/alias-insensitive)
            if ($autoCategoryName && is_null($categoryId)) {
                $categoryId = Category::whereRaw('LOWER(name) = ?', [strtolower($autoCategoryName)])->value('id');
            }

            // Auto-assign teacher if OCR text contains their name (teacher docs only)
            if (
                !empty($ocrText) && is_null($teacherId) && !$this->isSchoolProperty(
                    Category::where('id', $categoryId)->value('name')
                )
            ) {
                foreach (Teacher::all() as $teacher) {
                    if (stripos($ocrText, $teacher->full_name) !== false) {
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
            if (file_exists($decryptedPath))
                @unlink($decryptedPath);
        }

        // Determine if School Property
        $categoryName = $categoryId ? Category::where('id', $categoryId)->value('name') : null;
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

        // Require teacher for teacher-docs; return 422 instead of generic error
        if (is_null($teacherId)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'A teacher is required for ' . ($categoryName ?: 'this document') . '.',
            ]);
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
        return in_array($categoryName, $this->schoolPropertyCategories, true);
    }

    /** Map shorthands to canonical DB names. */
    private function normalizeCategoryName(?string $name): ?string
    {
        if (!$name)
            return null;
        $map = [
            'TOR' => 'Transcript of Records',
            'PDS' => 'Personal Data Sheet',
            'COAD' => 'Certification of Assumption to Duty',
            'WES' => 'Work Experience Sheet',
            'DTR' => 'Daily Time Record',
            'APPOINTMENT' => 'Appointment Form',
        ];
        $key = trim($name);
        return $map[$key] ?? $map[strtoupper($key)] ?? $key;
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
