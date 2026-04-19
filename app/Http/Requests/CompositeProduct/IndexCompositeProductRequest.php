<?php

namespace App\Http\Requests\CompositeProduct;

use Illuminate\Foundation\Http\FormRequest;

class IndexCompositeProductRequest extends FormRequest
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
