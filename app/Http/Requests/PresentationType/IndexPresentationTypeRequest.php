<?php

namespace App\Http\Requests\PresentationType;

use Illuminate\Foundation\Http\FormRequest;

class IndexPresentationTypeRequest extends FormRequest
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
