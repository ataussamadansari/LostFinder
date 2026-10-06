<?php

namespace Database\Factories;

use App\Models\ConversationParticipant;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationParticipant>
 */
class ConversationParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create([
            'role' => $this->faker->randomElement(['tourist', 'driver']),
            'status' => 'active',
        ]);

        return [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'role' => $user->role,
            'joined_at' => now(),
            'left_at' => null,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the participant has left.
     */
    public function left(): static
    {
        return $this->state(fn (array $attributes) => [
            'left_at' => now(),
            'status' => 'left',
        ]);
    }
}