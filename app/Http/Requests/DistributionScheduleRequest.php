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
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'barangay' => 'required|string|max:255',
            'relief_pack_id' => 'required|exists:relief_packs,id',
            'planned_quantity' => 'required|integer|min:1',
        ];
    }
}