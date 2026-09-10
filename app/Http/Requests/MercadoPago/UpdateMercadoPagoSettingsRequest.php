<?php

namespace App\Http\Requests\MercadoPago;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMercadoPagoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'string', 'exists:tenant.payment_methods,uuid'],
        ];
    }
}
