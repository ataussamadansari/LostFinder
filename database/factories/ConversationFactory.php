<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Journey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $journey = Journey::factory()->create();

        return [
            'uuid' => Str::uuid(),
            'journey_id' => $journey->id,
            'status' => 'active',
            'last_message_at' => now(),
        ];
    }

    /**
     * Indicate that the conversation is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
}