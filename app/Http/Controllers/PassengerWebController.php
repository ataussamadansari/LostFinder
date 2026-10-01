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
        $categories = ItemCategory::active()->get();
        $promotions = PromotionBanner::active('tourist_home')->get();
        $supportPhone = AppSetting::get('support_phone', '+91 98765 00000');
        $policeNumber = AppSetting::get('emergency_police_number', '112');
        $touristHelpline = AppSetting::get('tourist_helpline_number', '1363');

        return view('passenger.app', [
            'categories'      => $categories,
            'promotions'      => $promotions,
            'supportPhone'    => $supportPhone,
            'policeNumber'    => $policeNumber,
            'touristHelpline' => $touristHelpline,
            'scannedDriver'   => null,
        ]);
    }

    /**
     * Direct QR Code Landing Page (When scanned from physical sticker with camera)
     */
    public function scanLanding(string $token)
    {
        $driver = DriverProfile::with('user:id,name,phone,profile_picture')
            ->where('qr_code_token', $token)
            ->first();

        $categories = ItemCategory::active()->get();
        $promotions = PromotionBanner::active('tourist_home')->get();
        $supportPhone = AppSetting::get('support_phone', '+91 98765 00000');
        $policeNumber = AppSetting::get('emergency_police_number', '112');
        $touristHelpline = AppSetting::get('tourist_helpline_number', '1363');

        return view('passenger.app', [
            'categories'      => $categories,
            'promotions'      => $promotions,
            'supportPhone'    => $supportPhone,
            'policeNumber'    => $policeNumber,
            'touristHelpline' => $touristHelpline,
            'scannedDriver'   => $driver,
        ]);
    }
}
