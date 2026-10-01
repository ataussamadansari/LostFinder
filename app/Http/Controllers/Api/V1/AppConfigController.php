<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ItemCategory;
use App\Models\PromotionBanner;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    /**
     * 1. Server-Driven App Bootstrap / Configuration Endpoint.
     * Everything a client app needs on initial startup.
     */
    public function bootstrap(Request $request)
    {
        $platform = $request->header('X-Platform', $request->query('platform', 'android')); // android / ios
        $role = $request->query('role', 'tourist'); // tourist / driver
        $clientVersion = $request->header('X-App-Version', $request->query('app_version', '1.0.0'));

        $minVersion = AppSetting::get($role === 'driver' ? 'min_version_driver' : 'min_version_tourist', '1.0.0');
        $latestVersion = AppSetting::get($role === 'driver' ? 'latest_version_driver' : 'latest_version_tourist', '1.0.0');

        $forceUpdate = version_compare($clientVersion, $minVersion, '<');
        $hasUpdate = version_compare($clientVersion, $latestVersion, '<');

        // Dynamic categories
        $categories = ItemCategory::active()->get(['id', 'name', 'slug', 'icon', 'suggested_bounty']);

        // Default promotions for placement
        $placement = $role === 'driver' ? 'driver_home' : 'tourist_home';
        $promotions = PromotionBanner::active($placement)->take(5)->get();

        return response()->json([
            'status' => true,
            'data'   => [
                'maintenance' => [
                    'is_active' => AppSetting::getBoolean('app_maintenance_mode', false),
                    'message'   => AppSetting::get('app_maintenance_message', 'LostFinder is currently undergoing scheduled maintenance. Please check back shortly.'),
                ],
                'version_control' => [
                    'client_version' => $clientVersion,
                    'min_version'    => $minVersion,
                    'latest_version' => $latestVersion,
                    'force_update'   => $forceUpdate,
                    'has_update'     => $hasUpdate,
                    'update_message' => AppSetting::get('force_update_message', 'A critical update is available. Please update to continue.'),
                    'store_url'      => $platform === 'ios'
                        ? AppSetting::get('app_store_url', 'https://apps.apple.com/app/lostfinder')
                        : AppSetting::get('play_store_url', 'https://play.google.com/store/apps/details?id=app.lostfinder'),
                ],
                'item_categories' => $categories,
                'vehicle_types'   => [
                    ['key' => 'auto', 'label' => 'Auto Rickshaw', 'icon' => 'auto', 'description' => '3-Wheeler CNG/Petrol'],
                    ['key' => 'e_rickshaw', 'label' => 'E-Rickshaw (Tuk-Tuk)', 'icon' => 'e_rickshaw', 'description' => 'Eco-friendly Electric'],
                    ['key' => 'cab', 'label' => 'Cab / Taxi', 'icon' => 'cab', 'description' => '4-Wheeler Car'],
                    ['key' => 'bike', 'label' => 'Bike Taxi', 'icon' => 'bike', 'description' => '2-Wheeler Motorcycle'],
                ],
                'feature_flags'   => [
                    'enable_bounties'                  => AppSetting::getBoolean('enable_bounties', true),
                    'enable_sos_emergency'             => AppSetting::getBoolean('enable_sos_emergency', true),
                    'enable_promotions'                => AppSetting::getBoolean('enable_promotions', true),
                    'enable_driver_verification_badge' => AppSetting::getBoolean('enable_driver_verification_badge', true),
                ],
                'support'         => [
                    'police_helpline'         => AppSetting::get('emergency_police_number', '112'),
                    'traffic_police_helpline' => AppSetting::get('traffic_police_number', '1095'),
                    'tourist_helpline'        => AppSetting::get('tourist_helpline_number', '1363'),
                    'support_phone'           => AppSetting::get('support_phone', '+91 98765 00000'),
                    'support_email'           => AppSetting::get('support_email', 'support@lostfinder.app'),
                ],
                'promotions'      => $promotions,
            ],
        ]);
    }

    /**
     * 2. Fetch Active Ads / Promotions by Placement.
     */
    public function promotions(Request $request)
    {
        $placement = $request->query('placement', 'tourist_home');
        $banners = PromotionBanner::active($placement)->get();

        // Increment impressions in background
        if ($banners->isNotEmpty()) {
            PromotionBanner::whereIn('id', $banners->pluck('id'))->increment('impressions');
        }

        return response()->json([
            'status' => true,
            'data'   => $banners,
        ]);
    }

    /**
     * 3. Track Ad Click.
     */
    public function recordBannerClick(int $id)
    {
        $banner = PromotionBanner::find($id);

        if (!$banner) {
            return response()->json(['status' => false, 'message' => 'Banner not found'], 404);
        }

        $banner->increment('clicks');

        return response()->json([
            'status' => true,
            'data'   => [
                'banner_id'     => $banner->id,
                'action_type'   => $banner->action_type,
                'action_target' => $banner->action_target,
            ],
        ]);
    }

    /**
     * 4. List Active Item Categories.
     */
    public function categories()
    {
        $categories = ItemCategory::active()->get();

        return response()->json([
            'status' => true,
            'data'   => $categories,
        ]);
    }
}
