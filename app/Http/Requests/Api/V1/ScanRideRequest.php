<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ScanRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_token'       => 'required|string|exists:driver_profiles,qr_code_token',
            'scan_latitude'  => 'nullable|numeric|between:-90,90',
            'scan_longitude' => 'nullable|numeric|between:-180,180',
        ];
    }
}
