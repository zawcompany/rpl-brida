<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Manuscript */
class AuthorManuscriptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'field'                 => $this->researchField?->name ?? '—',
            'abstract'              => $this->abstract,
            'keywords'              => $this->keywords,
            'co_authors'            => $this->co_authors ?? [],
            'submitted_at'          => ($this->submitted_at ?? $this->created_at)?->format('d M Y'),
            'status'                => $this->status,
            'status_label'          => $this->status_label,
            'status_class'          => $this->status_badge_class,
            'file_name'             => $this->file_original_name,
            'file_url'              => route('author.manuscripts.download', [$this->id, 'original']),
            'revision_name'         => $this->revision_original_name,
            'revision_url'          => $this->revision_file_path
                ? route('author.manuscripts.download', [$this->id, 'revision'])
                : null,
            'editorial_note'        => $this->editorial_note,
            'decided_at'            => $this->decided_at?->format('d M Y'),
            'author_response'       => $this->author_response,
            'revision_submitted_at' => $this->revision_submitted_at?->format('d M Y'),
        ];
    }
}
