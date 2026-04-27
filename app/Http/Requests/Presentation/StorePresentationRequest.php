<?php

namespace App\Http\Requests\Presentation;

use Illuminate\Foundation\Http\FormRequest;

class StorePresentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'presentation_type_id' => ['required', 'string', 'exists:tenant.presentation_types,uuid'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'is_active' => ['boolean'],
        ];
    }
}
