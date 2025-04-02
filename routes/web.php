<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;

// ✅ Guest Routes (Login, Forgot Password)
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => Inertia::render('Auth/Login'))->name('login');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

// ✅ Laravel Breeze Auth Routes
require __DIR__ . '/auth.php';

// ✅ Authenticated User Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ✅ Document Upload (Student + School)
    Route::get('/upload', fn() => Inertia::render('Upload'))->name('upload');
    Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');

    // ✅ Documents Listing & Viewing
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');

    // ✅ User Profile (Self)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ Admin-Only Routes (User + Student Management)
Route::middleware(['auth', 'role:Admin'])->group(function () {
    // User Management
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/api/users', [UserController::class, 'getUsers'])->name('api.users');

    Route::get('/admin/edit-user/{id}', [ProfileController::class, 'editAdmin'])->name('admin.edit-user');
    Route::patch('/admin/edit-user/{id}', [ProfileController::class, 'updateAdmin'])->name('admin.update-user');

    // ✅ Student Management
    Route::get('/students/register', fn() => Inertia::render('RegisterStudent'))->name('students.register');
    Route::post('/students/register', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/records', [StudentController::class, 'index'])->name('students.records');

    // ✅ Update Grades
    Route::post('/students/{lrn}/update-grades', [StudentController::class, 'updateGrades'])->name('students.update-grades');
    Route::get('/students/{lrn}', [StudentController::class, 'show']);

    // (Optional: Add this if you want to fetch a single student's details with grades in future)
    // Route::get('/students/{lrn}', [StudentController::class, 'show'])->name('students.show');
});

// ✅ Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');
