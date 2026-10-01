<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send push notification to a driver.
     */
    public function sendToDriver(DriverProfile $driver, string $title, string $body, array $data = []): bool
    {
        if (!$driver->fcm_token) {
            Log::info("Driver #{$driver->id} has no registered FCM token.");
            return false;
        }

        return $this->sendToToken($driver->fcm_token, $title, $body, $data);
    }

    /**
     * Send push notification to a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $token = $user->driverProfile?->fcm_token;
        if (!$token) {
            return false;
        }

        return $this->sendToToken($token, $title, $body, $data);
    }

    /**
     * Broadcast notification to all active drivers or tourists.
     */
    public function broadcastToRole(string $role, string $title, string $body, array $data = []): int
    {
        $sentCount = 0;

        if ($role === 'driver') {
            $drivers = DriverProfile::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
            foreach ($drivers as $driver) {
                if ($this->sendToDriver($driver, $title, $body, $data)) {
                    $sentCount++;
                }
            }
        }

        Log::info("Broadcast notification to {$role}s dispatched. Success count: {$sentCount}");

        return $sentCount;
    }

    /**
     * Send push notification to a single FCM device registration token.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $enabled = AppSetting::getBoolean('fcm_enabled', false);
        $serverKey = AppSetting::get('fcm_server_key', '');

        Log::info("Push notification [Enabled: " . ($enabled ? 'true' : 'false') . "] '{$title}' -> {$token}");

        if (!$enabled || empty($serverKey)) {
            // Mock / dev mode: recorded successfully
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to'           => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data'         => $data,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('FCM Push notification error: ' . $e->getMessage());
            return false;
        }
    }
}
