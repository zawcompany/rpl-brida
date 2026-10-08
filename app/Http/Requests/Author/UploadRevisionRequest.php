<?php

namespace App\Http\Requests\Author;

use App\Models\Manuscript;
use Illuminate\Foundation\Http\FormRequest;

class UploadRevisionRequest extends FormRequest
{
    /** Hanya pemilik naskah yang boleh mengunggah revisi. */
    public function authorize(): bool
    {
        $manuscript = $this->route('manuscript');

        return $manuscript instanceof Manuscript && $manuscript->author_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'revision_file'   => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'author_response' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'revision_file'   => 'berkas revisi',
            'author_response' => 'catatan tanggapan penulis',
        ];
    }

    public function messages(): array
    {
        return [
            'required'              => ':attribute wajib diisi.',
            'revision_file.mimes'   => 'Berkas revisi harus berformat PDF atau DOCX.',
            'revision_file.max'     => 'Ukuran berkas revisi maksimal 10 MB.',
            'revision_file.uploaded' => 'Berkas gagal diunggah. Pastikan ukurannya tidak melebihi 10 MB.',
            'author_response.min'   => 'Catatan tanggapan minimal :min karakter; jelaskan perbaikan yang dilakukan.',
        ];
    }
}
