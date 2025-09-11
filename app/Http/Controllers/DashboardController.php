<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // ---------- Totals ----------
        $totalDocuments = Document::count();
        $totalTeachers = Teacher::count();
        $activeCount = Teacher::where('status', 'active')->count();
        $inactiveCount = Teacher::where('status', 'inactive')->count();
        $totalUsers = User::where('status', 'active')->count(); // kept from your original

        // ---------- Storage usage (MB) + simple breakdown ----------
        // Walk documents and sum file sizes safely (skip missing files).
        $totalBytes = 0;
        $byType = [
            'PDF' => 0,
            'Images' => 0,   // jpg jpeg png gif
            'XLS' => 0,   // xls xlsx
            'DOC' => 0,   // doc docx
            'Other' => 0,
        ];

        // If you have a lot of rows, chunk to avoid memory spikes
        Document::select('path')->chunk(500, function ($docs) use (&$totalBytes, &$byType) {
            foreach ($docs as $doc) {
                $path = $doc->path;
                if (!$path || !Storage::disk('public')->exists($path)) {
                    continue;
                }
                $size = Storage::disk('public')->size($path);
                $totalBytes += $size;

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $byType['PDF'] += $size;
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $byType['Images'] += $size;
                } elseif (in_array($ext, ['xls', 'xlsx'])) {
                    $byType['XLS'] += $size;
                } elseif (in_array($ext, ['doc', 'docx'])) {
                    $byType['DOC'] += $size;
                } else {
                    $byType['Other'] += $size;
                }
            }
        });

        $totalMB = $totalBytes / 1024 / 1024;
        $formattedStorage = number_format($totalMB, 2) . ' MB';

        // Breakdown string like "PDF 65% · Images 25% · XLS 10%"
        $storageBreakdown = $this->formatStorageBreakdown($byType, $totalBytes);

        // ---------- Paginated teacher list (cards) ----------
        $teachers = Teacher::select('id', 'full_name', 'status', 'photo_path', 'department')
            ->orderBy('full_name')
            ->paginate(10)
            ->through(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->full_name,
                    'photo' => $t->photo_path ? "/storage/{$t->photo_path}" : '/images/user-avatar.png',
                    'status' => $t->status ?: 'inactive',
                    'department' => $t->department ?? null,
                ];
            });

        // ---------- Avatars (top carousel) with recent docs for Quick Actions ----------
        // Sending 20 avatars keeps payload small and UX snappy; change to ->get() for all.
        $avatars = Teacher::select('id', 'full_name', 'photo_path', 'status', 'department')
            ->orderBy('full_name')
            ->take(20)
            ->get()
            ->map(function ($t) {
                // Most recent 3 documents for instant modal open
                $recent = Document::where('teacher_id', $t->id)
                    ->latest()
                    ->take(3)
                    ->get(['id', 'name', 'created_at'])
                    ->map(fn($d) => [
                        'id' => $d->id,
                        'name' => $d->name,
                        'created_at_human' => optional($d->created_at)->diffForHumans(),
                    ]);

                return [
                    'id' => $t->id,
                    'name' => $t->full_name,
                    'photo' => $t->photo_path ? "/storage/{$t->photo_path}" : '/images/user-avatar.png',
                    'status' => $t->status ?: 'inactive',
                    'department' => $t->department ?? null,
                    'recent' => $recent,
                ];
            });

        // ---------- Recent activity (surface last 5 document events) ----------
        // If you use a dedicated Activity model or audit log, swap this block accordingly.
        $recentActivity = Document::latest()
            ->take(5)
            ->get(['id', 'name', 'user_id', 'created_at'])
            ->map(function ($d) {
                $userName = optional(User::find($d->user_id))->name ?? 'System';
                return [
                    'user' => $userName,
                    'action' => 'uploaded',
                    'title' => $d->name,
                    'time' => optional($d->created_at)->diffForHumans(),
                ];
            });

        return Inertia::render('Dashboard', [
            // widgets
            'totalStorage' => $formattedStorage,
            'storageBreakdown' => $storageBreakdown,
            'totalDocuments' => $totalDocuments,
            'totalUsers' => $totalUsers,
            'totalTeachers' => $totalTeachers,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,

            // lists
            'teachers' => $teachers,    // paginated list
            'avatars' => $avatars,     // for carousel (with 'recent')

            // activity feed
            'recentActivity' => $recentActivity,

            // flash
            'success' => session('message'),
        ]);
    }

    /**
     * Build a compact human string e.g., "PDF 65% · Images 25% · XLS 10%".
     */
    private function formatStorageBreakdown(array $byType, int $totalBytes): string
    {
        if ($totalBytes <= 0) {
            return '';
        }

        // Keep the top 3 non-zero categories by share
        $parts = collect($byType)
            ->map(fn($bytes, $label) => [
                'label' => $label,
                'pct' => $bytes > 0 ? round(($bytes / $totalBytes) * 100) : 0,
            ])
            ->filter(fn($row) => $row['pct'] > 0)
            ->sortByDesc('pct')
            ->take(3)
            ->map(fn($row) => "{$row['label']} {$row['pct']}%")
            ->values()
            ->all();

        return implode(' · ', $parts);
    }
}
