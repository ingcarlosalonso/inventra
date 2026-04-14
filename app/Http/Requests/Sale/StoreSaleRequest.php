<?php

namespace App\Http\Requests\Sale;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'exists:tenant.clients,uuid'],
            'point_of_sale_id' => ['required', 'string', 'exists:tenant.points_of_sale,uuid'],
            'sale_state_id' => ['nullable', 'string', 'exists:tenant.sale_states,uuid'],
            'daily_cash_id' => ['nullable', 'string', 'exists:tenant.daily_cashes,uuid'],
            'currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_presentation_id' => ['required', 'string', 'exists:tenant.product_presentations,uuid', 'distinct'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],

            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'string', 'exists:tenant.payment_methods,uuid'],
            'payments.*.currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }
}
