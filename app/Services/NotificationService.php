<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create in-app notification and dispatch FCM push alert to recipient's registered devices.
     */
    public function notifyUser(User $recipient, string $type, string $title, string $body, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'created_at' => now(),
        ]);

        // Dispatch FCM push to user devices
        $tokens = UserDevice::where('user_id', $recipient->id)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->pluck('push_token')
            ->toArray();

        if (!empty($tokens)) {
            $this->sendPushToTokens($tokens, $title, $body, $data);
        }

        return $notification;
    }

    /**
     * Dispatch FCM push notification to one or multiple device tokens.
     */
    public function sendPushToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        $fcmServerKey = config('services.fcm.key');

        // If FCM is not configured or in testing environment without real key, log and return true
        if (empty($fcmServerKey) || app()->environment('testing')) {
            Log::info('FCM Push notification dispatched (simulated):', [
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => array_map('strval', $data),
                'priority' => 'high',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('FCM Push notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get paginated notifications for user alongside total unread count.
     */
    public function getUserNotifications(User $user, int $perPage = 20): array
    {
        $paginator = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return [
            'notifications' => $paginator,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification, User $user): Notification
    {
        if ($notification->user_id !== $user->id) {
            throw new \DomainException('Unauthorized to update this notification.');
        }

        $notification->markAsRead();

        return $notification->fresh();
    }

    /**
     * Mark all unread notifications as read for user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
