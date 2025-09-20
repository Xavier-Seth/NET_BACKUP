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

    /**
     * Handle a single file upload (teacher docs and school property docs).
     *
     * @param  UploadedFile $file
     * @param  int|null     $teacherId
     * @param  int|null     $categoryId
     * @param  User         $user
     * @param  array        $options ['skip_ocr'=>bool, 'scanned_text'=>?string, 'allow_duplicate'=>bool]
     * @return \App\Models\Document|\App\Models\SchoolPropertyDocument
     * @throws \Throwable|ValidationException
     */
    public function handle(
        UploadedFile $file,
        ?int $teacherId,
        ?int $categoryId,
        User $user,
        array $options = []
    ) {
        // ── Inputs & flags ────────────────────────────────────────────────────
        $teacherId = $teacherId ?: null;
        $categoryId = $categoryId ?: null;
        $allowDuplicate = (bool) ($options['allow_duplicate'] ?? false);
        $skipOcr = (bool) ($options['skip_ocr'] ?? false);

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $size = $file->getSize();

        // ── Encrypt & store raw file (hashed filename prevents collisions) ───
        $encryptedContents = $this->encryptFileContents($file->getRealPath());
        $filename = $file->hashName();
        $path = "documents/{$filename}";
        Storage::disk('public')->put($path, $encryptedContents, 'public');

        // ── Decrypt to temp for preview/ocr/convert ──────────────────────────
        $decryptedPath = storage_path("app/public/temp-decrypted-{$filename}");
        file_put_contents($decryptedPath, $this->decryptFileContents($encryptedContents));

        $ocrText = $options['scanned_text'] ?? null;
        $autoCategory = null;
        $pdfPreviewPath = null;

        try {
            // Convert Office to PDF for preview
            if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                $outputDir = storage_path('app/public/converted');
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }
                $command = "soffice --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($decryptedPath);
                @exec($command);

                $baseName = pathinfo($decryptedPath, PATHINFO_FILENAME);
                $convertedPdf = $outputDir . '/' . $baseName . '.pdf';

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

            // PDFs: copy decrypted as preview (iframe-friendly)
            if ($extension === 'pdf') {
                $previewPath = "previews/{$filename}";
                Storage::disk('public')->put($previewPath, file_get_contents($decryptedPath));
                $pdfPreviewPath = $previewPath;
            }

            // OCR + Classification (only if needed and not skipped)
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
                        $autoCategory = $this->normalizeCategoryName($data['subcategory'] ?? null);
                        Log::info("OCR + Classification via Flask during upload: {$autoCategory}");
                    } else {
                        Log::warning("Flask responded but failed to classify: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::warning("Flask server not reachable during upload: " . $e->getMessage());
                }
            }

            // Map detected category → id if user did not set
            if ($autoCategory && is_null($categoryId)) {
                $categoryId = Category::whereRaw('LOWER(name) = ?', [strtolower($autoCategory)])->value('id');
            }

            // Auto-detect teacher from OCR text (only for teacher docs)
            if (!empty($ocrText) && is_null($teacherId)) {
                $tmpName = $categoryId ? Category::where('id', $categoryId)->value('name') : null;
                if (!$this->isSchoolProperty($tmpName)) {
                    foreach (Teacher::all() as $teacher) {
                        if (stripos($ocrText, $teacher->full_name) !== false) {
                            $teacherId = $teacher->id;
                            Log::info("Auto-detected Teacher: '{$teacher->full_name}' (ID: {$teacher->id})");
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            throw $e;
        } finally {
            if (file_exists($decryptedPath)) {
                @unlink($decryptedPath);
            }
        }

        // ── Final category/teacher gating ─────────────────────────────────────
        $categoryName = $categoryId ? Category::where('id', $categoryId)->value('name') : null;
        $isSchoolProperty = $this->isSchoolProperty($categoryName);

        // School property path (ICS/RIS) → no teacher gating
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

        // Teacher is required for teacher docs
        if (is_null($teacherId)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'A teacher is required for ' . ($categoryName ?: 'this document') . '.',
            ]);
        }

        // ── Duplicate handling for teacher documents ──────────────────────────
        // Define duplicate as existing record with same teacher_id & category_id (one per type)
        $existing = Document::query()
            ->where('teacher_id', $teacherId)
            ->where('category_id', $categoryId)
            ->latest('id')
            ->first();

        // If there is an existing doc and UI did NOT allow duplicates → block here.
        if ($existing && !$allowDuplicate) {
            throw ValidationException::withMessages([
                'duplicate' => "A document of this type already exists for the selected teacher: '{$existing->name}'",
            ]);
        }

        // If ADD ANOTHER but same display name already exists for same teacher/category,
        // give this new one a unique *display* name (storage path already unique by hash).
        $displayName = $originalName;
        if ($existing && $allowDuplicate) {
            $alreadySameName = Document::where('teacher_id', $teacherId)
                ->where('category_id', $categoryId)
                ->where('name', $originalName)
                ->exists();

            if ($alreadySameName) {
                $displayName = $this->suffixDisplayName($originalName, now()->format('Ymd_His'));
            }
        }

        // ── Create final teacher document record ──────────────────────────────
        return Document::create([
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'category_id' => $categoryId,
            'name' => $displayName,   // keep original name; add suffix only if needed above
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'pdf_preview_path' => $pdfPreviewPath,
            'extracted_text' => $ocrText,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function isSchoolProperty($categoryName): bool
    {
        return in_array($categoryName, $this->schoolPropertyCategories, true);
    }

    /** Map shorthands/aliases to canonical DB names. */
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

    /** Suffix display name (before extension). */
    private function suffixDisplayName(string $name, string $suffix): string
    {
        $dot = strrpos($name, '.');
        if ($dot === false) {
            return "{$name} ({$suffix})";
        }
        $base = substr($name, 0, $dot);
        $ext = substr($name, $dot + 1);
        return "{$base} ({$suffix}).{$ext}";
    }

    /** Delete stored binary + preview if any. */
    private function deleteDocumentFiles(Document $doc): void
    {
        if ($doc->path && Storage::disk('public')->exists($doc->path)) {
            Storage::disk('public')->delete($doc->path);
        }
        if ($doc->pdf_preview_path && Storage::disk('public')->exists($doc->pdf_preview_path)) {
            Storage::disk('public')->delete($doc->pdf_preview_path);
        }
    }

    // ── Encryption ────────────────────────────────────────────────────────────

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
        $enc = substr($data, 16);
        return openssl_decrypt($enc, 'aes-256-cbc', $key, 0, $iv);
    }
}
