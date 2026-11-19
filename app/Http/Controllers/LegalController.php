<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Halaman legal statis: kebijakan privasi, syarat penggunaan, etika publikasi. */
class LegalController extends Controller
{
    private const PAGES = [
        'privacy' => ['title' => 'Kebijakan Privasi',   'view' => 'legal.privacy'],
        'terms'   => ['title' => 'Syarat Penggunaan',   'view' => 'legal.terms'],
        'ethics'  => ['title' => 'Etika Publikasi',     'view' => 'legal.ethics'],
    ];

    public function show(string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return view(self::PAGES[$page]['view'], [
            'pageTitle' => self::PAGES[$page]['title'],
            'version'   => config('simpil.legal.version'),
            'contact'   => config('simpil.legal.contact'),
        ]);
    }
}
