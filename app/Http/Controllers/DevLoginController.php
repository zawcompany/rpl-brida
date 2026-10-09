<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pintasan login HANYA untuk lingkungan lokal. Rutenya tidak didaftarkan di luar APP_ENV=local
 * dan di sini ada pengaman kedua (defense in depth).
 */
class DevLoginController extends Controller
{
    private const ROLES = ['Administrator', 'Editor', 'Author', 'Reviewer'];

    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless(in_array($role, self::ROLES, true), 404);

        $user = User::updateOrCreate(
            ['email' => strtolower($role) . '@brida.com'],
            ['name' => 'Akun Tes ' . $role, 'password' => 'password123', 'role' => $role]
        );

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
