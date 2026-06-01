<?php

namespace App\Http\Requests\DailyCash;

use Illuminate\Foundation\Http\FormRequest;

class IndexDailyCashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'is_closed' => ['nullable', 'boolean'],
        ];
    }
}
