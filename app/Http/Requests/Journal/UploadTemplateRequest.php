<?php

namespace App\Http\Requests\Journal;

use App\Models\Journal;
use App\Rules\SafeDocument;
use Illuminate\Foundation\Http\FormRequest;

/** Unggah template jurnal: Administrator, atau editor jurnal yang bersangkutan. */
class UploadTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user    = $this->user();
        $journal = $this->route('journal');

        return $user !== null
            && ($user->isAdmin() || ($journal instanceof Journal && in_array($journal->id, $user->journalIds(Journal::EDITOR), true)));
    }

    public function rules(): array
    {
        return [
            'template' => ['required', 'file', new SafeDocument(['docx', 'pdf']), 'max:' . config('simpil.upload.manuscript_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'template.required' => 'Pilih berkas template (DOCX atau PDF).',
            'template.max'      => 'Ukuran template maksimal 10 MB.',
        ];
    }
}
