<?php

namespace App\Http\Requests\Reviewer;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

/** Tolak penugasan review: alasan wajib agar editor dapat menugaskan reviewer lain. */
class DeclineReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && ($this->user()?->can('respond', $review) ?? false);
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:1000']];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.min'      => 'Alasan penolakan minimal :min karakter.',
        ];
    }
}
