<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\DriverProfile;

class RideTest extends TestCase
{
    use RefreshDatabase;

    private function createDriver(): DriverProfile
    {
        $user = User::create([
            'name'  => 'Mohan Auto',
            'phone' => '+919876543200',
            'role'  => 'driver',
        ]);

        return $user->driverProfile()->create([
            'vehicle_number' => 'UP65XY1122',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345678',
            'qr_code_token'  => 'DRV_TESTTOKEN99',
            'is_verified'    => true,
        ]);
    }

    public function test_anyone_can_fetch_driver_info_via_valid_qr_token()
    {
        $driver = $this->createDriver();

        $response = $this->getJson('/api/v1/ride/driver-info/' . $driver->qr_code_token);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'vehicle_number' => 'UP65XY1122',
                    'vehicle_type'   => 'auto',
                    'driver_name'    => 'Mohan Auto',
                ],
            ]);
    }

    public function test_invalid_qr_token_returns_404()
    {
        $response = $this->getJson('/api/v1/ride/driver-info/INVALID_TOKEN_123');

        $response->assertStatus(404)
            ->assertJson(['status' => false]);
    }

    public function test_authenticated_tourist_can_scan_qr_and_create_ride_session()
    {
        $driver = $this->createDriver();

        $tourist = User::create([
            'name'  => 'Emma Watson',
            'phone' => '+919988776655',
            'role'  => 'tourist',
        ]);
        $token = $tourist->createToken('token', ['tourist'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/tourist/ride/scan', [
                'qr_token'       => $driver->qr_code_token,
                'scan_latitude'  => 25.3176, // Varanasi coordinates
                'scan_longitude' => 82.9739,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data' => ['ride_session_id', 'vehicle_number']]);

        // Verify Database
        $this->assertDatabaseHas('ride_sessions', [
            'driver_profile_id' => $driver->id,
            'status'            => 'active',
        ]);

        // Verify trip counter incremented
        $this->assertEquals(1, $driver->fresh()->total_trips);
    }

    public function test_driver_cannot_scan_as_tourist()
    {
        $driver1 = $this->createDriver();

        $driverUser2 = User::create([
            'name'  => 'Other Driver',
            'phone' => '+919876543201',
            'role'  => 'driver',
        ]);
        $driverToken = $driverUser2->createToken('token', ['driver'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $driverToken)
            ->postJson('/api/v1/tourist/ride/scan', [
                'qr_token' => $driver1->qr_code_token,
            ]);

        $response->assertStatus(403);
    }

    public function test_tourist_can_view_their_past_rides()
    {
        $driver = $this->createDriver();

        $tourist = User::create([
            'name'  => 'Emma Watson',
            'phone' => '+919988776655',
            'role'  => 'tourist',
        ]);
        $token = $tourist->createToken('token', ['tourist'])->plainTextToken;

        // Ride scan karein
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/tourist/ride/scan', [
                'qr_token' => $driver->qr_code_token,
            ]);

        // Ride history check karein
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tourist/rides');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['data']]);
    }
}
