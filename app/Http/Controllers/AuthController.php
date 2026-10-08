<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /** Redirect map berdasarkan role — satu titik konfigurasi, DRY. */
    private const ROLE_VIEWS = [
        'Administrator' => 'roles.admin.dashboard',
        'Editor'        => 'roles.editor.dashboard',
        'Reviewer'      => 'roles.reviewer.dashboard',
        'Author'        => 'roles.author.dashboard',
    ];

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        return $this->redirectByRole(Auth::user()->role);
    }

    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Registrasi publik selalu menghasilkan role Author
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // dicasting ke 'hashed' di model
            'role'     => 'Author',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // -------------------------------------------------------------------------
    // Helper — logika redirect tunggal berbasis role (KISS & DRY)
    // -------------------------------------------------------------------------

    private function redirectByRole(string $role)
    {
        $view = self::ROLE_VIEWS[$role] ?? self::ROLE_VIEWS['Author'];

        return redirect()->route('dashboard');
    }
}
