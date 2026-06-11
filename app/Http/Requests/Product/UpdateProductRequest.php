<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'product_type_id' => ['required', 'string', 'exists:tenant.product_types,uuid'],
            'currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'is_active' => ['boolean'],
            'barcodes' => ['nullable', 'array'],
            'barcodes.*' => [
                'required',
                'string',
                'max:255',
                'distinct',
                Rule::unique('tenant.barcodes', 'barcode')
                    ->where('product_id', '!=', $product->id),
            ],
            'presentations' => ['required', 'array', 'min:1'],
            'presentations.*.presentation_id' => ['required', 'string', 'exists:tenant.presentations,uuid', 'distinct'],
            'presentations.*.price' => ['required', 'numeric', 'min:0'],
            'presentations.*.min_stock' => ['required', 'numeric', 'min:0'],
        ];
    }
}
