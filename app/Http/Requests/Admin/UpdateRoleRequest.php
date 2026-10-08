<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))]];
    }

    public function messages(): array
    {
        return ['role.*' => 'Pilih role yang valid.'];
    }
}
