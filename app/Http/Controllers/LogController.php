<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Log::with('user')->orderBy('created_at', 'desc');

        // IF USER IS A TEACHER: Filter logs
        if ($user->role === 'Teacher') {
            // 1. Get the teacher profile linked to this user
            $teacherProfile = $user->teacher;

            if ($teacherProfile) {
                $fullName = $teacherProfile->full_name; // e.g. "Juan Dela Cruz"

                // Show logs where:
                // A) The teacher THEMSELVES did the action (user_id matches)
                // B) OR the activity text mentions their name (e.g. "Uploaded document for Juan Dela Cruz")
                $query->where(function ($q) use ($user, $fullName) {
                    $q->where('user_id', $user->id)
                        ->orWhere('activity', 'LIKE', "%{$fullName}%");
                });
            } else {
                // If no profile yet, only show their own direct actions
                $query->where('user_id', $user->id);
            }
        }

        // IF ADMIN / ADMIN STAFF: They see everything (no extra filter needed)

        $logs = $query->get();

        return Inertia::render('Logs', [
            'logs' => $logs,
        ]);
    }
}