<?php

namespace App\Http\Requests\ProductMovementType;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductMovementTypeRequest extends FormRequest
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
