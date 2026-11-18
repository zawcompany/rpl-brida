<?php

namespace App\Support;

/**
 * Konteks navigasi halaman jurnal & arsip: pengguna yang masuk dari Dashboard (tautan di dashboard membawa
 * ?dari=dashboard) melihat halaman itu DI DALAM layout dashboard (sidebar tetap ada). Tanpa penanda itu,
 * atau bagi tamu, halaman yang sama memakai layout publik. Menekan menu di header publik tidak membawa
 * penanda, sehingga konteksnya berakhir.
 */
class Nav
{
    public const KEY = 'dari';
    public const FROM_DASHBOARD = 'dashboard';

    /** Pengguna sedang menjelajah halaman jurnal/arsip dari dashboard-nya? (query GET atau field form POST) */
    public static function fromDashboard(): bool
    {
        return auth()->check() && request()->input(self::KEY) === self::FROM_DASHBOARD;
    }

    /** Parameter yang diteruskan ke tautan/redirect internal agar konteks tidak hilang antarhalaman. */
    public static function keep(): array
    {
        return self::fromDashboard() ? [self::KEY => self::FROM_DASHBOARD] : [];
    }

    /** Layout untuk halaman yang bisa dibuka dari publik maupun dari dashboard. */
    public static function layout(): string
    {
        return self::fromDashboard() ? 'layouts.app' : 'layouts.guest';
    }
}
