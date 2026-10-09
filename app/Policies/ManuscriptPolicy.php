<?php

namespace App\Policies;

use App\Models\Manuscript;
use App\Models\User;

/**
 * Siapa yang boleh membuka berkas sebuah naskah (SRS NF-04: direktori terproteksi + RBAC).
 *  - Editor            : semua naskah
 *  - Author            : naskahnya sendiri
 *  - Reviewer          : naskah yang pernah/sedang ditugaskan kepadanya
 *  - Administrator/lain: tidak ada akses ke isi naskah
 */
class ManuscriptPolicy
{
    public function viewFile(User $user, Manuscript $manuscript): bool
    {
        return match ($user->role) {
            'Editor'   => true,
            'Author'   => $manuscript->author_id === $user->id,
            'Reviewer' => $manuscript->reviews()->where('reviewer_id', $user->id)->exists(),
            default    => false,
        };
    }
}
