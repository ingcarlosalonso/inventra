<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function payableExistsRule(): Exists
    {
        $table = $this->input('payable_type') === 'sale' ? 'tenant.sales' : 'tenant.orders';

        return Rule::exists($table, 'uuid');
    }

    public function rules(): array
    {
        return [
            'payable_type' => ['required', 'string', Rule::in(['sale', 'order'])],
            'payable_id' => ['required', 'string', $this->payableExistsRule()],
            'payment_method_id' => ['required', 'string', 'exists:tenant.payment_methods,uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'daily_cash_id' => ['nullable', 'string', 'exists:tenant.daily_cashes,uuid'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
