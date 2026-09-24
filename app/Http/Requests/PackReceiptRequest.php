<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_name' => ['required', 'string', 'max:150'],
            'date_received' => ['date'],

            'reliefList' => ['required', 'array', 'max:999', 'min:1'],
            'reliefList.*.relief_pack_id' => ['required', 'exists:relief_packs,id'],
            'reliefList.*.quantity' => ['required', 'max:999', 'min:1', 'numeric'],
        ];
    }
}