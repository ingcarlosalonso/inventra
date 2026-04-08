<?php

namespace App\Http\Requests\ProductType;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tenant.product_types,name'],
            'is_active' => ['boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:tenant.product_types,id'],
        ];
    }
}
