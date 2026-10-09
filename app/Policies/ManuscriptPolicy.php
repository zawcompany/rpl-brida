<?php

namespace App\Policies;

use App\Models\Manuscript;
use App\Models\User;

/**
 * Siapa yang boleh membuka berkas sebuah naskah (SRS NF-04: direktori terproteksi + RBAC).
 *  - Editor            : semua naskah
 *  - Author            : naskahnya sendiri
 *  - Reviewer          : naskah dengan penugasan aktifnya (bukan yang ditolak/digantikan)
 *  - Administrator/lain: tidak ada akses ke isi naskah
 */
class ManuscriptPolicy
{
    public function viewFile(User $user, Manuscript $manuscript): bool
    {
        return match ($user->role) {
            'Editor'   => true,
            'Author'   => $manuscript->author_id === $user->id,
            'Reviewer' => $manuscript->reviews()->where('reviewer_id', $user->id)
                ->whereNull('superseded_at')->where('status', '!=', 'ditolak_reviewer')->exists(),
            default    => false,
        };
    }
}
