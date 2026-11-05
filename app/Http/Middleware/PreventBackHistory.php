<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melarang browser menyimpan halaman (cache/bfcache). Dipakai pada halaman tamu (login, daftar) agar tombol
 * Back memuat ulang dari server, sehingga middleware `guest` mengalihkan pengguna yang sudah masuk ke dashboard
 * alih-alih menampilkan form login lama.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
