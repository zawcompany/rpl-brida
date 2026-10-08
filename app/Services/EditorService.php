<?php

namespace App\Services;

use App\Models\Manuscript;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * EditorService — Single Responsibility: semua logika bisnis editor ada di sini.
 * Controller hanya memanggil service ini, tidak ada logika bisnis di controller.
 */
class EditorService
{
    // -------------------------------------------------------------------------
    // Dashboard: Stat Widgets
    // -------------------------------------------------------------------------

    /**
     * Mengembalikan 4 data widget statistik dashboard editor.
     * Setiap widget berisi: count, label, icon_key, change_percent, change_direction.
     */
    public function getDashboardStats(): array
    {
        $now       = Carbon::now();
        $thisMonth = $now->month;
        $thisYear  = $now->year;
        $lastMonth = $now->copy()->subMonth();

        $widgets = [
            [
                'key'        => 'naskah_baru',
                'label'      => 'Naskah Baru',
                'icon'       => 'inbox',
                'color'      => 'blue',
                'statuses'   => ['pending', 'pemeriksaan_awal'],
            ],
            [
                'key'        => 'sedang_ditinjau',
                'label'      => 'Sedang Ditinjau',
                'icon'       => 'eye',
                'color'      => 'purple',
                'statuses'   => ['ditinjau'],
            ],
            [
                'key'        => 'menunggu_keputusan',
                'label'      => 'Menunggu Keputusan',
                'icon'       => 'clock',
                'color'      => 'yellow',
                'statuses'   => ['menunggu_keputusan'],
            ],
            [
                'key'        => 'menunggu_publikasi',
                'label'      => 'Menunggu Publikasi',
                'icon'       => 'check',
                'color'      => 'green',
                'statuses'   => ['disetujui'],
            ],
        ];

        return collect($widgets)->map(function (array $widget) use ($thisMonth, $thisYear, $lastMonth): array {
            $currentCount = Manuscript::whereIn('status', $widget['statuses'])->count();

            $lastCount = Manuscript::whereIn('status', $widget['statuses'])
                ->whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();

            $changePercent   = $lastCount > 0
                ? round((($currentCount - $lastCount) / $lastCount) * 100, 1)
                : ($currentCount > 0 ? 100.0 : 0.0);

            return [
                'label'            => $widget['label'],
                'icon'             => $widget['icon'],
                'color'            => $widget['color'],
                'count'            => $currentCount,
                'change_percent'   => abs($changePercent),
                'change_direction' => $changePercent >= 0 ? 'up' : 'down',
            ];
        })->all();
    }

    // -------------------------------------------------------------------------
    // Dashboard: Recent Activity
    // -------------------------------------------------------------------------

    /**
     * 5 naskah terbaru untuk tabel aktivitas dashboard.
     */
    public function getRecentActivity(): Collection
    {
        return Manuscript::with(['author', 'researchField'])
            ->latest()
            ->limit(5)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Naskah Baru: Data Table dengan Pagination, Search & Filter
    // -------------------------------------------------------------------------

    /**
     * Data naskah baru (pending/pemeriksaan_awal) dengan filter dinamis.
     *
     * @param array{search?: string, date?: string, per_page?: int} $filters
     */
    public function getNewManuscripts(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        return Manuscript::with(['author', 'researchField'])
            ->whereIn('status', Manuscript::NEW_STATUSES)
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where(function ($q) use ($filters) {
                    $q->where('title', 'like', '%' . $filters['search'] . '%')
                      ->orWhereHas('author', fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'));
                })
            )
            ->when(
                filled($filters['date'] ?? null),
                fn ($q) => $q->whereDate('created_at', $filters['date'])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    // -------------------------------------------------------------------------
    // Rekomendasi Reviewer (Workload Balancing + Field Matching)
    // -------------------------------------------------------------------------

    /**
     * Mengembalikan daftar reviewer yang diurutkan berdasarkan:
     * 1. Kesesuaian bidang keilmuan (bobot tertinggi).
     * 2. Beban kerja aktif (workload) — yang paling sedikit diprioritaskan.
     *
     * @return Collection<int, array{reviewer: User, score: int, active_load: int, matched: bool}>
     */
    public function getReviewerRecommendations(Manuscript $manuscript): Collection
    {
        $fieldId = $manuscript->research_field_id;

        return User::where('role', 'Reviewer')
            ->with('researchFields')
            ->withCount([
                'reviews as active_load' => fn ($q) => $q->whereIn('status', ['ditugaskan', 'diterima']),
            ])
            ->get()
            ->map(function (User $reviewer) use ($fieldId): array {
                $matched = $fieldId
                    && $reviewer->researchFields->pluck('id')->contains($fieldId);

                // Skor: 10 poin jika bidang cocok, dikurangi beban aktif (maks 5 poin penalti)
                $score = ($matched ? 10 : 0) - min($reviewer->active_load, 5);

                return [
                    'reviewer'    => $reviewer,
                    'score'       => $score,
                    'active_load' => $reviewer->active_load,
                    'matched'     => $matched,
                ];
            })
            ->sortByDesc('score')
            ->values();
    }

    // -------------------------------------------------------------------------
    // Tindakan: Penugasan Reviewer & Keputusan Administrasi
    // -------------------------------------------------------------------------

    /**
     * Memproses keputusan administrasi awal editor terhadap naskah.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function processDecision(
        Manuscript $manuscript,
        string $decision,
        ?int $reviewerId,
        ?string $editorNote,
        int $editorId
    ): Manuscript {
        $manuscript->editor_note = $editorNote;

        if ($decision === 'ditolak') {
            $manuscript->status = 'ditolak';
            $manuscript->save();
            return $manuscript;
        }

        // Lanjut ke Review
        $manuscript->status = 'ditinjau';
        $manuscript->save();

        // Buat record penugasan review
        Review::create([
            'manuscript_id' => $manuscript->id,
            'reviewer_id'   => $reviewerId,
            'assigned_by'   => $editorId,
            'status'        => 'ditugaskan',
            'due_at'        => now()->addDays(14), // Default 2 minggu
        ]);

        return $manuscript->fresh();
    }
}
