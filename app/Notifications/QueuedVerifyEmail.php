<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email verifikasi lewat antrean: request pengguna tidak menunggu SES/SMTP.
 * Subclass VerifyEmail agar tautan bertanda tangan dan isi email tetap bawaan Laravel.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
