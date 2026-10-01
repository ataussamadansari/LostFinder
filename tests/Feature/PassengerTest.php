<?php

namespace Tests\Feature;

use App\Models\DriverProfile;
use App\Models\LostClaim;
use App\Models\Passenger;
use App\Models\RideSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassengerTest extends TestCase
{
    use RefreshDatabase;

    private function createDriver(): DriverProfile
    {
        $user = User::create([
            'name'  => 'Suresh Yadav',
            'phone' => '+919876500001',
            'role'  => 'driver',
        ]);

        return DriverProfile::create([
            'user_id'        => $user->id,
            'vehicle_number' => 'UP65AB1234',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL987654321',
            'qr_code_token'  => 'DRV_TESTTOKEN1',
            'is_verified'    => true,
        ]);
    }

    private function createTourist(string $name = 'Alice Tourist', string $phone = '+919123456780'): array
    {
        $user = User::create([
            'name'  => $name,
            'phone' => $phone,
            'role'  => 'tourist',
        ]);

        $passenger = Passenger::create([
            'user_id'        => $user->id,
            'masked_alias'   => 'Passenger #1234',
            'preferred_lang' => 'en',
        ]);

        $token = $user->createToken('token', ['tourist'])->plainTextToken;

        return [$user, $passenger, $token];
    }

    public function test_tourist_can_view_and_update_passenger_profile()
    {
        [$user, $passenger, $token] = $this->createTourist();

        // 1. Get Profile
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tourist/profile');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'name'           => 'Alice Tourist',
                    'masked_alias'   => 'Passenger #1234',
                    'preferred_lang' => 'en',
                ],
            ]);

        // 2. Update Profile
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/tourist/profile', [
                'name'                    => 'Alice Wonderland',
                'masked_alias'            => 'Passenger #9999',
                'preferred_lang'          => 'hi',
                'emergency_contact_phone' => '+919876543299',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'Tourist profile updated successfully',
                'data'    => [
                    'name'                    => 'Alice Wonderland',
                    'masked_alias'            => 'Passenger #9999',
                    'preferred_lang'          => 'hi',
                    'emergency_contact_phone' => '+919876543299',
                ],
            ]);

        $this->assertDatabaseHas('passengers', [
            'id'             => $passenger->id,
            'masked_alias'   => 'Passenger #9999',
            'preferred_lang' => 'hi',
        ]);
    }

    public function test_tourist_can_view_single_ride_details()
    {
        $driver = $this->createDriver();
        [$user, $passenger, $token] = $this->createTourist();

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tourist/rides/' . $ride->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'id'                => $ride->id,
                    'status'            => 'active',
                    'driver_profile'    => [
                        'vehicle_number' => 'UP65AB1234',
                    ],
                ],
            ]);
    }

    public function test_tourist_cannot_view_another_tourists_ride_session()
    {
        $driver = $this->createDriver();
        [$user1, $passenger1, $token1] = $this->createTourist('Alice', '+919123456781');
        [$user2, $passenger2, $token2] = $this->createTourist('Bob', '+919123456782');

        $ride1 = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger1->id,
            'status'            => 'active',
        ]);

        // Tourist 2 tries to view Tourist 1's ride
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/v1/tourist/rides/' . $ride1->id);

        $response->assertStatus(404)
            ->assertJson(['status' => false]);
    }

    public function test_tourist_can_mark_ride_session_as_completed()
    {
        $driver = $this->createDriver();
        [$user, $passenger, $token] = $this->createTourist();

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/tourist/rides/' . $ride->id . '/complete');

        $response->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Ride completed successfully']);

        $this->assertEquals('completed', $ride->fresh()->status);
    }

    public function test_tourist_can_file_lost_item_claim_and_gets_handover_otp()
    {
        $driver = $this->createDriver();
        [$user, $passenger, $token] = $this->createTourist();

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/tourist/claims', [
                'ride_session_id'  => $ride->id,
                'item_category'    => 'Smartphone',
                'item_description' => 'Black iPhone 15 Pro with transparent case',
                'bounty_amount'    => 500.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'item_category',
                    'handover_otp',
                    'claim_status',
                    'bounty_amount',
                ],
            ]);

        $this->assertDatabaseHas('lost_claims', [
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Smartphone',
            'claim_status'     => 'reported',
            'bounty_amount'    => 500.00,
        ]);

        // Ride session must be flagged
        $this->assertEquals('flagged', $ride->fresh()->status);
    }

    public function test_tourist_cannot_file_claim_for_another_users_ride()
    {
        $driver = $this->createDriver();
        [$user1, $passenger1, $token1] = $this->createTourist('Alice', '+919123456781');
        [$user2, $passenger2, $token2] = $this->createTourist('Bob', '+919123456782');

        $ride1 = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger1->id,
            'status'            => 'active',
        ]);

        // Tourist 2 tries to file a claim for Tourist 1's ride
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/v1/tourist/claims', [
                'ride_session_id'  => $ride1->id,
                'item_category'    => 'Wallet',
                'item_description' => 'Brown leather wallet with ID cards',
            ]);

        $response->assertStatus(403);
    }

    public function test_tourist_can_list_and_filter_their_lost_claims()
    {
        $driver = $this->createDriver();
        [$user, $passenger, $token] = $this->createTourist();

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Bag',
            'item_description' => 'Blue backpack',
            'handover_otp'     => '123456',
            'claim_status'     => 'reported',
        ]);

        LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Keys',
            'item_description' => 'Keychain with 3 keys',
            'handover_otp'     => '654321',
            'claim_status'     => 'found',
        ]);

        // Fetch all claims
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tourist/claims');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['data']]);
        $this->assertCount(2, $response->json('data.data'));

        // Filter by status=found
        $filterResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tourist/claims?status=found');

        $filterResponse->assertStatus(200);
        $this->assertCount(1, $filterResponse->json('data.data'));
        $this->assertEquals('Keys', $filterResponse->json('data.data.0.item_category'));
    }

    public function test_tourist_can_view_single_claim_details()
    {
        $driver = $this->createDriver();
        [$user, $passenger, $token] = $this->createTourist();

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'active',
        ]);

        $claim = LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Watch',
            'item_description' => 'Silver wristwatch',
            'handover_otp'     => '789123',
            'claim_status'     => 'reported',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tourist/claims/' . $claim->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'id'            => $claim->id,
                    'item_category' => 'Watch',
                    'handover_otp'  => '789123',
                ],
            ]);
    }

    public function test_tourist_can_cancel_reported_claim()
    {
        $driver = $this->createDriver();
        [$user, $passenger, $token] = $this->createTourist();

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'flagged',
        ]);

        $claim = LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Book',
            'item_description' => 'Novels',
            'handover_otp'     => '998877',
            'claim_status'     => 'reported',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/tourist/claims/' . $claim->id);

        $response->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Claim cancelled successfully']);

        $this->assertDatabaseMissing('lost_claims', ['id' => $claim->id]);
        $this->assertEquals('completed', $ride->fresh()->status);
    }
}
