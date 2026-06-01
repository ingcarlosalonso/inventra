<?php

namespace App\Http\Requests\OrderState;

use Illuminate\Foundation\Http\FormRequest;

class IndexOrderStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
