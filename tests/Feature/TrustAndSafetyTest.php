<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\Media;
use App\Models\Rating;
use App\Models\Report;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrustAndSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'support'], ['description' => 'Support Staff']);
    }

    protected function setupJourneyWithDriverAndPassenger(): array
    {
        $driverUser = User::create([
            'phone' => '+919876543201',
            'name' => 'Karan Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-TS001',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rating_avg' => 5.0,
            'rating_count' => 0,
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-TS001',
            'registration_number' => 'KA01TS001',
            'vehicle_type' => 'cab',
            'make' => 'Hyundai',
            'model' => 'Aura',
            'color' => 'Silver',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);

        VehicleDriverAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHours(2),
            'expires_at' => now()->addHours(70),
            'status' => 'completed',
        ]);

        $passengerUser = User::create([
            'phone' => '+919811199991',
            'name' => 'Simran Tourist',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $passengerUser->id,
            'connected_at' => now()->subHours(2),
            'status' => 'active',
        ]);

        return [$driverUser, $driver, $journey, $passengerUser];
    }

    public function test_passenger_can_submit_rating_and_updates_driver_average(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson('/api/v1/ratings', [
            'journey_uuid' => $journey->uuid,
            'rating' => 5,
            'comment' => 'Very polite driver, returned my wallet promptly!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'rating' => 5,
                    'comment' => 'Very polite driver, returned my wallet promptly!',
                ],
            ]);

        $this->assertDatabaseHas('ratings', [
            'journey_id' => $journey->id,
            'from_user_id' => $passengerUser->id,
            'to_user_id' => $driverUser->id,
            'rating' => 5,
        ]);

        $driver->refresh();
        $this->assertEquals(5.00, (float) $driver->rating_avg);
        $this->assertEquals(1, $driver->rating_count);

        // Add second passenger rating on same driver (via another journey)
        $passenger2 = User::create([
            'phone' => '+919811199992',
            'name' => 'Rahul Tourist',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $journey2 = Journey::create([
            'vehicle_id' => $journey->vehicle_id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'expires_at' => now()->addHours(71),
            'status' => 'completed',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey2->id,
            'passenger_id' => $passenger2->id,
            'connected_at' => now()->subHour(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($passenger2);
        $res2 = $this->postJson('/api/v1/ratings', [
            'journey_uuid' => $journey2->uuid,
            'rating' => 3,
            'comment' => 'Okay ride.',
        ]);
        $res2->assertStatus(201);

        $driver->refresh();
        // Average of 5 and 3 is 4.00
        $this->assertEquals(4.00, (float) $driver->rating_avg);
        $this->assertEquals(2, $driver->rating_count);
    }

    public function test_duplicate_rating_is_rejected(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        Sanctum::actingAs($passengerUser);

        // First rating succeeds
        $res1 = $this->postJson('/api/v1/ratings', [
            'journey_uuid' => $journey->uuid,
            'rating' => 4,
        ]);
        $res1->assertStatus(201);

        // Second rating on same journey fails
        $res2 = $this->postJson('/api/v1/ratings', [
            'journey_uuid' => $journey->uuid,
            'rating' => 5,
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You have already submitted a rating for this journey.',
            ]);
    }

    public function test_unregistered_passenger_cannot_rate_journey(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        $stranger = User::create([
            'phone' => '+919877766655',
            'name' => 'Stranger',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        Sanctum::actingAs($stranger);

        $res = $this->postJson('/api/v1/ratings', [
            'journey_uuid' => $journey->uuid,
            'rating' => 5,
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You were not a registered passenger on this journey.',
            ]);
    }

    public function test_user_can_submit_incident_report_with_evidence(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        $evidenceMedia = Media::create([
            'disk' => 'private',
            'path' => 'reports/scratch_evidence.jpg',
            'original_name' => 'scratch_evidence.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 150000,
            'visibility' => 'private',
            'uploaded_by' => $passengerUser->id,
        ]);

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson('/api/v1/reports', [
            'type' => 'misconduct',
            'description' => 'Driver demanded extra cash payment off-meter during the journey.',
            'reported_user_id' => $driverUser->id,
            'vehicle_id' => $journey->vehicle_id,
            'evidence_media_ids' => [$evidenceMedia->id],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'misconduct',
                    'status' => 'open',
                ],
            ]);

        $reportId = $response->json('data.id');

        $this->assertDatabaseHas('reports', [
            'id' => $reportId,
            'reporter_id' => $passengerUser->id,
            'reported_user_id' => $driverUser->id,
            'type' => 'misconduct',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('report_evidence', [
            'report_id' => $reportId,
            'media_id' => $evidenceMedia->id,
        ]);
    }

    public function test_report_with_unowned_evidence_rejected(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        $strangerMedia = Media::create([
            'disk' => 'private',
            'path' => 'reports/stranger.jpg',
            'original_name' => 'stranger.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 150000,
            'visibility' => 'private',
            'uploaded_by' => $driverUser->id,
        ]);

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson('/api/v1/reports', [
            'type' => 'fraud',
            'description' => 'Attempting to use another user media evidence illegally.',
            'evidence_media_ids' => [$strangerMedia->id],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'One or more evidence media files do not belong to you or do not exist.',
            ]);
    }

    public function test_admin_can_list_and_resolve_report(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        $report = Report::create([
            'reporter_id' => $passengerUser->id,
            'reported_user_id' => $driverUser->id,
            'type' => 'misconduct',
            'description' => 'Driver was aggressive when passenger asked for receipt.',
            'status' => 'open',
        ]);

        $adminUser = User::create([
            'phone' => '+919999900001',
            'name' => 'Safety Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin = AdminUser::create([
            'user_id' => $adminUser->id,
            'employee_id' => 'EMP-SAF001',
            'department' => 'trust_and_safety',
            'status' => 'active',
        ]);

        $adminRole = AdminRole::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole->id);

        Sanctum::actingAs($adminUser);

        // List reports
        $listRes = $this->getJson('/api/v1/admin/reports?status=open');
        $listRes->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertCount(1, $listRes->json('data.reports'));

        // View single report
        $showRes = $this->getJson("/api/v1/admin/reports/{$report->id}");
        $showRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $report->id,
                    'type' => 'misconduct',
                ],
            ]);

        // Resolve report
        $updateRes = $this->patchJson("/api/v1/admin/reports/{$report->id}", [
            'status' => 'resolved',
            'notes' => 'Driver issued warning and re-trained on conduct guidelines.',
        ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'resolved',
                    'resolved_by' => $adminUser->id,
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'resolved_by' => $adminUser->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminUser->id,
            'action' => 'report.resolved',
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
        ]);
    }

    public function test_user_can_block_and_unblock_another_user(): void
    {
        [$driverUser, $driver, $journey, $passengerUser] = $this->setupJourneyWithDriverAndPassenger();

        Sanctum::actingAs($passengerUser);

        // Block driver
        $blockRes = $this->postJson("/api/v1/users/{$driverUser->id}/block", [
            'reason' => 'Driver sent unsolicited contact.',
        ]);

        $blockRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'blocked_user_id' => $driverUser->id,
                    'reason' => 'Driver sent unsolicited contact.',
                ],
            ]);

        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $passengerUser->id,
            'blocked_user_id' => $driverUser->id,
        ]);

        // List blocked users
        $listRes = $this->getJson('/api/v1/users/blocked');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data.blocked_users'));

        // Unblock driver
        $unblockRes = $this->deleteJson("/api/v1/users/{$driverUser->id}/block");
        $unblockRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unblocked_user_id' => $driverUser->id,
                ],
            ]);

        $this->assertDatabaseMissing('blocked_users', [
            'user_id' => $passengerUser->id,
            'blocked_user_id' => $driverUser->id,
        ]);
    }

    public function test_admin_can_query_audit_logs(): void
    {
        $adminUser = User::create([
            'phone' => '+919999900002',
            'name' => 'Audit Officer',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin = AdminUser::create([
            'user_id' => $adminUser->id,
            'employee_id' => 'EMP-AUD001',
            'department' => 'compliance',
            'status' => 'active',
        ]);

        $adminRole = AdminRole::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole->id);

        AuditLog::create([
            'user_id' => $adminUser->id,
            'action' => 'driver.verified',
            'auditable_type' => Driver::class,
            'auditable_id' => 1,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'verified'],
            'created_at' => now(),
        ]);

        Sanctum::actingAs($adminUser);

        $response = $this->getJson('/api/v1/admin/audit-logs?action=driver.verified');
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(1, $response->json('data.audit_logs'));
        $this->assertEquals('driver.verified', $response->json('data.audit_logs.0.action'));
    }

    public function test_regular_user_cannot_access_audit_logs(): void
    {
        $tourist = User::create([
            'phone' => '+919999900003',
            'name' => 'Regular Tourist',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tourist);

        $response = $this->getJson('/api/v1/admin/audit-logs');
        $response->assertStatus(403);
    }
}
