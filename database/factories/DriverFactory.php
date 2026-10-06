<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create([
            'role' => 'driver',
            'status' => 'active',
        ]);

        return [
            'user_id' => $user->id,
            'driver_code' => 'DRV-' . strtoupper($this->faker->bothify('???-#####')),
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rating_avg' => $this->faker->randomFloat(2, 3.0, 5.0),
            'rating_count' => $this->faker->numberBetween(5, 50),
        ];
    }

    /**
     * Indicate that the driver is pending verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
            'verified_at' => null,
        ]);
    }

    /**
     * Indicate that the driver is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'suspended',
        ]);
    }
}