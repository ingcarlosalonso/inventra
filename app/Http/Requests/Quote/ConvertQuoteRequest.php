<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

class ConvertQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'point_of_sale_id' => ['required', 'string', 'exists:tenant.points_of_sale,uuid'],
            'sale_state_id' => ['nullable', 'string', 'exists:tenant.sale_states,uuid'],
            'daily_cash_id' => ['nullable', 'string', 'exists:tenant.daily_cashes,uuid'],

            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'string', 'exists:tenant.payment_methods,uuid'],
            'payments.*.currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }
}
