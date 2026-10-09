<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pengingat dari editor kepada reviewer agar segera merespon / menyelesaikan review.
 */
class ReviewReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Review $review)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->review->manuscript->title;
        $due   = $this->review->due_at?->format('d M Y');

        $mail = (new MailMessage)
            ->subject('Pengingat Review Naskah — SIMPIL BRIDA')
            ->greeting("Yth. {$notifiable->name},")
            ->line("Mohon segera menindaklanjuti review untuk naskah \"{$title}\".");

        if ($due) {
            $mail->line("Tenggat waktu review: {$due}.");
        }

        return $mail->action('Buka Dashboard', url('/dashboard'))
            ->line('Terima kasih atas kontribusi Anda.');
    }
}
