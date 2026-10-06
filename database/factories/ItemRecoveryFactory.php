<?php

namespace Database\Factories;

use App\Models\ItemRecovery;
use App\Models\LostItemTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemRecovery>
 */
class ItemRecoveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticket = LostItemTicket::factory()->create();
        $finder = User::factory()->create([
            'role' => 'tourist',
            'status' => 'active',
        ]);

        return [
            'ticket_id' => $ticket->id,
            'found_by' => $finder->id,
            'found_at' => now(),
            'handover_method' => $this->faker->randomElement(['direct', 'police', 'office']),
            'handover_at' => null,
            'passenger_confirmed' => false,
            'driver_confirmed' => false,
            'notes' => null,
        ];
    }

    /**
     * Indicate that both passenger and driver have confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'passenger_confirmed' => true,
            'driver_confirmed' => true,
            'handover_at' => now(),
        ]);
    }
}