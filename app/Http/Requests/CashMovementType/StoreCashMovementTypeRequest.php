<?php

namespace App\Http\Requests\CashMovementType;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashMovementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tenant.cash_movement_types,name'],
            'is_income' => ['required', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
