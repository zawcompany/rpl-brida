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
use App\Http\Controllers\ReviewerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


// 1. HALAMAN PUBLIK — tanpa autentikasi (beranda, artikel terbit, arsip, berkas PDF artikel terbit)
Route::get('/', [PublicController::class, 'home'])->name('home');

Route::get('/articles', [PublicController::class, 'index'])->name('reader.index');
Route::get('/articles/{id}', [PublicController::class, 'article'])->whereNumber('id')->name('reader.article');
Route::get('/articles/{id}/pdf', [PublicController::class, 'pdf'])->whereNumber('id')->name('articles.pdf');
Route::get('/download/{id}', [PublicController::class, 'download'])->whereNumber('id')
    ->middleware('throttle:60,1')->name('articles.download');

Route::get('/archives', [PublicController::class, 'archives'])->name('archives.index');
Route::get('/archives/{issue}', [PublicController::class, 'issue'])->whereNumber('issue')->name('archives.show');

Route::get('/panduan/template', [PublicController::class, 'template'])->name('guide.template');

// URL Reader lama -> URL baru
Route::redirect('/reader', '/articles');
Route::redirect('/reader/artikel/{id}', '/articles/{id}');

// 2. Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
});

// 3. Dashboard berbasis role
Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

// 3b. Berkas naskah/revisi/final
Route::get('/berkas/{manuscript}/{type}', [
    ManuscriptFileController::class,
    'show',
])
    ->whereIn('type', ['original', 'revision', 'final'])
    ->middleware('auth')
    ->name('files.manuscript');

// 3c. Lampiran catatan review (reviewer pemilik & editor)
Route::get('/berkas-review/{review}', [ManuscriptFileController::class, 'review'])
    ->middleware('auth')
    ->name('files.review');

// 4. Logout — POST tanpa middleware auth (idempoten: sesi yang sudah habis tetap keluar bersih).
//    GET /logout (tombol Back / ketik URL) tidak lagi 405: diarahkan ke dashboard (tamu -> login).
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// (Route::redirect() bersifat ANY dan akan menimpa POST di URI yang sama, jadi dibatasi ke GET/HEAD.)
Route::match(['get', 'head'], '/logout', \Illuminate\Routing\RedirectController::class)
    ->defaults('destination', '/dashboard')->defaults('status', 302);

// 4b. Profil Saya (semua role) — selalu pada user yang login, tanpa id di URL
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Verifikasi email
    Route::redirect('/email/verifikasi', '/profil')->name('verification.notice');
    Route::get('/email/verifikasi/{id}/{hash}', [ProfileController::class, 'verifyEmail'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verifikasi/kirim-ulang', [ProfileController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifikasi/{id}/buka', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'markRead'])->name('notifications.read');
});

// 5. EDITOR MODULE
Route::middleware(['auth', 'role:editor'])
    ->prefix('editor')
    ->name('editor.')
    ->group(function () {

        Route::get('/dashboard', [
            EditorController::class,
            'dashboard',
        ])->name('dashboard');

        // Naskah Baru
        Route::get('/naskah-baru', [
            EditorController::class,
            'newManuscripts',
        ])->name('manuscripts.new');

        Route::get('/naskah/{manuscript}/detail', [
            EditorController::class,
            'manuscriptDetail',
        ])->name('manuscripts.detail');

        Route::post('/naskah/{manuscript}/assign', [
            EditorController::class,
            'assignReviewer',
        ])->name('manuscripts.assign');

        // Peninjauan Naskah
        Route::get('/peninjauan', [
            EditorController::class,
            'underReview',
        ])->name('reviews.index');

        Route::get('/peninjauan/{manuscript}/detail', [
            EditorController::class,
            'reviewDetail',
        ])->name('reviews.detail');

        Route::post('/peninjauan/{manuscript}/pengingat', [
            EditorController::class,
            'sendReminder',
        ])->name('reviews.remind');

        Route::post('/peninjauan/{manuscript}/ganti-reviewer', [
            EditorController::class,
            'changeReviewer',
        ])->name('reviews.change');

        // Keputusan Editorial
        Route::get('/keputusan', [
            EditorController::class,
            'decisions',
        ])->name('decisions.index');

        Route::get('/keputusan/{manuscript}/detail', [
            EditorController::class,
            'decisionDetail',
        ])->name('decisions.detail');

        Route::post('/keputusan/{manuscript}', [
            EditorController::class,
            'storeDecision',
        ])->name('decisions.store');

        Route::post('/keputusan/{manuscript}/review-ulang', [
            EditorController::class,
            'requestReReview',
        ])->name('decisions.rereview');

        // Edisi & Publikasi
        Route::get('/edisi', [
            IssueController::class,
            'index',
        ])->name('issues.index');

        Route::post('/edisi', [
            IssueController::class,
            'store',
        ])->name('issues.store');

        Route::put('/edisi/{issue}', [
            IssueController::class,
            'update',
        ])->name('issues.update');

        Route::delete('/edisi/{issue}', [
            IssueController::class,
            'destroy',
        ])->name('issues.destroy');

        Route::post('/edisi/{issue}/naskah', [
            IssueController::class,
            'attachManuscript',
        ])->name('issues.manuscripts.attach');

        Route::delete('/edisi/{issue}/naskah/{manuscript}', [
            IssueController::class,
            'detachManuscript',
        ])->name('issues.manuscripts.detach');

        Route::post('/edisi/{issue}/publikasi', [
            IssueController::class,
            'publish',
        ])->name('issues.publish');

        // Direktori Reviewer
        Route::get('/reviewer', [
            ReviewerDirectoryController::class,
            'index',
        ])->name('reviewers.index');

        Route::get('/reviewer/{user}/profil', [
            ReviewerDirectoryController::class,
            'profile',
        ])->name('reviewers.profile');
    });

// 6. AUTHOR MODULE
Route::middleware(['auth', 'role:author'])
    ->prefix('author')
    ->as('author.')
    ->group(function () {

        Route::get('/dashboard', [
            AuthorController::class,
            'dashboard',
        ])->name('dashboard');

        // Naskah Baru
        Route::get('/naskah-baru', [
            AuthorController::class,
            'create',
        ])->name('manuscripts.create');

        Route::post('/naskah-baru', [
            AuthorController::class,
            'store',
        ])->name('manuscripts.store');

        // Naskah Saya
        Route::get('/naskah-saya', [
            AuthorController::class,
            'index',
        ])->name('manuscripts.index');

        Route::get('/naskah/{manuscript}/detail', [
            AuthorController::class,
            'show',
        ])->name('manuscripts.show');

        // Hasil Review & Revisi
        Route::get('/revisi', [
            AuthorController::class,
            'revisions',
        ])->name('revisions.index');

        Route::get('/revisi/{manuscript}/detail', [
            AuthorController::class,
            'revisionDetail',
        ])->name('revisions.detail');

        Route::post('/revisi/{manuscript}', [
            AuthorController::class,
            'uploadRevision',
        ])->name('revisions.upload');
    });

// 7. Dev-mode shortcut — hanya aktif saat APP_ENV=local
if (app()->environment('local')) {
    Route::get('/dev-login/{role}', DevLoginController::class)
        ->name('dev.login');
}

// 8. ADMIN MODULE
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        Route::get('/dashboard', [
            AdminController::class,
            'dashboard',
        ])->name('dashboard');

        // Kelola Pengguna
        Route::get('/pengguna', [
            AdminController::class,
            'users',
        ])->name('users.index');

        Route::post('/pengguna', [
            AdminController::class,
            'storeUser',
        ])->name('users.store');

        Route::get('/pengguna/{user}', [
            AdminController::class,
            'showUser',
        ])->name('users.show');

        Route::put('/pengguna/{user}', [
            AdminController::class,
            'updateUser',
        ])->name('users.update');

        Route::patch('/pengguna/{user}/status', [
            AdminController::class,
            'toggleStatus',
        ])->name('users.status');

        Route::post('/pengguna/{user}/reset-password', [
            AdminController::class,
            'resetPassword',
        ])->name('users.reset');

        // Role & Hak Akses
        Route::get('/role', [
            AdminController::class,
            'roles',
        ])->name('roles.index');

        Route::patch('/pengguna/{user}/role', [
            AdminController::class,
            'updateRole',
        ])->name('users.role');
    });

// 9. REVIEWER MODULE
Route::middleware(['auth', 'role:reviewer'])
    ->prefix('reviewer')
    ->as('reviewer.')
    ->group(function () {

        Route::get('/dashboard', [ReviewerController::class, 'dashboard'])->name('dashboard');

        // Daftar naskah yang ditugaskan (tabel AJAX) & yang sudah selesai direview
        Route::get('/naskah-ditugaskan', [ReviewerController::class, 'index'])->name('manuscripts.index');
        Route::get('/naskah-selesai', [ReviewerController::class, 'completed'])->name('manuscripts-selesai');

        // Detail & aksi pada satu penugasan review (modal)
        Route::get('/penugasan/{review}/detail', [ReviewerController::class, 'detail'])->name('reviews.detail');
        Route::post('/penugasan/{review}/terima', [ReviewerController::class, 'accept'])->name('reviews.accept');
        Route::post('/penugasan/{review}/tolak', [ReviewerController::class, 'decline'])->name('reviews.decline');
        Route::post('/penugasan/{review}/hasil', [ReviewerController::class, 'submit'])->name('reviews.submit');
        Route::put('/penugasan/{review}/hasil', [ReviewerController::class, 'update'])->name('reviews.update');
    });

// URL profil lama per-role -> Profil Saya bersama
Route::redirect('/admin/profil', '/profil');
Route::redirect('/reviewer/profil', '/profil');
