<?php

namespace App\Models;

use App\Services\Files\CoverImageStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    public const DRAFT     = 'draft';
    public const PUBLISHED = 'published';

    protected $fillable = ['volume', 'number', 'year', 'title', 'cover_image', 'status', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

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
