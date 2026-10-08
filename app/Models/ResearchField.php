<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchField extends Model
{
    protected $fillable = ['name', 'slug'];

    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reviewer_research_field');
    }

    public function manuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class);
    }
}
