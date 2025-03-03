<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // Import Gate

    public function store(Request $request): RedirectResponse
    {
        // ✅ Ensure only authorized users (e.g., LIS) can register new users
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        // ✅ Validate the new fields
        $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed',
            'date_of_birth' => 'nullable|date',
            'religion' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:15|unique:users,phone_number',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:Admin,LIS',
        ]);

        // ✅ Store new user with updated fields
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
            'role' => $request->role, // Save the user role
            'status' => $request->status ?? 'active', // ✅ Fixed status field
        ]);

        event(new Registered($user));

        return redirect()->route('dashboard')->with('success', 'User registered successfully!');
    }


}
