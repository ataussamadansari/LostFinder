<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyHandoverOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'driver';
    }

    public function rules(): array
    {
        return [
            'handover_otp' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'handover_otp.required' => 'Handover OTP is required.',
            'handover_otp.size'     => 'Handover OTP must be exactly 6 digits.',
        ];
    }
}
