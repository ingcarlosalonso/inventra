<?php

namespace App\Http\Requests\PointOfSale;

use Illuminate\Foundation\Http\FormRequest;

class IndexPointOfSaleRequest extends FormRequest
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
