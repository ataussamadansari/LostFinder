<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public const OTP_EXPIRY_SECONDS = 300; // 5 minutes
    public const RESEND_COOLDOWN_SECONDS = 60; // 1 minute
    public const MAX_ATTEMPTS = 5;

    /**
     * Normalize a phone number to standard E.164 format.
     */
    public function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // If 10 digits without country code, assume +91 (India)
        if (preg_match('/^[6-9]\d{9}$/', $cleaned)) {
            return '+91' . $cleaned;
        }

        // If starts with 91 and has 12 digits without +, prepend +
        if (preg_match('/^91[6-9]\d{9}$/', $cleaned)) {
            return '+' . $cleaned;
        }

        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Generate and dispatch an OTP for the given phone number.
     *
     * @return array{success: bool, message: string, resend_in?: int, debug_otp?: string}
     */
    public function requestOtp(string $phone): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $cooldownKey = "otp_cooldown:{$normalizedPhone}";
        $otpKey = "otp:{$normalizedPhone}";

        // Check if cooldown is active
        if (Cache::has($cooldownKey)) {
            $ttl = Cache::get($cooldownKey . '_ttl', self::RESEND_COOLDOWN_SECONDS);
            return [
                'success' => false,
                'message' => "Please wait before requesting another OTP.",
                'resend_in' => (int) $ttl,
            ];
        }

        // Generate 6-digit numeric OTP
        $otp = (string) random_int(100000, 999999);

        // Store OTP with attempt counter and expiry
        Cache::put($otpKey, [
            'code' => $otp,
            'attempts' => 0,
        ], self::OTP_EXPIRY_SECONDS);

        // Store cooldown
        Cache::put($cooldownKey, true, self::RESEND_COOLDOWN_SECONDS);

        // Log OTP in local/testing environment
        if (app()->environment('local', 'testing')) {
            Log::info("LostFinder OTP for [{$normalizedPhone}]: {$otp}");
        }

        // Production SMS gateway dispatch placeholder (Twilio / MSG91 / Fast2SMS)

        return [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'resend_in' => self::RESEND_COOLDOWN_SECONDS,
            'debug_otp' => app()->environment('local', 'testing') ? $otp : null,
        ];
    }

    /**
     * Verify an OTP for the given phone number.
     *
     * @return array{valid: bool, message: string}
     */
    public function verifyOtp(string $phone, string $otp): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $otpKey = "otp:{$normalizedPhone}";

        // Allow static master test OTP in local/testing environment
        if (app()->environment('local', 'testing') && $otp === '123456') {
            Cache::forget($otpKey);
            return ['valid' => true, 'message' => 'OTP verified successfully (Dev master bypass).'];
        }

        $data = Cache::get($otpKey);

        if (!$data) {
            return [
                'valid' => false,
                'message' => 'OTP has expired or was not requested. Please request a new OTP.',
            ];
        }

        // Check maximum attempt threshold
        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($otpKey);
            return [
                'valid' => false,
                'message' => 'Too many failed attempts. This OTP has been invalidated. Please request a new one.',
            ];
        }

        if (!hash_equals((string) $data['code'], (string) $otp)) {
            $data['attempts']++;
            Cache::put($otpKey, $data, self::OTP_EXPIRY_SECONDS);

            $remaining = self::MAX_ATTEMPTS - $data['attempts'];
            return [
                'valid' => false,
                'message' => "Invalid OTP. You have {$remaining} attempt(s) remaining.",
            ];
        }

        // Verification successful, consume OTP
        Cache::forget($otpKey);

        return [
            'valid' => true,
            'message' => 'OTP verified successfully.',
        ];
    }

    /**
     * Clear active OTP cache for a phone.
     */
    public function clearOtp(string $phone): void
    {
        $normalizedPhone = $this->normalizePhone($phone);
        Cache::forget("otp:{$normalizedPhone}");
        Cache::forget("otp_cooldown:{$normalizedPhone}");
    }
}
