<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Str;

use App\Models\Document;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\User;

use App\Services\DocumentUploadService;
use App\Services\CategorizationService;
use App\Services\LogService;

// --- 1. IMPORT NOTIFICATION CLASS ---
use App\Notifications\DocumentUploaded;

class DocumentController extends Controller
{
    /** 📂 Main Teacher Documents Page */
    public function index()
    {
        $documents = Document::with(['teacher', 'category', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $teachers = Teacher::orderBy('full_name')->get();
        $categories = Category::orderBy('name')->get();

        $categorizationService = new CategorizationService();
        $teacherDocumentTypes = $categorizationService->getTeacherDocumentTypes();

        return Inertia::render('Teacher/TeacherProfile', [
            'documents' => $documents,
            'teachers' => $teachers,
            'categories' => $categories,
            'teacherDocumentTypes' => $teacherDocumentTypes,
        ]);
    }

    /** 📤 Upload Page */
    public function create()
    {
        $teachers = Teacher::orderBy('full_name')->get();
        $categories = Category::orderBy('name')->get();
        $teacherDocumentTypes = (new CategorizationService())->getTeacherDocumentTypes();

        return Inertia::render('Upload', [
            'teachers' => $teachers,
            'categories' => $categories,
            'teacherDocumentTypes' => $teacherDocumentTypes,
        ]);
    }

    /** 🧩 Helper: best-effort decrypt; if it fails, fall back to original bytes */
    private function safeDecrypt(string $cipherOrPlain, DocumentUploadService $crypto): string
    {
        try {
            // Try normal decrypt (encrypted teacher docs are stored as base64(iv+cipher))
            $plain = $crypto->decryptFileContents($cipherOrPlain);
            if (is_string($plain) && $plain !== '') {
                return $plain;
            }
        } catch (\Throwable $e) {
            // ignore and fall back below
        }

        // If not decryptable, assume it's already plain (e.g., after odd restores)
        return $cipherOrPlain;
    }

    /** 🖼 Preview decrypted or converted document */
    public function preview(Document $document)
    {
        // 1) Validate file existence
        if (!$document->path || !Storage::disk('public')->exists($document->path)) {
            abort(404, 'File not found.');
        }

        $path = Storage::disk('public')->path($document->path);

        // ---------------------------------------------------------
        // ROBUST IMAGE DETECTION
        // ---------------------------------------------------------
        // Check Mime Type from DB or File
        $mime = $document->mime_type ?: mime_content_type($path);

        // Check Extension as fallback
        $extension = strtolower(pathinfo($document->path, PATHINFO_EXTENSION));
        $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'];

        // Is it an image?
        $isImage = ($mime && str_starts_with($mime, 'image/')) || in_array($extension, $imageExtensions);

        // Ensure we have a valid MIME for the header if it is an image
        if ($isImage && (!$mime || !str_starts_with($mime, 'image/'))) {
            $mime = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
        }

        // ---------------------------------------------------------
        // 2) PRIORITY: Handle Images (DECRYPTED)
        // ---------------------------------------------------------
        if ($isImage) {
            // Get the encrypted content
            $content = Storage::disk('public')->get($document->path);

            // Decrypt it
            $crypto = new DocumentUploadService();
            $bytes = $this->safeDecrypt($content, $crypto);

            return response($bytes, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $document->name . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // ---------------------------------------------------------
        // 3) Then Check for PDF Preview (For Word/Excel/PPT)
        // ---------------------------------------------------------
        if ($document->pdf_preview_path && Storage::disk('public')->exists($document->pdf_preview_path)) {
            $fullPath = Storage::disk('public')->path($document->pdf_preview_path);
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . pathinfo($document->name, PATHINFO_FILENAME) . '.pdf"',
            ]);
        }

        // ---------------------------------------------------------
        // 4) Fallback: Decrypt original (PDFs, etc)
        // ---------------------------------------------------------
        $crypto = new DocumentUploadService();
        $cipherOrPlain = Storage::disk('public')->get($document->path);
        $bytes = $this->safeDecrypt($cipherOrPlain, $crypto);

        return response($bytes, 200, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $document->name . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** 📥 Download decrypted file */
    public function download(Document $document)
    {
        if (!$document->path || !Storage::disk('public')->exists($document->path)) {
            abort(404, 'File not found.');
        }

        $crypto = new DocumentUploadService();
        $cipherOrPlain = Storage::disk('public')->get($document->path);
        $bytes = $this->safeDecrypt($cipherOrPlain, $crypto);

        $mime = $document->mime_type ?: 'application/octet-stream';

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $document->name . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** 🗂 Multi-upload entry for Teacher + Property Docs */
    public function upload(Request $request, DocumentUploadService $uploadService)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg,webp,bmp,gif|max:20480',
            'meta' => 'nullable|string',
            'allow_duplicate' => 'nullable|boolean',
            'skip_ocr' => 'nullable|boolean',
            'scanned_text' => 'nullable|string',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $user = $request->user() ?? User::findOrFail(auth()->id());

        // Parse meta JSON from Upload.vue
        $meta = [];
        if ($request->filled('meta')) {
            try {
                $meta = json_decode($request->input('meta'), true) ?: [];
                if (!is_array($meta))
                    $meta = [];
            } catch (\Throwable $e) {
                $meta = [];
            }
        }

        $byName = [];
        foreach ($meta as $m) {
            if (!empty($m['name']) && is_string($m['name'])) {
                $byName[$m['name']] = $m;
            }
        }

        $skipOcr = (bool) $request->boolean('skip_ocr');
        $globalAllowDuplicate = (bool) $request->boolean('allow_duplicate');

        $legacyTeacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $legacyCategoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $legacyScannedText = $request->input('scanned_text');

        $results = [];

        foreach ($request->file('files') as $file) {
            $orig = $file->getClientOriginalName();
            $m = $byName[$orig] ?? [];

            $categoryId = isset($m['category_id']) ? (int) $m['category_id'] : $legacyCategoryId;
            $teacherId = isset($m['teacher_id']) ? (int) $m['teacher_id'] : $legacyTeacherId;
            $override = (bool) ($m['override'] ?? $request->boolean('override'));
            $scanned = $m['scanned_text'] ?? $legacyScannedText ?? '';

            if ($teacherId && !$globalAllowDuplicate) {
                $sameCat = $categoryId ? $this->isDuplicate($teacherId, $categoryId) : false;
                $sameName = $this->hasSameNameForTeacher($teacherId, $orig, $categoryId);
                if ($sameCat || $sameName) {
                    $results[] = [
                        'name' => $orig,
                        'success' => false,
                        'duplicate' => true,
                        'message' => $sameName ? 'Duplicate filename detected' : 'Duplicate document type detected',
                    ];
                    continue;
                }
            }

            try {
                $doc = $uploadService->handle(
                    $file,
                    $teacherId,
                    $categoryId,
                    $user,
                    [
                        'allow_duplicate' => $globalAllowDuplicate,
                        'skip_ocr' => $skipOcr,
                        'scanned_text' => $scanned,
                        'override' => $override,
                    ]
                );

                $relations = [];
                if (method_exists($doc, 'teacher'))
                    $relations[] = 'teacher';
                if (method_exists($doc, 'category'))
                    $relations[] = 'category';
                if ($relations)
                    $doc->loadMissing($relations);

                $typeLabel = $doc instanceof \App\Models\SchoolPropertyDocument ? 'school property document' : 'document';
                $teacherPart = (method_exists($doc, 'teacher') && $doc->relationLoaded('teacher') && $doc->teacher)
                    ? " for teacher {$doc->teacher->full_name}" : '';
                $categoryPart = (method_exists($doc, 'category') && $doc->relationLoaded('category') && $doc->category)
                    ? " under category '{$doc->category->name}'" : '';

                LogService::record("Uploaded {$typeLabel} '{$doc->name}'{$teacherPart}{$categoryPart}.");

                // --- 2. SEND NOTIFICATION TO TEACHER IF APPLICABLE ---
                if ($teacherId) {
                    $teacherProfile = Teacher::find($teacherId);
                    // Check if this teacher has a linked User account
                    if ($teacherProfile && $teacherProfile->user_id) {
                        $teacherUser = User::find($teacherProfile->user_id);

                        if ($teacherUser) {
                            $teacherUser->notify(new DocumentUploaded([
                                'message' => "New document uploaded: {$orig}",
                                'document_name' => $orig,
                                'by_user' => $user->first_name . ' ' . $user->last_name,
                            ]));
                        }
                    }
                }
                // -----------------------------------------------------

                $results[] = [
                    'name' => $orig,
                    'success' => true,
                    'duplicate' => false,
                    'id' => $doc->id,
                ];
            } catch (\Throwable $e) {
                report($e);
                $results[] = [
                    'name' => $orig,
                    'success' => false,
                    'duplicate' => false,
                    'message' => 'Upload error',
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    /** 🔍 Scan via Flask (OCR + Classification) */
    public function scan(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg,webp,bmp,gif|max:102400',
        ]);

        $file = $request->file('file');
        $tempPath = $file->store('temp-scan', 'public');
        $fullPath = storage_path('app/public/' . $tempPath);

        $ocrText = '';
        $matchedTeacher = null;
        $matchedCategory = null;
        $belongsToTeacher = false;
        $categoryId = null;
        $fallbackUsed = false;

        try {
            $response = Http::attach('file', file_get_contents($fullPath), $file->getClientOriginalName())
                ->post('http://127.0.0.1:5000/extract-and-classify');

            if ($response->successful()) {
                $data = $response->json();
                $ocrText = $data['text'] ?? $data['text_preview'] ?? '';
                $matchedCategory = $data['subcategory'] ?? null;
            } else {
                $fallbackUsed = true;
                LaravelLog::error("Flask scan OCR failed: " . $response->body());
            }

            $matchedCategory = $this->normalizeCategoryName($matchedCategory);

            if ($matchedCategory) {
                $categoryId = Category::whereRaw('LOWER(name) = ?', [strtolower($matchedCategory)])->value('id');
            }

            if (!empty($ocrText)) {
                foreach (Teacher::all() as $teacher) {
                    if (Str::contains(strtolower($ocrText), strtolower($teacher->full_name))) {
                        $matchedTeacher = $teacher->full_name;
                        break;
                    }
                }
            }

            if ($matchedCategory) {
                $teacherDocumentTypes = (new CategorizationService())->getTeacherDocumentTypes();
                $belongsToTeacher = collect($teacherDocumentTypes)
                    ->map(fn($n) => strtolower($n))
                    ->contains(strtolower($matchedCategory));
            }
        } catch (\Exception $e) {
            $fallbackUsed = true;
            LaravelLog::error("Exception during scan: " . $e->getMessage());
        } finally {
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
        }

        return response()->json([
            'teacher' => $matchedTeacher,
            'category' => $matchedCategory,
            'category_id' => $categoryId,
            'belongs_to_teacher' => $belongsToTeacher,
            'text' => $ocrText,
            'fallback_used' => $fallbackUsed,
        ]);
    }

    /** 📋 DTR page (filter + list) */
    public function dtrIndex(Request $request)
    {
        $query = Document::with(['teacher', 'category'])
            ->whereHas('category', fn($q) => $q->where('name', 'Daily Time Record'));

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->teacher) {
            $query->whereHas('teacher', fn($q) => $q->where('full_name', $request->teacher));
        }

        return Inertia::render('Teacher/DtrDocuments', [
            'documents' => $query->latest()->paginate(10)->withQueryString(),
            'teachers' => Teacher::orderBy('full_name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'filters' => $request->only(['search', 'teacher']),
        ]);
    }

    /** ✏️ Edit document metadata */
    public function updateMetadata(Request $request, Document $document)
    {
        $request->validate([
            'teacher_id' => 'nullable|exists:teachers,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'nullable|string|max:255',
        ]);

        $document->update([
            'teacher_id' => $request->teacher_id,
            'category_id' => $request->category_id,
            'name' => $request->name ?? $document->name,
        ]);

        $document->loadMissing(['teacher', 'category']);
        $teacherPart = $document->teacher ? " for teacher {$document->teacher->full_name}" : '';
        $categoryPart = $document->category ? " under category '{$document->category->name}'" : '';

        // 1. Log the action
        LogService::record("Updated metadata for document '{$document->name}'{$teacherPart}{$categoryPart}.");

        // 2. --- SEND NOTIFICATION TO TEACHER (NEWLY ADDED) ---
        // We notify the teacher currently assigned to the document
        if ($document->teacher_id) {
            $teacherProfile = Teacher::find($document->teacher_id);

            // Check if this teacher has a linked User account
            if ($teacherProfile && $teacherProfile->user_id) {
                $teacherUser = User::find($teacherProfile->user_id);
                $currentUser = auth()->user(); // Get the Admin/Staff doing the edit

                if ($teacherUser) {
                    $teacherUser->notify(new DocumentUploaded([
                        'message' => "Document details updated: {$document->name}",
                        'document_name' => $document->name,
                        'by_user' => $currentUser->first_name . ' ' . $currentUser->last_name,
                    ]));
                }
            }
        }
        // -----------------------------------------------------

        return back()->with('success', 'Document metadata updated.');
    }

    /** 🗑 Delete document (with log AND NOTIFICATION) */
    public function destroy(Document $document)
    {
        // 1. Get info before deletion so we can construct the notification
        $document->loadMissing(['teacher']);

        $teacherUserToNotify = null;
        if ($document->teacher && $document->teacher->user_id) {
            $teacherUserToNotify = User::find($document->teacher->user_id);
        }

        $docName = $document->name;
        $currentUser = auth()->user();

        // 2. Perform Deletion
        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }
        if ($document->pdf_preview_path && Storage::disk('public')->exists($document->pdf_preview_path)) {
            Storage::disk('public')->delete($document->pdf_preview_path);
        }

        $document->delete();

        // 3. Log it
        $teacherPart = $document->teacher ? " belonging to teacher {$document->teacher->full_name}" : '';
        LogService::record("Deleted document '{$docName}'{$teacherPart}.");

        // 4. --- SEND NOTIFICATION TO TEACHER ---
        if ($teacherUserToNotify) {
            $teacherUserToNotify->notify(new DocumentUploaded([
                'message' => "Document deleted: {$docName}",
                'document_name' => $docName,
                'by_user' => $currentUser->first_name . ' ' . $currentUser->last_name,
            ]));
        }
        // ---------------------------------------

        return back()->with('success', 'Document deleted successfully.');
    }

    // ─────────── Helpers ───────────

    /** 🧠 Normalize OCR / Flask category output */
    private function normalizeCategoryName(?string $name): ?string
    {
        if (!$name)
            return null;
        $map = [
            // existing
            'TOR' => 'Transcript of Records',
            'PDS' => 'Personal Data Sheet',
            'COAD' => 'Certification of Assumption to Duty',
            'WES' => 'Work Experience Sheet',
            'DTR' => 'Daily Time Record',
            'APPOINTMENT' => 'Appointment Form',
            // new categories
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

    private function isDuplicate(?int $teacherId, ?int $categoryId): bool
    {
        if (!$teacherId || !$categoryId)
            return false;
        return Document::where('teacher_id', $teacherId)
            ->where('category_id', $categoryId)
            ->exists();
    }

    private function hasSameNameForTeacher(int $teacherId, string $originalName, ?int $categoryId = null): bool
    {
        $q = Document::where('teacher_id', $teacherId)
            ->where('name', $originalName);

        if (!is_null($categoryId))
            $q->where('category_id', $categoryId);

        return $q->exists();
    }
}