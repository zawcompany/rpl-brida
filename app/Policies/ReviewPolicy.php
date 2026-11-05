<?php

namespace App\Policies;

use App\Models\Journal;
use App\Models\Review;
use App\Models\User;

/**
 * Akses ke sebuah penugasan review:
 *  - respond : hanya reviewer pemilik penugasan (terima/tolak/kirim/ubah);
 *  - view    : reviewer pemilik, atau Editor (membaca hasil review);
 *  - viewFile: lampiran catatan review — reviewer pemilik atau Editor (tidak untuk Author: review anonim).
 */
class ReviewPolicy
{
    public function respond(User $user, Review $review): bool
    {
        return $user->role === 'Reviewer' && $review->reviewer_id === $user->id;
    }

    public function view(User $user, Review $review): bool
    {
        return $this->respond($user, $review) || $this->isEditorOfJournal($user, $review);
    }

    /** Editor hanya melihat review pada naskah jurnal tempat ia menjadi editor. */
    private function isEditorOfJournal(User $user, Review $review): bool
    {
        return $user->role === 'Editor'
            && in_array($review->manuscript?->journal_id, $user->journalIds(Journal::EDITOR), true);
    }

    public function viewFile(User $user, Review $review): bool
    {
        return $this->view($user, $review);
    }
}
