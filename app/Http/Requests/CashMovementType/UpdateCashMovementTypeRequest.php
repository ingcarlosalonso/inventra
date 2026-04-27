<?php

namespace App\Http\Requests\CashMovementType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashMovementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('cash_movement_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:tenant.cash_movement_types,name,{$id}"],
            'is_income' => ['required', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
