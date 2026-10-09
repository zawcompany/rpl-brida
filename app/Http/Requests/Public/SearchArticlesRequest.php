<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/** Pencarian/filter artikel publik (GET). Terbuka untuk umum; semua parameter dibatasi tipenya. */
class SearchArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'     => ['nullable', 'string', 'max:100'],
            'field' => ['nullable', 'integer', 'exists:research_fields,id'],
            'issue' => ['nullable', 'integer', 'exists:issues,id'],
        ];
    }
}
