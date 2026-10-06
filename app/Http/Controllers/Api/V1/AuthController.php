<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserProfile;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected OtpService $otpService)
    {
    }

    /**
     * Request an OTP for a given mobile number.
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->requestOtp($request->validated('phone'));

        if (!$result['success']) {
            return $this->errorResponse(
                $result['message'],
                429,
                ['resend_in' => $result['resend_in'] ?? null]
            );
        }

        return $this->successResponse([
            'phone' => $this->otpService->normalizePhone($request->validated('phone')),
            'resend_in' => $result['resend_in'],
            'debug_otp' => $result['debug_otp'] ?? null,
        ], $result['message']);
    }

    /**
     * Verify the OTP and authenticate / register the user.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $normalizedPhone = $this->otpService->normalizePhone($request->validated('phone'));
        $verification = $this->otpService->verifyOtp($normalizedPhone, $request->validated('otp'));

        if (!$verification['valid']) {
            return $this->errorResponse($verification['message'], 422);
        }

        // Locate existing user or create a new tourist account
        $user = User::where('phone', $normalizedPhone)->first();
        $isNewUser = false;

        if ($user) {
            if (in_array($user->status, ['suspended', 'blocked'])) {
                return $this->errorResponse('Your account has been suspended or blocked. Please contact support.', 403);
            }

            $user->update([
                'last_login_at' => now(),
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ]);
        } else {
            $isNewUser = true;
            $defaultName = 'User ' . substr($normalizedPhone, -4);

            $user = User::create([
                'phone' => $normalizedPhone,
                'name' => $defaultName,
                'role' => 'tourist',
                'status' => 'active',
                'phone_verified_at' => now(),
                'last_login_at' => now(),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $defaultName,
            ]);

            NotificationPreference::create([
                'user_id' => $user->id,
            ]);
        }

        // Register device telemetry if provided
        if ($request->filled('device_id') && $request->filled('platform')) {
            UserDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $request->validated('device_id'),
                ],
                [
                    'platform' => $request->validated('platform'),
                    'push_token' => $request->validated('push_token', ''),
                    'app_version' => $request->validated('app_version'),
                    'last_active_at' => now(),
                ]
            );
        }

        // Issue Sanctum Bearer Token
        $tokenName = $request->validated('device_name') ?: ($request->validated('platform') ?: 'mobile_app');
        $token = $user->createToken($tokenName)->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'is_new_user' => $isNewUser,
            'user' => new UserResource($user->load('profile', 'driver')),
        ], 'Authenticated successfully.');
    }

    /**
     * Log out the authenticated user by revoking the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully.');
    }

    /**
     * Get the authenticated user's details.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()->load('profile', 'driver', 'notificationPreference')),
            'User profile retrieved successfully.'
        );
    }
}
