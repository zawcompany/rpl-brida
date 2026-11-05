<?php

namespace Tests;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setiap akun yang dibuat di tes otomatis menjadi anggota jurnal bawaan (migrasi membuatnya)
     * dengan peran yang sama seperti akunnya. Ini meniru data lama yang dimigrasikan ke satu jurnal,
     * sehingga tes alur kerja tidak perlu mengurus keanggotaan satu per satu.
     * Tes yang menguji keanggotaan sendiri mengubah properti ini menjadi false.
     */
    protected bool $autoJoinDefaultJournal = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->autoJoinDefaultJournal) {
            User::created(function (User $user): void {
                $journal = Journal::default();

                if ($journal && in_array($user->role, Journal::ROLES, true)) {
                    $user->joinJournal($journal, $user->role);
                }
            });
        }
    }
}
