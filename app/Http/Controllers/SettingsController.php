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

    /**
     * Old POST endpoint (kept for background/manual use).
     * Creates a backup then redirects back with a flash message.
     * Not used for direct download because XHR cannot stream files.
     */
    public function runBackup(Request $request, BackupService $backup)
    {
        $name = $backup->make();
        return back()->with('success', "Backup created: {$name}");
    }

    /**
     * NEW: GET endpoint that creates the backup and immediately streams it.
     * Use this for the "Run Backup Now" button to trigger a real browser download.
     */
    public function runAndDownload(Request $request, BackupService $backup)
    {
        $name = $backup->make();
        $full = storage_path("app/backups/{$name}");

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

    public function archives(Request $request)
    {
        return response()->json($this->mapArchives());
    }

    public function download(Request $request, string $name)
    {
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
