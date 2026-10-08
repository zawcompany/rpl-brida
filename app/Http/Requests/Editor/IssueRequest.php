<?php

namespace App\Http\Requests\Editor;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pembuatan / perubahan edisi jurnal.
 */
class IssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    public function rules(): array
    {
        /** @var Issue|null $issue */
        $issue = $this->route('issue');

        return [
            'volume' => ['required', 'integer', 'min:1', 'max:9999'],
            'number' => [
                'required', 'integer', 'min:1', 'max:9999',
                Rule::unique('issues')
                    ->where('volume', $this->input('volume'))
                    ->where('year', $this->input('year'))
                    ->ignore($issue?->id),
            ],
            'year'   => ['required', 'integer', 'min:2000', 'max:2100'],
            'title'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.unique' => 'Edisi dengan volume, nomor, dan tahun tersebut sudah ada.',
        ];
    }
}
