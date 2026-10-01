<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_number' => 'required|string|max:25|unique:driver_profiles,vehicle_number',
            'vehicle_type'   => 'required|in:auto,e_rickshaw,cab,bike',
            'license_number' => 'required|string|max:50',
            'rc_photo'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'fcm_token'      => 'nullable|string',
        ];
    }
}
