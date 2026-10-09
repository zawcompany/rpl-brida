<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Ubah password sendiri: wajib password saat ini; error masuk ke error bag terpisah ('password'). */
class ChangePasswordRequest extends FormRequest
{
    protected $errorBag = 'password';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['current_password' => 'password saat ini', 'password' => 'password baru'];
    }

    public function messages(): array
    {
        return [
            'required'           => ':attribute wajib diisi.',
            'current_password'   => 'Password saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ];
    }
}
