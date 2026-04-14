<?php

namespace App\Http\Requests\Quote;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'exists:tenant.clients,uuid'],
            'currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_presentation_id' => ['required', 'string', 'exists:tenant.product_presentations,uuid', 'distinct'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
