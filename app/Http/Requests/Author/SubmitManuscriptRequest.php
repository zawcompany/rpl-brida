<?php

namespace App\Http\Requests\Author;

use Illuminate\Foundation\Http\FormRequest;

class SubmitManuscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware role:author
    }

    /** Buang baris penulis pendamping yang kosong sepenuhnya (nama & email kosong). */
    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('co_authors', []))
            ->filter(fn ($row) => is_array($row) && (filled($row['name'] ?? null) || filled($row['email'] ?? null)))
            ->values()
            ->all();

        $this->merge(['co_authors' => $rows]);
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'research_field_id'    => ['required', 'integer', 'exists:research_fields,id'],
            'abstract'             => ['required', 'string', 'max:5000'],
            'keywords'             => ['required', 'string', 'max:255'],
            'co_authors'           => ['nullable', 'array', 'max:10'],
            'co_authors.*.name'    => ['required', 'string', 'max:255'],
            'co_authors.*.email'   => ['nullable', 'email', 'max:255'],
            'file'                 => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'             => 'judul naskah',
            'research_field_id' => 'bidang keahlian',
            'abstract'          => 'abstrak',
            'keywords'          => 'kata kunci',
            'co_authors.*.name' => 'nama penulis pendamping',
            'co_authors.*.email' => 'email penulis pendamping',
            'file'              => 'berkas naskah',
        ];
    }

    public function messages(): array
    {
        return [
            'required'    => ':attribute wajib diisi.',
            'file.mimes'  => 'Berkas naskah harus berformat PDF atau DOCX.',
            'file.max'    => 'Ukuran berkas naskah maksimal 10 MB.',
            'file.uploaded' => 'Berkas gagal diunggah. Pastikan ukurannya tidak melebihi 10 MB.',
            'exists'      => ':attribute tidak valid.',
            'email'       => ':attribute tidak valid.',
        ];
    }
}
