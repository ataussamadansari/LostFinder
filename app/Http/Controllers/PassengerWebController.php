<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\DriverProfile;
use App\Models\ItemCategory;
use App\Models\PromotionBanner;
use Illuminate\Http\Request;

class PassengerWebController extends Controller
{
    /**
     * Passenger Web / PWA Homepage
     */
    public function index(Request $request)
    {
        return $this->renderView($request, null);
    }

    /**
     * Direct QR Code Landing Page (When scanned from physical sticker with camera)
     */
    public function scanLanding(Request $request, string $token)
    {
        $driver = DriverProfile::with('user:id,name,phone,profile_picture')
            ->where('qr_code_token', $token)
            ->first();

        return $this->renderView($request, $driver);
    }

    private function renderView(Request $request, ?DriverProfile $scannedDriver)
    {
        $categories = ItemCategory::active()->get();
        $promotions = PromotionBanner::active('tourist_home')->get();

        $serverConfig = [
            'maintenance' => [
                'is_active' => AppSetting::getBoolean('app_maintenance_mode', false),
                'message'   => AppSetting::get('app_maintenance_message', 'LostFinder is currently undergoing scheduled maintenance. Please check back shortly.'),
            ],
            'feature_flags' => [
                'enable_bounties'      => AppSetting::getBoolean('enable_bounties', true),
                'enable_sos_emergency' => AppSetting::getBoolean('enable_sos_emergency', true),
                'enable_promotions'    => AppSetting::getBoolean('enable_promotions', true),
            ],
            'emergency_contacts' => [
                'police'  => AppSetting::get('emergency_police_number', '112'),
                'tourist' => AppSetting::get('tourist_helpline_number', '1363'),
                'traffic' => AppSetting::get('traffic_police_number', '1095'),
                'support' => AppSetting::get('support_phone', '+91 98765 00000'),
            ],
        ];

        return view('passenger.app', [
            'categories'      => $categories,
            'promotions'      => $promotions,
            'supportPhone'    => $serverConfig['emergency_contacts']['support'],
            'policeNumber'    => $serverConfig['emergency_contacts']['police'],
            'touristHelpline' => $serverConfig['emergency_contacts']['tourist'],
            'trafficNumber'   => $serverConfig['emergency_contacts']['traffic'],
            'serverConfig'    => $serverConfig,
            'scannedDriver'   => $scannedDriver,
        ]);
    }
}
