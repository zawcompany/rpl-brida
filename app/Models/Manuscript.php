<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    /** Semua nilai status yang valid — single source of truth. */
    public const STATUSES = [
        'pending'              => 'Pending',
        'pemeriksaan_awal'     => 'Pemeriksaan Awal',
        'ditinjau'             => 'Sedang Ditinjau',
        'menunggu_keputusan'   => 'Menunggu Keputusan',
        'disetujui'            => 'Disetujui',
        'ditolak'              => 'Ditolak',
        'diterbitkan'          => 'Diterbitkan',
    ];

    /** Status yang termasuk "Naskah Baru" (inbox editor). */
    public const NEW_STATUSES = ['pending', 'pemeriksaan_awal'];

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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function activeReview(): HasMany
    {
        return $this->hasMany(Review::class)->whereIn('status', ['ditugaskan', 'diterima']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
            'disetujui'                   => 'bg-green-100 text-green-800',
            'ditolak'                     => 'bg-red-100 text-red-800',
            'diterbitkan'                 => 'bg-gray-100 text-gray-800',
            default                       => 'bg-gray-100 text-gray-700',
        };
    }
}
