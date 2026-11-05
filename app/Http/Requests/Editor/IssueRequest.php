<?php

namespace App\Http\Requests\Editor;

use App\Models\Issue;
use App\Models\Journal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pembuatan / perubahan edisi jurnal.
 */
class IssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'editor';
    }

    /** Editor yang hanya mengelola satu jurnal tidak perlu memilih jurnal. */
    protected function prepareForValidation(): void
    {
        $ids = $this->user()?->editableJournalIds();

        if (! $this->route('issue') && ! $this->filled('journal_id') && is_array($ids) && count($ids) === 1) {
            $this->merge(['journal_id' => $ids[0]]);
        }
    }

    public function rules(): array
    {
        /** @var Issue|null $issue */
        $issue = $this->route('issue');

        // Jurnal ditetapkan saat edisi dibuat dan tidak berpindah; pada perubahan, jurnal edisi dipakai apa adanya.
        $journalId = $issue?->journal_id ?? $this->input('journal_id');
        $allowed   = $this->user()?->editableJournalIds() ?? Journal::pluck('id')->all();

        return [
            'journal_id' => $issue ? [] : ['required', 'integer', Rule::in($allowed)],
            'volume' => ['required', 'integer', 'min:1', 'max:9999'],
            'number' => [
                'required', 'integer', 'min:1', 'max:9999',
                Rule::unique('issues')
                    ->where('journal_id', $journalId)
                    ->where('volume', $this->input('volume'))
                    ->where('year', $this->input('year'))
                    ->ignore($issue?->id),
            ],
            'year'   => ['required', 'integer', 'min:2000', 'max:2100'],
            'title'  => ['nullable', 'string', 'max:255'],
            // 'dimensions' memaksa isi berkas benar-benar gambar yang dapat didekode (bukan sekadar ekstensi) dan membatasi resolusi
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:' . config('simpil.upload.cover_kb'), 'dimensions:min_width=50,min_height=50,max_width=6000,max_height=6000'],
        ];
    }

    public function messages(): array
    {
        return [
            'journal_id.required' => 'Pilih jurnal untuk edisi ini.',
            'journal_id.in'       => 'Anda bukan editor di jurnal tersebut.',
            'number.unique' => 'Edisi dengan volume, nomor, dan tahun tersebut sudah ada.',
            'cover_image.image' => 'Sampul harus berupa gambar.',
            'cover_image.mimes' => 'Sampul harus berformat JPG, PNG, atau WebP.',
            'cover_image.max'   => 'Ukuran sampul maksimal 2 MB.',
            'cover_image.dimensions' => 'Berkas bukan gambar yang valid, atau resolusinya di luar batas (50–6000 piksel).',
            'cover_image.uploaded' => 'Sampul gagal diunggah. Pastikan ukurannya tidak melebihi 2 MB.',
        ];
    }
}
