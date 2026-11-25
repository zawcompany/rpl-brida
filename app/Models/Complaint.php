<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pengaduan atau keberatan pengguna atas layanan SIMPIL (UU 25/2009 Pasal 36).
 * Dikelola petugas (admin) dalam batas waktu config('simpil.complaints.response_days').
 */
class Complaint extends Model
{
    public const CATEGORIES = [
        'layanan' => 'Pelayanan sistem atau proses naskah',
        'keberatan' => 'Keberatan atas keputusan editorial',
        'data_pribadi' => 'Permintaan terkait data pribadi (salinan, perbaikan, penghapusan)',
        'etika' => 'Dugaan pelanggaran etika atau plagiarisme',
        'lainnya' => 'Lainnya',
    ];

    public const STATUSES = [
        'diterima' => 'Diterima',
        'diproses' => 'Sedang diproses',
        'selesai' => 'Selesai',
    ];

    protected $fillable = [
        'user_id', 'manuscript_id', 'category', 'subject', 'description',
        'status', 'response', 'handled_by', 'responded_at',
    ];

    protected $casts = ['responded_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', 'selesai');
    }

    /** Nomor tiket yang dapat dirujuk pengadu, mis. "ADU-000012". */
    public function getTicketAttribute(): string
    {
        return 'ADU-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** Batas waktu tindak lanjut sejak pengaduan diterima. */
    public function getDueAtAttribute(): Carbon
    {
        return $this->created_at->copy()->addDays((int) config('simpil.complaints.response_days'));
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'selesai' && $this->due_at->isPast();
    }
}
