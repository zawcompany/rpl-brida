<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevLoginController;
use App\Http\Controllers\ManuscriptFileController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ReviewerDirectoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Landing page
Route::view('/', 'landing');

// 2. Auth routes — GET untuk form, POST untuk proses
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// 3. Dashboard berbasis role — hanya untuk user terautentikasi
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

// 3b. Berkas naskah/revisi/final: disk privat, akses via auth + ManuscriptPolicy (SRS NF-04)
Route::get('/berkas/{manuscript}/{type}', [ManuscriptFileController::class, 'show'])
    ->whereIn('type', ['original', 'revision', 'final'])
    ->middleware('auth')
    ->name('files.manuscript');

// 4. Logout
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ============================================================================
// 5. EDITOR MODULE — dilindungi auth + role:editor
// ============================================================================
Route::middleware(['auth', 'role:editor'])->prefix('editor')->name('editor.')->group(function () {

    Route::get('/dashboard', [EditorController::class, 'dashboard'])->name('dashboard');

    // Naskah Baru (tabel AJAX + modal detail + keputusan administrasi)
    Route::get('/naskah-baru', [EditorController::class, 'newManuscripts'])->name('manuscripts.new');
    Route::get('/naskah/{manuscript}/detail', [EditorController::class, 'manuscriptDetail'])->name('manuscripts.detail');
    Route::post('/naskah/{manuscript}/assign', [EditorController::class, 'assignReviewer'])->name('manuscripts.assign');

    // Peninjauan Naskah
    Route::get('/peninjauan', [EditorController::class, 'underReview'])->name('reviews.index');
    Route::get('/peninjauan/{manuscript}/detail', [EditorController::class, 'reviewDetail'])->name('reviews.detail');
    Route::post('/peninjauan/{manuscript}/pengingat', [EditorController::class, 'sendReminder'])->name('reviews.remind');
    Route::post('/peninjauan/{manuscript}/ganti-reviewer', [EditorController::class, 'changeReviewer'])->name('reviews.change');

    // Keputusan Editorial
    Route::get('/keputusan', [EditorController::class, 'decisions'])->name('decisions.index');
    Route::get('/keputusan/{manuscript}/detail', [EditorController::class, 'decisionDetail'])->name('decisions.detail');
    Route::post('/keputusan/{manuscript}', [EditorController::class, 'storeDecision'])->name('decisions.store');
    Route::post('/keputusan/{manuscript}/review-ulang', [EditorController::class, 'requestReReview'])->name('decisions.rereview');

    // Edisi & Publikasi
    Route::get('/edisi', [IssueController::class, 'index'])->name('issues.index');
    Route::post('/edisi', [IssueController::class, 'store'])->name('issues.store');
    Route::put('/edisi/{issue}', [IssueController::class, 'update'])->name('issues.update');
    Route::delete('/edisi/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');
    Route::post('/edisi/{issue}/naskah', [IssueController::class, 'attachManuscript'])->name('issues.manuscripts.attach');
    Route::delete('/edisi/{issue}/naskah/{manuscript}', [IssueController::class, 'detachManuscript'])->name('issues.manuscripts.detach');
    Route::post('/edisi/{issue}/publikasi', [IssueController::class, 'publish'])->name('issues.publish');

    // Direktori Reviewer
    Route::get('/reviewer', [ReviewerDirectoryController::class, 'index'])->name('reviewers.index');
    Route::get('/reviewer/{user}/profil', [ReviewerDirectoryController::class, 'profile'])->name('reviewers.profile');
});

// ============================================================================
// 6. AUTHOR MODULE — dilindungi auth + role:author
// ============================================================================
Route::middleware(['auth', 'role:author'])->prefix('author')->as('author.')->group(function () {

    Route::get('/dashboard', [AuthorController::class, 'dashboard'])->name('dashboard');

    // Naskah Baru (form pengajuan)
    Route::get('/naskah-baru', [AuthorController::class, 'create'])->name('manuscripts.create');
    Route::post('/naskah-baru', [AuthorController::class, 'store'])->name('manuscripts.store');

    // Naskah Saya (tabel AJAX + modal tracking)
    Route::get('/naskah-saya', [AuthorController::class, 'index'])->name('manuscripts.index');
    Route::get('/naskah/{manuscript}/detail', [AuthorController::class, 'show'])->name('manuscripts.show');

    // Hasil Review & Revisi
    Route::get('/revisi', [AuthorController::class, 'revisions'])->name('revisions.index');
    Route::get('/revisi/{manuscript}/detail', [AuthorController::class, 'revisionDetail'])->name('revisions.detail');
    Route::post('/revisi/{manuscript}', [AuthorController::class, 'uploadRevision'])->name('revisions.upload');
});

// ============================================================================
// 8. ADMIN MODULE — dilindungi auth + role:admin (alias role 'Administrator')
// ============================================================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->as('admin.')->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Kelola Pengguna (UC-03)
    Route::get('/pengguna', [AdminController::class, 'users'])->name('users.index');
    Route::post('/pengguna', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/pengguna/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::put('/pengguna/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::patch('/pengguna/{user}/status', [AdminController::class, 'toggleStatus'])->name('users.status');
    Route::post('/pengguna/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset');

    // Role & Hak Akses (UC-04)
    Route::get('/role', [AdminController::class, 'roles'])->name('roles.index');
    Route::patch('/pengguna/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');

    // Profil Admin (UC-05)
    Route::get('/profil', [AdminController::class, 'profile'])->name('profile.edit');
    Route::put('/profil', [AdminController::class, 'updateProfile'])->name('profile.update');
});

// 7. Dev-mode shortcut — hanya terdaftar saat APP_ENV=local (tidak ada di produksi / route:cache produksi)
if (app()->environment('local')) {
    Route::get('/dev-login/{role}', DevLoginController::class)->name('dev.login');
}
