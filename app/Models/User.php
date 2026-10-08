<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    /** Jumlah naskah aktif yang sedang di-review oleh user ini. */
    public function activeReviewCount(): int
    {
        return $this->reviews()->whereIn('status', ['ditugaskan', 'diterima'])->count();
    }
}
