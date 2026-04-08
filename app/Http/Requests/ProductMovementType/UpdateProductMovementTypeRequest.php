<?php

namespace App\Http\Requests\ProductMovementType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductMovementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product_movement_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:tenant.product_movement_types,name,{$id}"],
            'is_income' => ['required', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
