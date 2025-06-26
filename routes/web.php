<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SchoolPropertyDocumentController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\LogController;

use App\Models\Teacher;
use App\Models\Category;
use App\Models\Document;

// Guest Routes (Login, Forgot Password)
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => Inertia::render('Auth/Login'))->name('login');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

// Laravel Breeze Auth Routes
require __DIR__ . '/auth.php';

// Authenticated User Routes
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Upload Documents
    Route::get('/upload', fn() => Inertia::render('Upload', [
        'teachers' => Teacher::orderBy('full_name')->get(),
        'categories' => Category::orderBy('name')->get(),
    ]))->name('upload');

    Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::post('/upload/scan', [DocumentController::class, 'scan'])->name('documents.scan');

    // Documents (Generic)
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // DTR Documents
    Route::get('/documents/dtr', [DocumentController::class, 'dtrIndex'])->name('documents.dtr');
    Route::patch('/documents/{document}/update-metadata', [DocumentController::class, 'updateMetadata'])->name('documents.update-metadata');

    // ✅ Teacher Profile Page (uses controller that includes categories)
    Route::get('/documents/teachers-profile', [DocumentController::class, 'index'])->name('documents.teachers-profile');

    // School Property Documents (ICS / RIS)
    Route::get('/documents/school-properties', [SchoolPropertyDocumentController::class, 'index'])->name('school_properties.index');
    Route::get('/documents/school-properties/{schoolDocument}/download', [SchoolPropertyDocumentController::class, 'download'])->name('school_properties.download');
    Route::delete('/documents/school-properties/{schoolDocument}', [SchoolPropertyDocumentController::class, 'destroy'])->name('school_properties.destroy');

    // Teacher Management
    Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/register', fn() => Inertia::render('Teacher/RegisterTeacher'))
        ->middleware('role:Admin,Admin Staff')->name('teachers.register');
    Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
    Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->middleware('role:Admin,Admin Staff')->name('teachers.show');
    Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])->middleware('role:Admin,Admin Staff')->name('teachers.edit');
    Route::patch('/teachers/{teacher}', [TeacherController::class, 'update'])->middleware('role:Admin,Admin Staff')->name('teachers.update');
    Route::post('/teachers/{teacher}/update', [TeacherController::class, 'update'])->middleware('role:Admin,Admin Staff')->name('teachers.update.post');
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->middleware('role:Admin,Admin Staff')->name('teachers.destroy');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // Delete Self (Admin Only)
    Route::middleware(['role:Admin'])->delete('/api/users/{id}', [UserController::class, 'destroy']);
});

// Admin-only User Management
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/api/users', [UserController::class, 'getUsers'])->name('api.users');

    Route::get('/admin/edit-user/{id}', [ProfileController::class, 'editAdmin'])->name('admin.edit-user');
    Route::patch('/admin/edit-user/{id}', [ProfileController::class, 'updateAdmin'])->name('admin.update-user');
    Route::post('/admin/edit-user/{id}/update', [ProfileController::class, 'updateAdmin'])->name('admin.update-user.post');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('auth:sanctum');
});

// Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');
