<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\DriverProfile;
use App\Models\ItemCategory;
use App\Models\LostClaim;
use App\Models\Passenger;
use App\Models\PromotionBanner;
use App\Models\RideSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Super Admin Account for Filament ERP
        $admin = User::firstOrCreate(
            ['email' => 'admin@lostfinder.app'],
            [
                'name'              => 'Super Admin',
                'phone'             => '+910000000000',
                'password'          => Hash::make('admin123'),
                'role'              => 'admin',
                'is_active'         => true,
                'phone_verified_at' => now(),
            ]
        );

        // 2. Demo Driver Account
        $driverUser = User::firstOrCreate(
            ['phone' => '+919876543210'],
            [
                'name'              => 'Ramesh Yadav',
                'email'             => 'driver@lostfinder.app',
                'password'          => Hash::make('driver123'),
                'role'              => 'driver',
                'is_active'         => true,
                'phone_verified_at' => now(),
            ]
        );

        $driverProfile = DriverProfile::firstOrCreate(
            ['user_id' => $driverUser->id],
            [
                'vehicle_number' => 'UP 65 AB 1234',
                'vehicle_type'   => 'auto',
                'license_number' => 'DL1420110012345',
                'qr_code_token'  => 'DRV_DEMO123456',
                'is_verified'    => true,
                'total_trips'    => 24,
            ]
        );

        // 3. Demo Tourist / Passenger Account
        $touristUser = User::firstOrCreate(
            ['phone' => '+919123456789'],
            [
                'name'                    => 'Alice Johnson',
                'email'                   => 'tourist@lostfinder.app',
                'role'                    => 'tourist',
                'emergency_contact_phone' => '+919999988888',
                'is_active'               => true,
                'phone_verified_at'       => now(),
            ]
        );

        $passenger = Passenger::firstOrCreate(
            ['user_id' => $touristUser->id],
            [
                'masked_alias'   => 'Passenger #ALICE',
                'preferred_lang' => 'en',
            ]
        );

        // 4. Demo Ride Session
        $rideSession = RideSession::firstOrCreate(
            [
                'driver_profile_id' => $driverProfile->id,
                'passenger_id'      => $passenger->id,
            ],
            [
                'scan_latitude'  => 25.3176,
                'scan_longitude' => 82.9739,
                'status'         => 'flagged',
            ]
        );

        // 5. Demo Lost Item Claim
        LostClaim::firstOrCreate(
            ['ride_session_id' => $rideSession->id],
            [
                'item_category'    => 'Smartphone',
                'item_description' => 'Black iPhone 15 with yellow protective case, left on rear passenger seat.',
                'handover_otp'     => '582910',
                'claim_status'     => 'found',
                'bounty_amount'    => 500.00,
            ]
        );

        // 6. Admin-Managed App Settings (SMS, Push, App Config, Helplines)
        $defaultSettings = [
            // SMS Gateway Settings
            ['key' => 'sms_enabled', 'value' => 'false', 'group' => 'sms', 'type' => 'boolean', 'description' => 'Enable or disable outgoing SMS across the platform'],
            ['key' => 'sms_provider', 'value' => 'mock', 'group' => 'sms', 'type' => 'string', 'description' => 'Active provider: mock, fast2sms, msg91, twilio'],
            ['key' => 'sms_api_key', 'value' => '', 'group' => 'sms', 'type' => 'string', 'description' => 'Provider authorization token / API key'],
            ['key' => 'sms_sender_id', 'value' => 'LSTFND', 'group' => 'sms', 'type' => 'string', 'description' => 'Sender Header ID (6 characters)'],
            ['key' => 'sms_otp_template', 'value' => 'Your LostFinder OTP is :otp. Valid for 5 minutes. Do not share it.', 'group' => 'sms', 'type' => 'string', 'description' => 'OTP SMS template'],

            // Push Notifications (FCM)
            ['key' => 'fcm_enabled', 'value' => 'false', 'group' => 'push', 'type' => 'boolean', 'description' => 'Enable Firebase Cloud Messaging push alerts'],
            ['key' => 'fcm_server_key', 'value' => '', 'group' => 'push', 'type' => 'string', 'description' => 'FCM Legacy Server Key or Service Token'],

            // App Versions & Maintenance
            ['key' => 'app_maintenance_mode', 'value' => 'false', 'group' => 'maintenance', 'type' => 'boolean', 'description' => 'Put client apps in maintenance mode'],
            ['key' => 'app_maintenance_message', 'value' => 'LostFinder is undergoing routine maintenance. We will be back shortly.', 'group' => 'maintenance', 'type' => 'string', 'description' => 'Maintenance announcement banner'],
            ['key' => 'min_version_tourist', 'value' => '1.0.0', 'group' => 'app_versions', 'type' => 'string', 'description' => 'Minimum required Tourist app version'],
            ['key' => 'latest_version_tourist', 'value' => '1.0.0', 'group' => 'app_versions', 'type' => 'string', 'description' => 'Latest available Tourist app version'],
            ['key' => 'min_version_driver', 'value' => '1.0.0', 'group' => 'app_versions', 'type' => 'string', 'description' => 'Minimum required Driver app version'],
            ['key' => 'latest_version_driver', 'value' => '1.0.0', 'group' => 'app_versions', 'type' => 'string', 'description' => 'Latest available Driver app version'],

            // Helplines & Support
            ['key' => 'emergency_police_number', 'value' => '112', 'group' => 'support', 'type' => 'string', 'description' => 'Emergency Police Helpline'],
            ['key' => 'traffic_police_number', 'value' => '1095', 'group' => 'support', 'type' => 'string', 'description' => 'Traffic Police Helpline'],
            ['key' => 'tourist_helpline_number', 'value' => '1363', 'group' => 'support', 'type' => 'string', 'description' => 'National Tourist Helpline'],
            ['key' => 'support_phone', 'value' => '+91 98765 00000', 'group' => 'support', 'type' => 'string', 'description' => 'LostFinder 24x7 Helpdesk Phone'],
            ['key' => 'support_email', 'value' => 'support@lostfinder.app', 'group' => 'support', 'type' => 'string', 'description' => 'Official Support Email'],
        ];

        foreach ($defaultSettings as $setting) {
            AppSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // 7. Server-Driven Item Categories
        $categories = [
            ['name' => 'Smartphone & Tablet', 'slug' => 'smartphone', 'icon' => 'device-phone-mobile', 'suggested_bounty' => 500.00, 'priority' => 10],
            ['name' => 'Wallet, Purse & Cash', 'slug' => 'wallet', 'icon' => 'wallet', 'suggested_bounty' => 300.00, 'priority' => 9],
            ['name' => 'Backpack & Travel Bag', 'slug' => 'backpack', 'icon' => 'briefcase', 'suggested_bounty' => 400.00, 'priority' => 8],
            ['name' => 'Keys (House / Vehicle)', 'slug' => 'keys', 'icon' => 'key', 'suggested_bounty' => 150.00, 'priority' => 7],
            ['name' => 'Official IDs, Cards & Documents', 'slug' => 'documents', 'icon' => 'document-text', 'suggested_bounty' => 500.00, 'priority' => 6],
            ['name' => 'Watch & Jewellery', 'slug' => 'jewellery', 'icon' => 'sparkles', 'suggested_bounty' => 1000.00, 'priority' => 5],
            ['name' => 'Camera & Electronics', 'slug' => 'electronics', 'icon' => 'camera', 'suggested_bounty' => 800.00, 'priority' => 4],
            ['name' => 'Clothing & Accessories', 'slug' => 'clothing', 'icon' => 'shopping-bag', 'suggested_bounty' => 200.00, 'priority' => 3],
        ];

        foreach ($categories as $cat) {
            ItemCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 8. Promotions / In-App Ads
        PromotionBanner::firstOrCreate(
            ['title' => 'Safe Journeys in Varanasi'],
            [
                'subtitle'      => 'Always scan the driver QR code when boarding.',
                'image_url'     => 'https://images.unsplash.com/photo-1561361513-2d000a50f0dc?w=800&auto=format&fit=crop',
                'action_type'   => 'none',
                'action_target' => null,
                'placement'     => 'tourist_home',
                'priority'      => 10,
                'is_active'     => true,
            ]
        );

        PromotionBanner::firstOrCreate(
            ['title' => 'Honest Drivers Earn Cash Rewards'],
            [
                'subtitle'      => 'Return left-behind items and receive instant bounties & 5-star ratings.',
                'image_url'     => 'https://images.unsplash.com/photo-1596704017254-9b121068fb31?w=800&auto=format&fit=crop',
                'action_type'   => 'none',
                'action_target' => null,
                'placement'     => 'driver_home',
                'priority'      => 10,
                'is_active'     => true,
            ]
        );
    }
}
