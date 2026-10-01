<?php

namespace Tests\Feature;

use App\Models\DriverProfile;
use App\Models\LostClaim;
use App\Models\Passenger;
use App\Models\RideSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTest extends TestCase
{
    use RefreshDatabase;

    private function createDriverUser(string $name = 'Suresh Yadav', string $phone = '+919876500001'): array
    {
        $user = User::create([
            'name'  => $name,
            'phone' => $phone,
            'role'  => 'driver',
        ]);

        $token = $user->createToken('token', ['driver'])->plainTextToken;

        return [$user, $token];
    }

    public function test_driver_can_register_profile_and_gets_unique_qr()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile', [
                'vehicle_number' => 'UP65AB9999',
                'vehicle_type'   => 'e_rickshaw',
                'license_number' => 'DL987654321',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data' => ['driver', 'scan_url', 'qr_image_url']]);

        $this->assertDatabaseHas('driver_profiles', [
            'vehicle_number' => 'UP65AB9999',
            'vehicle_type'   => 'e_rickshaw',
        ]);
    }

    public function test_driver_cannot_register_duplicate_profile()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile', [
                'vehicle_number' => 'UP65AB9999',
                'vehicle_type'   => 'auto',
                'license_number' => 'DL987654321',
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile', [
                'vehicle_number' => 'UP65AB8888',
                'vehicle_type'   => 'auto',
                'license_number' => 'DL987654321',
            ]);

        $response->assertStatus(400)
            ->assertJson(['status' => false, 'message' => 'Driver profile already exists.']);
    }

    public function test_driver_can_view_and_update_profile()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile', [
                'vehicle_number' => 'UP65AB9999',
                'vehicle_type'   => 'auto',
                'license_number' => 'DL987654321',
            ]);

        // 1. View Profile
        $getResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/profile');

        $getResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'vehicle_number' => 'UP65AB9999',
                    'vehicle_type'   => 'auto',
                ],
            ]);

        // 2. Update Profile
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile/update', [
                'vehicle_type'   => 'cab',
                'license_number' => 'DL99999999',
                'name'           => 'Suresh Kumar Yadav',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Driver profile updated successfully']);

        $this->assertDatabaseHas('driver_profiles', [
            'vehicle_type'   => 'cab',
            'license_number' => 'DL99999999',
        ]);
        $this->assertEquals('Suresh Kumar Yadav', $driverUser->fresh()->name);
    }

    public function test_driver_can_fetch_and_regenerate_qr_code()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $regResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile', [
                'vehicle_number' => 'UP65AB9999',
                'vehicle_type'   => 'auto',
                'license_number' => 'DL987654321',
            ]);

        $initialQrToken = $regResponse->json('data.driver.qr_code_token');

        // Fetch QR
        $getResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/qr');

        $getResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'vehicle_number' => 'UP65AB9999',
                    'qr_token'       => $initialQrToken,
                ],
            ]);

        // Regenerate QR
        $regenResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/qr/regenerate');

        $regenResponse->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'New QR code generated successfully']);

        $newQrToken = $regenResponse->json('data.qr_token');
        $this->assertNotEquals($initialQrToken, $newQrToken);
    }

    public function test_driver_can_view_rides_and_single_ride_session()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB9999',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345',
            'qr_code_token'  => 'DRV_TEST001',
        ]);

        $touristUser = User::create(['name' => 'Alice', 'phone' => '+919988776655', 'role' => 'tourist']);
        $passenger = Passenger::create(['user_id' => $touristUser->id, 'masked_alias' => 'Passenger #5544']);

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        // Ride History
        $historyResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/rides');

        $historyResponse->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['data']]);
        $this->assertCount(1, $historyResponse->json('data.data'));

        // Single Ride
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/rides/' . $ride->id);

        $showResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'id'        => $ride->id,
                    'passenger' => [
                        'masked_alias' => 'Passenger #5544',
                    ],
                ],
            ]);
    }

    public function test_driver_can_list_claims_and_handover_otp_is_hidden()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB9999',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345',
            'qr_code_token'  => 'DRV_TEST001',
        ]);

        $touristUser = User::create(['name' => 'Alice', 'phone' => '+919988776655', 'role' => 'tourist']);
        $passenger = Passenger::create(['user_id' => $touristUser->id, 'masked_alias' => 'Passenger #5544']);

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'flagged',
        ]);

        $claim = LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Wallet',
            'item_description' => 'Black leather wallet',
            'handover_otp'     => '987654',
            'claim_status'     => 'reported',
            'bounty_amount'    => 200.00,
        ]);

        // Claims listing
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/claims');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));

        // Security check: Driver must NEVER see the secret handover OTP in API response
        $this->assertArrayNotHasKey('handover_otp', $response->json('data.data.0'));

        // Single claim view
        $claimDetail = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/claims/' . $claim->id);

        $claimDetail->assertStatus(200);
        $this->assertArrayNotHasKey('handover_otp', $claimDetail->json('data'));
    }

    public function test_driver_can_update_claim_status()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB9999',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345',
            'qr_code_token'  => 'DRV_TEST001',
        ]);

        $touristUser = User::create(['name' => 'Alice', 'phone' => '+919988776655', 'role' => 'tourist']);
        $passenger = Passenger::create(['user_id' => $touristUser->id, 'masked_alias' => 'Passenger #5544']);

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'flagged',
        ]);

        $claim = LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Camera',
            'item_description' => 'Sony mirrorless camera',
            'handover_otp'     => '123456',
            'claim_status'     => 'reported',
        ]);

        // Update status to searching
        $searchResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/driver/claims/' . $claim->id . '/status', [
                'claim_status' => 'searching',
            ]);

        $searchResponse->assertStatus(200);
        $this->assertEquals('searching', $claim->fresh()->claim_status);

        // Update status to found
        $foundResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/driver/claims/' . $claim->id . '/status', [
                'claim_status' => 'found',
            ]);

        $foundResponse->assertStatus(200);
        $this->assertEquals('found', $claim->fresh()->claim_status);
    }

    public function test_driver_verifies_handover_with_valid_otp_and_completes_claim()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB9999',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345',
            'qr_code_token'  => 'DRV_TEST001',
        ]);

        $touristUser = User::create(['name' => 'Alice', 'phone' => '+919988776655', 'role' => 'tourist']);
        $passenger = Passenger::create(['user_id' => $touristUser->id, 'masked_alias' => 'Passenger #5544']);

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'flagged',
        ]);

        $claim = LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Phone',
            'item_description' => 'Samsung S24',
            'handover_otp'     => '654321',
            'claim_status'     => 'found',
            'bounty_amount'    => 1000.00,
        ]);

        // 1. Wrong OTP should fail
        $wrongOtpResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/claims/' . $claim->id . '/verify-handover', [
                'handover_otp' => '000000',
            ]);

        $wrongOtpResponse->assertStatus(422)
            ->assertJson(['status' => false, 'message' => 'Invalid handover OTP. Please verify the 6-digit code with the passenger.']);

        $this->assertEquals('found', $claim->fresh()->claim_status);

        // 2. Correct OTP succeeds
        $correctOtpResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/claims/' . $claim->id . '/verify-handover', [
                'handover_otp' => '654321',
            ]);

        $correctOtpResponse->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'Handover verified successfully! Item has been marked returned.',
                'data'    => [
                    'claim_status'  => 'returned',
                    'bounty_amount' => '1000.00',
                ],
            ]);

        $this->assertEquals('returned', $claim->fresh()->claim_status);
        $this->assertNotNull($claim->fresh()->resolved_at);
        $this->assertEquals('completed', $ride->fresh()->status);
    }

    public function test_driver_can_update_fcm_token()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/profile', [
                'vehicle_number' => 'UP65AB9999',
                'vehicle_type'   => 'auto',
                'license_number' => 'DL987654321',
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/driver/fcm', [
                'fcm_token' => 'sample_fcm_token_12345',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('driver_profiles', ['fcm_token' => 'sample_fcm_token_12345']);
    }

    public function test_driver_dashboard_stats()
    {
        [$driverUser, $token] = $this->createDriverUser();

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB9999',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345',
            'qr_code_token'  => 'DRV_TEST001',
            'total_trips'    => 12,
        ]);

        $touristUser = User::create(['name' => 'Alice', 'phone' => '+919988776655', 'role' => 'tourist']);
        $passenger = Passenger::create(['user_id' => $touristUser->id, 'masked_alias' => 'Passenger #5544']);

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Bag',
            'item_description' => 'Red bag',
            'handover_otp'     => '112233',
            'claim_status'     => 'returned',
            'bounty_amount'    => 350.00,
            'resolved_at'      => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/driver/stats');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'total_trips'           => 12,
                    'active_rides'          => 1,
                    'returned_claims'       => 1,
                    'total_bounties_earned' => 350.00,
                ],
            ]);
    }
}
