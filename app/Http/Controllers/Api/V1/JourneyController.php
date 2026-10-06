<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\Vehicle;
use App\Services\JourneyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    use ApiResponse;

    public function __construct(protected JourneyService $journeyService)
    {
    }

    /**
     * Start a new active journey for a verified vehicle.
     * Initiated by an authorized, verified driver.
     */
    public function start(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Only registered drivers can start a journey.', 403);
        }

        $vehicleCode = $request->input('vehicle_code') ?? $request->input('vehicle_uuid');

        if (!$vehicleCode) {
            return $this->errorResponse('Vehicle identifier (vehicle_code or vehicle_uuid) is required.', 422);
        }

        $vehicle = Vehicle::where('vehicle_code', $vehicleCode)
            ->orWhere('uuid', $vehicleCode)
            ->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        try {
            $journey = $this->journeyService->start($driver, $vehicle);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'uuid' => $journey->uuid,
            'status' => $journey->status,
            'started_at' => $journey->started_at?->toIso8601String(),
            'expires_at' => $journey->expires_at?->toIso8601String(),
            'vehicle' => [
                'vehicle_code' => $vehicle->vehicle_code,
                'registration_number' => $vehicle->registration_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
            ],
        ], 'Journey started successfully.', 201);
    }

    /**
     * Complete an active journey.
     */
    public function complete(Request $request, string $uuid): JsonResponse
    {
        $journey = Journey::where('uuid', $uuid)->first();

        if (!$journey) {
            return $this->errorResponse('Journey not found.', 404);
        }

        $user = $request->user();
        if ($user->driver?->id !== $journey->driver_id && !$user->isAdmin()) {
            return $this->errorResponse('Unauthorized to complete this journey.', 403);
        }

        try {
            $completedJourney = $this->journeyService->complete($journey, $user);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'uuid' => $completedJourney->uuid,
            'status' => $completedJourney->status,
            'ended_at' => $completedJourney->ended_at?->toIso8601String(),
        ], 'Journey completed successfully.');
    }

    /**
     * Cancel an active journey.
     */
    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $journey = Journey::where('uuid', $uuid)->first();

        if (!$journey) {
            return $this->errorResponse('Journey not found.', 404);
        }

        $user = $request->user();
        if ($user->driver?->id !== $journey->driver_id && !$user->isAdmin()) {
            return $this->errorResponse('Unauthorized to cancel this journey.', 403);
        }

        try {
            $cancelledJourney = $this->journeyService->cancel(
                $journey,
                $user,
                $request->input('reason')
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'uuid' => $cancelledJourney->uuid,
            'status' => $cancelledJourney->status,
            'ended_at' => $cancelledJourney->ended_at?->toIso8601String(),
        ], 'Journey cancelled successfully.');
    }

    /**
     * Connect an authenticated passenger to a journey via QR token or journey UUID.
     */
    public function connect(Request $request): JsonResponse
    {
        $qrToken = $request->input('qr_token');
        $journeyUuid = $request->input('journey_uuid') ?? $request->input('uuid');

        if (!$qrToken && !$journeyUuid) {
            return $this->errorResponse('Either qr_token or journey_uuid must be provided to connect.', 422);
        }

        try {
            $jp = $this->journeyService->connectPassenger($request->user(), $qrToken, $journeyUuid);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        $journey = $jp->journey->load(['vehicle', 'driver']);

        return $this->successResponse([
            'connection_id' => $jp->id,
            'journey_uuid' => $journey->uuid,
            'connected_at' => $jp->connected_at?->toIso8601String(),
            'status' => $jp->status,
            'share_details' => (bool) $jp->share_details,
            'vehicle' => [
                'vehicle_code' => $journey->vehicle->vehicle_code,
                'registration_number' => $journey->vehicle->registration_number,
                'vehicle_type' => $journey->vehicle->vehicle_type,
                'make' => $journey->vehicle->make,
                'model' => $journey->vehicle->model,
                'color' => $journey->vehicle->color,
            ],
            'driver' => [
                'driver_code' => $journey->driver->driver_code,
                'rating_avg' => (float) $journey->driver->rating_avg,
                'is_verified' => $journey->driver->isVerified(),
            ],
        ], 'Connected to journey successfully.');
    }

    /**
     * Disconnect a passenger from an active journey.
     */
    public function disconnect(Request $request, string $uuid): JsonResponse
    {
        $journey = Journey::where('uuid', $uuid)->first();

        if (!$journey) {
            return $this->errorResponse('Journey not found.', 404);
        }

        try {
            $jp = $this->journeyService->disconnectPassenger($journey, $request->user());
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'journey_uuid' => $journey->uuid,
            'status' => $jp->status,
            'disconnected_at' => $jp->disconnected_at?->toIso8601String(),
        ], 'Disconnected from journey successfully.');
    }

    /**
     * Toggle "Share My Details" disclosure for a connected passenger.
     */
    public function shareDetails(Request $request, string $uuid): JsonResponse
    {
        $journey = Journey::where('uuid', $uuid)->first();

        if (!$journey) {
            return $this->errorResponse('Journey not found.', 404);
        }

        $shareContact = (bool) $request->input('share_contact', true);
        $fields = (array) $request->input('fields', ['name', 'phone']);

        try {
            $jp = $this->journeyService->updateShareDetails($journey, $request->user(), $shareContact, $fields);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'journey_uuid' => $journey->uuid,
            'share_details' => (bool) $jp->share_details,
            'shared_fields' => $jp->shared_fields,
        ], 'Contact sharing settings updated successfully.');
    }

    /**
     * Retrieve current active journey for the authenticated user (driver or passenger).
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is a driver
        if ($user->isDriver() && $user->driver) {
            $journey = Journey::where('driver_id', $user->driver->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->with(['vehicle', 'journeyPassengers'])
                ->first();

            if ($journey) {
                return $this->successResponse([
                    'role' => 'driver',
                    'journey_uuid' => $journey->uuid,
                    'status' => $journey->status,
                    'started_at' => $journey->started_at?->toIso8601String(),
                    'expires_at' => $journey->expires_at?->toIso8601String(),
                    'passenger_count' => $journey->journeyPassengers()->where('status', 'active')->count(),
                    'vehicle' => [
                        'vehicle_code' => $journey->vehicle->vehicle_code,
                        'registration_number' => $journey->vehicle->registration_number,
                        'make' => $journey->vehicle->make,
                        'model' => $journey->vehicle->model,
                    ],
                ], 'Current active journey retrieved.');
            }
        }

        // Check if user is a passenger on an active journey
        $jp = JourneyPassenger::where('passenger_id', $user->id)
            ->where('status', 'active')
            ->whereHas('journey', function ($q) {
                $q->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->with(['journey.vehicle', 'journey.driver'])
            ->first();

        if ($jp) {
            $journey = $jp->journey;

            return $this->successResponse([
                'role' => 'passenger',
                'journey_uuid' => $journey->uuid,
                'status' => $journey->status,
                'connected_at' => $jp->connected_at?->toIso8601String(),
                'expires_at' => $journey->expires_at?->toIso8601String(),
                'share_details' => (bool) $jp->share_details,
                'vehicle' => [
                    'vehicle_code' => $journey->vehicle->vehicle_code,
                    'registration_number' => $journey->vehicle->registration_number,
                    'vehicle_type' => $journey->vehicle->vehicle_type,
                    'make' => $journey->vehicle->make,
                    'model' => $journey->vehicle->model,
                    'color' => $journey->vehicle->color,
                ],
                'driver' => [
                    'driver_code' => $journey->driver->driver_code,
                    'rating_avg' => (float) $journey->driver->rating_avg,
                    'is_verified' => $journey->driver->isVerified(),
                ],
            ], 'Current active journey retrieved.');
        }

        return $this->successResponse(null, 'No active journey in progress.');
    }

    /**
     * Show details of a specific journey.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $journey = Journey::with(['vehicle', 'driver'])->where('uuid', $uuid)->first();

        if (!$journey) {
            return $this->errorResponse('Journey not found.', 404);
        }

        $user = $request->user();
        $isDriver = $user->driver?->id === $journey->driver_id;
        $isPassenger = $journey->journeyPassengers()->where('passenger_id', $user->id)->exists();

        if (!$isDriver && !$isPassenger && !$user->isAdmin()) {
            return $this->errorResponse('Unauthorized to view this journey.', 403);
        }

        return $this->successResponse([
            'uuid' => $journey->uuid,
            'status' => $journey->status,
            'started_at' => $journey->started_at?->toIso8601String(),
            'ended_at' => $journey->ended_at?->toIso8601String(),
            'expires_at' => $journey->expires_at?->toIso8601String(),
            'vehicle' => [
                'vehicle_code' => $journey->vehicle->vehicle_code,
                'registration_number' => $journey->vehicle->registration_number,
                'make' => $journey->vehicle->make,
                'model' => $journey->vehicle->model,
            ],
            'driver' => [
                'driver_code' => $journey->driver->driver_code,
                'is_verified' => $journey->driver->isVerified(),
            ],
        ], 'Journey details retrieved.');
    }

    /**
     * Retrieve list of passengers connected to a journey.
     * Accessible by the driver of the journey or staff.
     */
    public function passengers(Request $request, string $uuid): JsonResponse
    {
        $journey = Journey::where('uuid', $uuid)->first();

        if (!$journey) {
            return $this->errorResponse('Journey not found.', 404);
        }

        $user = $request->user();
        if ($user->driver?->id !== $journey->driver_id && !$user->isAdmin()) {
            return $this->errorResponse('Unauthorized to view journey passengers.', 403);
        }

        $passengers = $journey->journeyPassengers()
            ->with('passenger')
            ->get()
            ->map(fn($jp) => $this->journeyService->formatPassengerForDriver($jp));

        return $this->successResponse($passengers, 'Journey passengers retrieved successfully.');
    }
}
