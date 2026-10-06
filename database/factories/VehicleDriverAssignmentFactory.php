<?php

namespace Database\Factories;

use App\Models\VehicleDriverAssignment;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleDriverAssignment>
 */
class VehicleDriverAssignmentFactory extends Factory
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
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'ended_at' => null,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the assignment is ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
            'status' => 'ended',
        ]);
    }
}