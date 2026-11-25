<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /** Redirect map berdasarkan role — satu titik konfigurasi, DRY. */
    private const ROLE_VIEWS = [
        'Administrator' => 'roles.admin.dashboard',
        'Editor' => 'roles.editor.dashboard',
        'Reviewer' => 'roles.reviewer.dashboard',
        'Author' => 'roles.author.dashboard',
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
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            ActivityLog::record('auth.login_failed', 'Percobaan masuk gagal untuk akun '.$this->maskEmail($credentials['email']), null);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        ActivityLog::record('auth.login', Auth::user()->name.' masuk ke sistem');

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Anda harus menyetujui Kebijakan Privasi dan Syarat Penggunaan untuk mendaftar.',
        ]);

        // Registrasi publik selalu menghasilkan role Author
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // dicasting ke 'hashed' di model
            'role' => 'Author',
            // Bukti persetujuan pemrosesan data pribadi (UU PDP): waktu + versi kebijakan.
            'privacy_consented_at' => now(),
            'consent_version' => config('simpil.legal.version'),
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
        if ($user = $request->user()) {
            ActivityLog::record('auth.logout', "{$user->name} keluar dari sistem", $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // -------------------------------------------------------------------------
    // Helper — logika redirect tunggal berbasis role (KISS & DRY)
    // -------------------------------------------------------------------------

    /** Surel disamarkan di jejak audit, mis. "pe***@contoh.go.id". */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2).'***'.($domain !== '' ? "@{$domain}" : '');
    }

    private function redirectByRole(string $role)
    {
        $view = self::ROLE_VIEWS[$role] ?? self::ROLE_VIEWS['Author'];

        return redirect()->route('dashboard');
    }
}
