<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MunicipalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $municipality = $this->route('municipality');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:75',
                Rule::unique('municipalities', 'name')->ignore($municipality?->id),
            ],
        ];
    }
}
