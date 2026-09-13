<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:2', 'max:75'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(['lgustaff', 'barangayofficial'])],
            'barangay_id' => [
                Rule::requiredIf(fn () => $this->input('role') === 'barangayofficial'),
                'nullable',
                Rule::exists('barangays', 'id'),
            ],
            // required on create, optional on update (leave blank to keep current password)
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }
}