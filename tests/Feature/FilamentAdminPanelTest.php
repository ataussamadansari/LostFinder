<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $driverUser;
    protected User $touristUser;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);

        // Create Admin User
        $this->adminUser = User::create([
            'name' => 'Admin Controller',
            'email' => 'admin@lostfinder.com',
            'phone' => '+919999900010',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin = AdminUser::create([
            'user_id' => $this->adminUser->id,
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        // Create Driver User
        $this->driverUser = User::create([
            'name' => 'Driver Dan',
            'email' => 'driver@lostfinder.com',
            'phone' => '+919999900020',
            'password' => bcrypt('password123'),
            'role' => 'driver',
            'status' => 'active',
        ]);

        // Create Tourist User
        $this->touristUser = User::create([
            'name' => 'Tourist Tara',
            'email' => 'tourist@lostfinder.com',
            'phone' => '+919999900030',
            'password' => bcrypt('password123'),
            'role' => 'tourist',
            'status' => 'active',
        ]);
    }

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_access_filament_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertSuccessful();
    }

    public function test_tourist_and_driver_are_forbidden_from_filament_panel(): void
    {
        // Tourist attempt
        $touristResponse = $this->actingAs($this->touristUser)->get('/admin');
        $touristResponse->assertForbidden();

        // Driver attempt
        $driverResponse = $this->actingAs($this->driverUser)->get('/admin');
        $driverResponse->assertForbidden();
    }

    public function test_suspended_admin_is_forbidden_from_filament_panel(): void
    {
        $this->adminUser->update(['status' => 'suspended']);

        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertForbidden();
    }
}
