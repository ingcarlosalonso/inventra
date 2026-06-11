<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'string', 'exists:tenant.suppliers,uuid'],
            'daily_cash_id' => ['nullable', 'string', 'exists:tenant.daily_cashes,uuid'],
            'supplier_invoice' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'received_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_presentation_id' => ['required', 'string', 'exists:tenant.product_presentations,uuid', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
