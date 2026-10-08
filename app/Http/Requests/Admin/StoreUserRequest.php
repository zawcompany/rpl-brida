<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Validasi tambah pengguna. UpdateUserRequest memakai ulang aturan dasar dari sini. */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules() + [
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    /** @return array<string, mixed> aturan yang sama untuk tambah & ubah */
    protected function baseRules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'role'        => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
            'institution' => ['nullable', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap', 'email' => 'email', 'password' => 'password',
            'role' => 'role', 'institution' => 'instansi', 'phone' => 'nomor telepon',
        ];
    }

    public function messages(): array
    {
        return [
            'required'     => ':attribute wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone.regex'  => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'password.min' => 'Password minimal :min karakter.',
        ];
    }
}
