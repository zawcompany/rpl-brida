<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'description', 'ip_address'];

    protected $casts = ['created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Catat satu aktivitas. Pelaku default = user yang sedang login. */
    public static function record(string $action, string $description, ?int $actorId = null): self
    {
        return static::create([
            'user_id'     => $actorId ?? auth()->id(),
            'action'      => $action,
            'description' => $description,
            'ip_address'  => request()?->ip(),
        ]);
    }
}
