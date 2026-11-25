<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna yang belum menyetujui kebijakan privasi versi terbaru diarahkan ke halaman persetujuan:
 * akun buatan admin, akun lama, atau saat kebijakan berubah (UU 27/2022 Pasal 21 ayat (2) dan Pasal 22).
 */
class EnsurePolicyConsent
{
    /** Rute yang tetap terbuka agar pengguna bisa membaca kebijakan, menyetujui, atau keluar. */
    private const ALLOWED_ROUTES = [
        'consent.show', 'consent.accept', 'logout',
        'legal.privacy', 'legal.terms', 'legal.ethics',
        'verification.verify', 'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! config('simpil.legal.require_consent')
            || ! $user
            || $user->consent_version === config('simpil.legal.version')
            || $request->routeIs(...self::ALLOWED_ROUTES)
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Setujui Kebijakan Privasi versi terbaru terlebih dahulu.'], 409);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('consent.show');
    }
}
