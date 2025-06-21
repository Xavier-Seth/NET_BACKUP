<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Count total documents
        $totalDocuments = Document::count();

        // Count only active users
        $totalUsers = User::where('status', 'active')->count();

        // Count total registered teachers
        $totalTeachers = Teacher::count();

        // Calculate total storage used in bytes, then convert to MB
        $totalStorageUsed = Document::all()->sum(function ($doc) {
            return Storage::disk('public')->exists($doc->path)
                ? Storage::disk('public')->size($doc->path)
                : 0;
        });

        $formattedStorage = number_format($totalStorageUsed / 1024 / 1024, 2); // in MB

        // Get paginated teacher list (5 per page) and format for UI
        $teachers = Teacher::select('id', 'full_name as name', 'status', 'photo_path as photo')
            ->orderBy('full_name')
            ->paginate(5)
            ->through(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'photo' => $teacher->photo ? "/storage/{$teacher->photo}" : '/images/user-avatar.png',
                    'status' => $teacher->status,
                ];
            });

        // ✅ Load ALL teachers for the avatar carousel
        $avatars = Teacher::select('id', 'full_name as name', 'photo_path as photo')
            ->orderBy('full_name') // or use ->orderBy('created_at', 'desc') for recent first
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'photo' => $teacher->photo ? "/storage/{$teacher->photo}" : '/images/user-avatar.png',
                ];
            });

        return Inertia::render('Dashboard', [
            'totalStorage' => $formattedStorage,
            'totalDocuments' => $totalDocuments,
            'totalUsers' => $totalUsers,
            'totalTeachers' => $totalTeachers,
            'teachers' => $teachers,      // paginated list view
            'avatars' => $avatars,        // full list for carousel
            'success' => session('message'),
        ]);
    }
}
