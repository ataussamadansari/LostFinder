<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.min'      => 'Phone number must be at least 7 digits.',
            'phone.max'      => 'Phone number may not be greater than 15 digits.',
        ];
    }
}
