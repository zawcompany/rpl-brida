<?php

namespace App\Http\Requests\Editor;

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
            'reviewer_id' => ['required', Rule::exists('users', 'id')->where('role', 'Reviewer')],
            'due_at'      => ['nullable', 'date', 'after_or_equal:today'],
            'editor_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_at.after_or_equal'   => 'Tenggat waktu tidak boleh sebelum hari ini.',
            'reviewer_id.required' => 'Pilih reviewer untuk review ulang.',
            'reviewer_id.exists'   => 'Reviewer yang dipilih tidak ditemukan.',
        ];
    }
}
