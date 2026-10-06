<?php

namespace Database\Factories;

use App\Models\LostItemTicket;
use App\Models\Journey;
use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\LostItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LostItemTicket>
 */
class LostItemTicketFactory extends Factory
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
        $driver = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $lostItem = LostItem::factory()->create();

        return [
            'ticket_number' => 'TKT-' . strtoupper($this->faker->bothify('????-#####')),
            'journey_id' => $journey->id,
            'passenger_id' => $passenger->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'lost_item_id' => $lostItem->id,
            'status' => 'driver_notified',
            'reported_at' => now(),
            'closed_at' => null,
        ];
    }

    /**
     * Indicate that the ticket is found.
     */
    public function found(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'found',
        ]);
    }

    /**
     * Indicate that the ticket is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}