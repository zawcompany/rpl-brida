<?php

use App\Services\JournalService;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('simpil:pengingat-akreditasi', function (JournalService $journals, NotificationService $notifier) {
    $count = $journals->remindExpiringAccreditations($notifier);
    $this->info("Pengingat akreditasi ulang dikirim untuk {$count} jurnal.");
})->purpose('Ingatkan admin dan editor bila masa berlaku akreditasi jurnal tinggal 6 bulan atau kurang');

Schedule::command('simpil:pengingat-akreditasi')->dailyAt('07:00');
