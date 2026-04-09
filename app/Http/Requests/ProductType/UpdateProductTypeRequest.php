<?php

namespace App\Http\Requests\ProductType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:tenant.product_types,name,{$id}"],
            'is_active' => ['boolean'],
            'parent_id' => ['nullable', 'string', 'exists:tenant.product_types,uuid'],
        ];
    }
}
