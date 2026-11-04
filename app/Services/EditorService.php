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
            ['author', 'researchField', 'currentReviews']
        );
    }

    /** @param array{search?: string, date?: string, per_page?: int} $filters */
    public function getAwaitingDecision(array $filters = []): LengthAwarePaginator
    {
        return $this->paginateManuscripts(
            ['menunggu_keputusan'],
            $filters,
            ['author', 'researchField', 'currentReviews']
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
    // Rekomendasi Reviewer — 3 reviewer terbaik (Load Balancing)
    // -------------------------------------------------------------------------

    /**
     * Rekomendasi 3 reviewer. Urutan: bidang keahlian cocok lebih dulu, lalu beban aktif
     * paling sedikit (Load Balancing), seri diputus berdasarkan nama agar deterministik.
     * Editor tetap bebas memilih siapa pun dari getAssignableReviewers().
     *
     * @param int[] $excludeReviewerIds reviewer yang dikecualikan (mis. saat penggantian)
     * @return Collection<int, array{id: int, name: string, email: string, active_load: int, field_match: bool}>
     */
    public function getRecommendedReviewers(Manuscript $manuscript, array $excludeReviewerIds = [], int $limit = Manuscript::REQUIRED_REVIEWERS): Collection
    {
        return $this->reviewersWithLoad($manuscript->research_field_id)
            ->when($excludeReviewerIds, fn (Builder $q) => $q->whereKeyNot($excludeReviewerIds))
            ->orderByDesc('field_match')
            ->orderBy('active_load')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'email'       => $u->email,
                'active_load' => (int) $u->active_load,
                'field_match' => (bool) $u->field_match,
            ]);
    }

    /**
     * Semua reviewer (untuk dropdown) beserta beban aktifnya, urut ASCENDING berdasarkan
     * beban kerja (jumlah review yang belum selesai) — prinsip Load Balancing.
     *
     * @param int[] $excludeReviewerIds
     * @return Collection<int, array{id: int, name: string, active_load: int}>
     */
    public function getAssignableReviewers(array $excludeReviewerIds = []): Collection
    {
        return $this->reviewersWithLoad()
            ->when($excludeReviewerIds, fn (Builder $q) => $q->whereKeyNot($excludeReviewerIds))
            ->orderBy('active_load')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'active_load' => (int) $u->active_load,
            ]);
    }

    /** Reviewer + `active_load` (review belum selesai) dan, bila bidang diberikan, `field_match`. */
    private function reviewersWithLoad(?int $researchFieldId = null): Builder
    {
        return User::reviewers()
            ->withCount(['reviews as active_load' => fn (Builder $q) => $q->active()])
            ->when($researchFieldId, fn (Builder $q) => $q->withExists([
                'researchFields as field_match' => fn (Builder $f) => $f->whereKey($researchFieldId),
            ]));
    }

    // -------------------------------------------------------------------------
    // Naskah Baru: keputusan administrasi
    // -------------------------------------------------------------------------

    /**
     * 'diterima' => naskah lanjut ke tahap review (buat penugasan untuk 3 reviewer); 'ditolak' => naskah ditolak.
     * Pemetaan keputusan -> status ada di Manuscript::INITIAL_DECISION_STATUS.
     *
     * @throws DomainException bila naskah sudah diproses
     */
    public function processDecision(
        Manuscript $manuscript,
        string $decision,
        array $reviewerIds,
        ?string $editorNote,
        int $editorId,
        ?string $dueAt = null
    ): Manuscript {
        $this->ensureStatus($manuscript, Manuscript::NEW_STATUSES, 'Naskah ini sudah diproses sebelumnya.');

        return DB::transaction(function () use ($manuscript, $decision, $reviewerIds, $editorNote, $editorId, $dueAt) {
            $manuscript->editor_note = $editorNote;
            $manuscript->status      = Manuscript::INITIAL_DECISION_STATUS[$decision];

            if ($manuscript->status === 'ditolak') {
                $manuscript->decided_at = now();
                $manuscript->save();
                $this->notifier->initialDecision($manuscript);

                return $manuscript;
            }

            $manuscript->save();

            $this->assignReviewers($manuscript, $reviewerIds, $editorId, $dueAt);
            $this->notifier->initialDecision($manuscript);

            return $manuscript->refresh();
        });
    }

    /** Tenggat default (hari ini + 14 hari) dalam format Y-m-d untuk input tanggal. */
    public function defaultDueDate(): string
    {
        return now()->addDays(self::REVIEW_DURATION_DAYS)->toDateString();
    }

    /**
     * Buat penugasan untuk tepat REQUIRED_REVIEWERS reviewer berbeda.
     *
     * @param int[] $reviewerIds
     * @throws DomainException
     */
    private function assignReviewers(Manuscript $manuscript, array $reviewerIds, int $editorId, ?string $dueAt = null): void
    {
        $reviewerIds = array_values(array_unique($reviewerIds));

        if (count($reviewerIds) !== Manuscript::REQUIRED_REVIEWERS) {
            throw new DomainException('Naskah harus direview oleh tepat ' . Manuscript::REQUIRED_REVIEWERS . ' reviewer yang berbeda.');
        }

        foreach ($reviewerIds as $reviewerId) {
            $this->assignReview($manuscript, (int) $reviewerId, $editorId, $dueAt);
        }
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
     * Kirim pengingat ke semua reviewer yang masih menunggak. Reviewer yang baru diingatkan
     * (dalam masa jeda) dilewati.
     *
     * @return int jumlah reviewer yang diingatkan
     * @throws DomainException bila tidak ada review berjalan atau semua masih dalam masa jeda
     */
    public function sendReminder(Manuscript $manuscript): int
    {
        $pending = $manuscript->currentReviews()->with(['reviewer', 'manuscript'])->get()
            ->filter(fn (Review $r) => $r->isActive());

        if ($pending->isEmpty()) {
            throw new DomainException('Pengingat hanya dapat dikirim untuk review yang masih berjalan.');
        }

        $due = $pending->reject(
            fn (Review $r) => $r->reminded_at && $r->reminded_at->gt(now()->subHours(self::REMINDER_COOLDOWN_HOURS))
        );

        if ($due->isEmpty()) {
            throw new DomainException('Pengingat sudah dikirim dalam ' . self::REMINDER_COOLDOWN_HOURS . ' jam terakhir.');
        }

        foreach ($due as $review) {
            $review->reviewer->notify(new ReviewReminderNotification($review));
            $review->forceFill(['reminded_at' => now(), 'reminder_count' => $review->reminder_count + 1])->save();
        }

        return $due->count();
    }

    /**
     * Ganti satu reviewer: penugasan lama ditandai superseded, penugasan baru dibuat.
     *
     * @throws DomainException bila reviewer belum boleh diganti
     */
    public function replaceReviewer(Manuscript $manuscript, int $reviewId, int $newReviewerId, ?string $editorNote, int $editorId, ?string $dueAt = null): Review
    {
        $this->ensureStatus($manuscript, ['ditinjau'], 'Reviewer hanya dapat diganti selama naskah berstatus Sedang Ditinjau.');

        $reviews = $manuscript->currentReviews()->get();
        $current = $reviews->firstWhere('id', $reviewId)
            ?? throw new DomainException('Penugasan reviewer tidak ditemukan pada naskah ini.');

        if (! $current->isReplaceable()) {
            throw new DomainException('Reviewer hanya dapat diganti jika menolak penugasan atau melewati tenggat waktu.');
        }

        // Satu reviewer tidak boleh memegang dua slot pada naskah yang sama.
        if ($reviews->contains('reviewer_id', $newReviewerId)) {
            throw new DomainException('Reviewer tersebut sudah ditugaskan pada naskah ini. Pilih reviewer lain.');
        }

        return DB::transaction(function () use ($manuscript, $current, $newReviewerId, $editorNote, $editorId, $dueAt) {
            $current->update(['superseded_at' => now()]);

            if (filled($editorNote)) {
                $manuscript->update(['editor_note' => $editorNote]);
            }

            return $this->assignReview($manuscript, $newReviewerId, $editorId, $dueAt);
        });
    }

    // -------------------------------------------------------------------------
    // Keputusan Editorial
    // -------------------------------------------------------------------------

    /**
     * Revisi minor/mayor mengembalikan naskah ke author (status 'revisi').
     *
     * @param string $decision kunci dari Manuscript::DECISION_STATUS (diterima|revisi_minor|revisi_mayor|ditolak)
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

        $this->notifier->editorialDecision($manuscript, Manuscript::DECISION_LABELS[$decision]);

        return $manuscript;
    }

    /**
     * Review ulang untuk naskah revisi dari author (revisi mayor): kembali ke tahap 'ditinjau'
     * dengan 3 penugasan baru. Hasil review sebelumnya tetap tersimpan sebagai riwayat.
     *
     * @throws DomainException bila bukan naskah revisi yang menunggu keputusan
     */
    public function requestReReview(Manuscript $manuscript, array $reviewerIds, ?string $editorNote, int $editorId, ?string $dueAt = null): void
    {
        $this->ensureStatus($manuscript, ['menunggu_keputusan'], 'Naskah ini tidak sedang menunggu keputusan editorial.');

        if (! $manuscript->isRevision()) {
            throw new DomainException('Review ulang hanya untuk naskah revisi yang dikirim author.');
        }

        DB::transaction(function () use ($manuscript, $reviewerIds, $editorNote, $editorId, $dueAt) {
            // Ronde baru: review ronde lama ditutup (tetap tersimpan) agar hitungan 3 reviewer mulai dari nol.
            $manuscript->currentReviews()->update(['superseded_at' => now()]);

            $manuscript->update(['status' => 'ditinjau', 'editor_note' => $editorNote ?: $manuscript->editor_note]);

            $this->assignReviewers($manuscript, $reviewerIds, $editorId, $dueAt);
        });
    }

    /**
     * Rangkuman rekomendasi reviewer untuk editor: jumlah per rekomendasi, mis. "Revisi Minor x2".
     *
     * @param Collection<int, Review> $reviews review yang sudah selesai
     * @return array<int, array{label: string, count: int, class: string}>
     */
    public function summarizeRecommendations(Collection $reviews): array
    {
        return $reviews->groupBy('recommendation')->map(fn (Collection $group, string $key) => [
            'label' => Review::RECOMMENDATION_LABELS[$key] ?? $key,
            'count' => $group->count(),
            'class' => $group->first()->recommendation_badge_class,
        ])->values()->all();
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
