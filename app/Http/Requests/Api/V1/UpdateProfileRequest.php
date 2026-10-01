<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name'                    => 'nullable|string|max:100',
            'email'                   => 'nullable|email|max:120|unique:users,email,' . $userId,
            'emergency_contact_phone' => 'nullable|string|min:7|max:20',
            'profile_picture'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ];
    }
}
