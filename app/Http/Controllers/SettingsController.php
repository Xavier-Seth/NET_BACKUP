<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rules\File;
use App\Services\BackupService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Models\SystemSetting;

class SettingsController extends Controller
{
    /**
     * Display the Settings page.
     */
    public function index(Request $request)
    {
        // Ensure there is always exactly one settings row
        $settings = SystemSetting::firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'Rizal Central School',
                'logo_path' => null,
            ]
        );

        return Inertia::render('Auth/Settings', [
            'archives' => [],
            'settings' => array_merge($settings->toArray(), [
                'logo_path_url' => $settings->logo_path
                    ? asset('storage/' . $settings->logo_path)
                    : null,
            ]),
        ]);
    }

    /**
     * Save General settings (school name + optional logo).
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        // Ensure record always exists
        $settings = SystemSetting::firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'Rizal Central School',
                'logo_path' => null,
            ]
        );

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($settings->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $path = $request->file('logo')->store('branding', 'public');
            $settings->logo_path = $path;
        }

        $settings->school_name = trim($validated['school_name']);
        $settings->save();

        return response()->json([
            'ok' => true,
            'message' => 'General settings saved.',
            'logo_url' => $settings->logo_path
                ? asset('storage/' . $settings->logo_path)
                : null,
            'settings' => $settings,
        ]);
    }

    /**
     * Run backup (creates encrypted & decrypted ZIPs).
     */
    public function runBackup(Request $request, BackupService $backup)
    {
        [$encName, $decName] = $backup->makeBoth();

        return response()->json([
            'ok' => true,
            'created' => [$encName, $decName],
            'message' => 'Backups created.',
        ]);
    }

    /**
     * Run backup and immediately download.
     */
    public function runAndDownload(Request $request, BackupService $backup)
    {
        $names = $backup->makeBoth();
        $name = $names[0] ?? null;
        if (!$name) {
            abort(500, 'Backup was not created.');
        }

        $full = storage_path("app/backups/{$name}");
        return $this->streamLocalFile($full, $name);
    }

    /**
     * Return paginated list of backup archives.
     */
    public function archives(Request $request)
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('perPage', 10);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 100);

        $result = $this->mapArchives($page, $perPage);

        if ($request->boolean('debug')) {
            $disk = Storage::disk('local');
            $abs = $disk->path('backups');
            $exists = @is_dir($abs);
            $scan = $exists ? @scandir($abs) : false;
            $files = $scan ? array_values(array_filter($scan, fn($f) => $f !== '.' && $f !== '..')) : [];

            $result['_debug'] = [
                'storage_path' => $abs,
                'exists' => $exists,
                'files_via_storage' => $disk->files('backups'),
                'files_via_scandir' => $files,
                'app_base_path' => base_path(),
                'env_filesystem_disk' => config('filesystems.default'),
                'php_user' => get_current_user(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Download a specific backup.
     */
    public function download(string $name)
    {
        $name = basename($name);
        $full = storage_path("app/backups/{$name}");
        if (!is_file($full)) {
            abort(404, 'Backup not found.');
        }

        return $this->streamLocalFile($full, $name);
    }

    /**
     * Restore from a ZIP already on the server (storage/app/backups/{name})
     */
    public function restoreFromExisting(Request $request, string $name, BackupService $backup)
    {
        $validated = $request->validate([
            'is_encrypted' => ['nullable', 'boolean'],
            'confirm' => ['required', 'in:RESTORE'],
            'mode' => ['nullable', 'in:replace,merge'],
        ]);

        $name = basename($name);
        $full = storage_path("app/backups/{$name}");
        if (!is_file($full)) {
            abort(404, 'Backup not found.');
        }

        // ✅ Always trust server-side filename detection to avoid double encryption
        $opts = [
            'encrypted' => $this->guessEncrypted($name),
            'mode' => $validated['mode'] ?? 'replace',
        ];

        [$ok, $msg, $report] = $backup->restoreFromArchive($full, $opts);

        // 🔧 post-restore fixes to prevent 403 and preview issues
        $this->postRestoreFixes();

        return response()->json([
            'ok' => $ok,
            'message' => $msg,
            'report' => $report,
        ], $ok ? 200 : 422);
    }

    /**
     * Restore from an uploaded ZIP (encrypted or decrypted)
     */
    public function restoreFromUpload(Request $request, BackupService $backup)
    {
        $validated = $request->validate([
            'archive' => ['required', File::types(['zip'])->max(512000)], // ~500MB cap
            'is_encrypted' => ['nullable', 'boolean'],
            'confirm' => ['required', 'in:RESTORE'],
            'mode' => ['nullable', 'in:replace,merge'],
        ]);

        // Store temporary uploaded file
        $tmp = $request->file('archive')->store('backups/tmp', 'local');
        $full = storage_path("app/{$tmp}");

        $opts = [
            // For uploads, we still honor the user's choice (cannot infer from name reliably)
            'encrypted' => (bool) ($validated['is_encrypted'] ?? false),
            'mode' => $validated['mode'] ?? 'replace',
        ];

        [$ok, $msg, $report] = $backup->restoreFromArchive($full, $opts);

        // Cleanup temporary file
        @unlink($full);

        // 🔧 post-restore fixes to prevent 403 and preview issues
        $this->postRestoreFixes();

        return response()->json([
            'ok' => $ok,
            'message' => $msg,
            'report' => $report,
        ], $ok ? 200 : 422);
    }

    /**
     * Update password for the logged-in user.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'max:64', 'different:current_password'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    /* ──────────────────────────────── Helpers ─────────────────────────────── */

    protected function mapArchives(int $page = 1, int $perPage = 10): array
    {
        $disk = Storage::disk('local');
        $files = $disk->files('backups');

        usort($files, fn($a, $b) => $disk->lastModified($b) <=> $disk->lastModified($a));

        $total = count($files);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($files, $offset, $perPage);

        $data = array_map(fn($f) => [
            'name' => basename($f),
            'date' => date('Y-m-d H:i:s', $disk->lastModified($f)),
            'size' => $disk->size($f),
        ], $slice);

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    protected function streamLocalFile(string $full, string $name): Response
    {
        return response()->streamDownload(fn() => readfile($full), $name, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Guess encryption type based on filename.
     * - backupdecrypt_*.zip → decrypted
     * - backup_*.zip        → encrypted
     * - *-enc.zip           → encrypted
     */
    protected function guessEncrypted(string $name): bool
    {
        $n = strtolower($name);
        if (str_starts_with($n, 'backupdecrypt_')) {
            return false;
        }
        if (str_ends_with($n, '-enc.zip')) {
            return true;
        }
        return str_starts_with($n, 'backup_'); // default encrypted/as-is archive
    }

    /**
     * Do all the environment fixes you typically need after restoring files/db.
     * Prevents 403 on /storage/* and broken preview temp writes.
     */
    protected function postRestoreFixes(): void
    {
        // 1) Ensure public/storage symlink exists
        try {
            // If symlink exists but is broken, re-link anyway
            if (!is_link(public_path('storage')) || !file_exists(public_path('storage'))) {
                // Try to unlink silently (works for both files and broken links)
                @unlink(public_path('storage'));
            }
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {
            // ignore on shared hosts that already link it
        }

        // 2) Ensure preview temp dir exists and is public
        try {
            Storage::disk('public')->makeDirectory('tmp_previews');
            // touch a placeholder to ensure visibility flips the directory mapping
            Storage::disk('public')->put('tmp_previews/.keep', '');
            Storage::disk('public')->setVisibility('tmp_previews/.keep', 'public');
        } catch (\Throwable $e) {
            // ignore
        }

        // 3) Normalize permissions (safe defaults)
        $this->chmodRecursive(storage_path(), 0755, 0644);
        $this->chmodRecursive(public_path('storage'), 0755, 0644);

        // 4) Clear caches so route/guard/config state isn't stale post-restore
        try {
            Artisan::call('route:clear');
        } catch (\Throwable $e) {
        }
        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
        }
        try {
            Artisan::call('view:clear');
        } catch (\Throwable $e) {
        }
    }

    /**
     * Recursively chmod directories/files (best-effort; ignores errors).
     */
    protected function chmodRecursive(string $path, int $dirPerm = 0755, int $filePerm = 0644): void
    {
        if (!is_dir($path)) {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                /** @var \SplFileInfo $item */
                if ($item->isDir()) {
                    @chmod($item->getPathname(), $dirPerm);
                } else {
                    @chmod($item->getPathname(), $filePerm);
                }
            }
        } catch (\Throwable $e) {
            // ignore permission errors
        }
    }
}
