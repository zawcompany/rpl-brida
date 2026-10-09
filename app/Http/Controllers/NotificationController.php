<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Notifikasi (semua role). Semua operasi dibatasi ke notifikasi milik user yang login:
 * id milik orang lain menghasilkan 404.
 */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): View
    {
        $unreadOnly = $request->query('filter') === 'unread';
        $user = $request->user();

        return view('notifications.index', [
            'rows'        => $this->notifications->paginateFor($user, $unreadOnly),
            'unreadCount' => $this->notifications->unreadCount($user),
            'unreadOnly'  => $unreadOnly,
        ]);
    }

    /** Tandai dibaca lalu menuju halaman terkait. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $this->notifications->markRead($request->user(), $id);

        return redirect($this->notifications->safeTarget($notification));
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $this->notifications->markRead($request->user(), $id);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
