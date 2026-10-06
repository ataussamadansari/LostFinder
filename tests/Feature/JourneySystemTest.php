<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\QrCode;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JourneySystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'verification_team'], ['description' => 'KYC Verification Team']);
    }

    protected function createVerifiedDriverAndVehicle(): array
    {
        $driverUser = User::create([
            'phone' => '+919876543210',
            'name' => 'Ramesh Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-JRN001',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rating_avg' => 4.88,
            'rating_count' => 15,
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-JRN001',
            'registration_number' => 'KA01MJ1001',
            'vehicle_type' => 'cab',
            'make' => 'Maruti Suzuki',
            'model' => 'Dzire',
            'color' => 'White',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'status' => 'active',
        ]);

        $assignment = VehicleDriverAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'activejourneytesttoken1234567890',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return [$driverUser, $driver, $vehicle, $assignment, $qrCode];
    }

    protected function createTourist(): User
    {
        return User::create([
            'phone' => '+919888877777',
            'name' => 'Priya Tourist',
            'role' => 'tourist',
            'status' => 'active',
        ]);
    }

    public function test_verified_driver_can_start_journey(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();

        Sanctum::actingAs($driverUser);

        $response = $this->postJson('/api/v1/journeys', [
            'vehicle_code' => $vehicle->vehicle_code,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Journey started successfully.',
                'data' => [
                    'status' => 'active',
                    'vehicle' => [
                        'vehicle_code' => $vehicle->vehicle_code,
                        'registration_number' => $vehicle->registration_number,
                    ],
                ],
            ]);

        $uuid = $response->json('data.uuid');
        $this->assertNotNull($uuid);

        $this->assertDatabaseHas('journeys', [
            'uuid' => $uuid,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $driverUser->id,
            'action' => 'journey.started',
            'auditable_type' => Journey::class,
        ]);
    }

    public function test_unverified_driver_cannot_start_journey(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();

        $driver->update(['verification_status' => 'pending']);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson('/api/v1/journeys', [
            'vehicle_code' => $vehicle->vehicle_code,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Driver must be verified and active to start a journey.',
            ]);
    }

    public function test_driver_cannot_start_journey_without_active_assignment(): void
    {
        [$driverUser, $driver, $vehicle, $assignment] = $this->createVerifiedDriverAndVehicle();

        $assignment->update(['status' => 'ended', 'ended_at' => now()]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson('/api/v1/journeys', [
            'vehicle_code' => $vehicle->vehicle_code,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Driver is not actively assigned to this vehicle.',
            ]);
    }

    public function test_driver_cannot_start_journey_without_active_qr(): void
    {
        [$driverUser, $driver, $vehicle, $assignment, $qrCode] = $this->createVerifiedDriverAndVehicle();

        $qrCode->update(['status' => 'disabled']);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson('/api/v1/journeys', [
            'vehicle_code' => $vehicle->vehicle_code,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Vehicle does not have an active QR code.',
            ]);
    }

    public function test_driver_cannot_start_conflicting_active_journey(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();

        Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson('/api/v1/journeys', [
            'vehicle_code' => $vehicle->vehicle_code,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'An active journey is already in progress for this vehicle.',
            ]);
    }

    public function test_passenger_can_connect_to_journey_via_qr_token(): void
    {
        [$driverUser, $driver, $vehicle, $assignment, $qrCode] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        Sanctum::actingAs($tourist);

        $response = $this->postJson('/api/v1/journeys/connect', [
            'qr_token' => $qrCode->token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Connected to journey successfully.',
                'data' => [
                    'journey_uuid' => $journey->uuid,
                    'status' => 'active',
                    'share_details' => false,
                    'vehicle' => [
                        'vehicle_code' => $vehicle->vehicle_code,
                        'registration_number' => $vehicle->registration_number,
                    ],
                    'driver' => [
                        'driver_code' => $driver->driver_code,
                        'is_verified' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('journey_passengers', [
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'active',
            'share_details' => false,
        ]);
    }

    public function test_passenger_can_connect_via_journey_uuid(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        Sanctum::actingAs($tourist);

        $response = $this->postJson('/api/v1/journeys/connect', [
            'journey_uuid' => $journey->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'journey_uuid' => $journey->uuid,
                    'status' => 'active',
                ],
            ]);
    }

    public function test_duplicate_passenger_connection_is_idempotent(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        Sanctum::actingAs($tourist);

        $this->postJson('/api/v1/journeys/connect', ['journey_uuid' => $journey->uuid]);
        $secondResponse = $this->postJson('/api/v1/journeys/connect', ['journey_uuid' => $journey->uuid]);

        $secondResponse->assertStatus(200);

        $this->assertEquals(1, JourneyPassenger::where('journey_id', $journey->id)->where('passenger_id', $tourist->id)->count());
    }

    public function test_passenger_can_disconnect_from_journey(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'active',
            'connected_at' => now(),
        ]);

        Sanctum::actingAs($tourist);

        $response = $this->postJson("/api/v1/journeys/{$journey->uuid}/disconnect");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'journey_uuid' => $journey->uuid,
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('journey_passengers', [
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'completed',
        ]);
    }

    public function test_passenger_can_toggle_share_details(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'active',
            'connected_at' => now(),
            'share_details' => false,
        ]);

        // Prior to sharing: driver sees masked details
        Sanctum::actingAs($driverUser);
        $passengersResponse1 = $this->getJson("/api/v1/journeys/{$journey->uuid}/passengers");

        $passengersResponse1->assertStatus(200)
            ->assertJsonFragment([
                'phone' => null,
                'share_details' => false,
            ]);

        // Passenger enables share details
        Sanctum::actingAs($tourist);
        $shareResponse = $this->postJson("/api/v1/journeys/{$journey->uuid}/share-details", [
            'share_contact' => true,
            'fields' => ['name', 'phone'],
        ]);

        $shareResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'share_details' => true,
                ],
            ]);

        // Driver now sees exposed details
        Sanctum::actingAs($driverUser);
        $passengersResponse2 = $this->getJson("/api/v1/journeys/{$journey->uuid}/passengers");

        $passengersResponse2->assertStatus(200)
            ->assertJsonFragment([
                'display_name' => $tourist->name,
                'phone' => $tourist->phone,
                'share_details' => true,
            ]);
    }

    public function test_driver_can_complete_and_cancel_journey(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'active',
            'connected_at' => now(),
        ]);

        // Complete
        Sanctum::actingAs($driverUser);
        $completeResponse = $this->postJson("/api/v1/journeys/{$journey->uuid}/complete");

        $completeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('journeys', [
            'id' => $journey->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('journey_passengers', [
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'completed',
        ]);
    }

    public function test_current_journey_endpoint_returns_context_for_driver_and_passenger(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(72),
            'status' => 'active',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $tourist->id,
            'status' => 'active',
            'connected_at' => now(),
        ]);

        // Driver context
        Sanctum::actingAs($driverUser);
        $driverCurrent = $this->getJson('/api/v1/journeys/current');

        $driverCurrent->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'driver',
                    'passenger_count' => 1,
                    'status' => 'active',
                ],
            ]);

        // Passenger context
        Sanctum::actingAs($tourist);
        $passengerCurrent = $this->getJson('/api/v1/journeys/current');

        $passengerCurrent->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'passenger',
                    'status' => 'active',
                    'vehicle' => [
                        'registration_number' => $vehicle->registration_number,
                    ],
                ],
            ]);
    }

    public function test_expire_journeys_command_expires_journeys_past_72_hours(): void
    {
        [$driverUser, $driver, $vehicle] = $this->createVerifiedDriverAndVehicle();
        $tourist = $this->createTourist();

        // Expired journey
        $expiredJourney = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHours(73),
            'expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $jp = JourneyPassenger::create([
            'journey_id' => $expiredJourney->id,
            'passenger_id' => $tourist->id,
            'status' => 'active',
            'connected_at' => now()->subHours(73),
        ]);

        $this->artisan('journeys:expire-inactive')
            ->expectsOutputToContain('Expired journeys processed: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('journeys', [
            'id' => $expiredJourney->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('journey_passengers', [
            'id' => $jp->id,
            'status' => 'expired',
        ]);
    }
}
