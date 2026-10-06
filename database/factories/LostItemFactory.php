<?php

namespace Database\Factories;

use App\Models\LostItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LostItem>
 */
class LostItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create([
            'role' => 'tourist',
            'status' => 'active',
        ]);

        return [
            'user_id' => $user->id,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'category' => $this->faker->randomElement(['electronics', 'documents', 'clothing', 'accessories', 'other']),
            'lost_time' => now()->subDay($this->faker->numberBetween(0, 30)),
            'location' => $this->faker->city() . ' ' . $this->faker->streetAddress(),
            'status' => 'open',
            'photo_id' => null,
        ];
    }
}