<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalDocuments = Document::count();
        $totalUsers = User::count();

        // Calculate total storage used
        $totalStorageUsed = Document::all()->sum(function ($doc) {
            return Storage::disk('public')->exists($doc->path)
                ? Storage::disk('public')->size($doc->path)
                : 0;
        });

        // Get recent uploads with uploader name
        $recentUploads = Document::with('user') // Eager load user
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'filename' => $doc->name,
                    'type' => $doc->type,
                    'file_path' => $doc->path,
                    'pdf_preview_path' => $doc->pdf_preview_path,
                    'uploaded_by' => $doc->user ? $doc->user->name : 'N/A',
                    'created_at' => $doc->created_at,
                ];
            });

        return Inertia::render('Dashboard', [
            'totalDocuments' => $totalDocuments,
            'totalUsers' => $totalUsers,
            'totalStorage' => number_format($totalStorageUsed / (1024 * 1024), 2) . ' MB',
            'recentUploads' => $recentUploads,
            'success' => session('message'),
        ]);
    }
}
