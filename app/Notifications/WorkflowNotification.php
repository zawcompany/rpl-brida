<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi alur kerja yang tampil di halaman Notifikasi (kanal database).
 * 'url' selalu path internal (diawali "/") — divalidasi ulang saat dibuka (anti open-redirect).
 */
class WorkflowNotification extends Notification
{
    /** tipe => [label, kelas badge] — satu sumber untuk tampilan. */
    public const TYPES = [
        'manuscript.submitted'  => ['Naskah Baru',         'bg-blue-100 text-blue-700'],
        'review.assigned'       => ['Penugasan Review',    'bg-purple-100 text-purple-700'],
        'review.reminder'       => ['Pengingat',           'bg-yellow-100 text-yellow-800'],
        'review.completed'      => ['Review Selesai',      'bg-green-100 text-green-700'],
        'review.declined'       => ['Review Ditolak',      'bg-red-100 text-red-700'],
        'manuscript.accepted'   => ['Naskah Diterima',     'bg-green-100 text-green-700'],
        'manuscript.rejected'   => ['Naskah Ditolak',      'bg-red-100 text-red-700'],
        'manuscript.revision'   => ['Perlu Revisi',        'bg-orange-100 text-orange-800'],
        'revision.submitted'    => ['Revisi Diterima',     'bg-blue-100 text-blue-700'],
        'manuscript.published'  => ['Naskah Terbit',       'bg-gray-200 text-gray-700'],
    ];

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $url = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => $this->type,
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
        ];
    }
}
