<?php

namespace Database\Factories;

use App\Models\JourneyPassenger;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JourneyPassenger>
 */
class JourneyPassengerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $journey = Journey::factory()->create();
        $passenger = User::factory()->create([
            'role' => 'tourist',
            'status' => 'active',
        ]);

        return [
            'journey_id' => $journey->id,
            'passenger_id' => $passenger->id,
            'connected_at' => now(),
            'disconnected_at' => null,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the passenger is disconnected.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'disconnected_at' => now(),
            'status' => 'disconnected',
        ]);
    }
}