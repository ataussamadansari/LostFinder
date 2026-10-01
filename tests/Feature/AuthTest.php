<?php

namespace Tests\Feature;

use App\Models\Passenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp()
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '9876543210',
            'country_code' => '+91',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'debug_otp' => '123456',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['phone', 'expires_in_seconds'],
            ]);
    }

    public function test_tourist_can_verify_otp_and_gets_passenger_profile()
    {
        // 1. Request OTP
        $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '9876543210',
            'country_code' => '+91',
        ]);

        // 2. Verify OTP
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543210',
            'country_code' => '+91',
            'otp' => '123456',
            'role' => 'tourist',
            'name' => 'Rahul Sharma',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'token', 'user' => ['id', 'passenger']]);

        $this->assertDatabaseHas('users', ['phone' => '+919876543210', 'role' => 'tourist']);

        $this->assertDatabaseHas('passengers', [
            'user_id' => $response->json('user.id'),
        ]);

        $this->assertNotNull($response->json('user.passenger.masked_alias'));
    }

    public function test_driver_can_verify_otp_without_auto_passenger_profile()
    {
        $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '9876500000',
            'country_code' => '+91',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876500000',
            'country_code' => '+91',
            'otp' => '123456',
            'role' => 'driver',
            'name' => 'Mohan Driver',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'token', 'user' => ['id', 'driver_profile']]);

        $this->assertDatabaseHas('users', ['phone' => '+919876500000', 'role' => 'driver']);
        $this->assertDatabaseMissing('passengers', ['user_id' => $response->json('user.id')]);
    }

    public function test_verify_otp_fails_with_invalid_otp()
    {
        $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '9876543210',
            'country_code' => '+91',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543210',
            'country_code' => '+91',
            'otp' => '000000',
            'role' => 'tourist',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => false, 'message' => 'Invalid or expired OTP']);
    }

    public function test_deactivated_user_cannot_login()
    {
        User::create([
            'name' => 'Banned User',
            'phone' => '+919876543299',
            'role' => 'tourist',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '9876543299',
            'country_code' => '+91',
        ]);

        $response->assertStatus(403)
            ->assertJson(['status' => false]);
    }

    public function test_role_mismatch_prevents_login()
    {
        User::create([
            'name' => 'Existing Driver',
            'phone' => '+919876543211',
            'role' => 'driver',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '9876543211',
            'country_code' => '+91',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '9876543211',
            'country_code' => '+91',
            'otp' => '123456',
            'role' => 'tourist',
        ]);

        $response->assertStatus(422)
            ->assertJson(['status' => false]);
    }

    public function test_authenticated_user_can_get_me_and_update_profile()
    {
        $user = User::create([
            'name' => 'Original Name',
            'phone' => '+919123456789',
            'role' => 'tourist',
        ]);

        $token = $user->createToken('token', ['tourist'])->plainTextToken;

        // 1. Get Me
        $getMeResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $getMeResponse->assertStatus(200)
            ->assertJson(['status' => true, 'user' => ['name' => 'Original Name']]);

        // 2. Update Profile
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Updated Name',
                'emergency_contact_phone' => '+919999900000',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'user' => [
                    'name' => 'Updated Name',
                    'emergency_contact_phone' => '+919999900000',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'emergency_contact_phone' => '+919999900000',
        ]);
    }

    public function test_user_can_logout()
    {
        $user = User::create([
            'name' => 'Tourist User',
            'phone' => '+919123456788',
            'role' => 'tourist',
        ]);

        $token = $user->createToken('token', ['tourist'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Logged out successfully']);

        $this->assertCount(0, $user->tokens);
    }

    public function test_admin_can_login_with_valid_password()
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@lostfinder.app',
            'phone' => '+910000000000',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/auth/admin-login', [
            'email' => 'admin@lostfinder.app',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => true])
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_role_middleware_blocks_unauthorized_user()
    {
        // Tourist login
        $tourist = User::create([
            'name' => 'Tourist User',
            'phone' => '+919999988888',
            'role' => 'tourist',
        ]);

        $token = $tourist->createToken('token', ['tourist'])->plainTextToken;

        // Tourist driver-only endpoint access karne ki koshish karega
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/ping');

        $response->assertStatus(403)
            ->assertJson(['status' => false]);
    }
}
