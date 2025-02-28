<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login'); // Ensure this path is correct
})->name('login');

// Dashboard (Requires Authentication)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/upload', function () {
        return Inertia::render('Upload'); // Ensure 'Upload.vue' exists
    })->name('upload');

    Route::get('/documents', function () {
        return Inertia::render('Documents'); // Ensure 'Documents.vue' exists
    })->name('documents');
});

// User Profile Routes (Requires Authentication)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Forgot Password Routes
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
});

// Logout Route (Redirects to Login Page)
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// User List Route (No Authentication Required)
Route::get('/users', function () {
    return Inertia::render('Users');
})->name('users.index');

// Registration Page Route
Route::get('/register', function () {
    return Inertia::render('Auth/Register'); // Ensure this matches the Vue file's folder
})->name('register');

// Authentication Routes (Default Laravel Auth)
require __DIR__ . '/auth.php';
