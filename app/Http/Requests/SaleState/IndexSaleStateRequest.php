<?php

namespace App\Http\Requests\SaleState;

use Illuminate\Foundation\Http\FormRequest;

class IndexSaleStateRequest extends FormRequest
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
