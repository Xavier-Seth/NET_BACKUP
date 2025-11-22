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
        $filename = $file->hashName();               // includes extension, e.g. 9a8b...c1.jpg
        $path = "documents/{$filename}";
        Storage::disk('public')->put($path, $encryptedContents, 'public');

        // ── Decrypt to temp for preview/ocr/convert ──────────────────────────
        $decryptedPath = storage_path("app/public/temp-decrypted-{$filename}");
        file_put_contents($decryptedPath, $this->decryptFileContents($encryptedContents));

        $ocrText = $options['scanned_text'] ?? null;
        $autoCategory = null;

        // this is what your Vue reads; we will also use it for images
        $pdfPreviewPath = null;

        try {
            // A) Office → PDF (DOC/DOCX/XLS/XLSX)
            if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                $outputDir = storage_path('app/public/converted');
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }
                $command = "soffice --headless --convert-to pdf --outdir " .
                    escapeshellarg($outputDir) . ' ' . escapeshellarg($decryptedPath);
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

            // B) PDF → copy decrypted as preview (iframe-friendly)
            if ($extension === 'pdf') {
                $previewPath = "previews/{$filename}";
                Storage::disk('public')->put($previewPath, file_get_contents($decryptedPath));
                $pdfPreviewPath = $previewPath;
            }

            // C) IMAGE → copy decrypted as preview (IMPORTANT FIX)
            // We deliberately also set pdf_preview_path so existing Vue code works.
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'])) {
                $previewPath = "previews/{$filename}";
                Storage::disk('public')->put($previewPath, file_get_contents($decryptedPath));
                $pdfPreviewPath = $previewPath; // iframe can load images too
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

            // Auto-detect teacher from OCR text (teacher docs only)
            if (!empty($ocrText) && is_null($teacherId)) {

                $tmpName = $categoryId ? Category::where('id', $categoryId)->value('name') : null;

                // Only try teacher-matching if this is NOT ICS/RIS
                if (!$this->isSchoolProperty($tmpName)) {

                    foreach (Teacher::all() as $teacher) {

                        // Build all possible name order variants
                        $variants = [
                            $teacher->full_name,
                            "{$teacher->last_name} {$teacher->first_name} {$teacher->middle_name}",
                            "{$teacher->last_name} {$teacher->first_name}",
                            "{$teacher->first_name} {$teacher->last_name}",
                            "{$teacher->first_name} {$teacher->middle_name} {$teacher->last_name}",
                        ];

                        foreach ($variants as $nameVariant) {

                            if ($this->isNameMatch($nameVariant, $ocrText)) {
                                $teacherId = $teacher->id;

                                Log::info("Auto-detected Teacher (Robust): '{$teacher->full_name}' via variant '{$nameVariant}'");

                                break 2; // Exit BOTH loops (teacher + variant loop)
                            }
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
            // Smart "(n)" increment for same-name ICS/RIS documents in this category
            $finalName = $this->generateIncrementedNameForProperty($originalName, $categoryId);

            return SchoolPropertyDocument::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'property_type' => $categoryName,
                'document_no' => null,
                'issued_date' => null,
                'received_by' => null,
                'received_date' => null,
                'description' => null,
                'name' => $finalName,
                'path' => $path,
                'mime_type' => $mime,
                'size' => $size,
                'pdf_preview_path' => $pdfPreviewPath, // images/pdfs/converted office
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
        $existing = Document::query()
            ->where('teacher_id', $teacherId)
            ->where('category_id', $categoryId)
            ->latest('id')
            ->first();

        if ($existing && !$allowDuplicate) {
            throw ValidationException::withMessages([
                'duplicate' => "A document of this type already exists for the selected teacher: '{$existing->name}'",
            ]);
        }

        // NEW: smart "(n)" increment for same-name copies when allow_duplicate=1
        $displayName = $originalName;
        if ($allowDuplicate) {
            $displayName = $this->generateIncrementedNameForTeacher($originalName, $teacherId, $categoryId);
        }

        // ── Create final teacher document record ──────────────────────────────
        return Document::create([
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'category_id' => $categoryId,
            'name' => $displayName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'pdf_preview_path' => $pdfPreviewPath, // images/pdfs/converted office
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
        if (!$name) {
            return null;
        }

        $map = [
            // Existing
            'TOR' => 'Transcript of Records',
            'PDS' => 'Personal Data Sheet',
            'COAD' => 'Certification of Assumption to Duty',
            'WES' => 'Work Experience Sheet',
            'DTR' => 'Daily Time Record',
            'APPOINTMENT' => 'Appointment Form',

            // NEW categories & common aliases
            'SALN' => 'SAL-N',
            'SAL-N' => 'SAL-N',
            'STATEMENT OF ASSETS, LIABILITIES AND NET WORTH' => 'SAL-N',
            'SERVICE CREDIT' => 'Service credit ledgers',
            'SERVICE CREDITS' => 'Service credit ledgers',
            'CREDIT LEDGER' => 'Service credit ledgers',
            'LEDGER OF CREDITS' => 'Service credit ledgers',
            'LEAVE CREDITS' => 'Service credit ledgers',
            'IPCRF' => 'IPCRF',
            'NOSI' => 'NOSI',
            'NOSA' => 'NOSA',
            'TRAVEL ORDER' => 'Travel order',
            'AUTHORITY TO TRAVEL' => 'Travel order',
        ];

        $key = trim($name);
        return $map[$key] ?? $map[strtoupper($key)] ?? $key;
    }

    /**
     * Strip an existing trailing " (n)" from a base name. Example: "file (3)" -> "file"
     */
    private function stripParenCounter(string $base): string
    {
        return preg_replace('/\s\(\d+\)$/', '', $base);
    }

    /**
     * Compute a non-colliding name for TEACHER docs within teacher+category, using " (n)".
     */
    private function generateIncrementedNameForTeacher(
        string $originalName,
        int $teacherId,
        int $categoryId
    ): string {
        $dot = strrpos($originalName, '.');
        $base = $dot === false ? $originalName : substr($originalName, 0, $dot);
        $ext = $dot === false ? '' : substr($originalName, $dot + 1);
        $cleanBase = $this->stripParenCounter($base);

        $existing = Document::where('teacher_id', $teacherId)
            ->where('category_id', $categoryId)
            ->where(function ($q) use ($cleanBase, $ext) {
                if ($ext !== '') {
                    $q->where('name', $cleanBase . '.' . $ext)
                        ->orWhere('name', 'like', $cleanBase . ' (%)' . '.' . $ext);
                } else {
                    $q->where('name', $cleanBase)
                        ->orWhere('name', 'like', $cleanBase . ' (%)');
                }
            })
            ->pluck('name')
            ->all();

        if (!in_array($originalName, $existing, true)) {
            return $originalName;
        }

        $max = 0;
        foreach ($existing as $name) {
            $pattern = $ext !== ''
                ? '/^' . preg_quote($cleanBase, '/') . '\s\((\d+)\)\.' . preg_quote($ext, '/') . '$/i'
                : '/^' . preg_quote($cleanBase, '/') . '\s\((\d+)\)$/i';
            if (preg_match($pattern, $name, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }
        $next = $max + 1;

        return $ext !== ''
            ? "{$cleanBase} ({$next}).{$ext}"
            : "{$cleanBase} ({$next})";
    }

    /**
     * Compute a non-colliding name for SCHOOL PROPERTY docs within a category (ICS/RIS).
     */
    private function generateIncrementedNameForProperty(string $originalName, int $categoryId): string
    {
        $dot = strrpos($originalName, '.');
        $base = $dot === false ? $originalName : substr($originalName, 0, $dot);
        $ext = $dot === false ? '' : substr($originalName, $dot + 1);
        $cleanBase = $this->stripParenCounter($base);

        $existing = SchoolPropertyDocument::where('category_id', $categoryId)
            ->where(function ($q) use ($cleanBase, $ext) {
                if ($ext !== '') {
                    $q->where('name', $cleanBase . '.' . $ext)
                        ->orWhere('name', 'like', $cleanBase . ' (%)' . '.' . $ext);
                } else {
                    $q->where('name', $cleanBase)
                        ->orWhere('name', 'like', $cleanBase . ' (%)');
                }
            })
            ->pluck('name')
            ->all();

        if (!in_array($originalName, $existing, true)) {
            return $originalName;
        }

        $max = 0;
        foreach ($existing as $name) {
            $pattern = $ext !== ''
                ? '/^' . preg_quote($cleanBase, '/') . '\s\((\d+)\)\.' . preg_quote($ext, '/') . '$/i'
                : '/^' . preg_quote($cleanBase, '/') . '\s\((\d+)\)$/i';
            if (preg_match($pattern, $name, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }
        $next = $max + 1;

        return $ext !== ''
            ? "{$cleanBase} ({$next}).{$ext}"
            : "{$cleanBase} ({$next})";
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

    /** Encrypt a file from disk, returning base64(iv+cipher). */
    public function encryptFileContents(string $filePath): string
    {
        $key = base64_decode(env('FILE_ENCRYPTION_KEY'));
        $iv = random_bytes(16);
        $data = file_get_contents($filePath);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /** Decrypt base64(iv+cipher) back to raw bytes. */
    public function decryptFileContents(string $base64): string
    {
        $key = base64_decode(env('FILE_ENCRYPTION_KEY'));
        $data = base64_decode($base64);
        $iv = substr($data, 0, 16);
        $enc = substr($data, 16);
        return openssl_decrypt($enc, 'aes-256-cbc', $key, 0, $iv);
    }

    /** ✅ Encrypt raw bytes already in memory (used by restore from decrypted ZIP). */
    public function encryptBytes(string $plainBytes): string
    {
        $key = base64_decode(env('FILE_ENCRYPTION_KEY'));
        $iv = random_bytes(16);
        $enc = openssl_encrypt($plainBytes, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $enc);
    }

    /** Optional alias if you want symmetry. */
    public function decryptBytes(string $base64Cipher): string
    {
        return $this->decryptFileContents($base64Cipher);
    }

    /* ----------------------------------------------------------
 |  ROBUST TEACHER NAME MATCHING (NEW)
 * ----------------------------------------------------------*/

    private function normalizeName($name)
    {
        $name = strtolower($name);
        $name = preg_replace('/[^\p{L}\s]/u', '', $name); // remove punctuation
        $name = preg_replace('/\s+/', ' ', trim($name)); // normalize spaces
        return $name;
    }

    private function tokenizeName($name)
    {
        return array_values(array_filter(explode(' ', $name)));
    }

    private function scoreNameMatch($expectedTokens, $ocrTokens)
    {
        $score = 0;

        foreach ($expectedTokens as $e) {
            foreach ($ocrTokens as $o) {

                if ($e === $o) {                    // exact match
                    $score += 3;
                } elseif (strlen($e) == 1 && str_starts_with($o, $e)) { // initial vs fullname
                    $score += 2;
                } elseif (strlen($o) == 1 && str_starts_with($e, $o)) {
                    $score += 2;
                } elseif (similar_text($e, $o, $pct) && $pct >= 70) {   // partial match
                    $score += 1;
                }
            }
        }

        return $score;
    }

    public function isNameMatch($expectedName, $ocrName)
    {
        $expected = $this->normalizeName($expectedName);
        $ocr = $this->normalizeName($ocrName);

        $expectedTokens = $this->tokenizeName($expected);
        $ocrTokens = $this->tokenizeName($ocr);

        if (empty($expectedTokens) || empty($ocrTokens)) {
            return false;
        }

        $score = $this->scoreNameMatch($expectedTokens, $ocrTokens);
        $max = count($expectedTokens) * 3;

        $confidence = ($score / $max) * 100;

        return $confidence >= 65;   // threshold
    }

}
