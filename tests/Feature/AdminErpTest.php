<?php

namespace Tests\Feature;

use App\Models\DriverProfile;
use App\Models\LostClaim;
use App\Models\Passenger;
use App\Models\RideSession;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminErpTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name'      => 'Super Admin',
            'email'     => 'admin@lostfinder.app',
            'phone'     => '+910000000001',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login()
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_tourist_cannot_access_admin_panel()
    {
        $tourist = User::create([
            'name'      => 'Tourist User',
            'phone'     => '+919999911111',
            'role'      => 'tourist',
            'is_active' => true,
        ]);

        $response = $this->actingAs($tourist)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_driver_cannot_access_admin_panel()
    {
        $driver = User::create([
            'name'      => 'Driver User',
            'phone'     => '+919999922222',
            'role'      => 'driver',
            'is_active' => true,
        ]);

        $response = $this->actingAs($driver)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_deactivated_admin_cannot_access_admin_panel()
    {
        $inactiveAdmin = User::create([
            'name'      => 'Suspended Admin',
            'email'     => 'badadmin@lostfinder.app',
            'phone'     => '+910000000002',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveAdmin)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_active_admin_can_access_dashboard_and_all_erp_sections()
    {
        $admin = $this->createAdmin();

        // 1. Dashboard
        $dashboardResponse = $this->actingAs($admin)->get('/admin');
        $dashboardResponse->assertStatus(200);

        // 2. Driver Profiles
        $driversResponse = $this->actingAs($admin)->get('/admin/driver-profiles');
        $driversResponse->assertStatus(200);

        // 3. Lost Claims
        $claimsResponse = $this->actingAs($admin)->get('/admin/lost-claims');
        $claimsResponse->assertStatus(200);

        // 4. Ride Sessions
        $ridesResponse = $this->actingAs($admin)->get('/admin/ride-sessions');
        $ridesResponse->assertStatus(200);

        // 5. Users Management
        $usersResponse = $this->actingAs($admin)->get('/admin/users');
        $usersResponse->assertStatus(200);
    }

    public function test_admin_can_view_driver_and_claims_data_in_database()
    {
        $admin = $this->createAdmin();

        $driverUser = User::create([
            'name'  => 'Ramesh Driver',
            'phone' => '+919876543210',
            'role'  => 'driver',
        ]);

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB1234',
            'vehicle_type'   => 'auto',
            'license_number' => 'DL12345678',
            'qr_code_token'  => 'DRV_TEST001',
            'is_verified'    => false,
        ]);

        $touristUser = User::create(['name' => 'Alice', 'phone' => '+919123456789', 'role' => 'tourist']);
        $passenger = Passenger::create(['user_id' => $touristUser->id, 'masked_alias' => 'Passenger #1234']);

        $ride = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'status'            => 'flagged',
        ]);

        $claim = LostClaim::create([
            'ride_session_id'  => $ride->id,
            'item_category'    => 'Smartphone',
            'item_description' => 'iPhone 15 Pro Max',
            'handover_otp'     => '123456',
            'claim_status'     => 'reported',
            'bounty_amount'    => 500.00,
        ]);

        $response = $this->actingAs($admin)->get('/admin/lost-claims');
        $response->assertStatus(200);

        // Verify database records
        $this->assertDatabaseHas('driver_profiles', ['vehicle_number' => 'UP65AB1234', 'is_verified' => false]);
        $this->assertDatabaseHas('lost_claims', ['item_category' => 'Smartphone', 'claim_status' => 'reported']);
    }
}
