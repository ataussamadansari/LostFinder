<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Services\MediaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    use ApiResponse;

    public function __construct(protected MediaService $mediaService)
    {
    }

    /**
     * Get the authenticated driver's profile and active vehicle assignment.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $driver = $user->driver()->with(['activeAssignment.vehicle', 'documents'])->first();

        if (!$driver) {
            return $this->errorResponse('Driver profile not found. Please apply to register as a driver.', 404);
        }

        return $this->successResponse([
            'driver_code' => $driver->driver_code,
            'verification_status' => $driver->verification_status,
            'verified_at' => $driver->verified_at?->toIso8601String(),
            'rating_avg' => (float) $driver->rating_avg,
            'rating_count' => (int) $driver->rating_count,
            'is_verified' => $driver->isVerified(),
            'active_vehicle' => $driver->activeAssignment?->vehicle ? [
                'uuid' => $driver->activeAssignment->vehicle->uuid,
                'vehicle_code' => $driver->activeAssignment->vehicle->vehicle_code,
                'registration_number' => $driver->activeAssignment->vehicle->registration_number,
                'vehicle_type' => $driver->activeAssignment->vehicle->vehicle_type,
                'make' => $driver->activeAssignment->vehicle->make,
                'model' => $driver->activeAssignment->vehicle->model,
                'color' => $driver->activeAssignment->vehicle->color,
                'verification_status' => $driver->activeAssignment->vehicle->verification_status,
            ] : null,
        ], 'Driver profile retrieved successfully.');
    }

    /**
     * Apply to register as a driver.
     */
    public function apply(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->driver) {
            return $this->successResponse($user->driver, 'Driver application already exists.');
        }

        // Generate a human-readable driver code: DRV-XXXXXX
        $driverCode = 'DRV-' . strtoupper(Str::random(6));

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_code' => $driverCode,
            'verification_status' => 'pending',
            'rating_avg' => 0.00,
            'rating_count' => 0,
        ]);

        $user->update(['role' => 'driver']);

        return $this->successResponse($driver, 'Driver application submitted successfully. Please submit your verification documents.', 201);
    }

    /**
     * List all submitted KYC documents.
     */
    public function getDocuments(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found.', 404);
        }

        $documents = $driver->documents()->with('media')->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'document_number' => $doc->document_number,
                'status' => $doc->status,
                'verified_at' => $doc->verified_at?->toIso8601String(),
                'rejection_reason' => $doc->rejection_reason,
                'expires_at' => $doc->expires_at?->toDateString(),
                'created_at' => $doc->created_at?->toIso8601String(),
            ];
        });

        return $this->successResponse($documents, 'Driver documents retrieved successfully.');
    }

    /**
     * Upload a driver KYC verification document.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found. Please apply first.', 404);
        }

        $validated = $request->validate([
            'document_type' => ['required', 'in:government_id,driving_license,address_proof,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'], // 10MB max
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        // Upload to private disk
        $media = $this->mediaService->upload(
            file: $request->file('file'),
            directory: 'drivers/documents',
            visibility: 'private',
            uploader: $request->user(),
            disk: 'private'
        );

        $document = DriverDocument::create([
            'driver_id' => $driver->id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'media_id' => $media->id,
            'status' => 'pending',
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        // If driver was rejected, move back to under_review
        if ($driver->verification_status === 'rejected') {
            $driver->update(['verification_status' => 'under_review']);
        }

        return $this->successResponse([
            'id' => $document->id,
            'document_type' => $document->document_type,
            'status' => $document->status,
            'media_uuid' => $media->uuid,
        ], 'Document uploaded successfully and queued for verification.', 201);
    }
}
