<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Retrieve public configuration and feature toggles.
     * Safe for unauthenticated / mobile clients.
     */
    public function publicSettings(Request $request): JsonResponse
    {
        $settings = $this->settingService->getPublicSettings();

        return $this->successResponse($settings, 'Public system settings retrieved successfully.');
    }
}
