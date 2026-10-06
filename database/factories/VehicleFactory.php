<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'vehicle_code' => 'VEH-' . strtoupper($this->faker->bothify('???-#####')),
            'registration_number' => $this->faker->bothify('KA##AB####'),
            'vehicle_type' => $this->faker->randomElement(['cab', 'auto', 'bike', 'van']),
            'make' => $this->faker->randomElement(['Maruti Suzuki', 'Hyundai', 'Toyota', 'Honda', 'Mahindra']),
            'model' => $this->faker->randomElement(['Dzire', 'Aura', 'Etios', 'City', 'Thar']),
            'color' => $this->faker->randomElement(['White', 'Black', 'Silver', 'Red', 'Blue']),
            'verification_status' => 'verified',
            'verified_at' => now(),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the vehicle is pending verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
            'verified_at' => null,
        ]);
    }

    /**
     * Indicate that the vehicle is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
        ]);
    }
}