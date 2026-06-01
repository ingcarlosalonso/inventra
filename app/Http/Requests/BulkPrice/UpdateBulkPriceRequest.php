<?php

namespace App\Http\Requests\BulkPrice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBulkPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric'],
            'product_type_id' => ['nullable', 'string', 'exists:tenant.product_types,uuid'],
            'only_active' => ['boolean'],
        ];
    }
}
