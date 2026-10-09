<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Respons seragam saat sesi/token CSRF kedaluwarsa (419) atau tidak terautentikasi (401):
 *  - halaman biasa : redirect ke /login dengan pesan jelas;
 *  - AJAX / fetch  : JSON {message, redirect} agar JS mengalihkan ke /login dengan mulus.
 * Pesan di-flash ke sesi pada kedua kasus sehingga halaman login selalu menampilkannya.
 */
class SessionExpiredResponse
{
    public const MESSAGE = 'Sesi Anda telah berakhir karena tidak aktif. Silakan masuk kembali.';

    public static function make(Request $request, int $status): JsonResponse|RedirectResponse
    {
        $login = route('login');
        $redirect = redirect($login);

        if ($request->hasSession()) {
            $redirect->withErrors(['email' => self::MESSAGE]); // meng-flash pesan ke sesi
        }

        return $request->expectsJson()
            ? response()->json(['message' => self::MESSAGE, 'redirect' => $login], $status)
            : $redirect;
    }
}
