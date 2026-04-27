<?php

namespace App\Http\Requests\SaleState;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('sale_state')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:tenant.sale_states,name,{$id}"],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_default' => ['boolean'],
            'is_final_state' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }
}
