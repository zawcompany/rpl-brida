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
    public const AUTHOR = 'Author';

    public const EDITOR = 'Editor';

    public const REVIEWER = 'Reviewer';

    /** Peran yang boleh disandang di sebuah jurnal. */
    public const ROLES = [self::AUTHOR, self::EDITOR, self::REVIEWER];

    /** Lisensi terbuka yang dapat dipilih per jurnal (UU 28/2014: hak ekonomi tetap milik penulis). */
    public const LICENSES = [
        'CC-BY-4.0' => ['label' => 'CC BY 4.0',       'url' => 'https://creativecommons.org/licenses/by/4.0/'],
        'CC-BY-SA-4.0' => ['label' => 'CC BY-SA 4.0',    'url' => 'https://creativecommons.org/licenses/by-sa/4.0/'],
        'CC-BY-NC-4.0' => ['label' => 'CC BY-NC 4.0',    'url' => 'https://creativecommons.org/licenses/by-nc/4.0/'],
        'CC-BY-NC-ND-4.0' => ['label' => 'CC BY-NC-ND 4.0', 'url' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/'],
    ];

    public const DEFAULT_LICENSE = 'CC-BY-4.0';

    /** Peringkat akreditasi yang berlaku (Permendiktisaintek 9/2026 Pasal 9 ayat (2)). */
    public const ACCREDITATION_RANKS = [1, 2, 3, 4];

    /** Akreditasi ulang diajukan paling lambat 6 bulan sebelum masa berlaku berakhir (Pasal 13 ayat (1)). */
    public const REACCREDITATION_NOTICE_MONTHS = 6;

    /** Format e-ISSN: 4 digit, tanda hubung, 3 digit, 1 digit/X. */
    public const ISSN_PATTERN = '/^\d{4}-\d{3}[\dX]$/';

    protected $fillable = [
        'name', 'abbreviation', 'field', 'slug', 'description', 'cover_image',
        'template_path', 'template_original_name', 'template_version', 'is_active',
        'e_issn', 'license',
        'accreditation_rank', 'accreditation_decree', 'accreditation_valid_until', 'accreditation_reminded_at',
        'focus_scope', 'author_guidelines',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'accreditation_rank' => 'integer',
        'accreditation_valid_until' => 'date',
        'accreditation_reminded_at' => 'datetime',
    ];

    /** @return array{label: string, url: string} info lisensi; nilai tak dikenal jatuh ke lisensi bawaan. */
    public static function licenseInfo(?string $code): array
    {
        return self::LICENSES[$code] ?? self::LICENSES[self::DEFAULT_LICENSE];
    }

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

    /**
     * Selain slug, alamat lama/berbasis id (mis. /jurnal/1/metadata.xml yang dipakai pemanen metadata) tetap dikenali.
     * Slug yang kebetulan berupa angka tetap ditemukan lewat slug bila tidak ada id yang cocok.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field === null && ctype_digit((string) $value)) {
            return $this->whereKey((int) $value)->first() ?? parent::resolveRouteBinding($value, $field);
        }

        return parent::resolveRouteBinding($value, $field);
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

    // ------------------------------------------------------------------ Akreditasi

    /** Terakreditasi bila punya peringkat dan masa berlakunya belum lewat. */
    public function isAccredited(): bool
    {
        return $this->accreditation_rank !== null
            && $this->accreditation_valid_until !== null
            && $this->accreditation_valid_until->endOfDay()->isFuture();
    }

    /** Masa berlaku tinggal 6 bulan atau kurang: saatnya mengajukan akreditasi ulang. */
    public function accreditationExpiresSoon(): bool
    {
        return $this->isAccredited()
            && $this->accreditation_valid_until->lte(now()->addMonths(self::REACCREDITATION_NOTICE_MONTHS));
    }

    /** "Terakreditasi Peringkat 2 · berlaku s.d. 31 Dec 2029", atau null bila tidak terakreditasi. */
    public function getAccreditationLabelAttribute(): ?string
    {
        if (! $this->isAccredited()) {
            return null;
        }

        return "Terakreditasi Peringkat {$this->accreditation_rank} · berlaku s.d. "
            .$this->accreditation_valid_until->format('d M Y');
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
