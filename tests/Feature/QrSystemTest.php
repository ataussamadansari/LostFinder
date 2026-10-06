<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\QrCode;
use App\Models\QrScanLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'verification_team'], ['description' => 'KYC Verification Team']);
    }

    protected function createAdmin(): array
    {
        $user = User::create([
            'phone' => '+919876500001',
            'name' => 'Admin Operator',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $adminUser = AdminUser::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $adminUser->assignRole('admin');

        $token = $user->createToken('admin_token')->plainTextToken;

        return [$user, $adminUser, $token];
    }

    protected function createVerifiedVehicleAndDriver(): array
    {
        $driverUser = User::create([
            'phone' => '+919876543210',
            'name' => 'Rajesh Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-100001',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rating_avg' => 4.90,
            'rating_count' => 12,
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-KA0101',
            'registration_number' => 'KA01AB9999',
            'vehicle_type' => 'cab',
            'make' => 'Toyota',
            'model' => 'Etios',
            'color' => 'Silver',
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

        return [$vehicle, $driver, $driverUser, $assignment];
    }

    public function test_admin_can_generate_qr_code_for_vehicle(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/vehicles/{$vehicle->vehicle_code}/generate");

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'QR code generated successfully.',
                'data' => [
                    'version' => 1,
                    'status' => 'pending',
                    'vehicle_code' => $vehicle->vehicle_code,
                ],
            ]);

        $this->assertEquals(32, strlen($response->json('data.token')));

        $this->assertDatabaseHas('qr_codes', [
            'vehicle_id' => $vehicle->id,
            'version' => 1,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'qr.generated',
            'auditable_type' => QrCode::class,
        ]);
    }

    public function test_cannot_generate_qr_for_non_existent_vehicle(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/qr/vehicles/NON_EXISTENT/generate');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Vehicle not found.',
            ]);
    }

    public function test_admin_can_activate_qr_code_for_verified_vehicle(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'testtoken1234567890123456789012',
            'version' => 1,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/{$qrCode->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'QR code activated successfully.',
                'data' => [
                    'id' => $qrCode->id,
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrCode->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'qr.activated',
            'auditable_id' => $qrCode->id,
        ]);
    }

    public function test_cannot_activate_qr_code_for_unverified_or_inactive_vehicle(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();

        $unverifiedVehicle = Vehicle::create([
            'vehicle_code' => 'VEH-UNVERIFIED',
            'registration_number' => 'DL01XY1234',
            'vehicle_type' => 'cab',
            'make' => 'Hyundai',
            'model' => 'Aura',
            'color' => 'White',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);

        $qrCode = QrCode::create([
            'vehicle_id' => $unverifiedVehicle->id,
            'token' => 'unverifiedtoken1234567890123456',
            'version' => 1,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/{$qrCode->id}/activate");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot activate QR code for an unverified or inactive vehicle.',
            ]);
    }

    public function test_single_active_invariant_auto_revokes_previous_active_qr(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        // Previous QR is active
        $qrV1 = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'activev1token123456789012345678',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now()->subDays(10),
        ]);

        // New replacement QR is pending
        $qrV2 = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'pendingv2token12345678901234567',
            'version' => 2,
            'status' => 'pending',
        ]);

        // Activate QR V2
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/{$qrV2->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $qrV2->id,
                    'status' => 'active',
                ],
            ]);

        // QR V1 must now be revoked
        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrV1->id,
            'status' => 'revoked',
        ]);

        // QR V2 is active
        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrV2->id,
            'status' => 'active',
        ]);

        // Audit log for auto revocation
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'qr.auto_revoked',
            'auditable_id' => $qrV1->id,
        ]);
    }

    public function test_admin_can_disable_and_revoke_qr_code(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'disabletesttoken123456789012345',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Disable
        $disableResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/{$qrCode->id}/disable", [
                'reason' => 'Routine inspection suspension',
            ]);

        $disableResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'disabled'],
            ]);

        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrCode->id,
            'status' => 'disabled',
        ]);

        // Revoke
        $revokeResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/{$qrCode->id}/revoke", [
                'reason' => 'Permanent sticker decommission',
            ]);

        $revokeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'revoked'],
            ]);

        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrCode->id,
            'status' => 'revoked',
        ]);

        // Cannot disable a revoked QR
        $failDisable = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/qr/{$qrCode->id}/disable");

        $failDisable->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot disable a revoked QR code.',
            ]);
    }

    public function test_public_resolver_resolves_active_qr_with_zero_pii(): void
    {
        [$vehicle, $driver, $driverUser] = $this->createVerifiedVehicleAndDriver();

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'publicvalidtoken123456789012345',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/qr/{$qrCode->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'token' => $qrCode->token,
                    'qr_status' => 'active',
                    'vehicle' => [
                        'vehicle_code' => $vehicle->vehicle_code,
                        'registration_number' => $vehicle->registration_number,
                        'vehicle_type' => $vehicle->vehicle_type,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'color' => $vehicle->color,
                        'is_verified' => true,
                    ],
                    'driver' => [
                        'driver_code' => $driver->driver_code,
                        'rating_avg' => 4.90,
                        'is_verified' => true,
                    ],
                    'is_journey_available' => true,
                ],
            ]);

        // Zero-PII assertions: phone, name, email must never appear in response
        $content = $response->getContent();
        $this->assertStringNotContainsString($driverUser->phone, $content);
        $this->assertStringNotContainsString($driverUser->name, $content);
    }

    public function test_public_resolver_rejects_invalid_disabled_and_revoked_tokens(): void
    {
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        // Disabled QR
        $disabledQr = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'disabledtoken123456789012345678',
            'version' => 1,
            'status' => 'disabled',
        ]);

        $this->getJson("/api/v1/qr/{$disabledQr->token}")
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'This LostFinder QR is unavailable.',
            ]);

        // Revoked QR
        $revokedQr = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'revokedtoken1234567890123456789',
            'version' => 2,
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->getJson("/api/v1/qr/{$revokedQr->token}")
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'This LostFinder QR is unavailable.',
            ]);

        // Non-existent token
        $this->getJson('/api/v1/qr/completelyinvalidtoken999999999')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'This LostFinder QR is unavailable.',
            ]);
    }

    public function test_public_scan_logs_telemetry_in_database(): void
    {
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'telemetrytoken12345678901234567',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $this->withServerVariables([
            'REMOTE_ADDR' => '103.21.244.15',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ])->getJson("/api/v1/qr/{$qrCode->token}");

        $this->assertDatabaseHas('qr_scan_logs', [
            'qr_code_id' => $qrCode->id,
            'vehicle_id' => $vehicle->id,
            'ip_address' => '103.21.244.15',
        ]);

        $log = QrScanLog::where('qr_code_id', $qrCode->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('iPhone', $log->user_agent);
    }

    public function test_web_resolver_renders_html_landing_page(): void
    {
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'webtokentest1234567890123456789',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $response = $this->get("/q/{$qrCode->token}");

        $response->assertStatus(200)
            ->assertSee('LostFinder')
            ->assertSee($vehicle->registration_number)
            ->assertSee('VERIFIED VEHICLE');
    }

    public function test_admin_can_download_svg_sticker_and_view_scan_logs(): void
    {
        [$admin, $adminUser, $token] = $this->createAdmin();
        [$vehicle] = $this->createVerifiedVehicleAndDriver();

        $qrCode = QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'downloadtokentest12345678901234',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Download SVG sticker
        $downloadResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->get("/api/v1/admin/qr/{$qrCode->id}/download");

        $downloadResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false)
            ->assertSee($vehicle->registration_number)
            ->assertSee('LOSTFINDER');

        // Telemetry scan
        QrScanLog::create([
            'qr_code_id' => $qrCode->id,
            'vehicle_id' => $vehicle->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'LostFinder-MobileApp/1.0',
            'scanned_at' => now(),
        ]);

        // View logs
        $logsResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/qr/{$qrCode->id}/logs");

        $logsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 1,
                ],
            ])
            ->assertJsonFragment([
                'ip_address' => '192.168.1.1',
            ]);
    }
}
