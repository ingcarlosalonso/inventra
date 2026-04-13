<?php

namespace App\Http\Requests\PresentationType;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePresentationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('presentation_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:tenant.presentation_types,name,{$id}"],
            'abbreviation' => ['required', 'string', 'max:20', "unique:tenant.presentation_types,abbreviation,{$id}"],
            'is_active' => ['boolean'],
        ];
    }
}
