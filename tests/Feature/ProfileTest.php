<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_and_update_profile(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Aditi Sharma',
            'role' => 'tourist',
            'status' => 'active',
        ]);
        $user->profile()->create([
            'first_name' => 'Aditi',
            'last_name' => 'Sharma',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        // GET /api/v1/profile
        $getResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile');

        $getResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Aditi Sharma',
                    'profile' => [
                        'first_name' => 'Aditi',
                        'last_name' => 'Sharma',
                    ],
                ],
            ]);

        // PATCH /api/v1/profile
        $patchResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/profile', [
                'first_name' => 'Aditi',
                'last_name' => 'Verma',
                'gender' => 'female',
            ]);

        $patchResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Aditi Verma',
                    'profile' => [
                        'first_name' => 'Aditi',
                        'last_name' => 'Verma',
                        'gender' => 'female',
                    ],
                ],
            ]);
    }

    public function test_user_can_upload_and_delete_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Aditi Sharma',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        // Upload photo
        $uploadResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/profile/photo', [
                'photo' => $file,
            ]);

        $uploadResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile photo uploaded successfully.',
            ]);

        $this->assertNotNull($user->fresh()->profile->profile_photo_id);

        // Delete photo
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/profile/photo');

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile photo removed successfully.',
            ]);

        $this->assertNull($user->fresh()->profile->profile_photo_id);
    }

    public function test_user_can_register_and_delete_device(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Aditi Sharma',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        // Register device
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices', [
                'device_id' => 'iphone-device-id-999',
                'platform' => 'ios',
                'push_token' => 'apns-token-xyz',
                'app_version' => '1.0.0',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'device_id' => 'iphone-device-id-999',
                    'platform' => 'ios',
                ],
            ]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'iphone-device-id-999',
        ]);

        // Delete device
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/devices/iphone-device-id-999');

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Device unregistered successfully.',
            ]);

        $this->assertDatabaseMissing('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'iphone-device-id-999',
        ]);
    }
}
