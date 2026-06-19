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
        $existingBarcodeIds = $this->route('product')
            ->barcodes()
            ->pluck('barcodes.id')
            ->toArray();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'string', 'exists:tenant.brands,uuid'],
            'product_type_id' => ['required', 'string', 'exists:tenant.product_types,uuid'],
            'currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'is_active' => ['boolean'],
            'presentations' => ['required', 'array', 'min:1'],
            'presentations.*.presentation_id' => ['required', 'string', 'exists:tenant.presentations,uuid', 'distinct'],
            'presentations.*.price' => ['required', 'numeric', 'min:0'],
            'presentations.*.min_stock' => ['required', 'numeric', 'min:0'],
            'presentations.*.barcodes' => ['nullable', 'array'],
            'presentations.*.barcodes.*' => [
                'required',
                'string',
                'max:255',
                'distinct',
                Rule::unique('tenant.barcodes', 'barcode')->whereNotIn('id', $existingBarcodeIds),
            ],
        ];
    }
}
