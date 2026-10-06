<?php

namespace Database\Factories;

use App\Models\Journey;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Journey>
 */
class JourneyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $driver = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create();

        return [
            'uuid' => Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'ended_at' => null,
            'expires_at' => now()->addHours(71),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the journey is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the journey is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
            'status' => 'cancelled',
        ]);
    }
}