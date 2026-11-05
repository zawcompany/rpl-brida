<?php

namespace App\Models;

use App\Services\Files\CoverImageStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    public const DRAFT     = 'draft';
    public const PUBLISHED = 'published';

    protected $fillable = ['journal_id', 'volume', 'number', 'year', 'title', 'cover_image', 'status', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Issue $issue): void {
            $issue->journal_id ??= Journal::defaultId();
        });
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /** Batasi ke jurnal tertentu; null = tanpa pembatasan (Administrator). */
    public function scopeInJournals(Builder $query, ?array $journalIds): Builder
    {
        return $journalIds === null ? $query : $query->whereIn('issues.journal_id', $journalIds);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::PUBLISHED);
    }

    public function manuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED;
    }

    /** URL sampul (atau placeholder bawaan bila belum diunggah). */
    public function getCoverUrlAttribute(): string
    {
        return app(CoverImageStore::class)->url($this->cover_image);
    }

    /** Label ringkas, mis. "Vol. 2 No. 1 (2026)". */
    public function getLabelAttribute(): string
    {
        return "Vol. {$this->volume} No. {$this->number} ({$this->year})";
    }
}
