<?php

namespace App\Http\Requests\Editor;

use App\Models\Manuscript;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi keputusan editorial akhir (diterima / revisi / ditolak).
 */
class EditorialDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    public function rules(): array
    {
        return [
            'decision'       => ['required', Rule::in(array_keys(Manuscript::DECISION_STATUS))],
            // Author berhak tahu alasannya bila naskah diminta revisi atau ditolak.
            'editorial_note' => ['required_unless:decision,diterima', 'nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required'             => 'Keputusan akhir wajib dipilih.',
            'decision.in'                   => 'Keputusan tidak valid.',
            'editorial_note.required_unless' => 'Catatan editorial wajib diisi untuk revisi atau penolakan.',
        ];
    }
}
