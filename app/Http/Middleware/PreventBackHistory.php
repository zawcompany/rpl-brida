<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Melarang browser menyimpan halaman HTML (cache/bfcache), dipasang di grup `web`. Tombol Back memuat ulang dari
 * server, sehingga: halaman tamu (login, daftar) mengalihkan pengguna yang sudah masuk ke dashboard, header halaman
 * publik menampilkan tombol sesuai status login, dan halaman dalam tidak terlihat lagi setelah keluar.
 * Berkas (PDF, XML, JSON, gambar) tidak disentuh agar header cache-nya sendiri tetap berlaku.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content-Type halaman Blade baru diisi saat respons dikirim, jadi header kosong dianggap HTML.
        $type = $response->headers->get('Content-Type');

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse || ($type && ! str_starts_with($type, 'text/html'))) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
