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

        $totalStorageUsed = 0;
        $documents = Document::all();

        foreach ($documents as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                $totalStorageUsed += Storage::disk('public')->size($doc->file_path);
            }
        }

        return Inertia::render('Dashboard', [
            'totalDocuments' => $totalDocuments,
            'totalUsers' => $totalUsers,
            'totalStorage' => number_format($totalStorageUsed / (1024 * 1024), 2) . ' MB',
        ]);
    }
}
