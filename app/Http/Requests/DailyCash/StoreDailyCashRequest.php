<?php

namespace App\Http\Requests\DailyCash;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyCashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'point_of_sale_id.unique' => __('daily_cashes.already_open_for_pos'),
        ];
    }

    public function rules(): array
    {
        return [
            'point_of_sale_id' => [
                'required', 'string', 'exists:tenant.points_of_sale,uuid',
                Rule::unique('tenant.daily_cashes', 'point_of_sale_id')
                    ->where(fn ($q) => $q->where('is_closed', false)->whereNull('deleted_at')),
            ],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opened_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
