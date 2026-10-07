<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Halaman Landing / Utama (Public Reader)
Route::get('/', function () {
    return view('landing');
});

// 2. Route Auth (Form Login & Register)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// 3. Route Dashboard Utama Berdasarkan Role Pengguna yang Login
Route::get('/dashboard', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login');
    }

    if ($user->role === 'Administrator') {
        return view('roles.admin.dashboard');
    } elseif ($user->role === 'Editor') {
        return view('roles.editor.dashboard');
    } elseif ($user->role === 'Reviewer') {
        return view('roles.reviewer.dashboard');
    }

    // Default untuk Author
    return view('roles.author.dashboard');
})->middleware(['auth'])->name('dashboard');

// 4. Route Logout
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// 5. Route Simulasi Dev Mode (Dipaksa updateOrCreate agar Role selalu Akurat)
Route::get('/dev-login/{role}', function ($role) {
    $allowedRoles = ['Administrator', 'Editor', 'Author', 'Reviewer'];
    
    if (!in_array($role, $allowedRoles)) {
        abort(404, 'Role tidak ditemukan.');
    }

    // Memaksa pembaruan data user & role di database
    $user = User::updateOrCreate(
        ['email' => strtolower($role) . '@brida.com'],
        [
            'name' => 'Akun Tes ' . $role,
            'password' => bcrypt('password123'),
            'role' => $role,
        ]
    );

    // Login otomatis
    Auth::login($user);
    request()->session()->regenerate();

    return redirect()->route('dashboard');
});