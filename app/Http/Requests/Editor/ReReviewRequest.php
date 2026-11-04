<?php

namespace App\Http\Requests\Editor;

use App\Models\Manuscript;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pengiriman naskah revisi untuk review ulang.
 */
class ReReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    public function rules(): array
    {
        return [
            'reviewer_ids'   => ['required', 'array', 'size:' . Manuscript::REQUIRED_REVIEWERS],
            'reviewer_ids.*' => ['distinct', Rule::exists('users', 'id')->where('role', 'Reviewer')],
            'due_at'      => ['nullable', 'date', 'after_or_equal:today'],
            'editor_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_at.after_or_equal'   => 'Tenggat waktu tidak boleh sebelum hari ini.',
            'reviewer_ids.required' => 'Pilih ' . Manuscript::REQUIRED_REVIEWERS . ' reviewer untuk review ulang.',
            'reviewer_ids.size'     => 'Review ulang harus dilakukan oleh tepat ' . Manuscript::REQUIRED_REVIEWERS . ' reviewer.',
            'reviewer_ids.*.distinct' => 'Reviewer yang dipilih tidak boleh sama.',
            'reviewer_ids.*.exists' => 'Reviewer yang dipilih tidak ditemukan.',
        ];
    }
}
