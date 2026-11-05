<?php

namespace App\Http\Requests\Admin;

use App\Models\ResearchField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/** Tambah / ubah nama bidang keahlian. Nama (dan slug turunannya) harus unik. */
class ResearchFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $current = $this->route('field');

        return [
            'name' => [
                'required', 'string', 'max:120',
                fn (string $attribute, mixed $value, \Closure $fail) => ResearchField::where('slug', Str::slug((string) $value))
                    ->when($current, fn ($q) => $q->whereKeyNot($current->id))
                    ->exists() ? $fail('Bidang keahlian dengan nama tersebut sudah ada.') : null,
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => is_string($this->name) ? trim(preg_replace('/\s+/', ' ', $this->name)) : $this->name]);
    }

    public function attributes(): array
    {
        return ['name' => 'nama bidang'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.'];
    }
}
