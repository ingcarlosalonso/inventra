<?php

namespace App\Http\Requests\CashMovementType;

use Illuminate\Foundation\Http\FormRequest;

class IndexCashMovementTypeRequest extends FormRequest
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
