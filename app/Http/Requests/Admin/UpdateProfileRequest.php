<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Edit profil admin yang sedang login (UC-05). Ganti password wajib menyertakan password saat ini. */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'         => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'current_password' => 'password saat ini', 'password' => 'password baru'];
    }

    public function messages(): array
    {
        return [
            'required'                 => ':attribute wajib diisi.',
            'required_with'            => 'Password saat ini wajib diisi untuk mengganti password.',
            'current_password'         => 'Password saat ini tidak sesuai.',
            'email.unique'             => 'Email sudah digunakan akun lain.',
            'password.confirmed'       => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}
