<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Render the Users page (Inertia)
     */
    public function index()
    {
        return \Inertia\Inertia::render('Users');
    }

    /**
     * API: Return user list as JSON
     * Used for Vue frontend via axios
     */
    public function getUsers()
    {
        // FIX: We eager load the 'teacher' relationship to get the Teacher ID.
        // We then map the result to include 'teacher_id' in the JSON response.

        $users = User::with('teacher:id,user_id') // optimized: only get id and user_id from teachers table
            ->select('id', 'first_name', 'last_name', 'middle_name', 'role', 'email', 'status')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'role' => $user->role,
                    'email' => $user->email,
                    'status' => $user->status,
                    // If the user has a linked teacher profile, grab that ID. Otherwise null.
                    'teacher_id' => $user->teacher ? $user->teacher->id : null,
                ];
            });

        return response()->json($users);
    }

    /**
     * API: Delete a user (Admin only)
     * - Cannot delete yourself
     * - Cannot delete the last remaining Admin
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();

        if (!$currentUser || $currentUser->role !== 'Admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Prevent deleting your own account
        if ((int) $currentUser->id === (int) $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        // Prevent deleting the only remaining Admin (active or not — keep at least one Admin record)
        $totalAdmins = User::where('role', 'Admin')->count();
        if ($user->role === 'Admin' && $totalAdmins <= 1) {
            return response()->json(['message' => 'Cannot delete the only remaining Admin account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}