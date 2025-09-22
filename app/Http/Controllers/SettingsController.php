<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BackupService;
use Illuminate\Support\Facades\Hash;


class SettingsController extends Controller
{
    public function index(Request $request)
    {
        // Initial page render — the Vue page will fetch paginated data on mount.
        return Inertia::render('Auth/Settings', [
            'archives' => [], // client fetches page 1 via /settings/backup/archives
        ]);
    }

    /**
     * Create backups (encrypted + decrypted) WITHOUT auto-download.
     * Returns a small JSON so the UI can refresh the list.
     */
    public function runBackup(Request $request, BackupService $backup)
    {
        [$encName, $decName] = $backup->makeBoth(); // creates both variants
        return response()->json([
            'ok' => true,
            'created' => [$encName, $decName],
            'message' => 'Backups created.',
        ]);
    }

    /**
     * (Optional) Create and stream-download immediately.
     */
    public function runAndDownload(Request $request, BackupService $backup)
    {
        $names = $backup->makeBoth();
        $name = $names[0] ?? null; // default to the first one (encrypted)
        if (!$name) {
            abort(500, 'Backup was not created.');
        }

        $full = storage_path("app/backups/{$name}");
        return $this->streamLocalFile($full, $name);
    }

    /**
     * List archives as JSON (paginated).
     * Query params: ?page=1&perPage=10
     */
    public function archives(Request $request)
    {
        // pagination inputs
        $page = max((int) $request->query('page', 1), 1);
        // force 10 per request as asked; still sanitize if someone changes the client
        $perPage = (int) $request->query('perPage', 10);
        if ($perPage <= 0)
            $perPage = 10;
        if ($perPage > 100)
            $perPage = 100;

        $result = $this->mapArchives($page, $perPage);

        // Optional debug payload
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
     * Serve /settings/backup/download/{name}
     */
    public function download(string $name)
    {
        // prevent path traversal
        $name = basename($name);
        $full = storage_path("app/backups/{$name}");
        if (!is_file($full)) {
            abort(404, 'Backup not found.');
        }
        return $this->streamLocalFile($full, $name);
    }

    /* ── helpers ─────────────────────────────────────────────── */

    /**
     * Build a paginated list of backup files, sorted by lastModified DESC (newest first).
     *
     * @return array{
     *   data: array<int, array{name:string,date:string,size:int}>,
     *   total:int,
     *   per_page:int,
     *   current_page:int,
     *   last_page:int
     * }
     */
    protected function mapArchives(int $page = 1, int $perPage = 10): array
    {
        $disk = Storage::disk('local');
        $files = $disk->files('backups'); // array of "backups/filename.zip"

        // sort by lastModified DESC (newest first), independent of filename
        usort($files, function ($a, $b) use ($disk) {
            return $disk->lastModified($b) <=> $disk->lastModified($a);
        });

        $total = count($files);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $pageSlice = array_slice($files, $offset, $perPage);

        $data = array_values(array_map(function ($f) use ($disk) {
            return [
                'name' => basename($f),
                'date' => date('Y-m-d H:i:s', $disk->lastModified($f)),
                'size' => $disk->size($f),
            ];
        }, $pageSlice));

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
        return response()->streamDownload(function () use ($full) {
            readfile($full);
        }, $name, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }


    public function updatePassword(Request $request)
    {
        // Validate inputs
        $validated = $request->validate([
            // Validates against the current authenticated user's password
            'current_password' => ['required', 'current_password'],
            // You used `confirm_password` in the Vue, so we'll validate it with "same:new_password"
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
}
