<?php

namespace App\Http\Requests\Editor;

use App\Models\Manuscript;
use App\Rules\SafeDocument;
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
            'decision' => ['required', Rule::in(array_keys(Manuscript::DECISION_STATUS))],
            // Author berhak tahu alasannya bila naskah diminta revisi atau ditolak.
            'editorial_note' => ['required_unless:decision,diterima', 'nullable', 'string', 'max:5000'],
            // Lampiran opsional untuk penulis, disiapkan editor sendiri (bukan berkas reviewer yang diteruskan).
            'decision_file' => ['nullable', 'file', new SafeDocument(['pdf', 'docx']), 'max:'.config('simpil.upload.manuscript_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan akhir wajib dipilih.',
            'decision.in' => 'Keputusan tidak valid.',
            'editorial_note.required_unless' => 'Catatan editorial wajib diisi untuk revisi atau penolakan.',
            'decision_file.max' => 'Ukuran lampiran untuk penulis maksimal 10 MB.',
            'decision_file.uploaded' => 'Lampiran gagal diunggah. Pastikan ukurannya tidak melebihi 10 MB.',
        ];
    }
}
