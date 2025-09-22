<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

// Controllers
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SchoolPropertyDocumentController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingsController;

// Models
use App\Models\Teacher;
use App\Models\Category;

/* ------------------------------
   Guest Routes
------------------------------ */
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/', fn() => Inertia::render('Auth/Login'))->name('login');

    // Forgot password
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    // Reset password form (from email link)
    Route::get('/reset-password/{token}', function (Request $request, string $token) {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    })->name('password.reset');

    // Reset password (submit new password)
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

require __DIR__ . '/auth.php';

/* ------------------------------
   Authenticated Routes
------------------------------ */
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings (page visible to all users)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    // ✅ Security tab (password update)
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])
        ->name('settings.security.update');

    // ✅ Fresh CSRF token for SPA retries
    Route::get('/csrf-token', fn() => response()->json(['token' => csrf_token()]))
        ->name('csrf.token');

    // ✅ General tab (school name + logo) — limit to Admin & Admin Staff
    Route::middleware('role:Admin,Admin Staff')->group(function () {
        Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])
            ->name('settings.general.update');

        // Backup
        Route::post('/settings/backup/run', [SettingsController::class, 'runBackup'])->name('settings.backup.run');
        Route::get('/settings/backup/run-download', [SettingsController::class, 'runAndDownload'])->name('settings.backup.run_download');
        Route::get('/settings/backup/archives', [SettingsController::class, 'archives'])->name('settings.backup.archives');
        Route::get('/settings/backup/download/{name}', [SettingsController::class, 'download'])->name('settings.backup.download');
    });

    // Upload (document upload page)
    Route::get('/upload', fn() => Inertia::render('Upload', [
        'teachers' => Teacher::orderBy('full_name')->get(),
        'categories' => Category::orderBy('name')->get(),
    ]))->name('upload');
    Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::post('/upload/scan', [DocumentController::class, 'scan'])->name('documents.scan');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/dtr', [DocumentController::class, 'dtrIndex'])->name('documents.dtr');
    Route::patch('/documents/{document}/update-metadata', [DocumentController::class, 'updateMetadata'])->name('documents.update-metadata');

    // Preview / Download / Delete
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Teacher Profile alias
    Route::get('/documents/teachers-profile', [DocumentController::class, 'index'])->name('documents.teachers-profile');

    // School Property Documents (ICS / RIS)
    Route::get('/documents/school-properties', [SchoolPropertyDocumentController::class, 'index'])->name('school_properties.index');
    Route::get('/documents/school-properties/{schoolDocument}/download', [SchoolPropertyDocumentController::class, 'download'])->name('school_properties.download');
    Route::delete('/documents/school-properties/{schoolDocument}', [SchoolPropertyDocumentController::class, 'destroy'])->name('school_properties.destroy');

    // Teachers
    Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/register', fn() => Inertia::render('Teacher/RegisterTeacher'))
        ->middleware('role:Admin,Admin Staff')
        ->name('teachers.register');
    Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
    Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->middleware('role:Admin,Admin Staff')->name('teachers.show');
    Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])->middleware('role:Admin,Admin Staff')->name('teachers.edit');
    Route::patch('/teachers/{teacher}', [TeacherController::class, 'update'])->middleware('role:Admin,Admin Staff')->name('teachers.update');
    Route::post('/teachers/{teacher}/update', [TeacherController::class, 'update'])->middleware('role:Admin,Admin Staff')->name('teachers.update.post');
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->middleware('role:Admin,Admin Staff')->name('teachers.destroy');

    // Profile (self)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // Admin-only API for user delete self-protection
    Route::middleware(['role:Admin'])->delete('/api/users/{id}', [UserController::class, 'destroy']);
});

/* ------------------------------
   Admin-only User Management
------------------------------ */
Route::middleware(['auth', 'role:Admin'])->group(function () {
    // ✅ Fixed: use different names
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/api/users', [UserController::class, 'getUsers'])->name('api.users');

    Route::get('/admin/edit-user/{id}', [ProfileController::class, 'editAdmin'])->name('admin.edit-user');
    Route::patch('/admin/edit-user/{id}', [ProfileController::class, 'updateAdmin'])->name('admin.update-user');
    Route::post('/admin/edit-user/{id}/update', [ProfileController::class, 'updateAdmin'])->name('admin.update-user.post');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('auth:sanctum');
});

/* ------------------------------
   Logout
------------------------------ */
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');
