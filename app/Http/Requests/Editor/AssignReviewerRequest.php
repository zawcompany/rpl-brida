<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest untuk validasi penugasan reviewer & keputusan administrasi awal naskah.
 * Isolasi validasi sesuai prinsip SRP (SOLID).
 */
class AssignReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Editor';
    }

    public function rules(): array
    {
        return [
            'decision'    => ['required', 'in:ditinjau,ditolak'],
            'reviewer_id' => ['required_if:decision,ditinjau', 'nullable', 'exists:users,id'],
            'editor_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required'       => 'Keputusan administrasi wajib dipilih.',
            'decision.in'             => 'Keputusan tidak valid.',
            'reviewer_id.required_if' => 'Reviewer wajib dipilih ketika keputusan adalah "Lanjut ke Review".',
            'reviewer_id.exists'      => 'Reviewer yang dipilih tidak ditemukan.',
        ];
    }
}
