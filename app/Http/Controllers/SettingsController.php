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
        // Authorization handled by route middleware (role:Admin,Admin Staff)
        $name = $backup->make();
        return back()->with('success', "Backup created: {$name}");
    }

    public function archives(Request $request)
    {
        // Authorization handled by route middleware (role:Admin,Admin Staff)
        return response()->json($this->mapArchives());
    }

    public function download(Request $request, string $name)
    {
        // Authorization handled by route middleware (role:Admin,Admin Staff)
        $path = "backups/{$name}";
        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, $name, [
            'Content-Type' => 'application/zip',
        ]);
    }

    // ── helpers ───────────────────────────────────────────────
    protected function mapArchives(): array
    {
        $files = Storage::files('backups');
        rsort($files); // newest first

        return array_values(array_map(function ($f) {
            return [
                'name' => basename($f),
                'date' => date('Y-m-d H:i:s', Storage::lastModified($f)),
                'size' => Storage::size($f),
            ];
        }, $files));
    }
}
