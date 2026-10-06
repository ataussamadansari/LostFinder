<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'phone_verified' => !is_null($this->phone_verified_at),
            'email_verified' => !is_null($this->email_verified_at),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'profile' => $this->whenLoaded('profile', function () {
                return [
                    'first_name' => $this->profile?->first_name,
                    'last_name' => $this->profile?->last_name,
                    'gender' => $this->profile?->gender,
                    'date_of_birth' => $this->profile?->date_of_birth?->toDateString(),
                    'photo_url' => $this->profile?->profilePhoto?->url,
                ];
            }, [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'gender' => $this->profile?->gender,
                'date_of_birth' => $this->profile?->date_of_birth?->toDateString(),
                'photo_url' => $this->profile?->profilePhoto?->url,
            ]),
            'driver' => $this->when($this->driver !== null, function () {
                return [
                    'driver_code' => $this->driver->driver_code,
                    'verification_status' => $this->driver->verification_status,
                    'verified_at' => $this->driver->verified_at?->toIso8601String(),
                    'rating_avg' => (float) $this->driver->rating_avg,
                    'rating_count' => (int) $this->driver->rating_count,
                    'is_verified' => $this->driver->isVerified(),
                ];
            }),
        ];
    }
}
