<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:tenant.users,email,{$userId}"],
            'password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:tenant.roles,id'],
            'is_active' => ['boolean'],
        ];
    }
}
