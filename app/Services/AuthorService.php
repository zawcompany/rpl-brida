<?php

namespace App\Services;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * AuthorService — seluruh logika bisnis sisi author:
 * widget dashboard, pengajuan naskah, daftar & tracking, serta unggah revisi.
 * Setiap query dibatasi ke naskah milik author yang sedang login.
 */
class AuthorService
{
    private const DISK = 'public';
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** Status yang dihitung sebagai "Sedang Ditinjau" di widget. */
    public const IN_REVIEW_STATUSES = ['pemeriksaan_awal', 'ditinjau', 'menunggu_keputusan'];

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    /**
     * 4 widget: Total Naskah, Sedang Ditinjau, Perlu Revisi, Diterbitkan.
     * 'url' = tujuan klik; 'warning' = tampil sebagai peringatan.
     */
    public function getDashboardStats(User $author): array
    {
        $counts = $author->manuscripts()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $sum = fn (array $statuses): int => (int) collect($statuses)->sum(fn ($s) => $counts[$s] ?? 0);

        return [
            ['label' => 'Total Naskah',     'count' => (int) $counts->sum(),
                'icon' => 'doc',   'color' => 'blue',   'warning' => false, 'url' => route('author.manuscripts.index')],
            ['label' => 'Sedang Ditinjau',  'count' => $sum(self::IN_REVIEW_STATUSES),
                'icon' => 'eye',   'color' => 'purple', 'warning' => false, 'url' => route('author.manuscripts.index', ['status' => 'ditinjau'])],
            ['label' => 'Perlu Revisi',     'count' => $sum(['revisi']),
                'icon' => 'alert', 'color' => 'orange', 'warning' => true,  'url' => route('author.revisions.index')],
            ['label' => 'Diterbitkan',      'count' => $sum(['diterbitkan']),
                'icon' => 'check', 'color' => 'green',  'warning' => false, 'url' => route('author.manuscripts.index', ['status' => 'diterbitkan'])],
        ];
    }

    /** 5 naskah terakhir yang diajukan. */
    public function getRecentManuscripts(User $author): Collection
    {
        return $author->manuscripts()->with('researchField')->latest('submitted_at')->latest('id')->limit(5)->get();
    }

    // -------------------------------------------------------------------------
    // Pengajuan naskah
    // -------------------------------------------------------------------------

    public function getResearchFields(): Collection
    {
        return ResearchField::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Ajukan naskah baru: simpan berkas, catat metadata, status awal 'pending'
     * (langsung masuk ke antrean "Naskah Baru" editor).
     *
     * @param array{title: string, research_field_id: int, abstract: string, keywords: string, co_authors?: array} $data
     */
    public function submit(User $author, array $data, UploadedFile $file): Manuscript
    {
        $path = $file->store("manuscripts/{$author->id}", self::DISK);

        return $author->manuscripts()->create([
            'title'              => $data['title'],
            'research_field_id'  => $data['research_field_id'],
            'abstract'           => $data['abstract'],
            'keywords'           => $data['keywords'],
            'co_authors'         => array_values($data['co_authors'] ?? []) ?: null,
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'status'             => 'pending',
            'submitted_at'       => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Daftar naskah
    // -------------------------------------------------------------------------

    /** Naskah Saya: pencarian judul/kata kunci, filter status, paginasi. */
    public function getMyManuscripts(User $author, array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        return $author->manuscripts()
            ->with('researchField')
            ->when(filled($search), fn (Builder $q) => $q->where(
                fn (Builder $q) => $q->where('title', 'like', "%{$search}%")->orWhere('keywords', 'like', "%{$search}%")
            ))
            ->when(filled($status) && isset(Manuscript::STATUSES[$status]), fn (Builder $q) => $q->where('status', $status))
            ->latest('submitted_at')->latest('id')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    /** Hasil Review & Revisi: hanya naskah berstatus 'revisi'. */
    public function getRevisionQueue(User $author, array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return $author->manuscripts()
            ->with('researchField')
            ->where('status', 'revisi')
            ->when(filled($search), fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->latest('decided_at')->latest('id')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    // -------------------------------------------------------------------------
    // Detail & tracking
    // -------------------------------------------------------------------------

    public function owns(User $author, Manuscript $manuscript): bool
    {
        return $manuscript->author_id === $author->id;
    }

    /**
     * Timeline: Submitted -> Under Review -> Revision Required -> Decision -> Published.
     * state: done | current | upcoming | skipped | rejected
     *
     * @return array<int, array{key: string, label: string, state: string, date: ?string}>
     */
    public function buildTimeline(Manuscript $manuscript): array
    {
        $stage = match ($manuscript->status) {
            'pending', 'pemeriksaan_awal'  => 0,
            'ditinjau'                     => 1,
            'revisi'                       => 2,
            'menunggu_keputusan', 'ditolak' => 3,
            'disetujui'                    => 4,
            'diterbitkan'                  => 5,
            default                        => 0,
        };
        $rejected = $manuscript->status === 'ditolak';
        $hadRevision = $manuscript->revision_file_path !== null || $manuscript->status === 'revisi';
        $fmt = fn ($d) => $d?->format('d M Y');

        $steps = [
            ['key' => 'submitted', 'label' => 'Submitted',         'date' => $fmt($manuscript->submitted_at ?? $manuscript->created_at)],
            ['key' => 'review',    'label' => 'Under Review',      'date' => null],
            ['key' => 'revision',  'label' => 'Revision Required', 'date' => $fmt($manuscript->revision_submitted_at ?? ($manuscript->status === 'revisi' ? $manuscript->decided_at : null))],
            ['key' => 'decision',  'label' => 'Decision',          'date' => $fmt($rejected || $stage >= 4 ? $manuscript->decided_at : null)],
            ['key' => 'published', 'label' => 'Published',         'date' => $fmt($manuscript->published_at)],
        ];

        foreach ($steps as $i => &$step) {
            $step['state'] = match (true) {
                $rejected && $i === 3         => 'rejected',
                $rejected && $i === 4         => 'skipped',
                $i === 2 && $i < $stage && ! $hadRevision => 'skipped',
                $i < $stage                   => 'done',
                $i === $stage                 => 'current',
                default                       => 'upcoming',
            };
        }

        return $steps;
    }

    // -------------------------------------------------------------------------
    // Revisi
    // -------------------------------------------------------------------------

    /**
     * Komentar reviewer untuk author — anonim (tanpa identitas reviewer).
     *
     * @return array<int, array{label: string, recommendation: ?string, recommendation_class: string, comments: string}>
     */
    public function getReviewerFeedback(Manuscript $manuscript): array
    {
        return $manuscript->reviews()
            ->whereNull('superseded_at')
            ->where('status', 'selesai')
            ->orderBy('completed_at')
            ->get()
            ->values()
            ->map(fn ($review, int $i) => [
                'label'                => 'Reviewer ' . ($i + 1),
                'recommendation'       => $review->recommendation_label,
                'recommendation_class' => $review->recommendation_badge_class,
                'comments'             => $review->comments ?: 'Tanpa catatan.',
            ])->all();
    }

    /**
     * Unggah revisi: simpan berkas + surat tanggapan, lalu status -> 'menunggu_keputusan'
     * sehingga langsung muncul di antrean Keputusan Editorial.
     *
     * @throws DomainException bila naskah tidak sedang berstatus 'revisi'
     */
    public function submitRevision(Manuscript $manuscript, UploadedFile $file, string $response): Manuscript
    {
        if ($manuscript->status !== 'revisi') {
            throw new DomainException('Naskah ini tidak sedang meminta revisi.');
        }

        $oldPath = $manuscript->revision_file_path;
        $path = $file->store("revisions/{$manuscript->author_id}", self::DISK);

        try {
            $manuscript->update([
                'revision_file_path'     => $path,
                'revision_original_name' => $file->getClientOriginalName(),
                'author_response'        => $response,
                'revision_submitted_at'  => now(),
                'status'                 => 'menunggu_keputusan',
            ]);
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($path);
            throw $e;
        }

        if ($oldPath) {
            Storage::disk(self::DISK)->delete($oldPath); // ganti revisi lama dari putaran sebelumnya
        }

        return $manuscript;
    }

    // -------------------------------------------------------------------------
    // Unduhan
    // -------------------------------------------------------------------------

    /** @return array{path: string, name: string}|null */
    public function resolveDownload(Manuscript $manuscript, string $type): ?array
    {
        [$path, $name] = $type === 'revision'
            ? [$manuscript->revision_file_path, $manuscript->revision_original_name]
            : [$manuscript->file_path, $manuscript->file_original_name];

        return $path && Storage::disk(self::DISK)->exists($path)
            ? ['path' => $path, 'name' => $name ?: basename($path)]
            : null;
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
