<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminLoginRequest;
use App\Http\Requests\Api\V1\SendOtpRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Format and sanitize full international phone number.
     */
    private function formatPhoneNumber(?string $countryCode, string $phone): array
    {
        $countryCode = $countryCode ?: '+91';
        if (!str_starts_with($countryCode, '+')) {
            $countryCode = '+' . $countryCode;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        $ccDigits = ltrim($countryCode, '+');

        // If phone already begins with country code digits (e.g. 919123456789)
        if (str_starts_with($digits, $ccDigits) && strlen($digits) === strlen($ccDigits) + 10) {
            $cleanPhone = substr($digits, strlen($ccDigits));
        } else {
            $cleanPhone = ltrim($digits, '0');
        }

        $fullPhone = $countryCode . $cleanPhone;

        return [$countryCode, $cleanPhone, $fullPhone];
    }

    /**
     * 1. Send OTP (Driver & Tourist)
     */
    public function sendOtp(SendOtpRequest $request)
    {
        [$countryCode, $cleanPhone, $fullPhone] = $this->formatPhoneNumber($request->country_code, $request->phone);

        $existingUser = User::where('phone', $fullPhone)->first();
        if ($existingUser && !$existingUser->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        // Test/local environment uses fixed 123456, production generates 6-digit random code
        $otp = app()->environment('production') ? (string) random_int(100000, 999999) : '123456';

        // Cache for 5 minutes
        Cache::put('otp_' . $fullPhone, $otp, now()->addMinutes(5));

        // Dispatch SMS via admin-managed SMS Gateway
        app(\App\Services\SmsService::class)->sendOtp($fullPhone, $otp);

        return response()->json([
            'status'    => true,
            'message'   => 'OTP sent successfully',
            'data'      => [
                'phone'              => $fullPhone,
                'expires_in_seconds' => 300,
            ],
            'debug_otp' => !app()->environment('production') ? $otp : null,
        ]);
    }

    /**
     * 2. Verify OTP & Issue Token
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        [$countryCode, $cleanPhone, $fullPhone] = $this->formatPhoneNumber($request->country_code, $request->phone);
        $cachedOtp = Cache::get('otp_' . $fullPhone);

        // Allow demo test OTP '123456' in non-production environments OR for seeded demo phone
        $isDemoOtp = (!app()->environment('production') || in_array($fullPhone, ['+919123456789', '+919876543210'])) && $request->otp === '123456';

        if (!$isDemoOtp && (!$cachedOtp || $cachedOtp !== $request->otp)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid or expired OTP',
            ], 401);
        }

        if ($cachedOtp) {
            Cache::forget('otp_' . $fullPhone);
        }

        $user = User::where('phone', $fullPhone)->first();

        if ($user) {
            if (!$user->is_active) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            if ($user->role !== $request->role) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This account is already registered as a ' . $user->role . '. Please select ' . $user->role . ' to login.',
                ], 422);
            }

            if ($request->filled('name') && $user->name !== $request->name) {
                $user->name = $request->name;
            }
            if (!$user->phone_verified_at) {
                $user->phone_verified_at = now();
            }
            $user->save();
        } else {
            $user = User::create([
                'name'              => $request->name ?? ($request->role === 'driver' ? 'Driver' : 'Tourist'),
                'country_code'      => $countryCode,
                'phone'             => $fullPhone,
                'role'              => $request->role,
                'phone_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        // Ensure Passenger profile exists if user is tourist
        if ($user->role === 'tourist' && !$user->passenger) {
            Passenger::create([
                'user_id'      => $user->id,
                'masked_alias' => 'Passenger #' . Str::upper(Str::random(5)),
            ]);
        }

        $token = $user->createToken('auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user->fresh()->load(['driverProfile', 'passenger']),
        ]);
    }

    /**
     * 3. Admin Email/Password Login
     */
    public function adminLogin(AdminLoginRequest $request)
    {
        $user = User::where('email', $request->email)->where('role', 'admin')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials or unauthorized',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Admin account is suspended',
            ], 403);
        }

        $token = $user->createToken('admin_token', ['admin'])->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Admin authorized',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    /**
     * 4. Current Logged-in User Profile
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => true,
            'user'   => $request->user()->load(['driverProfile', 'passenger']),
        ]);
    }

    /**
     * 5. Update Profile
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = $request->file('profile_picture')->store('avatars', 'public');
        }

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('emergency_contact_phone')) {
            $user->emergency_contact_phone = $request->emergency_contact_phone;
        }

        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully',
            'user'    => $user->fresh()->load(['driverProfile', 'passenger']),
        ]);
    }

    /**
     * 6. Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
