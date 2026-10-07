<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman Landing / Utama
Route::get('/', function () {
    return view('landing');
});

// Route Login & Register Form (Ganti view sesuai lokasi file blade auth kamu)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Route Dashboard Berdasarkan Role
Route::get('/dashboard', function () {
    $role = Auth::user()->role ?? 'Author';

    if ($role === 'Administrator') {
        return view('roles.admin.dashboard');
    } elseif ($role === 'Editor') {
        return view('roles.editor.dashboard');
    } elseif ($role === 'Author') {
        return view('roles.author.dashboard');
    } elseif ($role === 'Reviewer') {
        return view('roles.reviewer.dashboard');
    }

    return redirect('/');
})->middleware(['auth'])->name('dashboard');

// Route Logout
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// Route Simulasi Dev Mode (Hanya untuk Testing Tampilan)
Route::get('/dev-login/{role}', function ($role) {
    $allowedRoles = ['Administrator', 'Editor', 'Author', 'Reviewer'];
    
    if (!in_array($role, $allowedRoles)) {
        abort(404, 'Role tidak ditemukan.');
    }

    // Buat atau ambil user dummy di database
    $user = User::firstOrCreate(
        ['email' => strtolower($role) . '@brida.com'],
        [
            'name' => 'Akun Tes ' . $role,
            'password' => bcrypt('password123'),
            'role' => $role,
        ]
    );

    // Login otomatis
    Auth::login($user);

    return redirect()->route('dashboard');
});