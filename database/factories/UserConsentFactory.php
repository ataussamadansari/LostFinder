<?php

namespace Database\Factories;

use App\Models\UserConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConsent>
 */
class UserConsentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'consent_type' => $this->faker->randomElement(['share_contact', 'marketing', 'analytics']),
            'is_granted' => $this->faker->boolean(),
            'granted_at' => now(),
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that consent is granted.
     */
    public function granted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_granted' => true,
            'revoked_at' => null,
        ]);
    }
}