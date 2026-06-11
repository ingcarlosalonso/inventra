<?php

namespace App\Http\Requests\ProductMovementType;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductMovementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tenant.product_movement_types,name'],
            'is_income' => ['required', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
