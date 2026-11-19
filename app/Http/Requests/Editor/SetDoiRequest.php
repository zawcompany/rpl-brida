<?php

namespace App\Http\Requests\Editor;

use App\Models\Manuscript;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Penetapan DOI artikel oleh editor. Kosong = hapus DOI. */
class SetDoiRequest extends FormRequest
{
    /** DOI: awalan 10.NNNN(+) , garis miring, lalu akhiran tanpa spasi. */
    public const PATTERN = '/^10\.\d{4,9}\/\S+$/';

    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    /** Terima DOI ditempel sebagai URL ("https://doi.org/10.x/y") atau "doi:10.x/y"; simpan hanya bentuk bakunya. */
    protected function prepareForValidation(): void
    {
        $doi = trim((string) $this->input('doi'));
        $doi = preg_replace('#^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)#i', '', $doi);

        $this->merge(['doi' => $doi === '' ? null : $doi]);
    }

    public function rules(): array
    {
        /** @var Manuscript|null $manuscript */
        $manuscript = $this->route('manuscript');

        return [
            'doi' => ['nullable', 'string', 'max:255', 'regex:' . self::PATTERN, Rule::unique('manuscripts', 'doi')->ignore($manuscript?->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'doi.regex'  => 'Format DOI tidak valid. Contoh: 10.12345/simpil.2026.001',
            'doi.unique' => 'DOI ini sudah dipakai artikel lain.',
            'doi.max'    => 'DOI terlalu panjang (maksimal 255 karakter).',
        ];
    }
}
