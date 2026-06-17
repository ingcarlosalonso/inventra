<?php

namespace App\Http\Requests\ReleaseRead;

use App\Models\Release;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReleaseReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['uuid' => $this->route('uuid')]);
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid', Rule::exists(Release::class, 'uuid')],
        ];
    }
}
