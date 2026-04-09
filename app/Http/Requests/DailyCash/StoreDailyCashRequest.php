<?php

namespace App\Http\Requests\DailyCash;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyCashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'point_of_sale_id' => ['required', 'string', 'exists:tenant.points_of_sale,uuid'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opened_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
