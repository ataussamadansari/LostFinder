<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'driver';
    }

    public function rules(): array
    {
        return [
            'claim_status' => 'required|in:searching,found,disputed',
        ];
    }

    public function messages(): array
    {
        return [
            'claim_status.required' => 'Claim status is required.',
            'claim_status.in'       => 'Claim status must be searching, found, or disputed.',
        ];
    }
}
