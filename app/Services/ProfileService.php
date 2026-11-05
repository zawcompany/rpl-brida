<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Journal;
use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProfileService — profil pengguna yang sedang login (semua role).
 * Selalu bekerja pada user yang diberikan pemanggil ($request->user()), tidak pernah pada id dari input.
 */
class ProfileService
{
    /** Kolom profil yang boleh diubah sendiri oleh pengguna (whitelist anti mass-assignment). */
    private const EDITABLE = ['name', 'email', 'phone', 'institution'];

    public function __construct(
        private readonly AuthorService $authors,
        private readonly EditorService $editors,
        private readonly AdminService $admins,
        private readonly ResearchFieldService $fields,
    ) {
    }

    // -------------------------------------------------------------------------
    // Tampilan profil per role
    // -------------------------------------------------------------------------

    /** @return array<int, array{label: string, value: int|string}> kartu ringkasan sesuai role */
    public function summaryFor(User $user): array
    {
        return match ($user->role) {
            'Author'        => $this->fromWidgets($this->authors->getDashboardStats($user)),
            'Editor'        => $this->fromWidgets($this->editors->getDashboardStats()),
            'Reviewer'      => [
                ['label' => 'Review Aktif',   'value' => $user->activeReviewCount()],
                ['label' => 'Review Selesai', 'value' => $user->reviews()->where('status', 'selesai')->count()],
                ['label' => 'Batas Beban',    'value' => User::MAX_ACTIVE_REVIEWS],
            ],
            'Administrator' => $this->adminSummary(),
            default         => [],
        };
    }

    /** Bidang keahlian hanya relevan untuk Reviewer (dasar rekomendasi penugasan). */
    public function expertiseOptions(User $user): ?Collection
    {
        return $user->role === 'Reviewer' ? $this->fields->options() : null;
    }

    // -------------------------------------------------------------------------
    // Perubahan
    // -------------------------------------------------------------------------

    /**
     * Ubah data profil. Email baru membatalkan verifikasi dan mengirim ulang tautan verifikasi.
     *
     * @param array{name: string, email: string, phone?: ?string, institution?: ?string, research_field_ids?: array} $data
     */
    public function update(User $user, array $data): User
    {
        DB::transaction(function () use ($user, $data) {
            $user->fill(Arr::only($data, self::EDITABLE));

            $emailChanged = $user->isDirty('email');
            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($user->role === 'Reviewer' && array_key_exists('research_field_ids', $data)) {
                $user->researchFields()->sync($data['research_field_ids'] ?? []);
            }

            ActivityLog::record('profile.updated', "Profil {$user->name} diperbarui", $user->id);

            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }
        });

        return $user;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]); // di-hash Bcrypt oleh cast model

        ActivityLog::record('profile.password', "Password {$user->name} diubah", $user->id);
    }

    /** @return bool false bila email sudah terverifikasi (tidak ada yang dikirim) */
    public function resendVerification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }

    // -------------------------------------------------------------------------

    /** Ringkasan admin: pengguna, naskah, dan total jurnal (statistik dihitung sekali). */
    private function adminSummary(): array
    {
        $stats = $this->admins->getDashboardStats();

        return [
            ['label' => 'Total Pengguna', 'value' => $stats['total_users']],
            ['label' => 'Total Naskah',   'value' => $stats['total_manuscripts']],
            ['label' => 'Total Jurnal',   'value' => Journal::count()],
        ];
    }

    private function fromWidgets(array $widgets): array
    {
        return array_map(fn (array $w) => ['label' => $w['label'], 'value' => $w['count']], $widgets);
    }
}
