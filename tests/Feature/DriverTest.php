<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_apply_as_driver(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Ravi Kumar',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/driver/apply');

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Driver application submitted successfully. Please submit your verification documents.',
                'data' => [
                    'verification_status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('drivers', [
            'user_id' => $user->id,
            'verification_status' => 'pending',
        ]);

        $this->assertEquals('driver', $user->fresh()->role);
    }

    public function test_driver_can_get_profile(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Ravi Kumar',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_code' => 'DRV-ABC123',
            'verification_status' => 'verified',
            'rating_avg' => 4.85,
            'rating_count' => 12,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/driver/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'driver_code' => 'DRV-ABC123',
                    'verification_status' => 'verified',
                    'rating_avg' => 4.85,
                    'rating_count' => 12,
                    'is_verified' => true,
                ],
            ]);
    }

    public function test_driver_can_upload_kyc_document(): void
    {
        Storage::fake('private');

        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Ravi Kumar',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_code' => 'DRV-KYC001',
            'verification_status' => 'pending',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->create('driving_license.pdf', 500, 'application/pdf');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/driver/documents', [
                'document_type' => 'driving_license',
                'document_number' => 'DL-KA01-202300099',
                'file' => $file,
                'expires_at' => now()->addYears(5)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Document uploaded successfully and queued for verification.',
                'data' => [
                    'document_type' => 'driving_license',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('driver_documents', [
            'driver_id' => $driver->id,
            'document_type' => 'driving_license',
            'document_number' => 'DL-KA01-202300099',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('media', [
            'disk' => 'private',
            'visibility' => 'private',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_suspended_user_blocked_from_driver_routes(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Suspended Driver',
            'role' => 'driver',
            'status' => 'suspended',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/driver/profile');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account has been suspended or blocked. Please contact support.',
            ]);
    }
}
