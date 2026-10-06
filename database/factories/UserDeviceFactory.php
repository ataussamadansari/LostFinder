<?php

namespace Database\Factories;

use App\Models\UserDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserDevice>
 */
class UserDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'device_id' => $this->faker->uuid(),
            'device_name' => $this->faker->randomElement(['iPhone 14', 'Samsung Galaxy', 'Pixel 7', 'OnePlus 11']),
            'platform' => $this->faker->randomElement(['ios', 'android']),
            'push_token' => $this->faker->bothify('????-????-????-????-????'),
            'is_active' => true,
            'registered_at' => now(),
            'last_used_at' => now(),
        ];
    }

    /**
     * Indicate that the device is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}