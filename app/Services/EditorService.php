<?php

namespace App\Services;

use App\Models\Manuscript;
use App\Models\Review;
use App\Models\ResearchField;
use App\Models\User;
use App\Notifications\ReviewReminderNotification;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * EditorService — seluruh logika bisnis alur kerja editor:
 * widget dashboard, daftar naskah, rekomendasi reviewer, dan transisi status naskah.
 * Controller hanya memanggil service ini. Pelanggaran aturan alur dilempar sebagai DomainException.
 */
class EditorService
{
    public function __construct(private readonly NotificationService $notifier)
    {
    }

    /** Durasi default review (hari) sejak penugasan. */
    public const REVIEW_DURATION_DAYS = 14;

    /** Jeda minimum antar pengingat ke reviewer yang sama (jam). */
    public const REMINDER_COOLDOWN_HOURS = 24;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    /**
     * 4 widget statistik dashboard: count + perubahan persen vs bulan lalu.
     */
    public function getDashboardStats(): array
    {
        $lastMonth = Carbon::now()->subMonth();

        $widgets = [
            ['label' => 'Naskah Baru',         'icon' => 'inbox', 'color' => 'blue',   'statuses' => Manuscript::NEW_STATUSES],
            ['label' => 'Sedang Ditinjau',     'icon' => 'eye',   'color' => 'purple', 'statuses' => ['ditinjau']],
            ['label' => 'Menunggu Keputusan',  'icon' => 'clock', 'color' => 'yellow', 'statuses' => ['menunggu_keputusan']],
            ['label' => 'Menunggu Publikasi',  'icon' => 'check', 'color' => 'green',  'statuses' => ['disetujui']],
        ];

        return collect($widgets)->map(function (array $widget) use ($lastMonth): array {
            $current = Manuscript::whereIn('status', $widget['statuses'])->count();

            $previous = Manuscript::whereIn('status', $widget['statuses'])
                ->whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();

            $change = $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : ($current > 0 ? 100.0 : 0.0);

            return [
                'label'            => $widget['label'],
                'icon'             => $widget['icon'],
                'color'            => $widget['color'],
                'count'            => $current,
                'change_percent'   => abs($change),
                'change_direction' => $change >= 0 ? 'up' : 'down',
            ];
        })->all();
    }

    /** 5 naskah terbaru untuk tabel aktivitas dashboard. */
    public function getRecentActivity(): Collection
    {
        return Manuscript::with(['author', 'researchField'])->latest('updated_at')->limit(5)->get();
    }

    // -------------------------------------------------------------------------
    // Daftar naskah (Naskah Baru / Peninjauan / Keputusan)
    // -------------------------------------------------------------------------

    /** @param array{search?: string, date?: string, per_page?: int} $filters */
    public function getNewManuscripts(array $filters = []): LengthAwarePaginator
    {
        return $this->paginateManuscripts(Manuscript::NEW_STATUSES, $filters, ['author', 'researchField']);
    }

    /** @param array{search?: string, date?: string, per_page?: int} $filters */
    public function getUnderReview(array $filters = []): LengthAwarePaginator
    {
        return $this->paginateManuscripts(
            Manuscript::REVIEW_STATUSES,
            $filters,
            ['author', 'researchField', 'currentReview.reviewer']
        );
    }

    /** @param array{search?: string, date?: string, per_page?: int} $filters */
    public function getAwaitingDecision(array $filters = []): LengthAwarePaginator
    {
        return $this->paginateManuscripts(
            ['menunggu_keputusan'],
            $filters,
            ['author', 'researchField', 'currentReview']
        );
    }

    private function paginateManuscripts(array $statuses, array $filters, array $with): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return Manuscript::with($with)
            ->whereIn('status', $statuses)
            ->when(filled($search), fn (Builder $q) => $q->where(
                fn (Builder $q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('author', fn (Builder $a) => $a->where('name', 'like', "%{$search}%"))
            ))
            ->when(filled($filters['date'] ?? null), fn (Builder $q) => $q->whereDate('created_at', $filters['date']))
            ->latest()
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    // -------------------------------------------------------------------------
    // Rekomendasi Reviewer — TEPAT 1 reviewer terbaik
    // -------------------------------------------------------------------------

    /**
     * Reviewer terbaik untuk sebuah naskah:
     *  1. hanya reviewer dengan bidang keahlian yang sama dengan naskah;
     *  2. dari kandidat tersebut, pilih yang beban review aktifnya paling sedikit
     *     (seri diputus berdasarkan nama agar hasil deterministik).
     *
     * @param int|null $excludeReviewerId reviewer yang dikecualikan (mis. saat penggantian)
     * @return array{id: int, name: string, email: string, active_load: int}|null null bila tak ada yang cocok
     */
    public function getBestReviewer(Manuscript $manuscript, ?int $excludeReviewerId = null): ?array
    {
        if (! $manuscript->research_field_id) {
            return null;
        }

        $reviewer = $this->reviewersWithLoad()
            ->whereHas('researchFields', fn (Builder $q) => $q->whereKey($manuscript->research_field_id))
            ->when($excludeReviewerId, fn (Builder $q) => $q->whereKeyNot($excludeReviewerId))
            ->orderBy('active_load')
            ->orderBy('name')
            ->first();

        return $reviewer ? [
            'id'          => $reviewer->id,
            'name'        => $reviewer->name,
            'email'       => $reviewer->email,
            'active_load' => (int) $reviewer->active_load,
        ] : null;
    }

    /**
     * Semua reviewer (untuk dropdown) beserta beban aktifnya.
     *
     * @return Collection<int, array{id: int, name: string, active_load: int}>
     */
    public function getAssignableReviewers(?int $excludeReviewerId = null): Collection
    {
        return $this->reviewersWithLoad()
            ->when($excludeReviewerId, fn (Builder $q) => $q->whereKeyNot($excludeReviewerId))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'active_load' => (int) $u->active_load,
            ]);
    }

    private function reviewersWithLoad(): Builder
    {
        return User::reviewers()->withCount([
            'reviews as active_load' => fn (Builder $q) => $q->active(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Naskah Baru: keputusan administrasi
    // -------------------------------------------------------------------------

    /**
     * 'diterima' => naskah lanjut ke tahap review (buat penugasan reviewer); 'ditolak' => naskah ditolak.
     * Pemetaan keputusan -> status ada di Manuscript::INITIAL_DECISION_STATUS.
     *
     * @throws DomainException bila naskah sudah diproses
     */
    public function processDecision(
        Manuscript $manuscript,
        string $decision,
        ?int $reviewerId,
        ?string $editorNote,
        int $editorId,
        ?string $dueAt = null
    ): Manuscript {
        $this->ensureStatus($manuscript, Manuscript::NEW_STATUSES, 'Naskah ini sudah diproses sebelumnya.');

        return DB::transaction(function () use ($manuscript, $decision, $reviewerId, $editorNote, $editorId, $dueAt) {
            $manuscript->editor_note = $editorNote;
            $manuscript->status      = Manuscript::INITIAL_DECISION_STATUS[$decision];

            if ($manuscript->status === 'ditolak') {
                $manuscript->decided_at = now();
                $manuscript->save();
                $this->notifier->initialDecision($manuscript);

                return $manuscript;
            }

            $manuscript->save();

            $this->assignReview($manuscript, $reviewerId, $editorId, $dueAt);
            $this->notifier->initialDecision($manuscript);

            return $manuscript->refresh();
        });
    }

    /** Tenggat default (hari ini + 14 hari) dalam format Y-m-d untuk input tanggal. */
    public function defaultDueDate(): string
    {
        return now()->addDays(self::REVIEW_DURATION_DAYS)->toDateString();
    }

    private function assignReview(Manuscript $manuscript, int $reviewerId, int $editorId, ?string $dueAt = null): Review
    {
        $review = Review::create([
            'manuscript_id' => $manuscript->id,
            'reviewer_id'   => $reviewerId,
            'assigned_by'   => $editorId,
            'status'        => 'ditugaskan',
            'due_at'        => filled($dueAt) ? Carbon::parse($dueAt)->endOfDay() : now()->addDays(self::REVIEW_DURATION_DAYS),
        ])->setRelation('manuscript', $manuscript);

        $this->notifier->reviewerAssigned($review);

        return $review;
    }

    // -------------------------------------------------------------------------
    // Peninjauan: pengingat & ganti reviewer
    // -------------------------------------------------------------------------

    /**
     * @throws DomainException bila review sudah selesai/ditolak atau pengingat terlalu sering
     */
    public function sendReminder(Manuscript $manuscript): Review
    {
        $review = $this->currentReviewOrFail($manuscript);

        if (! $review->isActive()) {
            throw new DomainException('Pengingat hanya dapat dikirim untuk review yang masih berjalan.');
        }

        if ($review->reminded_at && $review->reminded_at->gt(now()->subHours(self::REMINDER_COOLDOWN_HOURS))) {
            throw new DomainException('Pengingat sudah dikirim dalam ' . self::REMINDER_COOLDOWN_HOURS . ' jam terakhir.');
        }

        $review->load(['reviewer', 'manuscript']);
        $review->reviewer->notify(new ReviewReminderNotification($review));

        $review->forceFill([
            'reminded_at'    => now(),
            'reminder_count' => $review->reminder_count + 1,
        ])->save();

        return $review;
    }

    /**
     * Ganti reviewer: penugasan lama ditandai superseded, penugasan baru dibuat.
     *
     * @throws DomainException bila reviewer belum boleh diganti
     */
    public function replaceReviewer(Manuscript $manuscript, int $newReviewerId, ?string $editorNote, int $editorId, ?string $dueAt = null): Review
    {
        $this->ensureStatus($manuscript, ['ditinjau'], 'Reviewer hanya dapat diganti selama naskah berstatus Sedang Ditinjau.');

        $current = $this->currentReviewOrFail($manuscript);

        if (! $current->isReplaceable()) {
            throw new DomainException('Reviewer hanya dapat diganti jika menolak penugasan atau melewati tenggat waktu.');
        }

        if ($current->reviewer_id === $newReviewerId) {
            throw new DomainException('Pilih reviewer yang berbeda dari reviewer sebelumnya.');
        }

        return DB::transaction(function () use ($manuscript, $current, $newReviewerId, $editorNote, $editorId, $dueAt) {
            $current->update(['superseded_at' => now()]);

            if (filled($editorNote)) {
                $manuscript->update(['editor_note' => $editorNote]);
            }

            return $this->assignReview($manuscript, $newReviewerId, $editorId, $dueAt);
        });
    }

    private function currentReviewOrFail(Manuscript $manuscript): Review
    {
        return $manuscript->currentReview()->first()
            ?? throw new DomainException('Naskah ini belum memiliki penugasan reviewer.');
    }

    // -------------------------------------------------------------------------
    // Keputusan Editorial
    // -------------------------------------------------------------------------

    /**
     * @param string $decision kunci dari Manuscript::DECISION_STATUS (diterima|revisi|ditolak)
     * @throws DomainException bila naskah tidak sedang menunggu keputusan
     */
    public function recordEditorialDecision(Manuscript $manuscript, string $decision, ?string $note): Manuscript
    {
        $this->ensureStatus($manuscript, ['menunggu_keputusan'], 'Naskah ini tidak sedang menunggu keputusan editorial.');

        $manuscript->update([
            'status'         => Manuscript::DECISION_STATUS[$decision],
            'editorial_note' => $note,
            'decided_at'     => now(),
        ]);

        $this->notifier->editorialDecision($manuscript);

        return $manuscript;
    }

    /**
     * Review ulang untuk naskah revisi dari author (revisi mayor): kembali ke tahap 'ditinjau'
     * dengan penugasan baru. Hasil review sebelumnya tetap tersimpan sebagai riwayat.
     *
     * @throws DomainException bila bukan naskah revisi yang menunggu keputusan
     */
    public function requestReReview(Manuscript $manuscript, int $reviewerId, ?string $editorNote, int $editorId, ?string $dueAt = null): Review
    {
        $this->ensureStatus($manuscript, ['menunggu_keputusan'], 'Naskah ini tidak sedang menunggu keputusan editorial.');

        if (! $manuscript->isRevision()) {
            throw new DomainException('Review ulang hanya untuk naskah revisi yang dikirim author.');
        }

        return DB::transaction(function () use ($manuscript, $reviewerId, $editorNote, $editorId, $dueAt) {
            $manuscript->update(['status' => 'ditinjau', 'editor_note' => $editorNote ?: $manuscript->editor_note]);

            return $this->assignReview($manuscript, $reviewerId, $editorId, $dueAt);
        });
    }

    // -------------------------------------------------------------------------
    // Direktori Reviewer
    // -------------------------------------------------------------------------

    /** @param array{search?: string, field?: int|string, per_page?: int} $filters */
    public function getReviewerDirectory(array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return $this->reviewersWithLoad()
            ->with('researchFields')
            ->when(filled($search), fn (Builder $q) => $q->where(
                fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(filled($filters['field'] ?? null), fn (Builder $q) => $q->whereHas(
                'researchFields', fn (Builder $f) => $f->whereKey($filters['field'])
            ))
            ->orderBy('name')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    /** Profil reviewer + riwayat penugasan terbaru. */
    public function getReviewerProfile(User $reviewer): array
    {
        $reviewer->load('researchFields');

        $history = $reviewer->reviews()
            ->with('manuscript:id,title,status')
            ->latest()
            ->limit(20)
            ->get();

        $activeLoad = $reviewer->activeReviewCount();

        return [
            'reviewer'  => $reviewer,
            'active'    => $activeLoad,
            'completed' => $reviewer->reviews()->where('status', 'selesai')->count(),
            'is_busy'   => $activeLoad >= User::MAX_ACTIVE_REVIEWS,
            'history'   => $history,
        ];
    }

    public function getResearchFields(): Collection
    {
        return ResearchField::orderBy('name')->get(['id', 'name']);
    }

    // -------------------------------------------------------------------------
    // Util
    // -------------------------------------------------------------------------

    /** @throws DomainException */
    private function ensureStatus(Manuscript $manuscript, array $allowed, string $message): void
    {
        if (! in_array($manuscript->status, $allowed, true)) {
            throw new DomainException($message);
        }
    }
}
