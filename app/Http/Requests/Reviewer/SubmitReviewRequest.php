<?php

namespace App\Http\Requests\Reviewer;

use App\Models\Review;
use App\Rules\SafeDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Kirim hasil review: rubrik, komentar, rekomendasi, dan lampiran opsional. UpdateReviewRequest memakai aturan yang sama. */
class SubmitReviewRequest extends FormRequest
{
    /** Hanya reviewer pemilik penugasan (ReviewPolicy). Aturan status ada di ReviewerService. */
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && ($this->user()?->can('respond', $review) ?? false);
    }

    public function rules(): array
    {
        $rubric = collect(Review::RUBRIC)->mapWithKeys(
            fn (string $label, string $key) => [$key => ['required', Rule::in(array_keys(Review::LEVELS))]]
        )->all();

        return $rubric + [
            'comments'       => ['required', 'string', 'min:10', 'max:5000'],
            'recommendation' => ['required', Rule::in(array_keys(Review::RECOMMENDATION_LABELS))],
            'review_file'    => ['nullable', 'file', new SafeDocument(['pdf', 'docx']), 'max:' . config('simpil.upload.manuscript_kb')],
        ];
    }

    public function attributes(): array
    {
        return Review::RUBRIC + [
            'comments'       => 'komentar reviewer',
            'recommendation' => 'rekomendasi',
            'review_file'    => 'berkas catatan review',
        ];
    }

    public function messages(): array
    {
        return [
            'required'       => ':attribute wajib diisi.',
            'in'             => ':attribute tidak valid.',
            'comments.min'   => 'Komentar reviewer minimal :min karakter agar bermanfaat bagi penulis.',
            'review_file.max' => 'Ukuran berkas catatan review maksimal 10 MB.',
        ];
    }
}
