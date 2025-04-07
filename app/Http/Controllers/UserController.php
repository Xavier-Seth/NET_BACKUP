<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * ✅ Render the Users page (Inertia)
     */
    public function index()
    {
        return Inertia::render('Users');
    }

    /**
     * ✅ API: Return user list as JSON
     * Used for Vue frontend via axios
     */
    public function getUsers()
    {
        $users = User::select('id', 'first_name', 'last_name', 'middle_name', 'role', 'email', 'status')->get();

        return response()->json($users);
    }

    /**
     * ✅ API: Delete a user
     * Called from Vue via axios.delete(`/api/users/${id}`)
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
