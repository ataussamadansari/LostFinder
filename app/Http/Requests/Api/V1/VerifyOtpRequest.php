<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'        => 'required|string|min:7|max:15',
            'country_code' => 'nullable|string|max:5',
            'otp'          => 'required|string|min:4|max:8',
            'role'         => 'required|in:driver,tourist',
            'name'         => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'otp.required'   => 'OTP code is required.',
            'role.required'  => 'User role is required (driver or tourist).',
            'role.in'        => 'Role must be either driver or tourist.',
        ];
    }
}
