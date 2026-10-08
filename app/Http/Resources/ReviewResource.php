<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Data penugasan review untuk modal editor.
 * Gunakan ->resolve() agar tidak dibungkus "data".
 *
 * @mixin \App\Models\Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'reviewer_name'        => $this->reviewer?->name,
            'reviewer_email'       => $this->reviewer?->email,
            'status'               => $this->status,
            'status_label'         => $this->status_label,
            'status_badge_class'   => $this->status_badge_class,
            'due_at'               => $this->due_at?->format('d M Y'),
            'is_overdue'           => $this->isOverdue(),
            'reminded_at'          => $this->reminded_at?->format('d M Y H:i'),
            'reminder_count'       => $this->reminder_count,
            'can_remind'           => $this->isActive(),
            'can_replace'          => $this->isReplaceable(),
            'comments'             => $this->comments,
            'recommendation'       => $this->recommendation,
            'recommendation_label' => $this->recommendation_label,
            'recommendation_class' => $this->recommendation_badge_class,
            'completed_at'         => $this->completed_at?->format('d M Y'),
        ];
    }
}
