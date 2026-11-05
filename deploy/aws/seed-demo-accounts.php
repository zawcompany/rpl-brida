<?php

/**
 * Membuat akun demo di server (dijalankan di dalam container app, bukan lewat web).
 * Tidak berisi rahasia: daftar akun dan password dibaca dari STDIN sebagai JSON:
 *   echo '[{"name":"...","email":"...","role":"Author","password":"..."}]' | php /tmp/seed-demo-accounts.php
 * Setiap akun ditandai terverifikasi dan (kecuali Administrator) menjadi anggota jurnal bawaan.
 */

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Journal;
use App\Models\User;

$accounts = json_decode(stream_get_contents(STDIN), true);

if (! is_array($accounts) || $accounts === []) {
    fwrite(STDERR, "Masukan JSON kosong atau tidak valid.\n");
    exit(1);
}

$journal = Journal::default();

foreach ($accounts as $a) {
    $user = User::updateOrCreate(
        ['email' => $a['email']],
        ['name' => $a['name'], 'role' => $a['role'], 'password' => $a['password']]
    );
    $user->forceFill(['email_verified_at' => now()])->save();

    if ($journal && in_array($a['role'], Journal::ROLES, true)) {
        $user->joinJournal($journal, $a['role']);
    }

    echo "OK {$a['role']} {$a['email']}\n";
}
