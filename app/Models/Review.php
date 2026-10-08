<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** Status yang masih menjadi beban kerja reviewer. */
    public const ACTIVE_STATUSES = ['ditugaskan', 'diterima'];

    public const STATUS_LABELS = [
        'ditugaskan'       => 'Belum Direspon',
        'diterima'         => 'Sedang Meninjau',
        'selesai'          => 'Selesai Review',
        'ditolak_reviewer' => 'Ditolak Reviewer',
    ];

    public const RECOMMENDATION_LABELS = [
        'diterima'     => 'Diterima',
        'revisi_minor' => 'Revisi Minor',
        'revisi_mayor' => 'Revisi Mayor',
        'ditolak'      => 'Ditolak',
    ];

    protected $fillable = [
        'manuscript_id',
        'reviewer_id',
        'assigned_by',
        'comments',
        'recommendation',
        'status',
        'due_at',
        'completed_at',
        'reminded_at',
        'reminder_count',
        'superseded_at',
    ];

    protected $casts = [
        'due_at'        => 'datetime',
        'completed_at'  => 'datetime',
        'reminded_at'   => 'datetime',
        'superseded_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assignedByEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // -------------------------------------------------------------------------
    // Scope & helper
    // -------------------------------------------------------------------------

    /** Penugasan yang masih berjalan (= beban aktif reviewer). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES)->whereNull('superseded_at');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true) && $this->superseded_at === null;
    }

    public function isOverdue(): bool
    {
        return $this->isActive() && $this->due_at !== null && $this->due_at->isPast();
    }

    /** Boleh diganti: reviewer menolak atau terlambat. */
    public function isReplaceable(): bool
    {
        return $this->superseded_at === null
            && ($this->status === 'ditolak_reviewer' || $this->isOverdue());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'ditugaskan'       => 'bg-yellow-100 text-yellow-800',
            'diterima'         => 'bg-blue-100 text-blue-800',
            'selesai'          => 'bg-green-100 text-green-800',
            'ditolak_reviewer' => 'bg-red-100 text-red-800',
            default            => 'bg-gray-100 text-gray-700',
        };
    }

    public function getRecommendationLabelAttribute(): ?string
    {
        return $this->recommendation
            ? (self::RECOMMENDATION_LABELS[$this->recommendation] ?? $this->recommendation)
            : null;
    }

    public function getRecommendationBadgeClassAttribute(): string
    {
        return match ($this->recommendation) {
            'diterima'     => 'bg-green-100 text-green-800',
            'revisi_minor' => 'bg-yellow-100 text-yellow-800',
            'revisi_mayor' => 'bg-orange-100 text-orange-800',
            'ditolak'      => 'bg-red-100 text-red-800',
            default        => 'bg-gray-100 text-gray-600',
        };
    }
}
