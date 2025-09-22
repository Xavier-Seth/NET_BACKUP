<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BackupService;
use Illuminate\Support\Facades\Hash;
use App\Models\SystemSetting;

class SettingsController extends Controller
{
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
                'logo_path_url' => $settings->logo_path ? Storage::url($settings->logo_path) : null,
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

        $settings = SystemSetting::firstOrFail();

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
            'logo_url' => $settings->logo_path ? Storage::url($settings->logo_path) : null,
            'settings' => $settings,
        ]);
    }

    public function runBackup(Request $request, BackupService $backup)
    {
        [$encName, $decName] = $backup->makeBoth();
        return response()->json([
            'ok' => true,
            'created' => [$encName, $decName],
            'message' => 'Backups created.',
        ]);
    }

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

    public function download(string $name)
    {
        $name = basename($name);
        $full = storage_path("app/backups/{$name}");
        if (!is_file($full)) {
            abort(404, 'Backup not found.');
        }
        return $this->streamLocalFile($full, $name);
    }

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

    /* ── Helpers ─────────────────────────────────────────────── */

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
}
