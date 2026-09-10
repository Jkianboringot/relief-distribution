<?php

namespace App\Http\Requests;

use App\Enums\barangayType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class barangayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:75', 'min:3', Rule::unique('barangays', 'name')->ignore($this->route('barangay'))],
            'location' => ['nullable', 'string', 'max:100', 'min:3'],
            'barangay_type' => ['required', new Enum(barangayType::class)],
        ];
    }
}
