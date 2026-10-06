<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleDriverAssignment;
use App\Services\MediaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    use ApiResponse;

    public function __construct(protected MediaService $mediaService)
    {
    }

    /**
     * List all vehicles assigned to or registered by the authenticated driver.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found.', 404);
        }

        $vehicles = Vehicle::whereHas('assignments', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id);
        })
        ->with(['documents.media', 'activeAssignment'])
        ->get()
        ->map(function ($vehicle) use ($driver) {
            $isActiveForDriver = $vehicle->activeAssignment?->driver_id === $driver->id;

            return [
                'uuid' => $vehicle->uuid,
                'vehicle_code' => $vehicle->vehicle_code,
                'registration_number' => $vehicle->registration_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'verification_status' => $vehicle->verification_status,
                'verified_at' => $vehicle->verified_at?->toIso8601String(),
                'status' => $vehicle->status,
                'is_active_for_you' => $isActiveForDriver,
                'documents_count' => $vehicle->documents->count(),
            ];
        });

        return $this->successResponse($vehicles, 'Vehicles retrieved successfully.');
    }

    /**
     * Register a new vehicle.
     */
    public function store(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found. Please register as a driver first.', 404);
        }

        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:30', 'unique:vehicles,registration_number'],
            'vehicle_type' => ['required', 'in:cab,taxi,auto,e_rickshaw,bus,tourist_vehicle,other'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        $vehicleCode = 'VEH-' . strtoupper(Str::random(6));

        $vehicle = Vehicle::create([
            'vehicle_code' => $vehicleCode,
            'registration_number' => strtoupper(trim($validated['registration_number'])),
            'vehicle_type' => $validated['vehicle_type'],
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
            'color' => $validated['color'] ?? null,
            'verification_status' => 'pending',
            'status' => 'active',
        ]);

        // Auto-assign vehicle to this driver if they have no active vehicle
        $activeAssignment = $driver->activeAssignment;
        if (!$activeAssignment) {
            VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'started_at' => now(),
                'status' => 'active',
            ]);
        } else {
            // Create record in ended state or historical
            VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'started_at' => now(),
                'status' => 'ended',
                'ended_at' => now(),
            ]);
        }

        return $this->successResponse([
            'uuid' => $vehicle->uuid,
            'vehicle_code' => $vehicle->vehicle_code,
            'registration_number' => $vehicle->registration_number,
            'vehicle_type' => $vehicle->vehicle_type,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'color' => $vehicle->color,
            'verification_status' => $vehicle->verification_status,
            'status' => $vehicle->status,
        ], 'Vehicle registered successfully. Please submit RC and Insurance documents for verification.', 201);
    }

    /**
     * Show vehicle details and documents.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found.', 404);
        }

        $vehicle = Vehicle::where('uuid', $uuid)
            ->with(['documents.media', 'activeAssignment'])
            ->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        return $this->successResponse([
            'uuid' => $vehicle->uuid,
            'vehicle_code' => $vehicle->vehicle_code,
            'registration_number' => $vehicle->registration_number,
            'vehicle_type' => $vehicle->vehicle_type,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'color' => $vehicle->color,
            'verification_status' => $vehicle->verification_status,
            'verified_at' => $vehicle->verified_at?->toIso8601String(),
            'status' => $vehicle->status,
            'is_active_for_you' => $vehicle->activeAssignment?->driver_id === $driver->id,
            'documents' => $vehicle->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'document_number' => $doc->document_number,
                    'status' => $doc->status,
                    'verified_at' => $doc->verified_at?->toIso8601String(),
                    'expires_at' => $doc->expires_at?->toDateString(),
                    'created_at' => $doc->created_at?->toIso8601String(),
                ];
            }),
        ], 'Vehicle retrieved successfully.');
    }

    /**
     * Upload vehicle compliance document (RC, Insurance, Permit, Fitness).
     */
    public function uploadDocument(Request $request, string $uuid): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found.', 404);
        }

        $vehicle = Vehicle::where('uuid', $uuid)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        $validated = $request->validate([
            'document_type' => ['required', 'in:registration,insurance,permit,fitness,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'], // 10MB max
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        // Upload to private disk
        $media = $this->mediaService->upload(
            file: $request->file('file'),
            directory: 'vehicles/documents',
            visibility: 'private',
            uploader: $request->user(),
            disk: 'private'
        );

        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'media_id' => $media->id,
            'status' => 'pending',
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        if ($vehicle->verification_status === 'rejected') {
            $vehicle->update(['verification_status' => 'under_review']);
        }

        return $this->successResponse([
            'id' => $document->id,
            'document_type' => $document->document_type,
            'status' => $document->status,
            'media_uuid' => $media->uuid,
        ], 'Vehicle document uploaded successfully and queued for verification.', 201);
    }

    /**
     * Assign vehicle as the active vehicle for this driver.
     */
    public function assignActive(Request $request, string $uuid): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found.', 404);
        }

        $vehicle = Vehicle::where('uuid', $uuid)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        if ($vehicle->status !== 'active') {
            return $this->errorResponse('This vehicle is inactive or blocked.', 400);
        }

        // Close any active assignment for this driver
        VehicleDriverAssignment::where('driver_id', $driver->id)
            ->where('status', 'active')
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

        // Close any active assignment on this vehicle from other drivers
        VehicleDriverAssignment::where('vehicle_id', $vehicle->id)
            ->where('status', 'active')
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

        // Create new active assignment
        $assignment = VehicleDriverAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return $this->successResponse([
            'assignment_id' => $assignment->id,
            'vehicle_uuid' => $vehicle->uuid,
            'registration_number' => $vehicle->registration_number,
            'status' => 'active',
            'started_at' => $assignment->started_at->toIso8601String(),
        ], 'Vehicle assigned as active successfully.');
    }

    /**
     * End active assignment for a vehicle.
     */
    public function endAssignment(Request $request, string $uuid): JsonResponse
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return $this->errorResponse('Driver profile not found.', 404);
        }

        $vehicle = Vehicle::where('uuid', $uuid)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        $updated = VehicleDriverAssignment::where('driver_id', $driver->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('status', 'active')
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

        if (!$updated) {
            return $this->errorResponse('No active assignment found for this vehicle.', 400);
        }

        return $this->successResponse(null, 'Active vehicle assignment ended successfully.');
    }
}
