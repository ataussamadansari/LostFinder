<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_otp(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '9876543210',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully.',
            ])
            ->assertJsonStructure([
                'data' => ['phone', 'resend_in'],
            ]);
    }

    public function test_request_otp_validates_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_can_verify_otp_and_registers_new_user(): void
    {
        $phone = '9876543210';
        $otpService = app(OtpService::class);
        $result = $otpService->requestOtp($phone);
        $otp = $result['debug_otp'];

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'otp' => $otp,
            'device_name' => 'Pixel 8',
            'platform' => 'android',
            'device_id' => 'device-uuid-123',
            'push_token' => 'fcm-token-abc',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Authenticated successfully.',
                'data' => [
                    'is_new_user' => true,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['uuid', 'phone', 'role', 'status'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '+919876543210',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('user_devices', [
            'device_id' => 'device-uuid-123',
            'platform' => 'android',
        ]);
    }

    public function test_verify_otp_fails_with_invalid_otp(): void
    {
        $phone = '9876543210';
        app(OtpService::class)->requestOtp($phone);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_verify_otp_rejects_suspended_user(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Suspended User',
            'role' => 'tourist',
            'status' => 'suspended',
        ]);

        $otpService = app(OtpService::class);
        $result = $otpService->requestOtp($user->phone);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $user->phone,
            'otp' => $result['debug_otp'],
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Active Passenger',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        // Test GET /me
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'phone' => '+919876543210',
                    'name' => 'Active Passenger',
                ],
            ]);

        // Test POST /logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        // Verify token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_user_can_update_profile_and_consents(): void
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'John Doe',
            'role' => 'tourist',
            'status' => 'active',
        ]);
        $user->profile()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $token = $user->createToken('test')->plainTextToken;

        // Update profile
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', [
                'first_name' => 'Jonathan',
                'last_name' => 'Smith',
                'gender' => 'male',
                'date_of_birth' => '1995-05-15',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Jonathan Smith',
                    'profile' => [
                        'first_name' => 'Jonathan',
                        'last_name' => 'Smith',
                        'gender' => 'male',
                        'date_of_birth' => '1995-05-15',
                    ],
                ],
            ]);

        // Update consent
        $consentResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/consents', [
                'consent_type' => 'share_contact',
                'is_granted' => true,
            ]);

        $consentResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'consent_type' => 'share_contact',
                    'is_granted' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_consents', [
            'user_id' => $user->id,
            'consent_type' => 'share_contact',
            'is_granted' => 1,
        ]);
    }
}
