<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributionTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_code' => 'required|string|exists:beneficiaries,qr_code',
        ];
    }
}