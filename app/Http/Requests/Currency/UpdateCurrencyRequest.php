<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('currency')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:tenant.currencies,name,{$id}"],
            'symbol' => ['required', 'string', 'max:10'],
            'iso_code' => ['required', 'string', 'max:3', "unique:tenant.currencies,iso_code,{$id}"],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
