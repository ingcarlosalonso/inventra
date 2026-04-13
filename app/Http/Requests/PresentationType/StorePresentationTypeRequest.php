<?php

namespace App\Http\Requests\PresentationType;

use Illuminate\Foundation\Http\FormRequest;

class StorePresentationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tenant.presentation_types,name'],
            'abbreviation' => ['required', 'string', 'max:20', 'unique:tenant.presentation_types,abbreviation'],
            'is_active' => ['boolean'],
        ];
    }
}
