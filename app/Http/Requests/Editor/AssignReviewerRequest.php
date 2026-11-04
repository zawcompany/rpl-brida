<?php

namespace App\Http\Requests\Editor;

use App\Models\Manuscript;
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
            'decision'    => ['required', Rule::in(array_keys(Manuscript::INITIAL_DECISION_STATUS))],
            // Tepat 3 reviewer berbeda (blind review) bila naskah diterima untuk direview.
            'reviewer_ids'   => ['required_if:decision,diterima', 'nullable', 'array', 'size:' . Manuscript::REQUIRED_REVIEWERS],
            'reviewer_ids.*' => ['distinct', Rule::exists('users', 'id')->where('role', 'Reviewer')],
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
            'reviewer_ids.required_if' => 'Tiga reviewer wajib dipilih ketika naskah diterima untuk direview.',
            'reviewer_ids.size'       => 'Naskah harus direview oleh tepat ' . Manuscript::REQUIRED_REVIEWERS . ' reviewer.',
            'reviewer_ids.*.distinct' => 'Reviewer yang dipilih tidak boleh sama.',
            'reviewer_ids.*.exists'   => 'Reviewer yang dipilih tidak ditemukan.',
        ];
    }
}
