<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureJournalAccess;
use App\Http\Middleware\EnsurePolicyConsent;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Responses\SessionExpiredResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'journal.access' => EnsureJournalAccess::class,
            'no-back' => PreventBackHistory::class,
        ]);
        $middleware->appendToGroup('web', EnsureAccountActive::class);
        $middleware->appendToGroup('web', EnsurePolicyConsent::class);
        // Halaman HTML tidak disimpan di cache browser: tombol Back memuat ulang dari server, sehingga header
        // (Masuk/Daftar vs Dashboard) selalu sesuai status login, dan halaman dalam tidak tampil setelah keluar.
        $middleware->appendToGroup('web', PreventBackHistory::class);

        // Pengguna yang sudah masuk lalu membuka Masuk/Daftar diarahkan ke dashboard dengan penjelasan.
        $middleware->redirectUsersTo(function (Request $request): string {
            if ($request->routeIs('register', 'login') && $request->hasSession()) {
                $request->session()->flash(
                    'auth_notice',
                    'Anda sudah masuk sebagai '.$request->user()?->name.'. Keluar terlebih dahulu bila ingin masuk atau mendaftar dengan akun lain.'
                );
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419: token CSRF/sesi kedaluwarsa (mis. tab lama dibuka kembali) -> login ulang yang mulus
        // (Laravel mengubah TokenMismatchException menjadi HttpException 419 sebelum callback ini dipanggil.)
        $exceptions->render(function (HttpException $e, Request $request) {
            return $e->getStatusCode() === 419 ? SessionExpiredResponse::make($request, 419) : null;
        });

        // 401 pada request AJAX -> JSON berisi tujuan redirect (halaman biasa tetap memakai redirect bawaan)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return $request->expectsJson() ? SessionExpiredResponse::make($request, 401) : null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
