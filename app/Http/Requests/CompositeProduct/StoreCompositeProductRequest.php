<?php

namespace App\Http\Requests\CompositeProduct;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompositeProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:tenant.products,uuid'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
