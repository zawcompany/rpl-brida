<?php

namespace App\Services;

use App\Models\Manuscript;
use App\Models\Review;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * NotificationService — mengirim notifikasi alur kerja (dipanggil dari service domain)
 * dan mengelola daftar notifikasi pengguna. Semua query dibatasi ke notifikasi milik user
 * yang diberikan, sehingga tidak ada akses lintas pengguna (IDOR).
 */
class NotificationService
{
    private const PER_PAGE = 15;

    // -------------------------------------------------------------------------
    // Pengiriman (dipanggil service domain)
    // -------------------------------------------------------------------------

    public function manuscriptSubmitted(Manuscript $manuscript): void
    {
        $this->send(
            $this->usersWithRole('Editor'),
            'manuscript.submitted',
            'Naskah baru diajukan',
            "\"{$manuscript->title}\" menunggu pemeriksaan administrasi.",
            route('editor.manuscripts.new', absolute: false)
        );
    }

    public function reviewerAssigned(Review $review): void
    {
        $this->send(
            $review->reviewer_id,
            'review.assigned',
            'Penugasan review baru',
            "Anda ditugaskan meninjau \"{$review->manuscript->title}\".",
            route('reviewer.manuscripts.index', absolute: false)
        );
    }

    public function reviewCompleted(Review $review, bool $updated = false): void
    {
        $this->send(
            $this->usersWithRole('Editor'),
            'review.completed',
            $updated ? 'Review diperbarui' : 'Review selesai',
            $updated
                ? "Reviewer memperbarui hasil review \"{$review->manuscript->title}\"."
                : "Review \"{$review->manuscript->title}\" selesai dan menunggu keputusan editorial.",
            route('editor.decisions.index', absolute: false)
        );
    }

    /** Reviewer menolak penugasan: editor perlu menugaskan reviewer lain. */
    public function reviewDeclined(Review $review): void
    {
        $this->send(
            $this->usersWithRole('Editor'),
            'review.declined',
            'Penugasan ditolak reviewer',
            "{$review->reviewer->name} menolak meninjau \"{$review->manuscript->title}\". Silakan tugaskan reviewer lain.",
            route('editor.reviews.index', absolute: false)
        );
    }

    /** Keputusan administrasi (Naskah Baru): diterima untuk direview atau ditolak. */
    public function initialDecision(Manuscript $manuscript): void
    {
        $accepted = $manuscript->status !== 'ditolak';

        $this->send(
            $manuscript->author_id,
            $accepted ? 'manuscript.accepted' : 'manuscript.rejected',
            $accepted ? 'Naskah diterima untuk direview' : 'Naskah ditolak',
            $accepted
                ? "\"{$manuscript->title}\" lolos pemeriksaan administrasi dan masuk tahap review."
                : "\"{$manuscript->title}\" ditolak pada pemeriksaan administrasi.",
            route('author.manuscripts.index', absolute: false)
        );
    }

    /** Keputusan editorial akhir (disetujui / revisi / ditolak). */
    public function editorialDecision(Manuscript $manuscript): void
    {
        [$type, $title, $route] = match ($manuscript->status) {
            'revisi'    => ['manuscript.revision', 'Naskah perlu revisi', 'author.revisions.index'],
            'ditolak'   => ['manuscript.rejected', 'Naskah ditolak', 'author.manuscripts.index'],
            default     => ['manuscript.accepted', 'Naskah disetujui', 'author.manuscripts.index'],
        };

        $this->send(
            $manuscript->author_id,
            $type,
            $title,
            "Keputusan editor untuk \"{$manuscript->title}\": {$manuscript->status_label}.",
            route($route, absolute: false)
        );
    }

    public function revisionSubmitted(Manuscript $manuscript): void
    {
        $this->send(
            $this->usersWithRole('Editor'),
            'revision.submitted',
            'Revisi dari author',
            "Author mengunggah revisi \"{$manuscript->title}\".",
            route('editor.decisions.index', absolute: false)
        );
    }

    public function published(Manuscript $manuscript): void
    {
        $this->send(
            $manuscript->author_id,
            'manuscript.published',
            'Naskah diterbitkan',
            "Selamat, \"{$manuscript->title}\" telah diterbitkan.",
            route('author.manuscripts.index', absolute: false)
        );
    }

    // -------------------------------------------------------------------------
    // Daftar notifikasi pengguna
    // -------------------------------------------------------------------------

    public function paginateFor(User $user, bool $unreadOnly = false): LengthAwarePaginator
    {
        $query = $unreadOnly ? $user->unreadNotifications() : $user->notifications();

        return $query->latest()->paginate(self::PER_PAGE)->withQueryString();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException bila bukan milik user */
    public function findFor(User $user, string $id): DatabaseNotification
    {
        return $user->notifications()->findOrFail($id);
    }

    public function markRead(User $user, string $id): DatabaseNotification
    {
        $notification = $this->findFor($user, $id);
        $notification->markAsRead();

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /** URL tujuan yang aman: hanya path internal ("/..."), bukan "//host" atau skema lain. */
    public function safeTarget(DatabaseNotification $notification): string
    {
        $url = (string) ($notification->data['url'] ?? '');

        return Str::startsWith($url, '/') && ! Str::startsWith($url, '//') ? $url : route('notifications.index', absolute: false);
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /** @param User|int|iterable<User> $to */
    private function send(User|int|iterable $to, string $type, string $title, string $message, ?string $url): void
    {
        $recipients = match (true) {
            $to instanceof User => collect([$to]),
            is_int($to)         => User::whereKey($to)->get(),
            default             => collect($to),
        };

        $recipients->each(fn (User $u) => $u->notify(new WorkflowNotification($type, $title, $message, $url)));
    }

    private function usersWithRole(string $role): Collection
    {
        return User::where('role', $role)->where('is_active', true)->get();
    }
}
