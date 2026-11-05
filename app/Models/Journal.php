<?php

namespace App\Models;

use App\Services\Files\CoverImageStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Satu jurnal di platform. Setiap jurnal punya template, anggota (peran per jurnal), naskah, dan edisi sendiri.
 */
class Journal extends Model
{
    public const AUTHOR   = 'Author';
    public const EDITOR   = 'Editor';
    public const REVIEWER = 'Reviewer';

    /** Peran yang boleh disandang di sebuah jurnal. */
    public const ROLES = [self::AUTHOR, self::EDITOR, self::REVIEWER];

    protected $fillable = [
        'name', 'abbreviation', 'field', 'slug', 'description', 'cover_image',
        'template_path', 'template_original_name', 'template_version', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Journal $journal): void {
            $journal->slug = $journal->slug ?: static::uniqueSlug($journal->name);
        });
    }

    /** URL memakai slug, bukan id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ------------------------------------------------------------------ Relasi

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function manuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /** Anggota dengan peran tertentu. */
    public function membersAs(string $role): BelongsToMany
    {
        return $this->members()->wherePivot('role', $role);
    }

    // ------------------------------------------------------------------ Scope & helper

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Jurnal bawaan (yang pertama dibuat); tempat data lama dan default bagi data tanpa jurnal. */
    public static function default(): ?self
    {
        return static::query()->orderBy('id')->first();
    }

    public static function defaultId(): ?int
    {
        return static::query()->orderBy('id')->value('id');
    }

    /** Template tersedia bila sudah diunggah, atau bila ini jurnal bawaan (memakai template bawaan aplikasi). */
    public function hasTemplate(): bool
    {
        return $this->template_path !== null || $this->usesBundledTemplate();
    }

    public function usesBundledTemplate(): bool
    {
        return $this->template_path === null
            && $this->id === static::defaultId()
            && is_file(resource_path('templates/template-naskah.docx'));
    }

    /** URL cover, atau null bila belum diunggah (tampilan memakai sampul gradasi dari nama jurnal). */
    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? app(CoverImageStore::class)->urlOrNull($this->cover_image) : null;
    }

    /** Huruf awal untuk sampul bawaan: singkatan, atau dua huruf pertama nama. */
    public function getInitialsAttribute(): string
    {
        return Str::upper(Str::limit($this->abbreviation ?: Str::substr($this->name, 0, 2), 4, ''));
    }

    /** "SIMPIL" atau, bila singkatan kosong, nama lengkap. */
    public function getShortNameAttribute(): string
    {
        return $this->abbreviation ?: $this->name;
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'jurnal';
        $slug = $base;

        for ($i = 2; static::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
