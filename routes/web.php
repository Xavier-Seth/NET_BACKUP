<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login'); // Ensure this is the correct path to your login component
})->name('login');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->json(['message' => 'Logged out successfully']);
})->middleware('auth');

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/upload', function () {
        return Inertia::render('Upload'); // Ensure 'Upload.vue' exists in resources/js/Pages/
    })->name('upload');
});

Route::get('/users', function () {
    return Inertia::render('Users');
})->name('users.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/documents', function () {
        return Inertia::render('Documents'); // Ensure 'Documents.vue' exists in resources/js/Pages/
    })->name('documents');
});

Route::get('/register', function () {
    return Inertia::render('Auth/Register'); // Correct path
})->name('register');