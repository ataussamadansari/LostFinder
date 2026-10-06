<?php

namespace Database\Factories;

use App\Models\VehicleDocument;
use App\Models\Vehicle;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleDocument>
 */
class VehicleDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vehicle = Vehicle::factory()->create();
        $media = Media::factory()->create([
            'disk' => 'private',
        ]);

        return [
            'vehicle_id' => $vehicle->id,
            'media_id' => $media->id,
            'document_type' => $this->faker->randomElement(['rc_book', 'insurance', 'permit', 'tax_receipt']),
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