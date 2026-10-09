<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Review;
use App\Models\User;
use App\Services\Files\ManuscriptFileStore;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * ReviewerService — seluruh logika sisi reviewer: statistik dashboard, daftar penugasan,
 * terima/tolak penugasan, kirim & ubah hasil review. Pelanggaran aturan alur dilempar
 * sebagai DomainException (controller -> 422). Kepemilikan penugasan dijaga ReviewPolicy.
 */
class ReviewerService
{
    /** Penugasan aktif yang jatuh tempo dalam N hari (atau sudah lewat) dianggap "mendekati deadline". */
    public const NEAR_DEADLINE_DAYS = 3;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function __construct(
        private readonly ManuscriptFileStore $files,
        private readonly NotificationService $notifier,
    ) {
    }

    // -------------------------------------------------------------------------
    // Dashboard & daftar
    // -------------------------------------------------------------------------

    /** @return array<int, array{label: string, count: int, icon: string, color: string, warning: bool, url: string}> */
    public function getDashboardStats(User $reviewer): array
    {
        $own = fn () => $this->ownReviews($reviewer);

        return [
            ['label' => 'Total Naskah Ditugaskan', 'count' => $own()->count(),
                'icon' => 'doc', 'color' => 'blue', 'warning' => false, 'url' => route('reviewer.manuscripts.index')],
            ['label' => 'Belum Direview', 'count' => $own()->active()->count(),
                'icon' => 'clock', 'color' => 'purple', 'warning' => false, 'url' => route('reviewer.manuscripts.index')],
            ['label' => 'Selesai Direview', 'count' => $own()->where('status', 'selesai')->count(),
                'icon' => 'check', 'color' => 'green', 'warning' => false, 'url' => route('reviewer.manuscripts-selesai')],
            ['label' => 'Mendekati Deadline', 'count' => $own()->active()->where('due_at', '<=', now()->addDays(self::NEAR_DEADLINE_DAYS))->count(),
                'icon' => 'alert', 'color' => 'orange', 'warning' => true, 'url' => route('reviewer.manuscripts.index')],
        ];
    }

    /** 5 penugasan terbaru untuk tabel aktivitas dashboard. */
    public function getRecentReviews(User $reviewer, int $limit = 5): Collection
    {
        return $this->ownReviews($reviewer)->with('manuscript.researchField')->latest()->limit($limit)->get();
    }

    /**
     * @param array{search?: ?string, status?: ?string, per_page?: int|string|null} $filters
     * @param string[]|null $onlyStatuses batasi status secara paksa (mis. halaman "Selesai")
     */
    public function paginateAssignments(User $reviewer, array $filters = [], ?array $onlyStatuses = null): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        return $this->ownReviews($reviewer)
            ->with('manuscript.researchField')
            ->when($onlyStatuses, fn (Builder $q) => $q->whereIn('status', $onlyStatuses))
            ->when(filled($status) && isset(Review::STATUS_LABELS[$status]), fn (Builder $q) => $q->where('status', $status))
            ->when(filled($search), fn (Builder $q) => $q->whereHas('manuscript', fn (Builder $m) => $m->where('title', 'like', "%{$search}%")))
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->latest('id')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    // -------------------------------------------------------------------------
    // Alur review
    // -------------------------------------------------------------------------

    /** @throws DomainException */
    public function accept(Review $review): Review
    {
        $this->ensureCurrent($review);

        if ($review->status !== 'ditugaskan') {
            throw new DomainException('Penugasan ini sudah direspon sebelumnya.');
        }

        $review->update(['status' => 'diterima']);
        ActivityLog::record('review.accepted', "Reviewer {$review->reviewer->name} menerima penugasan review", $review->reviewer_id);

        return $review;
    }

    /** @throws DomainException */
    public function decline(Review $review, string $reason): Review
    {
        $this->ensureCurrent($review);

        if ($review->status !== 'ditugaskan') {
            throw new DomainException('Hanya penugasan yang belum direspon yang dapat ditolak.');
        }

        $review->update(['status' => 'ditolak_reviewer', 'comments' => $reason, 'completed_at' => now()]);

        ActivityLog::record('review.declined', "Reviewer {$review->reviewer->name} menolak penugasan review", $review->reviewer_id);
        $this->notifier->reviewDeclined($review->load(['manuscript', 'reviewer']));

        return $review;
    }

    /**
     * Kirim hasil review pertama kali: simpan rubrik, komentar, rekomendasi (+ lampiran),
     * review -> 'selesai' dan naskah -> 'menunggu_keputusan' (muncul di Keputusan Editorial).
     *
     * @param array{comments: string, recommendation: string} $data  + kolom rubrik (Review::RUBRIC)
     * @throws DomainException
     */
    public function submit(Review $review, array $data, ?UploadedFile $file = null): Review
    {
        $this->ensureCurrent($review);

        if (! $review->isActive() || $review->manuscript->status !== 'ditinjau') {
            throw new DomainException('Review ini tidak dapat dikirim: penugasan tidak lagi aktif.');
        }

        DB::transaction(function () use ($review, $data, $file) {
            $review->fill($this->resultAttributes($data, $file, $review));
            $review->fill(['status' => 'selesai', 'completed_at' => now()])->save();

            $review->manuscript->update(['status' => 'menunggu_keputusan']);
        });

        ActivityLog::record('review.submitted', "Review \"{$review->manuscript->title}\" dikirim oleh {$review->reviewer->name}", $review->reviewer_id);
        $this->notifier->reviewCompleted($review);

        return $review;
    }

    /**
     * Ubah hasil review selama editor belum memutuskan (naskah masih 'menunggu_keputusan').
     *
     * @throws DomainException
     */
    public function update(Review $review, array $data, ?UploadedFile $file = null): Review
    {
        $this->ensureCurrent($review);

        if ($review->status !== 'selesai' || $review->manuscript->status !== 'menunggu_keputusan') {
            throw new DomainException('Review tidak dapat diubah karena editor sudah memproses naskah ini.');
        }

        $oldFile = $review->review_file_path;
        $review->fill($this->resultAttributes($data, $file, $review))->save();

        if ($file) {
            $this->files->delete($oldFile);
        }

        ActivityLog::record('review.updated', "Review \"{$review->manuscript->title}\" diperbarui oleh {$review->reviewer->name}", $review->reviewer_id);
        $this->notifier->reviewCompleted($review, updated: true);

        return $review;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /** Penugasan milik reviewer ini yang belum digantikan editor. */
    private function ownReviews(User $reviewer): Builder
    {
        return Review::query()->where('reviewer_id', $reviewer->id)->whereNull('superseded_at');
    }

    /** Kolom hasil review yang boleh ditulis (whitelist) + lampiran bila ada. */
    private function resultAttributes(array $data, ?UploadedFile $file, Review $review): array
    {
        $attributes = array_intersect_key($data, array_flip([...array_keys(Review::RUBRIC), 'comments', 'recommendation']));

        if ($file) {
            $attributes['review_file_path'] = $this->files->put($file, "reviews/{$review->id}");
            $attributes['review_file_name'] = $file->getClientOriginalName();
        }

        return $attributes;
    }

    /** @throws DomainException */
    private function ensureCurrent(Review $review): void
    {
        if ($review->superseded_at !== null) {
            throw new DomainException('Penugasan ini telah digantikan oleh editor.');
        }
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }
}
