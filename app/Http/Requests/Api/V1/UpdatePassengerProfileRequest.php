<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePassengerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'tourist';
    }

    public function rules(): array
    {
        return [
            'name'                    => 'nullable|string|max:100',
            'masked_alias'            => 'nullable|string|max:40',
            'preferred_lang'          => 'nullable|string|max:10',
            'emergency_contact_phone' => 'nullable|string|min:7|max:20',
        ];
    }
}
