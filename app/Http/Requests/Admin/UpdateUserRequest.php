<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Validasi ubah pengguna: email unik kecuali miliknya sendiri, password opsional. */
class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        return $this->baseRules() + [
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', Password::min(8)],
        ];
    }
}
