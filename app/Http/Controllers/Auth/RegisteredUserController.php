<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Http\RedirectResponse; // ✅ Correct return type

class RegisteredUserController extends Controller
{
    /**
     * Show the user registration form.
     */
    public function create()
    {
        return \Inertia\Inertia::render('Auth/Register');
    }

    /**
     * Handle the registration request and store the user.
     */
    public function store(Request $request): RedirectResponse // ✅ Fixed return type
    {
        $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed',
            'date_of_birth' => 'nullable|date',
            'religion' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:15|unique:users,phone_number',
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:Admin,Admin Staff',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:20480',
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('user_photos', 'public')
            : null;

        $user = User::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'sex' => $request->sex,
            'civil_status' => $request->civil_status,
            'date_of_birth' => $request->date_of_birth,
            'religion' => $request->religion,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'photo_path' => $photoPath,
        ]);

        event(new Registered($user));

        // ✅ Redirect instead of returning no content to avoid Inertia blank screen
        return redirect()->route('register');
    }
}
