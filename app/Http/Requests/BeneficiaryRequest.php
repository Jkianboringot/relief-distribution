<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiaryRequest extends FormRequest
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
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')],
            'first_name' => ['required', 'string', 'min:2', 'max:75'],
            'middle_name' => ['nullable', 'string', 'max:75'],
            'last_name' => ['required', 'string', 'min:2', 'max:75'],
            'birthdate' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'address' => ['required', 'string', 'min:3', 'max:150'],
            'household_members' => ['required', 'integer', 'min:1', 'max:50'],
            // qr_code, status, and registered_by are server-set — never user input
        ];
    }
}