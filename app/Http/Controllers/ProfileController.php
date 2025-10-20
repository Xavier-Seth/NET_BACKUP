<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Display the logged-in user's profile.
     */
    public function edit()
    {
        $user = Auth::user();

        $user->profilePicture = $user->photo_path
            ? asset('storage/' . $user->photo_path)
            : null;

        return Inertia::render('Profile/UserProfile', [
            'user' => $user,
        ]);
    }

    /**
     * Update the logged-in user's profile.
     * NOTE: Users cannot change their own role/status here.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'nullable|in:Male,Female',
            'civil_status' => 'nullable|in:Single,Married,Widowed',
            'date_of_birth' => 'nullable|date',
            'religion' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed'],
            'photo' => 'nullable|image|max:20480',
            // role/status intentionally NOT accepted here to avoid self-demotion/deactivation
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('user_photos', 'public');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->refresh();

        $user->profilePicture = $user->photo_path
            ? asset('storage/' . $user->photo_path)
            : null;

        return Inertia::render('Profile/UserProfile', [
            'user' => $user,
        ])->with('success', 'Profile updated successfully.');
    }

    /**
     * Admin Edit Another User
     */
    public function editAdmin($id)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Unauthorized access.');
        }

        $user = User::findOrFail($id);
        $user->profilePicture = $user->photo_path
            ? asset('storage/' . $user->photo_path)
            : null;

        return Inertia::render('Profile/AdminEditUser', [
            'user' => $user,
        ]);
    }

    /**
     * Admin Update Another User
     * Guards:
     * - Only Admin can use this.
     * - You cannot change your own role away from Admin or set your own status to inactive.
     * - You cannot demote/deactivate the only remaining Admin.
     */
    public function updateAdmin(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', 'in:Male,Female'],
            'civil_status' => ['required', 'in:Single,Married,Widowed'],
            'date_of_birth' => ['required', 'date'],
            'religion' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed'],
            'role' => ['required', 'in:Admin,Admin Staff,User'],
            'status' => ['required', 'in:active,inactive'],
            'photo' => 'nullable|image|max:20480',
        ]);

        // Photo
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('user_photos', 'public');
        }

        // Password (optional)
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $requestedRole = $validated['role'] ?? $user->role;
        $requestedStatus = $validated['status'] ?? $user->status;

        $isSelf = ((int) $currentUser->id === (int) $user->id);
        $isOnlyAdmin = User::where('role', 'Admin')->count() <= 1 && $user->role === 'Admin';

        // 1) Self-protection: an Admin cannot demote or deactivate themself
        if ($isSelf) {
            if ($requestedRole !== 'Admin') {
                throw ValidationException::withMessages([
                    'role' => 'You cannot change your own role away from Admin.',
                ]);
            }
            if ($requestedStatus !== 'active') {
                throw ValidationException::withMessages([
                    'status' => 'You cannot deactivate your own account.',
                ]);
            }
        }

        // 2) Last-Admin protection: cannot demote/deactivate the only remaining Admin
        if ($isOnlyAdmin) {
            if ($requestedRole !== 'Admin') {
                throw ValidationException::withMessages([
                    'role' => 'Cannot demote the only remaining Admin.',
                ]);
            }
            if ($requestedStatus !== 'active') {
                throw ValidationException::withMessages([
                    'status' => 'Cannot deactivate the only remaining Admin.',
                ]);
            }
        }

        // All good → update
        $user->update($validated);

        // Stay on page so your modal/toast can display (Inertia sees 200 OK)
        return back();
    }

    /**
     * Delete an account (only Admin can do so).
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        // Only Admin can delete others
        if (!$currentUser || $currentUser->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Unauthorized.');
        }

        // Prevent deleting your own account
        if ((int) $currentUser->id === (int) $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last remaining Admin
        $totalAdmins = User::where('role', 'Admin')->count();
        if ($user->role === 'Admin' && $totalAdmins <= 1) {
            return redirect()->route('users.index')->with('error', 'Cannot delete the only remaining Admin account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
