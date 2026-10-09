<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ubah profil sendiri (semua role). Tidak ada id/role di input — hanya kolom whitelist. */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email]);
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone'                => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'institution'          => ['nullable', 'string', 'max:255'],
            'research_field_ids'   => ['nullable', 'array', 'max:10'],
            'research_field_ids.*' => ['integer', 'distinct', 'exists:research_fields,id'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'phone' => 'nomor telepon', 'institution' => 'instansi', 'research_field_ids' => 'bidang keahlian'];
    }

    public function messages(): array
    {
        return [
            'required'     => ':attribute wajib diisi.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'phone.regex'  => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
        ];
    }
}
