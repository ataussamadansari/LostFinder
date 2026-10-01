<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an OTP message.
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        $template = AppSetting::get('sms_otp_template', 'Your LostFinder OTP is :otp. Valid for 5 minutes. Do not share it.');
        $message = str_replace(':otp', $otp, $template);

        return $this->sendMessage($phone, $message);
    }

    /**
     * Send a general SMS message according to admin settings.
     */
    public function sendMessage(string $phone, string $message): bool
    {
        $enabled = AppSetting::getBoolean('sms_enabled', false);
        $provider = AppSetting::get('sms_provider', 'mock');
        $apiKey = AppSetting::get('sms_api_key', '');
        $senderId = AppSetting::get('sms_sender_id', 'LSTFND');

        // Always log for transparency
        Log::info("SMS dispatch [Provider: {$provider}, Enabled: " . ($enabled ? 'true' : 'false') . "] to {$phone}: {$message}");

        if (!$enabled || $provider === 'mock' || empty($apiKey)) {
            // Mock mode / dev mode: Successfully recorded
            return true;
        }

        try {
            return match ($provider) {
                'fast2sms' => $this->sendViaFast2Sms($phone, $message, $apiKey, $senderId),
                'msg91'    => $this->sendViaMsg91($phone, $message, $apiKey, $senderId),
                'twilio'   => $this->sendViaTwilio($phone, $message, $apiKey, $senderId),
                default    => true,
            };
        } catch (\Throwable $e) {
            Log::error("SMS Sending failed via {$provider}: " . $e->getMessage());
            return false;
        }
    }

    private function sendViaFast2Sms(string $phone, string $message, string $apiKey, string $senderId): bool
    {
        // Remove country code for Indian numbers if needed
        $cleanPhone = ltrim(preg_replace('/[^0-9]/', '', $phone), '91');

        $response = Http::withHeaders([
            'authorization' => $apiKey,
        ])->post('https://www.fast2sms.com/dev/bulkV2', [
            'route'     => 'v3',
            'sender_id' => $senderId,
            'message'   => $message,
            'language'  => 'english',
            'flash'     => 0,
            'numbers'   => $cleanPhone,
        ]);

        return $response->successful();
    }

    private function sendViaMsg91(string $phone, string $message, string $apiKey, string $senderId): bool
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $response = Http::withHeaders([
            'authkey' => $apiKey,
        ])->post('https://api.msg91.com/api/v2/sendsms', [
            'sender' => $senderId,
            'route'  => '4',
            'country'=> '91',
            'sms'    => [
                [
                    'message' => $message,
                    'to'      => [$cleanPhone],
                ],
            ],
        ]);

        return $response->successful();
    }

    private function sendViaTwilio(string $phone, string $message, string $apiKey, string $senderId): bool
    {
        // Twilio API format: apiKey is "accountSid:authToken"
        [$sid, $token] = explode(':', $apiKey) + [null, null];
        if (!$sid || !$token) {
            Log::warning('Twilio credentials must be formatted as accountSid:authToken');
            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $senderId,
                'To'   => $phone,
                'Body' => $message,
            ]);

        return $response->successful();
    }
}
