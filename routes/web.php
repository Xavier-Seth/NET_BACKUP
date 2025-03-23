<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Inertia\Inertia;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;

// ✅ Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => Inertia::render('Auth/Login'))->name('login');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

// ✅ Authentication Routes (Default Laravel Breeze)
require __DIR__ . '/auth.php';

// ✅ Authenticated & Verified User Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');

    // Upload Page
    Route::get('/upload', fn() => Inertia::render('Upload'))->name('upload');

    // ✅ Document List Page — dynamic loading from DB
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');

    // ✅ View Individual Document (PDF, DOCX, etc.)
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');

    // ✅ Document Upload Handling
    Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');

    // ✅ User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ Admin-Only Routes
Route::middleware(['auth', 'role:Admin'])->group(function () {
    // Register new users
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // Users List Page
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // JSON list of users
    Route::get('/api/users', [UserController::class, 'getUsers'])->name('api.users');

    // Admin Editing Other Users
    Route::get('/admin/edit-user/{id}', [ProfileController::class, 'editAdmin'])->name('admin.edit-user');
    Route::patch('/admin/edit-user/{id}', [ProfileController::class, 'updateAdmin'])->name('admin.update-user');
});

// ✅ Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');
