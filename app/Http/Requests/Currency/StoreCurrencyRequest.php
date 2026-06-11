<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tenant.currencies,name'],
            'symbol' => ['required', 'string', 'max:10'],
            'iso_code' => ['required', 'string', 'max:3', 'unique:tenant.currencies,iso_code'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
