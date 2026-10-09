<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail penugasan untuk modal reviewer. Identitas author sengaja tidak disertakan (review anonim).
 * Gunakan ->resolve().
 *
 * @mixin \App\Models\Review
 */
class ReviewerReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $m = $this->manuscript;

        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'status_label' => $this->status_label,
            'status_class' => $this->status_badge_class,
            'is_overdue'   => $this->isOverdue(),
            'assigned_at'  => $this->created_at?->format('d M Y'),
            'due_at'       => $this->due_at?->format('d M Y'),

            'manuscript' => [
                'title'         => $m->title,
                'field'         => $m->researchField?->name ?? '—',
                'abstract'      => $m->abstract,
                'keywords'      => $m->keywords,
                'submitted_at'  => ($m->submitted_at ?? $m->created_at)?->format('d M Y'),
                'editor_note'   => $m->editor_note,
                'file_url'      => $m->file_url,
                'file_name'     => $m->file_original_name,
                'revision_url'  => $m->revision_file_url,
                'revision_name' => $m->revision_original_name,
            ],

            // Hasil yang sudah dikirim (null bila belum)
            'result' => $this->status === 'selesai' ? [
                'scores'               => $this->scores,
                'score_average'        => $this->score_average,
                'comments'             => $this->comments,
                'recommendation'       => $this->recommendation,
                'recommendation_label' => $this->recommendation_label,
                'file_name'            => $this->review_file_name,
                'file_url'             => $this->review_file_path ? route('files.review', $this->id) : null,
                'completed_at'         => $this->completed_at?->format('d M Y'),
            ] : null,

            'decline_reason' => $this->status === 'ditolak_reviewer' ? $this->comments : null,

            'can' => [
                'respond' => $this->superseded_at === null && $this->status === 'ditugaskan',
                'submit'  => $this->superseded_at === null && $this->isActive() && $m->status === 'ditinjau',
                'edit'    => $this->superseded_at === null && $this->status === 'selesai' && $m->status === 'menunggu_keputusan',
            ],

            'rubric'          => collect(Review::RUBRIC)->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values()->all(),
            'levels'          => array_keys(Review::LEVELS),
            'recommendations' => collect(Review::RECOMMENDATION_LABELS)->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])->values()->all(),
        ];
    }
}
