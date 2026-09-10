<?php

namespace App\Http\Requests\PointOfSale;

use Illuminate\Foundation\Http\FormRequest;

class AssignMercadoPagoTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'terminal_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
