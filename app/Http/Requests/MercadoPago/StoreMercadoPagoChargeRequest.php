<?php

namespace App\Http\Requests\MercadoPago;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreMercadoPagoChargeRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
