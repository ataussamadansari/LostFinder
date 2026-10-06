<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\Media;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'verification_team'], ['description' => 'KYC Verification Team']);
    }

    protected function createVerifier(): array
    {
        $user = User::create([
            'phone' => '+919999900001',
            'name' => 'Verifier Staff',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $adminUser = AdminUser::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $adminUser->assignRole('verification_team');

        $token = $user->createToken('verifier_token')->plainTextToken;

        return [$user, $adminUser, $token];
    }

    public function test_verifier_can_list_pending_drivers(): void
    {
        [$verifier, $adminUser, $token] = $this->createVerifier();

        $driverUser = User::create([
            'phone' => '+919999900010',
            'name' => 'Pending Driver One',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-PND001',
            'verification_status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/verification/drivers?status=pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'driver_code' => 'DRV-PND001',
            ]);
    }

    public function test_verifier_can_verify_driver_with_document(): void
    {
        [$verifier, $adminUser, $token] = $this->createVerifier();

        $driverUser = User::create([
            'phone' => '+919999900020',
            'name' => 'Applicant Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-APP001',
            'verification_status' => 'pending',
        ]);

        $media = Media::create([
            'disk' => 'private',
            'path' => 'drivers/documents/dl.pdf',
            'original_name' => 'dl.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'visibility' => 'private',
            'uploaded_by' => $driverUser->id,
        ]);

        $document = DriverDocument::create([
            'driver_id' => $driver->id,
            'document_type' => 'driving_license',
            'media_id' => $media->id,
            'status' => 'pending',
        ]);

        // First verify document
        $docVerifyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/verification/driver-documents/{$document->id}/verify");

        $docVerifyResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $document->id,
                    'status' => 'verified',
                ],
            ]);

        // Then verify driver
        $driverVerifyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/verification/drivers/{$driver->driver_code}/verify");

        $driverVerifyResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'driver_code' => 'DRV-APP001',
                    'verification_status' => 'verified',
                ],
            ]);

        $this->assertEquals('verified', $driver->fresh()->verification_status);
        $this->assertNotNull($driver->fresh()->verified_at);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $verifier->id,
            'action' => 'driver.verified',
            'auditable_type' => Driver::class,
            'auditable_id' => $driver->id,
        ]);

        // Check notification delivered to driver
        $this->assertDatabaseHas('notifications', [
            'user_id' => $driverUser->id,
            'type' => 'driver_verified',
        ]);
    }

    public function test_driver_cannot_self_verify(): void
    {
        $driverUser = User::create([
            'phone' => '+919999900030',
            'name' => 'Self Verifying Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-SLF002',
            'verification_status' => 'pending',
        ]);

        // Also assign verification_team admin role to the same user
        $adminUser = AdminUser::create([
            'user_id' => $driverUser->id,
            'status' => 'active',
        ]);
        $adminUser->assignRole('verification_team');

        $token = $driverUser->createToken('self_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/verification/drivers/{$driver->driver_code}/verify");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['driver']);
    }

    public function test_verifier_can_reject_driver_with_reason(): void
    {
        [$verifier, $adminUser, $token] = $this->createVerifier();

        $driverUser = User::create([
            'phone' => '+919999900040',
            'name' => 'Rejected Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-REJ001',
            'verification_status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/verification/drivers/{$driver->driver_code}/reject", [
                'reason' => 'Driving license copy is blurred and expired.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'driver_code' => 'DRV-REJ001',
                    'verification_status' => 'rejected',
                ],
            ]);

        $this->assertEquals('rejected', $driver->fresh()->verification_status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $driverUser->id,
            'type' => 'driver_rejected',
        ]);
    }

    public function test_verifier_can_verify_and_reject_vehicle(): void
    {
        [$verifier, $adminUser, $token] = $this->createVerifier();

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-VER001',
            'registration_number' => 'KA03MJ7777',
            'vehicle_type' => 'cab',
            'verification_status' => 'pending',
        ]);

        // Verify vehicle
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/verification/vehicles/{$vehicle->vehicle_code}/verify");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'vehicle_code' => 'VEH-VER001',
                    'verification_status' => 'verified',
                    'status' => 'active',
                ],
            ]);

        $this->assertEquals('verified', $vehicle->fresh()->verification_status);

        // Reject vehicle
        $rejectResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/verification/vehicles/{$vehicle->vehicle_code}/reject", [
                'reason' => 'RC registration details mismatch.',
            ]);

        $rejectResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'vehicle_code' => 'VEH-VER001',
                    'verification_status' => 'rejected',
                ],
            ]);

        $this->assertEquals('rejected', $vehicle->fresh()->verification_status);
    }

    public function test_admin_can_update_vehicle_status_to_blocked(): void
    {
        [$verifier, $adminUser, $token] = $this->createVerifier();

        // Give admin role for vehicles.suspend
        $adminUser->assignRole('admin');

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-BLK001',
            'registration_number' => 'MH12QW9999',
            'vehicle_type' => 'auto',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/vehicles/{$vehicle->vehicle_code}/status", [
                'status' => 'blocked',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'vehicle_code' => 'VEH-BLK001',
                    'status' => 'blocked',
                ],
            ]);

        $this->assertEquals('blocked', $vehicle->fresh()->status);
    }

    public function test_driver_can_end_active_vehicle_assignment(): void
    {
        $driverUser = User::create([
            'phone' => '+919999900050',
            'name' => 'Shift End Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-END001',
            'verification_status' => 'verified',
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-END001',
            'registration_number' => 'DL09KL1234',
            'vehicle_type' => 'cab',
            'status' => 'active',
        ]);

        $assignment = $driver->assignments()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'started_at' => now()->subHours(8),
        ]);

        $token = $driverUser->createToken('driver_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/driver/vehicles/{$vehicle->uuid}/end-assignment");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Active vehicle assignment ended successfully.',
            ]);

        $this->assertEquals('ended', $assignment->fresh()->status);
        $this->assertNotNull($assignment->fresh()->ended_at);
    }

    public function test_check_expiries_command_flags_expired_documents(): void
    {
        $driverUser = User::create([
            'phone' => '+919999900060',
            'name' => 'Expired Doc Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-EXP001',
            'verification_status' => 'verified',
        ]);

        $media = Media::create([
            'disk' => 'private',
            'path' => 'drivers/documents/old_dl.pdf',
            'original_name' => 'old_dl.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'visibility' => 'private',
            'uploaded_by' => $driverUser->id,
        ]);

        $expiredDoc = DriverDocument::create([
            'driver_id' => $driver->id,
            'document_type' => 'driving_license',
            'media_id' => $media->id,
            'status' => 'verified',
            'expires_at' => now()->subDay()->toDateString(), // Expired yesterday
        ]);

        // Run artisan command
        $this->artisan('verification:check-expiries')
            ->assertSuccessful();

        $this->assertEquals('expired', $expiredDoc->fresh()->status);
        $this->assertEquals('under_review', $driver->fresh()->verification_status);
    }
}
