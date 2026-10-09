<?php

namespace App\Rules;

use App\Services\Files\DocumentInspector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Aturan validasi unggah dokumen yang ketat (ekstensi asli + MIME asli + isi berkas).
 * Pemakaian di FormRequest: ['required', 'file', new SafeDocument(['pdf', 'docx']), 'max:10240']
 */
class SafeDocument implements ValidationRule
{
    /** @param string[] $allowed */
    public function __construct(private readonly array $allowed)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(':attribute harus berupa berkas.');

            return;
        }

        if ($reason = app(DocumentInspector::class)->inspect($value, $this->allowed)) {
            $fail($reason);
        }
    }
}
