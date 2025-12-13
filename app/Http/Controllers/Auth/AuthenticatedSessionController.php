<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // --- 1. SECURITY CHECK: Is the user active? ---
        if ($request->user()->status !== 'active') {
            // If not active, log them out immediately
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect back to login with an error message
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is inactive. Please contact the administrator.',
            ]);
        }

        // --- 2. ROLE REDIRECTION: Where should they go? ---

        // If the user is a Teacher, send them to their own profile view
        if ($request->user()->role === 'Teacher') {
            return redirect()->route('teacher.my-profile');
        }

        // Default redirect for Admin, Staff, or anyone else
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}