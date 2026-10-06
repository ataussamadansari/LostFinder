<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\UserConsent;
use App\Models\UserDevice;
use App\Services\MediaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(protected MediaService $mediaService)
    {
    }

    /**
     * Get the authenticated user's profile.
     */
    public function getProfile(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()->load('profile.profilePhoto', 'driver')),
            'Profile retrieved successfully.'
        );
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Update user core fields
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }

        $firstName = $validated['first_name'] ?? $user->profile?->first_name ?? $user->name;
        $lastName = $validated['last_name'] ?? $user->profile?->last_name ?? '';
        $user->name = trim("{$firstName} {$lastName}");
        $user->save();

        // Profile fields
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id], [
            'first_name' => $firstName,
        ]);

        $profile->first_name = $firstName;
        $profile->last_name = $lastName;

        if ($request->has('gender')) {
            $profile->gender = $validated['gender'];
        }

        if ($request->has('date_of_birth')) {
            $profile->date_of_birth = $validated['date_of_birth'];
        }

        // Handle profile photo upload via MediaService if included
        if ($request->hasFile('photo')) {
            $media = $this->mediaService->upload(
                file: $request->file('photo'),
                directory: 'users/profiles',
                visibility: 'public',
                uploader: $user,
                disk: 'public'
            );

            $profile->profile_photo_id = $media->id;
        }

        $profile->save();

        return $this->successResponse(
            new UserResource($user->fresh()->load('profile.profilePhoto', 'driver')),
            'Profile updated successfully.'
        );
    }

    /**
     * Dedicated endpoint to upload profile photo.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB max
        ]);

        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id], [
            'first_name' => $user->name,
        ]);

        $media = $this->mediaService->upload(
            file: $request->file('photo'),
            directory: 'users/profiles',
            visibility: 'public',
            uploader: $user,
            disk: 'public'
        );

        $profile->profile_photo_id = $media->id;
        $profile->save();

        return $this->successResponse([
            'media_uuid' => $media->uuid,
            'photo_url' => $media->url,
            'user' => new UserResource($user->fresh()->load('profile.profilePhoto', 'driver')),
        ], 'Profile photo uploaded successfully.');
    }

    /**
     * Delete profile photo.
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        if ($profile && $profile->profile_photo_id) {
            $profile->profile_photo_id = null;
            $profile->save();
        }

        return $this->successResponse(
            new UserResource($user->fresh()->load('profile.profilePhoto', 'driver')),
            'Profile photo removed successfully.'
        );
    }

    /**
     * Register or update user device push token.
     */
    public function updateDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios,web'],
            'push_token' => ['required', 'string'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'platform' => $validated['platform'],
                'push_token' => $validated['push_token'],
                'app_version' => $validated['app_version'] ?? null,
                'last_active_at' => now(),
            ]
        );

        return $this->successResponse($device, 'Device token updated successfully.');
    }

    /**
     * Unregister device push token.
     */
    public function deleteDevice(Request $request, string $deviceId): JsonResponse
    {
        $deleted = UserDevice::where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->delete();

        if (!$deleted) {
            return $this->errorResponse('Device not found.', 404);
        }

        return $this->successResponse(null, 'Device unregistered successfully.');
    }

    /**
     * Get user privacy consents.
     */
    public function getConsents(Request $request): JsonResponse
    {
        $consents = $request->user()->consents()->get();

        return $this->successResponse($consents, 'Consents retrieved successfully.');
    }

    /**
     * Update user privacy consent.
     */
    public function updateConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_type' => ['required', 'string', 'max:50'],
            'is_granted' => ['required', 'boolean'],
        ]);

        $consent = UserConsent::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'consent_type' => $validated['consent_type'],
            ],
            [
                'is_granted' => $validated['is_granted'],
                'granted_at' => $validated['is_granted'] ? now() : null,
                'revoked_at' => !$validated['is_granted'] ? now() : null,
            ]
        );

        return $this->successResponse($consent, 'Privacy consent updated successfully.');
    }
}
