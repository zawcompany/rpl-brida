<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang di-suspend admin langsung dikeluarkan pada request berikutnya,
 * termasuk sesi yang sedang berjalan.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda ditangguhkan. Hubungi administrator.'], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda ditangguhkan. Hubungi administrator.']);
        }

        return $next($request);
    }
}
