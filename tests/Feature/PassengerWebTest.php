<?php

namespace Tests\Feature;

use App\Models\DriverProfile;
use App\Models\ItemCategory;
use App\Models\PromotionBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassengerWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_passenger_homepage_renders_successfully()
    {
        ItemCategory::create([
            'name' => 'Backpack',
            'slug' => 'backpack',
            'icon' => 'bag',
            'is_active' => true,
        ]);

        PromotionBanner::create([
            'title' => 'Varanasi Safe Transit',
            'image_url' => 'https://example.com/banner.jpg',
            'placement' => 'tourist_home',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('LostFinder');
        $response->assertSee('Varanasi Safe Transit');
        $response->assertSee('Backpack');
    }

    public function test_qr_landing_page_renders_with_scanned_driver_details()
    {
        $driverUser = User::create([
            'name' => 'Ramesh Kumar',
            'phone' => '+919876543210',
            'role' => 'driver',
            'is_active' => true,
        ]);

        $driver = DriverProfile::create([
            'user_id' => $driverUser->id,
            'vehicle_number' => 'UP65-AB-9999',
            'vehicle_type' => 'auto',
            'is_verified' => true,
            'qr_code_token' => 'qr_token_test_12345',
        ]);

        $response = $this->get('/ride/qr/qr_token_test_12345');

        $response->assertStatus(200);
        $response->assertSee('UP65-AB-9999');
        $response->assertSee('Ramesh Kumar');
    }

    public function test_claims_and_rides_web_urls_load_safely()
    {
        $this->get('/claims')->assertStatus(200);
        $this->get('/rides')->assertStatus(200);
    }
}
