<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EditorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Landing page
Route::get('/', fn() => view('landing'));

// 2. Auth routes — GET untuk form, POST untuk proses
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 3. Dashboard berbasis role — hanya untuk user terautentikasi
Route::get('/dashboard', function () {
    $roleViews = [
        'Administrator' => 'roles.admin.dashboard',
        'Reviewer'      => 'roles.reviewer.dashboard',
        'Author'        => 'roles.author.dashboard',
    ];

    // Editor diarahkan ke EditorController@dashboard (data dinamis)
    if (auth()->user()->role === 'Editor') {
        return app(EditorController::class)->dashboard();
    }

    $role = auth()->user()->role;
    $view = $roleViews[$role] ?? $roleViews['Author'];
    return view($view);
})->middleware('auth')->name('dashboard');

// 4. Logout
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ============================================================================
// 5. EDITOR MODULE — dilindungi auth + role:Editor
// ============================================================================
Route::middleware(['auth', 'role:Editor'])->prefix('editor')->name('editor.')->group(function () {

    // Dashboard (sama dengan /dashboard tapi route bernama)
    Route::get('/dashboard', [EditorController::class, 'dashboard'])->name('dashboard');

    // Naskah Baru — tabel + AJAX search/filter
    Route::get('/naskah-baru', [EditorController::class, 'newManuscripts'])->name('manuscripts.new');

    // AJAX: ambil detail naskah + rekomendasi reviewer (untuk modal)
    Route::get('/naskah/{manuscript}/detail', [EditorController::class, 'manuscriptDetail'])->name('manuscripts.detail');

    // POST: keputusan administrasi awal
    Route::post('/naskah/{manuscript}/assign', [EditorController::class, 'assignReviewer'])->name('manuscripts.assign');
});

// 6. Dev-mode shortcut (hapus di production)
Route::get('/dev-login/{role}', function (string $role) {
    $allowed = ['Administrator', 'Editor', 'Author', 'Reviewer'];
    abort_unless(in_array($role, $allowed), 404);

    $user = \App\Models\User::updateOrCreate(
        ['email' => strtolower($role) . '@brida.com'],
        ['name' => 'Akun Tes ' . $role, 'password' => bcrypt('password123'), 'role' => $role]
    );

    auth()->login($user);
    request()->session()->regenerate();

    return redirect()->route('dashboard');
});