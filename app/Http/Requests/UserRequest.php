<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:75'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(Role::pluck('name'))],
            'barangay_id' => ['required_if:role,barangayofficial', 'nullable', 'exists:barangays,id'],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'confirmed', 'min:8'],
        ];
    }
}