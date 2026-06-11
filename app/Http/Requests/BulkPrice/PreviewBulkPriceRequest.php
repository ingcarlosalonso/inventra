<?php

namespace App\Http\Requests\BulkPrice;

use Illuminate\Foundation\Http\FormRequest;

class PreviewBulkPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_type_id' => ['nullable', 'string', 'exists:tenant.product_types,uuid'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
