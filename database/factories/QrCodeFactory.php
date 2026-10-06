<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vehicle = Vehicle::factory()->create();

        return [
            'vehicle_id' => $vehicle->id,
            'token' => $this->faker->unique()->bothify('????????????????'),
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that the QR code is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'revoked_at' => null,
        ]);
    }

    /**
     * Indicate that the QR code is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }
}