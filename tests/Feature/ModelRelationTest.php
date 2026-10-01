<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\DriverProfile;
use App\Models\Passenger;
use App\Models\RideSession;
use App\Models\LostClaim;

class ModelRelationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_verifies_complete_database_relationships_chain()
    {
        // 1. Driver User & Profile Create karein
        $driverUser = User::create([
            'name' => 'Ramesh Driver',
            'phone' => '+919876543210',
            'role' => 'driver',
        ]);

        $driverProfile = DriverProfile::create([
            'user_id' => $driverUser->id,
            'vehicle_number' => 'UP65AB1234',
            'vehicle_type' => 'auto',
            'license_number' => 'DL123456789',
            'qr_code_token' => 'DRV_TESTTOKEN1',
        ]);

        $this->assertEquals($driverProfile->id, $driverUser->driverProfile->id);
        $this->assertEquals($driverUser->id, $driverProfile->user->id);

        // 2. Tourist User & Passenger Profile Create karein
        $touristUser = User::create([
            'name' => 'Alice Tourist',
            'phone' => '+919123456780',
            'role' => 'tourist',
        ]);

        $passenger = Passenger::create([
            'user_id' => $touristUser->id,
            'masked_alias' => 'Passenger #9988',
        ]);

        $this->assertEquals($passenger->id, $touristUser->passenger->id);
        $this->assertEquals($touristUser->id, $passenger->user->id);

        // 3. Ride Session link karein
        $rideSession = RideSession::create([
            'driver_profile_id' => $driverProfile->id,
            'passenger_id' => $passenger->id,
            'status' => 'active',
        ]);

        $this->assertEquals('UP65AB1234', $rideSession->driverProfile->vehicle_number);
        $this->assertEquals('Passenger #9988', $rideSession->passenger->masked_alias);
        $this->assertCount(1, $driverProfile->rideSessions);

        // 4. Lost Claim raise karein
        $claim = LostClaim::create([
            'ride_session_id' => $rideSession->id,
            'item_category' => 'Phone',
            'item_description' => 'Black iPhone left on rear seat',
            'handover_otp' => '654321',
            'claim_status' => 'reported',
        ]);

        $this->assertEquals($rideSession->id, $claim->rideSession->id);
        $this->assertEquals('UP65AB1234', $claim->rideSession->driverProfile->vehicle_number);
        $this->assertCount(1, $rideSession->lostClaims);
    }
}
