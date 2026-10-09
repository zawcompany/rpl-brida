<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Responses\SessionExpiredResponse;
use Illuminate\Auth\AuthenticationException;
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
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureAccountActive::class);
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
