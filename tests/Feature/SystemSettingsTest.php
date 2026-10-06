<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $touristUser;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'support'], ['description' => 'Customer Support']);
        AdminRole::firstOrCreate(['name' => 'verification_team'], ['description' => 'KYC Verification Team']);

        // Create Admin
        $this->adminUser = User::create([
            'phone' => '+919999900001',
            'name' => 'Admin Boss',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin = AdminUser::create([
            'user_id' => $this->adminUser->id,
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        // Create Tourist
        $this->touristUser = User::create([
            'phone' => '+919999900002',
            'name' => 'Normal Tourist',
            'role' => 'tourist',
            'status' => 'active',
        ]);
    }

    public function test_public_settings_endpoint_returns_public_settings_and_hides_private_keys(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Public system settings retrieved successfully.',
            ]);

        $data = $response->json('data');

        // Verify public keys are present
        $this->assertArrayHasKey('general.app_name', $data);
        $this->assertEquals('LostFinder', $data['general.app_name']);
        $this->assertArrayHasKey('features.enable_realtime_chat', $data);
        $this->assertArrayHasKey('features.enable_push_notifications', $data);
        $this->assertArrayHasKey('features.enable_driver_onboarding', $data);

        // Verify private keys & secrets are NOT present
        $this->assertArrayNotHasKey('third_party.fcm_server_key', $data);
        $this->assertArrayNotHasKey('third_party.sms_auth_token', $data);
        $this->assertArrayNotHasKey('general.journey_ttl_hours', $data);
        $this->assertArrayNotHasKey('security.otp_ttl_minutes', $data);
    }

    public function test_admin_can_retrieve_all_grouped_settings_with_masked_secrets(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $settings = $response->json('data.settings');

        // Verify groups exist
        $this->assertArrayHasKey('general', $settings);
        $this->assertArrayHasKey('features', $settings);
        $this->assertArrayHasKey('third_party', $settings);
        $this->assertArrayHasKey('security', $settings);

        // Check masked encrypted secret
        $fcmItem = collect($settings['third_party'])->firstWhere('key', 'third_party.fcm_server_key');
        $this->assertNotNull($fcmItem);
        $this->assertTrue($fcmItem['is_encrypted']);
        $this->assertEquals('••••••••', $fcmItem['value']);
    }

    public function test_admin_can_batch_update_settings_and_generates_audit_logs(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'settings' => [
                'general.app_name' => 'LostFinder Ultra',
                'features.enable_realtime_chat' => false,
                'security.otp_max_attempts' => 5,
            ],
        ];

        $response = $this->patchJson('/api/v1/admin/settings', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 3,
                ],
            ]);

        // Verify database updated
        $this->assertDatabaseHas('system_settings', [
            'key' => 'general.app_name',
            'value' => 'LostFinder Ultra',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'features.enable_realtime_chat',
            'value' => '0',
        ]);

        // Verify AuditLog was recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'setting.updated',
            'auditable_type' => SystemSetting::class,
        ]);

        // Verify public endpoint reflects updated cache
        $publicResponse = $this->getJson('/api/v1/settings/public');
        $publicData = $publicResponse->json('data');
        $this->assertEquals('LostFinder Ultra', $publicData['general.app_name']);
        $this->assertFalse($publicData['features.enable_realtime_chat']);
    }

    public function test_unauthenticated_and_tourist_cannot_access_or_modify_admin_settings(): void
    {
        // Unauthenticated access
        $this->getJson('/api/v1/admin/settings')->assertStatus(401);
        $this->patchJson('/api/v1/admin/settings', ['settings' => ['general.app_name' => 'Hacked']])->assertStatus(401);
        $this->getJson('/api/v1/admin/permissions')->assertStatus(401);

        // Tourist access
        Sanctum::actingAs($this->touristUser);
        $this->getJson('/api/v1/admin/settings')->assertStatus(403);
        $this->patchJson('/api/v1/admin/settings', ['settings' => ['general.app_name' => 'Hacked']])->assertStatus(403);
        $this->getJson('/api/v1/admin/permissions')->assertStatus(403);
    }

    public function test_admin_can_retrieve_permissions_and_role_matrix(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/permissions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('categories', $data);

        // Verify categories include Governance & Settings
        $categories = $data['categories'];
        $this->assertArrayHasKey('Governance & Settings', $categories);
        $this->assertArrayHasKey('settings.view', $categories['Governance & Settings']);
        $this->assertArrayHasKey('settings.update', $categories['Governance & Settings']);
    }

    public function test_setting_update_validation_fails_on_empty_payload(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->patchJson('/api/v1/admin/settings', [
            'settings' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}
