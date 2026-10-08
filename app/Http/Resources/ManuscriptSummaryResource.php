<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan naskah untuk modal editor (read-only).
 * Gunakan ->resolve() agar tidak dibungkus "data".
 *
 * @mixin \App\Models\Manuscript
 */
class ManuscriptSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'abstract'           => $this->abstract,
            'keywords'           => $this->keywords,
            'status'             => $this->status,
            'status_label'       => $this->status_label,
            'author_name'        => $this->author?->name,
            'author_email'       => $this->author?->email,
            'field_name'         => $this->researchField?->name,
            'date'               => $this->created_at?->format('d M Y'),
            'file_url'           => $this->file_url,
            'file_name'          => $this->file_original_name,
            'revision_file_url'  => $this->revision_file_url,
            'revision_file_name' => $this->revision_original_name,
            'editor_note'        => $this->editor_note,
            'is_revision'        => $this->isRevision(),
        ];
    }
}
