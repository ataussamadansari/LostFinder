<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conversation = Conversation::factory()->create();
        $sender = User::factory()->create([
            'role' => $this->faker->randomElement(['tourist', 'driver']),
            'status' => 'active',
        ]);

        return [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'message_type' => $this->faker->randomElement(['text', 'image', 'audio']),
            'body' => $this->faker->text(),
            'media_id' => null,
            'is_read' => false,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the message is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}