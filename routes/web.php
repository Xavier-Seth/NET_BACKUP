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

use App\Models\Teacher;
use App\Models\Category;
use App\Models\Document;

// ✅ Guest Routes (Login, Forgot Password)
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => Inertia::render('Auth/Login'))->name('login');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

// ✅ Laravel Breeze Authentication Routes
require __DIR__ . '/auth.php';

// ✅ Authenticated User Routes
Route::middleware(['auth', 'verified'])->group(function () {

    // ✅ Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ✅ Upload documents (Upload Page)
    Route::get('/upload', function () {
        return Inertia::render('Upload', [
            'teachers' => Teacher::orderBy('full_name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    })->name('upload');

    // ✅ Upload documents (Post)
    Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');

    // ✅ Scan documents (OCR auto-detect Teacher + Category)
    Route::post('/upload/scan', [DocumentController::class, 'scan'])->name('documents.scan');

    // ✅ Documents Listing, Preview, Download
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // ✅ School Properties Documents (ICS / RIS)
    Route::get('/documents/school-properties', [SchoolPropertyDocumentController::class, 'index'])->name('school_properties.index');

    // ✅ Teacher Profile Documents Page
    Route::get('/documents/teachers-profile', function () {
        return Inertia::render('TeacherProfile', [
            'documents' => Document::with('teacher', 'category')->get(),
            'teachers' => Teacher::orderBy('full_name')->get(),
        ]);
    })->name('documents.teachers-profile');

    // ✅ User Profile (Self)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ✅ Register Teacher (Form Page)
    Route::get('/teachers/register', fn() => Inertia::render('RegisterTeacher'))
        ->middleware('role:Admin,Admin Staff')
        ->name('teachers.register');

    // ✅ Save New Teacher (POST)
    Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');

    // ✅ Delete own account (Admin Only)
    Route::middleware(['role:Admin'])->delete('/api/users/{id}', [UserController::class, 'destroy']);

    // ✅ Logs Page
    Route::get('/logs', fn() => Inertia::render('Logs'))->name('logs.index');
});

// ✅ Admin-Only Routes (User Management)
Route::middleware(['auth', 'role:Admin'])->group(function () {

    // ✅ User Registration (Admin)
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // ✅ User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/api/users', [UserController::class, 'getUsers'])->name('api.users');

    Route::get('/admin/edit-user/{id}', [ProfileController::class, 'editAdmin'])->name('admin.edit-user');
    Route::patch('/admin/edit-user/{id}', [ProfileController::class, 'updateAdmin'])->name('admin.update-user');

    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Admin']);
});

// ✅ Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');
