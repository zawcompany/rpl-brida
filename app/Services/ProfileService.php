<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Journal;
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
    ) {}

    // -------------------------------------------------------------------------
    // Tampilan profil per role
    // -------------------------------------------------------------------------

    /** @return array<int, array{label: string, value: int|string}> kartu ringkasan sesuai role */
    public function summaryFor(User $user): array
    {
        return match ($user->role) {
            'Author' => $this->fromWidgets($this->authors->getDashboardStats($user)),
            'Editor' => $this->fromWidgets($this->editors->getDashboardStats()),
            'Reviewer' => [
                ['label' => 'Review Aktif',   'value' => $user->activeReviewCount()],
                ['label' => 'Review Selesai', 'value' => $user->reviews()->where('status', 'selesai')->count()],
                ['label' => 'Batas Beban',    'value' => User::MAX_ACTIVE_REVIEWS],
            ],
            'Administrator' => $this->adminSummary(),
            default => [],
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
     * @param  array{name: string, email: string, phone?: ?string, institution?: ?string, research_field_ids?: array}  $data
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

    // -------------------------------------------------------------------------
    // Hak subjek data pribadi (UU 27/2022 Pasal 7, Pasal 21 ayat (2), Pasal 32)
    // -------------------------------------------------------------------------

    /**
     * Riwayat kegiatan pengguna sendiri dari jejak audit.
     *
     * @return Collection<int, ActivityLog>
     */
    public function activityOf(User $user, int $limit = 20): Collection
    {
        return ActivityLog::where('user_id', $user->id)->latest('created_at')->latest('id')->limit($limit)->get();
    }

    /**
     * Salinan data pribadi dalam format terbaca mesin (JSON). Hanya data milik pengguna sendiri;
     * identitas reviewer pada naskahnya tidak disertakan (blind review).
     *
     * @return array<string, mixed>
     */
    public function exportPersonalData(User $user): array
    {
        $user->load(['journals', 'researchFields']);

        ActivityLog::record('privacy.export', "{$user->name} mengunduh salinan data pribadinya", $user->id);

        return [
            'dibuat_pada' => now()->toIso8601String(),
            'akun' => [
                'nama' => $user->name,
                'email' => $user->email,
                'institusi' => $user->institution,
                'telepon' => $user->phone,
                'peran' => $user->role,
                'email_terverifikasi' => $user->email_verified_at?->toIso8601String(),
                'terdaftar_pada' => $user->created_at?->toIso8601String(),
                'persetujuan_privasi' => $user->privacy_consented_at?->toIso8601String(),
                'versi_kebijakan' => $user->consent_version,
            ],
            'bidang_keahlian' => $user->researchFields->pluck('name')->all(),
            'keanggotaan_jurnal' => $user->journals->map(fn (Journal $j) => ['jurnal' => $j->name, 'peran' => $j->pivot->role])->all(),
            'naskah' => $user->manuscripts()->get()->map(fn ($m) => [
                'judul' => $m->title,
                'status' => $m->status_label,
                'diajukan_pada' => $m->submitted_at?->toIso8601String(),
                'penulis_pendamping' => $m->co_authors,
                'konflik_kepentingan' => $m->conflict_of_interest,
            ])->all(),
            'review_yang_dikerjakan' => $user->reviews()->with('manuscript:id,title')->get()->map(fn ($r) => [
                'naskah' => $r->manuscript?->title,
                'status' => $r->status,
                'rekomendasi' => $r->recommendation,
                'selesai_pada' => $r->completed_at?->toIso8601String(),
            ])->all(),
            'riwayat_kegiatan' => ActivityLog::where('user_id', $user->id)->latest('created_at')->get()
                ->map(fn (ActivityLog $log) => ['waktu' => $log->created_at->toIso8601String(), 'kegiatan' => $log->description, 'ip' => $log->ip_address])
                ->all(),
        ];
    }

    /** Persetujuan atas kebijakan versi terbaru (akun lama, akun buatan admin, atau kebijakan yang berubah). */
    public function acceptPolicy(User $user): void
    {
        $user->forceFill(['privacy_consented_at' => now(), 'consent_version' => config('simpil.legal.version')])->save();

        ActivityLog::record('privacy.consent', "{$user->name} menyetujui kebijakan privasi versi ".config('simpil.legal.version'), $user->id);
    }
}
