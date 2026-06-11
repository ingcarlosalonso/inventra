<?php

namespace App\Http\Requests\CashMovement;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_movement_type_id' => ['required', 'string', 'exists:tenant.cash_movement_types,uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
