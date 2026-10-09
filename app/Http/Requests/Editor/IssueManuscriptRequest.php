<?php

namespace App\Http\Requests\Editor;

use App\Rules\SafeDocument;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi penetapan naskah ke edisi + upload PDF final (camera-ready).
 */
class IssueManuscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    public function rules(): array
    {
        return [
            'manuscript_id' => ['required', 'exists:manuscripts,id'],
            'final_file'    => ['required', 'file', new SafeDocument(['pdf']), 'max:' . config('simpil.upload.final_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'manuscript_id.required' => 'Pilih naskah yang akan ditambahkan.',
            'final_file.required'    => 'Berkas PDF final (camera-ready) wajib diunggah.',
            'final_file.max'         => 'Ukuran berkas final maksimal 20 MB.',
        ];
    }
}
