<?php

namespace App\Http\Requests\PointOfSale;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePointOfSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('pointOfSale')?->id;

        return [
            'number' => ['required', 'integer', 'min:1', 'max:999', "unique:tenant.points_of_sale,number,{$id}"],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'auto_open_time' => ['nullable', 'date_format:H:i'],
            'auto_close_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
