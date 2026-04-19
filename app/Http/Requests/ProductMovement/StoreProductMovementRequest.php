<?php

namespace App\Http\Requests\ProductMovement;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_presentation_id' => ['required', 'string', 'exists:tenant.product_presentations,uuid'],
            'product_movement_type_id' => ['required', 'string', 'exists:tenant.product_movement_types,uuid'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
