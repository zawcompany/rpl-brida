<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk membatasi akses berdasarkan role pengguna.
 * Penggunaan di routes: middleware('role:Editor') atau middleware('role:Editor,Administrator')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Perbandingan case-insensitive: 'role:editor' cocok dengan role 'Editor' di database.
        $allowed = array_map('strtolower', $roles);

        if (! $user || ! in_array(strtolower((string) $user->role), $allowed, true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
