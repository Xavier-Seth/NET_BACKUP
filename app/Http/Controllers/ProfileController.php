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
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed'],
            'photo' => 'nullable|image|max:20480', // 20MB
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('user_photos', 'public');
        }

        // Handle password
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Update user
        $user->update($validated);

        // Refresh to get latest DB values
        $user->refresh();

        // Include full image URL for Vue
        $user->profilePicture = $user->photo_path
            ? asset('storage/' . $user->photo_path)
            : null;

        // Return with updated props
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
        if ($currentUser->role !== 'Admin') {
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

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('user_photos', 'public');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // FIXED: Stay on same page so Vue can handle modal + toast
        return back(); // or: return response()->noContent();
    }


    /**
     * Delete an account (only Admin can do so).
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        // Prevent deleting your own account
        if ($currentUser->id === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last remaining admin
        if ($user->role === 'Admin' && User::where('role', 'Admin')->count() === 1) {
            return redirect()->route('users.index')->with('error', 'Cannot delete the only remaining Admin account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
