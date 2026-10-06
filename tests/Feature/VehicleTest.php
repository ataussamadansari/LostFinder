<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function createVerifiedDriver(): array
    {
        $user = User::create([
            'phone' => '+919876543210',
            'name' => 'Driver Suresh',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_code' => 'DRV-SUR001',
            'verification_status' => 'verified',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        return [$user, $driver, $token];
    }

    public function test_driver_can_register_vehicle(): void
    {
        [$user, $driver, $token] = $this->createVerifiedDriver();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/driver/vehicles', [
                'registration_number' => 'KA01AB1234',
                'vehicle_type' => 'cab',
                'make' => 'Maruti Suzuki',
                'model' => 'Dzire',
                'color' => 'White',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'registration_number' => 'KA01AB1234',
                    'vehicle_type' => 'cab',
                    'make' => 'Maruti Suzuki',
                    'model' => 'Dzire',
                    'verification_status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'registration_number' => 'KA01AB1234',
            'vehicle_type' => 'cab',
        ]);

        // Auto assigned as active because no other vehicle is assigned
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'driver_id' => $driver->id,
            'status' => 'active',
        ]);
    }

    public function test_driver_can_list_vehicles(): void
    {
        [$user, $driver, $token] = $this->createVerifiedDriver();

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-000001',
            'registration_number' => 'KA05MH9999',
            'vehicle_type' => 'auto',
            'make' => 'Bajaj',
            'model' => 'Compact 4S',
            'color' => 'Yellow-Green',
            'verification_status' => 'verified',
        ]);

        $driver->assignments()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/driver/vehicles');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'registration_number' => 'KA05MH9999',
                'vehicle_type' => 'auto',
                'is_active_for_you' => true,
            ]);
    }

    public function test_driver_can_upload_vehicle_document(): void
    {
        Storage::fake('private');
        [$user, $driver, $token] = $this->createVerifiedDriver();

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-000002',
            'registration_number' => 'MH02XY5555',
            'vehicle_type' => 'cab',
            'make' => 'Hyundai',
            'model' => 'Aura',
            'verification_status' => 'pending',
        ]);

        $file = UploadedFile::fake()->create('vehicle_rc.pdf', 300, 'application/pdf');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/driver/vehicles/{$vehicle->uuid}/documents", [
                'document_type' => 'registration',
                'document_number' => 'RC-99887766',
                'file' => $file,
                'expires_at' => now()->addYears(10)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Vehicle document uploaded successfully and queued for verification.',
                'data' => [
                    'document_type' => 'registration',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('vehicle_documents', [
            'vehicle_id' => $vehicle->id,
            'document_type' => 'registration',
            'document_number' => 'RC-99887766',
        ]);
    }

    public function test_driver_can_switch_active_vehicle(): void
    {
        [$user, $driver, $token] = $this->createVerifiedDriver();

        $vehicle1 = Vehicle::create([
            'vehicle_code' => 'VEH-000003',
            'registration_number' => 'DL01AA1111',
            'vehicle_type' => 'cab',
            'status' => 'active',
        ]);

        $vehicle2 = Vehicle::create([
            'vehicle_code' => 'VEH-000004',
            'registration_number' => 'DL01AA2222',
            'vehicle_type' => 'cab',
            'status' => 'active',
        ]);

        // Start with vehicle 1 active
        $assignment1 = $driver->assignments()->create([
            'vehicle_id' => $vehicle1->id,
            'status' => 'active',
            'started_at' => now()->subDays(5),
        ]);

        // Switch to vehicle 2
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/driver/vehicles/{$vehicle2->uuid}/assign-active");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Vehicle assigned as active successfully.',
                'data' => [
                    'vehicle_uuid' => $vehicle2->uuid,
                    'status' => 'active',
                ],
            ]);

        // Check vehicle 1 assignment ended
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'id' => $assignment1->id,
            'status' => 'ended',
        ]);

        // Check vehicle 2 is active
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle2->id,
            'status' => 'active',
        ]);
    }
}
