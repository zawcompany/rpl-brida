<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Ambil filter tabel dari query string, hanya yang berupa teks/angka.
     * URL buatan seperti ?status[]=x atau ?search[]=a dibuang (diperlakukan seperti tanpa filter)
     * agar tidak memicu galat 500 di layanan yang mengharapkan string.
     */
    protected function filters(Request $request, array $keys): array
    {
        return array_filter($request->only($keys), fn ($value) => is_scalar($value));
    }
}
