<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DriverProfile;
use App\Models\ItemCategory;
use App\Models\PromotionBanner;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerDrivenConfigAndAdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_bootstrap_returns_complete_server_driven_payload()
    {
        ItemCategory::create([
            'name'             => 'Smartphone',
            'slug'             => 'smartphone',
            'suggested_bounty' => 500.00,
            'is_active'        => true,
        ]);

        PromotionBanner::create([
            'title'       => 'Special Offer',
            'image_url'   => 'https://example.com/banner.jpg',
            'action_type' => 'url',
            'placement'   => 'tourist_home',
            'is_active'   => true,
        ]);

        AppSetting::set('emergency_police_number', '112', 'support');

        $response = $this->getJson('/api/v1/app/bootstrap?role=tourist&app_version=1.0.0');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'maintenance' => ['is_active', 'message'],
                    'version_control' => ['min_version', 'latest_version', 'force_update', 'has_update'],
                    'item_categories',
                    'vehicle_types',
                    'feature_flags' => ['enable_bounties', 'enable_sos_emergency', 'enable_promotions'],
                    'support' => ['police_helpline', 'support_phone', 'support_email'],
                    'promotions',
                ],
            ]);

        $this->assertEquals('112', $response->json('data.support.police_helpline'));
        $this->assertNotEmpty($response->json('data.item_categories'));
    }

    public function test_force_update_flag_triggers_when_client_version_is_outdated()
    {
        AppSetting::set('min_version_tourist', '2.0.0', 'app_versions');
        AppSetting::set('latest_version_tourist', '2.5.0', 'app_versions');

        $response = $this->getJson('/api/v1/app/bootstrap?role=tourist&app_version=1.0.0');

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.version_control.force_update'));
        $this->assertTrue($response->json('data.version_control.has_update'));
    }

    public function test_promotions_endpoint_returns_active_banners_and_tracks_impressions()
    {
        $banner = PromotionBanner::create([
            'title'        => 'Varanasi Boat Rides',
            'image_url'    => 'https://example.com/boat.jpg',
            'action_type'  => 'url',
            'placement'    => 'tourist_home',
            'is_active'    => true,
            'impressions'  => 5,
        ]);

        $response = $this->getJson('/api/v1/app/promotions?placement=tourist_home');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Impression counter must be incremented
        $this->assertEquals(6, $banner->fresh()->impressions);
    }

    public function test_banner_click_tracking_increments_counter()
    {
        $banner = PromotionBanner::create([
            'title'         => 'Local Guide Tour',
            'image_url'     => 'https://example.com/guide.jpg',
            'action_type'   => 'url',
            'action_target' => 'https://touristguide.com',
            'placement'     => 'tourist_home',
            'clicks'        => 10,
            'is_active'     => true,
        ]);

        $response = $this->postJson('/api/v1/app/promotions/' . $banner->id . '/click');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'banner_id'     => $banner->id,
                    'action_target' => 'https://touristguide.com',
                ],
            ]);

        $this->assertEquals(11, $banner->fresh()->clicks);
    }

    public function test_categories_endpoint_returns_active_categories()
    {
        ItemCategory::create([
            'name'             => 'Wallet',
            'slug'             => 'wallet',
            'suggested_bounty' => 300.00,
            'is_active'        => true,
        ]);

        ItemCategory::create([
            'name'             => 'Hidden Item',
            'slug'             => 'hidden',
            'is_active'        => false,
        ]);

        $response = $this->getJson('/api/v1/app/categories');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Wallet', $response->json('data.0.name'));
    }

    public function test_admin_managed_sms_service_operates_according_to_settings()
    {
        // 1. Mock mode
        AppSetting::set('sms_enabled', 'false', 'sms', 'boolean');
        AppSetting::set('sms_provider', 'mock', 'sms');

        $smsService = app(SmsService::class);
        $this->assertTrue($smsService->sendOtp('+919876543210', '123456'));

        // 2. Change settings dynamically
        AppSetting::set('sms_otp_template', 'Code: :otp for LostFinder.', 'sms');
        $this->assertEquals('Code: :otp for LostFinder.', AppSetting::get('sms_otp_template'));
    }

    public function test_admin_managed_notification_service_dispatches_cleanly()
    {
        $driverUser = User::create([
            'name'  => 'Driver Raj',
            'phone' => '+919988001122',
            'role'  => 'driver',
        ]);

        $driver = DriverProfile::create([
            'user_id'        => $driverUser->id,
            'vehicle_number' => 'UP65AB0001',
            'vehicle_type'   => 'auto',
            'qr_code_token'  => 'DRV_TESTFCM1',
            'fcm_token'      => 'sample_fcm_token_xyz',
        ]);

        AppSetting::set('fcm_enabled', 'false', 'push', 'boolean');

        $notificationService = app(NotificationService::class);
        $result = $notificationService->sendToDriver($driver, 'Test Title', 'Test Body');

        $this->assertTrue($result);
    }
}
