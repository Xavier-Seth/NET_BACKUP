<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Services\BackupService;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        // If your Vue file is at resources/js/Pages/Auth/Settings.vue, keep 'Auth/Settings'
        // If it's at resources/js/Pages/Settings.vue, change to 'Settings'
        return Inertia::render('Auth/Settings', [
            'archives' => $this->mapArchives(),
        ]);
    }

    public function runBackup(Request $request, BackupService $backup)
    {
        // 🔒 Only Admin / Admin Staff can run this (handled by middleware in web.php)
        $name = $backup->make();
        $full = storage_path("app/backups/{$name}");

        // Immediately download the freshly created ZIP
        return response()->download($full, $name, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function archives(Request $request)
    {
        // 🔒 Only Admin / Admin Staff can access (handled by middleware)
        return response()->json($this->mapArchives());
    }

    public function download(Request $request, string $name)
    {
        // 🔒 Only Admin / Admin Staff can access (handled by middleware)
        $path = "backups/{$name}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        $full = storage_path("app/{$path}");
        return response()->download($full, $name, [
            'Content-Type' => 'application/zip',
        ]);
    }

    // ── helpers ───────────────────────────────────────────────
    protected function mapArchives(): array
    {
        $disk = Storage::disk('local'); // force local to avoid FILESYSTEM_DISK mismatch
        $files = $disk->files('backups');
        rsort($files); // newest first

        return array_values(array_map(function ($f) use ($disk) {
            return [
                'name' => basename($f),
                'date' => date('Y-m-d H:i:s', $disk->lastModified($f)),
                'size' => $disk->size($f),
            ];
        }, $files));
    }
}
