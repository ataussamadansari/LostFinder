<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SafetyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafetyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SafetyService $safetyService
    ) {}

    /**
     * Block a user.
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $blocked = $this->safetyService->blockUser($request->user(), $id, $validated['reason'] ?? null);

            return $this->successResponse([
                'blocked_user_id' => $blocked->blocked_user_id,
                'reason' => $blocked->reason,
                'created_at' => $blocked->created_at?->toIso8601String(),
            ], 'User blocked successfully.');
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Unblock a user.
     */
    public function unblock(Request $request, int $id): JsonResponse
    {
        $unblocked = $this->safetyService->unblockUser($request->user(), $id);

        if (!$unblocked) {
            return $this->errorResponse('User was not blocked.', 404);
        }

        return $this->successResponse([
            'unblocked_user_id' => $id,
        ], 'User unblocked successfully.');
    }

    /**
     * List users blocked by authenticated user.
     */
    public function blocked(Request $request): JsonResponse
    {
        $blockedUsers = $this->safetyService->getBlockedUsers($request->user());

        $formatted = $blockedUsers->map(fn($b) => [
            'id' => $b->blocked_user_id,
            'name' => $b->blockedUser?->name ?? 'User',
            'reason' => $b->reason,
            'blocked_at' => $b->created_at?->toIso8601String(),
        ]);

        return $this->successResponse([
            'blocked_users' => $formatted,
        ], 'Blocked users retrieved successfully.');
    }
}
