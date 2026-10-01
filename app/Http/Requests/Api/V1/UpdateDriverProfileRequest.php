<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'driver';
    }

    public function rules(): array
    {
        $driverId = $this->user()?->driverProfile?->id;

        return [
            'vehicle_number' => 'nullable|string|max:25|unique:driver_profiles,vehicle_number,' . $driverId,
            'vehicle_type'   => 'nullable|in:auto,e_rickshaw,cab,bike',
            'license_number' => 'nullable|string|max:50',
            'rc_photo'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'fcm_token'      => 'nullable|string',
            'name'           => 'nullable|string|max:100',
        ];
    }
}
