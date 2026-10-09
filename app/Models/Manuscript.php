<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Manuscript extends Model
{
    protected $fillable = [
        'author_id',
        'research_field_id',
        'title',
        'abstract',
        'keywords',
        'file_path',
        'file_original_name',
        'status',
        'editor_note',
        'editorial_note',
        'submitted_at',
        'decided_at',
        'revision_file_path',
        'revision_original_name',
        'author_response',
        'revision_submitted_at',
        'co_authors',
        'final_file_path',
        'final_original_name',
        'issue_id',
        'published_at',
        'doi',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decided_at'   => 'datetime',
        'published_at' => 'datetime',
        'revision_submitted_at' => 'datetime',
        'co_authors'   => 'array',
    ];

    /** Semua nilai status yang valid — single source of truth. */
    public const STATUSES = [
        'pending'              => 'Pending',
        'pemeriksaan_awal'     => 'Pemeriksaan Awal',
        'ditinjau'             => 'Sedang Ditinjau',
        'menunggu_keputusan'   => 'Menunggu Keputusan',
        'revisi'               => 'Perlu Revisi',
        'disetujui'            => 'Disetujui',
        'ditolak'              => 'Ditolak',
        'diterbitkan'          => 'Diterbitkan',
    ];

    /** Status yang termasuk "Naskah Baru" (inbox editor). */
    public const NEW_STATUSES = ['pending', 'pemeriksaan_awal'];

    /** Status yang tampil di menu Peninjauan Naskah. */
    public const REVIEW_STATUSES = ['ditinjau', 'menunggu_keputusan'];

    /** Keputusan administrasi awal (Naskah Baru) -> status naskah. Terima = lanjut ke tahap review. */
    public const INITIAL_DECISION_STATUS = [
        'diterima' => 'ditinjau',
        'ditolak'  => 'ditolak',
    ];

    /** Keputusan editorial akhir -> status naskah. */
    public const DECISION_STATUS = [
        'diterima' => 'disetujui',
        'revisi'   => 'revisi',
        'ditolak'  => 'ditolak',
    ];

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function researchField(): BelongsTo
    {
        return $this->belongsTo(ResearchField::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Penugasan review terkini (yang belum digantikan reviewer lain). */
    public function currentReview(): HasOne
    {
        return $this->hasOne(Review::class)->whereNull('superseded_at')->latestOfMany();
    }

    // -------------------------------------------------------------------------
    // Accessor
    // -------------------------------------------------------------------------

    /** Artikel yang boleh dilihat publik: status 'diterbitkan' pada edisi yang sudah terbit. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'diterbitkan')
            ->whereHas('issue', fn (Builder $q) => $q->where('status', Issue::PUBLISHED));
    }

    /** "Penulis Utama, Rekan Satu, Rekan Dua" */
    public function getAuthorsLabelAttribute(): string
    {
        return collect([$this->author?->name])
            ->merge(collect($this->co_authors ?? [])->pluck('name'))
            ->filter()->implode(', ') ?: '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /** Kembalikan Tailwind CSS classes badge per status. */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending', 'pemeriksaan_awal' => 'bg-yellow-100 text-yellow-800',
            'ditinjau'                    => 'bg-blue-100 text-blue-800',
            'menunggu_keputusan'          => 'bg-purple-100 text-purple-800',
            'revisi'                      => 'bg-orange-100 text-orange-800',
            'disetujui'                   => 'bg-green-100 text-green-800',
            'ditolak'                     => 'bg-red-100 text-red-800',
            'diterbitkan'                 => 'bg-gray-100 text-gray-800',
            default                       => 'bg-gray-100 text-gray-700',
        };
    }

    /** Naskah ini adalah hasil revisi yang dikirim ulang author. */
    public function isRevision(): bool
    {
        return $this->revision_file_path !== null;
    }

    /** Berkas yang dirapikan editor: revisi terbaru bila ada, kalau tidak berkas asli. */
    public function getSourceFileUrlAttribute(): ?string
    {
        return $this->revision_file_url ?? $this->file_url;
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->fileRoute($this->file_path, 'original');
    }

    public function getRevisionFileUrlAttribute(): ?string
    {
        return $this->fileRoute($this->revision_file_path, 'revision');
    }

    public function getFinalFileUrlAttribute(): ?string
    {
        return $this->fileRoute($this->final_file_path, 'final');
    }

    /** Berkas privat: URL mengarah ke rute terotorisasi, bukan ke path storage. */
    private function fileRoute(?string $path, string $type): ?string
    {
        return $path ? route('files.manuscript', [$this->id, $type]) : null;
    }
}
