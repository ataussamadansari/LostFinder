<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingAdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * List all system configuration parameters grouped by domain.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = $this->settingService->getAllSettings();

        return $this->successResponse([
            'settings' => $settings,
        ], 'System settings retrieved successfully.');
    }

    /**
     * Update one or more system configuration settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array|min:1',
            'settings.*' => 'nullable',
        ]);

        $updated = $this->settingService->updateBatch(
            $validated['settings'],
            $request->user()
        );

        return $this->successResponse([
            'updated' => $updated,
            'count' => count($updated),
        ], 'System settings updated successfully.');
    }

    /**
     * Retrieve system role capabilities and permission catalog.
     */
    public function permissions(Request $request): JsonResponse
    {
        $matrix = $this->settingService->getPermissionsMatrix();

        return $this->successResponse($matrix, 'Permissions and role catalog retrieved successfully.');
    }
}
