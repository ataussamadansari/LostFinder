<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\Journey;
use App\Models\LostItem;
use App\Models\LostItemTicket;
use App\Models\Media;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default roles
        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'support'], ['description' => 'Customer Support']);
        AdminRole::firstOrCreate(['name' => 'verification_team'], ['description' => 'KYC Verification Team']);
    }

    public function test_tourist_role_attributes(): void
    {
        $user = User::create([
            'phone' => '+919111111111',
            'name' => 'Tourist Tina',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $this->assertTrue($user->isTourist());
        $this->assertFalse($user->isDriver());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->hasPermission('drivers.verify'));
    }

    public function test_driver_role_attributes(): void
    {
        $user = User::create([
            'phone' => '+919222222222',
            'name' => 'Driver Dave',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_code' => 'DRV-DAVE01',
            'verification_status' => 'pending',
        ]);

        $this->assertFalse($user->isTourist());
        $this->assertTrue($user->isDriver());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($driver->isVerified());

        $driver->update(['verification_status' => 'verified', 'verified_at' => now()]);
        $this->assertTrue($driver->fresh()->isVerified());
    }

    public function test_admin_role_and_permission_hierarchy(): void
    {
        $user = User::create([
            'phone' => '+919333333333',
            'name' => 'Admin Alex',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $adminUser = AdminUser::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $adminUser->assignRole('admin');

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasAdminRole('admin'));
        $this->assertFalse($user->isSuperAdmin());

        // Admin has drivers.verify and qr.generate
        $this->assertTrue($user->hasPermission('drivers.verify'));
        $this->assertTrue($user->hasPermission('qr.generate'));
        $this->assertTrue($user->hasPermission('lost_items.manage'));

        // Super Admin check
        $adminUser->assignRole('super_admin');
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission('arbitrary.nonexistent.action')); // wildcard
    }

    public function test_verification_team_restricted_permissions(): void
    {
        $user = User::create([
            'phone' => '+919444444444',
            'name' => 'Verifier Val',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $adminUser = AdminUser::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $adminUser->assignRole('verification_team');

        $this->assertTrue($user->hasPermission('drivers.verify'));
        $this->assertTrue($user->hasPermission('vehicles.verify'));
        $this->assertTrue($user->hasPermission('documents.verify'));

        // Cannot manage tickets or view audit logs
        $this->assertFalse($user->hasPermission('lost_items.manage'));
        $this->assertFalse($user->hasPermission('audit_logs.view'));
    }

    public function test_suspended_admin_loses_all_permissions(): void
    {
        $user = User::create([
            'phone' => '+919555555555',
            'name' => 'Suspended Staff',
            'role' => 'admin',
            'status' => 'suspended', // User suspended
        ]);

        $adminUser = AdminUser::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $adminUser->assignRole('super_admin');

        $this->assertFalse($user->hasPermission('drivers.verify'));
        $this->assertFalse($user->hasPermission('any.permission'));
    }

    public function test_driver_policy_prevents_self_verification(): void
    {
        // A driver who also has a verification_team admin role
        $driverUser = User::create([
            'phone' => '+919666666666',
            'name' => 'Self Verifier Attempt',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-SELF01',
            'verification_status' => 'pending',
        ]);

        $adminUser = AdminUser::create([
            'user_id' => $driverUser->id,
            'status' => 'active',
        ]);
        $adminUser->assignRole('verification_team');

        // Cannot verify own driver record
        $this->assertFalse(Gate::forUser($driverUser)->allows('verify', $driver));

        // Independent verifier can verify
        $otherVerifier = User::create([
            'phone' => '+919777777777',
            'name' => 'Independent Verifier',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $otherAdmin = AdminUser::create(['user_id' => $otherVerifier->id, 'status' => 'active']);
        $otherAdmin->assignRole('verification_team');

        $this->assertTrue(Gate::forUser($otherVerifier)->allows('verify', $driver));
    }

    public function test_ticket_policy_enforces_resource_ownership(): void
    {
        $passenger = User::create(['phone' => '+919888888881', 'name' => 'Passenger Pat', 'role' => 'tourist', 'status' => 'active']);
        $otherPassenger = User::create(['phone' => '+919888888882', 'name' => 'Other Passenger', 'role' => 'tourist', 'status' => 'active']);

        $driverUser = User::create(['phone' => '+919888888883', 'name' => 'Driver Dan', 'role' => 'driver', 'status' => 'active']);
        $driver = Driver::create(['user_id' => $driverUser->id, 'driver_code' => 'DRV-DAN001', 'verification_status' => 'verified']);

        $otherDriverUser = User::create(['phone' => '+919888888884', 'name' => 'Driver Bob', 'role' => 'driver', 'status' => 'active']);
        $otherDriver = Driver::create(['user_id' => $otherDriverUser->id, 'driver_code' => 'DRV-BOB001', 'verification_status' => 'verified']);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-999001',
            'registration_number' => 'DL04AB1234',
            'vehicle_type' => 'cab',
            'verification_status' => 'verified',
        ]);

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHours(2),
            'expires_at' => now()->addHours(70),
            'status' => 'active',
        ]);

        $lostItem = LostItem::create([
            'category' => 'wallet',
            'name' => 'Leather Wallet',
            'description' => 'Black leather wallet with cards',
            'status' => 'reported',
        ]);

        $ticket = LostItemTicket::create([
            'ticket_number' => 'LF-2026-000099',
            'journey_id' => $journey->id,
            'lost_item_id' => $lostItem->id,
            'passenger_id' => $passenger->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'created',
        ]);

        // Passenger ownership
        $this->assertTrue(Gate::forUser($passenger)->allows('view', $ticket));
        $this->assertTrue(Gate::forUser($passenger)->allows('cancel', $ticket));

        // Unrelated passenger cannot view or cancel
        $this->assertFalse(Gate::forUser($otherPassenger)->allows('view', $ticket));
        $this->assertFalse(Gate::forUser($otherPassenger)->allows('cancel', $ticket));

        // Assigned driver ownership
        $this->assertTrue(Gate::forUser($driverUser)->allows('view', $ticket));

        // Unrelated driver cannot view
        $this->assertFalse(Gate::forUser($otherDriverUser)->allows('view', $ticket));

        // Support staff can view
        $supportUser = User::create(['phone' => '+919888888885', 'name' => 'Support Sam', 'role' => 'admin', 'status' => 'active']);
        $supportAdmin = AdminUser::create(['user_id' => $supportUser->id, 'status' => 'active']);
        $supportAdmin->assignRole('support');

        $this->assertTrue(Gate::forUser($supportUser)->allows('view', $ticket));
    }

    public function test_driver_document_privacy_policy(): void
    {
        $driverUser = User::create(['phone' => '+919888888891', 'name' => 'Driver Document Owner', 'role' => 'driver', 'status' => 'active']);
        $driver = Driver::create(['user_id' => $driverUser->id, 'driver_code' => 'DRV-DOC001', 'verification_status' => 'pending']);

        $media = Media::create([
            'disk' => 'private',
            'path' => 'drivers/documents/test_id.pdf',
            'original_name' => 'id.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'visibility' => 'private',
            'uploaded_by' => $driverUser->id,
        ]);

        $document = DriverDocument::create([
            'driver_id' => $driver->id,
            'document_type' => 'government_id',
            'media_id' => $media->id,
            'status' => 'pending',
        ]);

        $otherUser = User::create(['phone' => '+919888888892', 'name' => 'Random Tourist', 'role' => 'tourist', 'status' => 'active']);

        // Owner driver can view
        $this->assertTrue(Gate::forUser($driverUser)->allows('view', $document));

        // Random user cannot view sensitive KYC document
        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $document));

        // Verification team can view
        $verifierUser = User::create(['phone' => '+919888888893', 'name' => 'Verifier Vik', 'role' => 'admin', 'status' => 'active']);
        $verifierAdmin = AdminUser::create(['user_id' => $verifierUser->id, 'status' => 'active']);
        $verifierAdmin->assignRole('verification_team');

        $this->assertTrue(Gate::forUser($verifierUser)->allows('view', $document));
        $this->assertTrue(Gate::forUser($verifierUser)->allows('verify', $document));
    }
}
