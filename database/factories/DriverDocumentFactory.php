<?php

namespace Database\Factories;

use App\Models\DriverDocument;
use App\Models\Driver;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverDocument>
 */
class DriverDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $driver = Driver::factory()->create();
        $media = Media::factory()->create([
            'disk' => 'private',
        ]);

        return [
            'driver_id' => $driver->id,
            'media_id' => $media->id,
            'document_type' => $this->faker->randomElement(['aadhar', 'pan', 'driving_license', 'passport']),
            'status' => 'pending',
            'uploaded_at' => now(),
            'verified_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Indicate that the document is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }
}