<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributionScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],

            'reliefList' => ['required', 'array', 'min:1'],
            'reliefList.*.relief_pack_id' => ['required', 'integer', 'exists:relief_packs,id', 'distinct'],
            'reliefList.*.quantity' => ['required', 'integer', 'min:1'],
            'reliefList.*.entitlement_per_beneficiary' => ['nullable', 'integer', 'min:1'],
        ];
    }
}