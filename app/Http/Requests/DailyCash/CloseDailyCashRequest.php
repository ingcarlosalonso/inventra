<?php

namespace App\Http\Requests\DailyCash;

use Illuminate\Foundation\Http\FormRequest;

class CloseDailyCashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
