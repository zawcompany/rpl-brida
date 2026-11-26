<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dibaca dari config (bukan env() langsung) agar tetap bekerja setelah `config:cache`.
        if ($proxies = config('simpil.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        if (config('simpil.force_https')) {
            URL::forceScheme('https');
        }

        $this->configureLoginRateLimit();
    }

    /**
     * Batas percobaan masuk: 5 kali/menit per akun+IP (menahan tebak password) dan 30 kali/menit per IP
     * (menahan serangan ke banyak akun). Dihitung terpisah dari batas rute lain, sehingga pengguna di satu
     * jaringan kantor/kampus tidak saling memblokir. Saat terlampaui, form login menampilkan pesan yang jelas.
     */
    private function configureLoginRateLimit(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $tooMany = fn (Request $request, array $headers): RedirectResponse => back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Terlalu banyak percobaan masuk. Tunggu 1 menit, lalu coba lagi.'])
                ->withHeaders($headers);

            return [
                Limit::perMinute(5)->by('login|'.Str::lower((string) $request->input('email')).'|'.$request->ip())->response($tooMany),
                Limit::perMinute(30)->by('login-ip|'.$request->ip())->response($tooMany),
            ];
        });
    }
}
