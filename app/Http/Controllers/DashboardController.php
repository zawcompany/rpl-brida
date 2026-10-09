<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/**
 * Titik masuk /dashboard: meneruskan ke dashboard sesuai role.
 * Berupa controller (bukan closure) agar `php artisan route:cache` dapat berjalan.
 */
class DashboardController extends Controller
{
    /** role => controller yang memiliki method dashboard() */
    private const HANDLERS = [
        'Administrator' => AdminController::class,
        'Editor'        => EditorController::class,
        'Author'        => AuthorController::class,
    ];

    public function __invoke(Request $request): View
    {
        $role = $request->user()->role;

        if (isset(self::HANDLERS[$role])) {
            return app()->call([app(self::HANDLERS[$role]), 'dashboard']);
        }

        // Reviewer (modul belum dibangun) dan role tak dikenal
        return view($role === 'Reviewer' ? 'roles.reviewer.dashboard' : 'roles.author.dashboard');
    }
}
