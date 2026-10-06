<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List user notifications with unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->notificationService->getUserNotifications(
            $request->user(),
            (int) $request->query('per_page', 20)
        );

        $paginator = $result['notifications'];

        $formatted = collect($paginator->items())->map(fn($n) => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ]);

        return $this->successResponse([
            'notifications' => $formatted,
            'unread_count' => $result['unread_count'],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Notifications retrieved successfully.');
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->notificationService->markAsRead($notification, $request->user());

        return $this->successResponse([
            'id' => $updated->id,
            'read_at' => $updated->read_at?->toIso8601String(),
        ], 'Notification marked as read.');
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return $this->successResponse([
            'marked_count' => $count,
        ], 'All notifications marked as read.');
    }
}
