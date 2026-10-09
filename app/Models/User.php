<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'institution', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Akun baru aktif secara default (sinkron dengan default kolom DB). */
    protected $attributes = ['is_active' => true];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    /** Naskah yang diajukan oleh user ini (sebagai Author). */
    public function manuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class, 'author_id');
    }

    /** Penugasan review yang diterima user ini (sebagai Reviewer). */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /** Bidang keilmuan yang dikuasai reviewer ini. */
    public function researchFields(): BelongsToMany
    {
        return $this->belongsToMany(ResearchField::class, 'reviewer_research_field');
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /** Role tersimpan di DB => label tampilan. */
    public const ROLE_LABELS = [
        'Author'        => 'Author',
        'Editor'        => 'Editor',
        'Reviewer'      => 'Reviewer',
        'Administrator' => 'Admin',
    ];

    protected static function booted(): void
    {
        // Jejak audit registrasi: berlaku untuk daftar mandiri maupun akun yang dibuat admin.
        static::created(function (User $user): void {
            ActivityLog::record(
                'user.registered',
                "Akun baru terdaftar: {$user->name} ({$user->role_label})",
                auth()->id() ?? $user->id
            );
        });
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role] ?? (string) $this->role;
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            'Administrator' => 'bg-red-100 text-red-700',
            'Editor'        => 'bg-purple-100 text-purple-700',
            'Reviewer'      => 'bg-blue-100 text-blue-700',
            default         => 'bg-gray-100 text-gray-700',
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Administrator';
    }

    /** Batas beban aktif; di atas ini reviewer dianggap "Sibuk". */
    public const MAX_ACTIVE_REVIEWS = 3;

    public function scopeReviewers(Builder $query): Builder
    {
        return $query->where('role', 'Reviewer');
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    /** Jumlah naskah aktif yang sedang di-review oleh user ini. */
    public function activeReviewCount(): int
    {
        return $this->reviews()->active()->count();
    }
}
