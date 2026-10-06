<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\Media;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Services\MediaService;
use App\Services\VerificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected VerificationService $verificationService,
        protected MediaService $mediaService
    ) {
    }

    /**
     * List drivers pending verification or under review.
     */
    public function pendingDrivers(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $drivers = Driver::with(['user.profile', 'documents.media', 'activeAssignment.vehicle'])
            ->when($status !== 'all', fn ($q) => $q->whereIn('verification_status', explode(',', $status)))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->successResponse($drivers, 'Pending drivers retrieved successfully.');
    }

    /**
     * Verify a driver.
     */
    public function verifyDriver(Request $request, string $driverCode): JsonResponse
    {
        $driver = Driver::where('driver_code', $driverCode)->first();

        if (!$driver) {
            return $this->errorResponse('Driver not found.', 404);
        }

        $verified = $this->verificationService->verifyDriver($driver, $request->user());

        return $this->successResponse([
            'driver_code' => $verified->driver_code,
            'verification_status' => $verified->verification_status,
            'verified_at' => $verified->verified_at?->toIso8601String(),
        ], 'Driver verified successfully.');
    }

    /**
     * Reject a driver.
     */
    public function rejectDriver(Request $request, string $driverCode): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $driver = Driver::where('driver_code', $driverCode)->first();

        if (!$driver) {
            return $this->errorResponse('Driver not found.', 404);
        }

        $rejected = $this->verificationService->rejectDriver(
            $driver,
            $request->input('reason'),
            $request->user()
        );

        return $this->successResponse([
            'driver_code' => $rejected->driver_code,
            'verification_status' => $rejected->verification_status,
        ], 'Driver rejected.');
    }

    /**
     * Verify a driver KYC document.
     */
    public function verifyDriverDocument(Request $request, int $id): JsonResponse
    {
        $document = DriverDocument::find($id);

        if (!$document) {
            return $this->errorResponse('Document not found.', 404);
        }

        $verified = $this->verificationService->verifyDriverDocument($document, $request->user());

        return $this->successResponse([
            'id' => $verified->id,
            'status' => $verified->status,
            'verified_at' => $verified->verified_at?->toIso8601String(),
        ], 'Driver document verified successfully.');
    }

    /**
     * Reject a driver KYC document.
     */
    public function rejectDriverDocument(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $document = DriverDocument::find($id);

        if (!$document) {
            return $this->errorResponse('Document not found.', 404);
        }

        $rejected = $this->verificationService->rejectDriverDocument(
            $document,
            $request->input('reason'),
            $request->user()
        );

        return $this->successResponse([
            'id' => $rejected->id,
            'status' => $rejected->status,
            'rejection_reason' => $rejected->rejection_reason,
        ], 'Driver document rejected.');
    }

    /**
     * List vehicles pending verification or under review.
     */
    public function pendingVehicles(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $vehicles = Vehicle::with(['documents.media', 'activeAssignment.driver.user'])
            ->when($status !== 'all', fn ($q) => $q->whereIn('verification_status', explode(',', $status)))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->successResponse($vehicles, 'Pending vehicles retrieved successfully.');
    }

    /**
     * Verify a vehicle.
     */
    public function verifyVehicle(Request $request, string $vehicleCode): JsonResponse
    {
        $vehicle = Vehicle::where('vehicle_code', $vehicleCode)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        $verified = $this->verificationService->verifyVehicle($vehicle, $request->user());

        return $this->successResponse([
            'vehicle_code' => $verified->vehicle_code,
            'registration_number' => $verified->registration_number,
            'verification_status' => $verified->verification_status,
            'status' => $verified->status,
            'verified_at' => $verified->verified_at?->toIso8601String(),
        ], 'Vehicle verified successfully.');
    }

    /**
     * Reject a vehicle.
     */
    public function rejectVehicle(Request $request, string $vehicleCode): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $vehicle = Vehicle::where('vehicle_code', $vehicleCode)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        $rejected = $this->verificationService->rejectVehicle(
            $vehicle,
            $request->input('reason'),
            $request->user()
        );

        return $this->successResponse([
            'vehicle_code' => $rejected->vehicle_code,
            'verification_status' => $rejected->verification_status,
        ], 'Vehicle rejected.');
    }

    /**
     * Verify a vehicle document.
     */
    public function verifyVehicleDocument(Request $request, int $id): JsonResponse
    {
        $document = VehicleDocument::find($id);

        if (!$document) {
            return $this->errorResponse('Vehicle document not found.', 404);
        }

        $verified = $this->verificationService->verifyVehicleDocument($document, $request->user());

        return $this->successResponse([
            'id' => $verified->id,
            'status' => $verified->status,
            'verified_at' => $verified->verified_at?->toIso8601String(),
        ], 'Vehicle document verified successfully.');
    }

    /**
     * Reject a vehicle document.
     */
    public function rejectVehicleDocument(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $document = VehicleDocument::find($id);

        if (!$document) {
            return $this->errorResponse('Vehicle document not found.', 404);
        }

        $rejected = $this->verificationService->rejectVehicleDocument(
            $document,
            $request->input('reason'),
            $request->user()
        );

        return $this->successResponse([
            'id' => $rejected->id,
            'status' => $rejected->status,
        ], 'Vehicle document rejected.');
    }

    /**
     * Update vehicle operational status (active, inactive, blocked).
     */
    public function updateVehicleStatus(Request $request, string $vehicleCode): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive,blocked'],
        ]);

        $vehicle = Vehicle::where('vehicle_code', $vehicleCode)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        $vehicle->update(['status' => $request->input('status')]);

        return $this->successResponse([
            'vehicle_code' => $vehicle->vehicle_code,
            'status' => $vehicle->status,
        ], 'Vehicle status updated successfully.');
    }

    /**
     * Securely stream a private document for verification inspection.
     */
    public function viewDocument(Request $request, string $mediaUuid): Response
    {
        $media = Media::where('uuid', $mediaUuid)->first();

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Media not found.'], 404);
        }

        return $this->mediaService->response($media);
    }
}
