<?php

namespace App\Http\Requests\Release;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'in:feature,fix,improvement,security,removal,deprecation'],
            'items.*.title' => ['required', 'string'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
