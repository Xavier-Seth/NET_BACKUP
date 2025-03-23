<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Display the logged-in user's profile.
     */
    public function edit()
    {
        // Only shows the current user's profile (for normal users)
        $user = Auth::user();

        return Inertia::render('Profile/UserProfile', [
            'user' => $user,
        ]);
    }

    /**
     * Update the logged-in user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married',
            'date_of_birth' => 'required|date',
            'religion' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Admin Edit Another User
     */
    public function editAdmin($id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Unauthorized access.');
        }

        // If you want to block Admin from editing themselves with the Admin UI,
        // uncomment the lines below:
        // if ($currentUser->id == $id) {
        //     return redirect()->route('profile.edit')->with('error', 'You cannot edit your own profile here.');
        // }

        $user = User::findOrFail($id);

        return Inertia::render('Profile/AdminEditUser', [
            'user' => $user,
        ]);
    }

    /**
     * Admin Update Another User
     */
    public function updateAdmin(Request $request, $id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', 'in:Male,Female'],
            'civil_status' => ['required', 'in:Single,Married'],
            'date_of_birth' => ['required', 'date'],
            'religion' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed'],
            'role' => ['required', 'in:Admin,LIS,User'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Delete an account (only Admin can do so).
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'Admin') {
            // If normal users can also delete themselves, you can remove this check. 
            // But in your code, it looks like only Admins can call destroy().
            return redirect()->route('profile.edit')->with('error', 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        // Prevent Admin from deleting themselves
        if ($currentUser->id === $user->id) {
            return redirect()->route('users.index')->with('error', 'Admin cannot delete their own account.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
