<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi penugasan reviewer & keputusan administrasi awal naskah.
 */
class AssignReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    public function rules(): array
    {
        return [
            'decision'    => ['required', 'in:ditinjau,ditolak'],
            'reviewer_id' => [
                'required_if:decision,ditinjau',
                'nullable',
                Rule::exists('users', 'id')->where('role', 'Reviewer'),
            ],
            'due_at'      => ['nullable', 'date', 'after_or_equal:today'],
            'editor_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_at.after_or_equal'   => 'Tenggat waktu tidak boleh sebelum hari ini.',
            'decision.required'       => 'Keputusan administrasi wajib dipilih.',
            'decision.in'             => 'Keputusan tidak valid.',
            'reviewer_id.required_if' => 'Reviewer wajib dipilih ketika keputusan adalah "Lanjut ke Review".',
            'reviewer_id.exists'      => 'Reviewer yang dipilih tidak ditemukan.',
        ];
    }
}
