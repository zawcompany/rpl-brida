<?php

namespace App\Support;

/**
 * Konteks navigasi halaman publik: pengguna yang masuk dari Dashboard (tautan di dashboard membawa ?dari=dashboard)
 * mendapat tombol "Kembali ke Dashboard" selama ia menjelajah jurnal dan arsip. Menekan menu di header publik
 * (Beranda, Jurnal, Artikel, Arsip) tidak membawa penanda itu, sehingga konteksnya berakhir.
 */
class Nav
{
    public const KEY = 'dari';
    public const FROM_DASHBOARD = 'dashboard';

    /** Pengguna sedang menjelajah halaman publik dari dashboard-nya? */
    public static function fromDashboard(): bool
    {
        return auth()->check() && request()->query(self::KEY) === self::FROM_DASHBOARD;
    }

    /** Parameter yang diteruskan ke tautan internal agar konteks tidak hilang antarhalaman. */
    public static function keep(): array
    {
        return self::fromDashboard() ? [self::KEY => self::FROM_DASHBOARD] : [];
    }
}
