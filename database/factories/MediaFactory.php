<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => $this->faker->randomElement(['public', 'private']),
            'path' => $this->faker->uuid() . '.' . $this->faker->fileExtension(),
            'original_name' => $this->faker->word() . '.' . $this->faker->fileExtension(),
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'application/pdf', 'text/plain']),
            'size' => $this->faker->numberBetween(1000, 10000000),
            'uploaded_by' => null,
            'entity_type' => $this->faker->randomElement(['user_profile', 'driver_document', 'vehicle_document', 'lost_item_photo', 'message_attachment']),
            'entity_id' => null,
        ];
    }
}