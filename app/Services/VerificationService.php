<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    /**
     * Verify a driver.
     */
    public function verifyDriver(Driver $driver, User $reviewer): Driver
    {
        if ($driver->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'driver' => 'Drivers cannot verify their own accounts.',
            ]);
        }

        // Verify driver has at least one document
        $hasApprovedDoc = $driver->documents()->where('status', 'verified')->exists();
        if (!$hasApprovedDoc) {
            // Also accept if they have pending docs by auto-approving them or requiring doc approval first
            $hasAnyDoc = $driver->documents()->exists();
            if (!$hasAnyDoc) {
                throw ValidationException::withMessages([
                    'documents' => 'Driver must have uploaded at least one identity document before verification.',
                ]);
            }
        }

        $driver->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $reviewer->id,
            'action' => 'driver.verified',
            'auditable_type' => Driver::class,
            'auditable_id' => $driver->id,
            'new_values' => ['verification_status' => 'verified'],
        ]);

        // Send notification to driver
        Notification::create([
            'user_id' => $driver->user_id,
            'title' => 'Driver Profile Verified',
            'body' => 'Congratulations! Your driver account has been verified. You may now operate verified vehicles.',
            'type' => 'driver_verified',
        ]);

        return $driver->fresh();
    }

    /**
     * Reject a driver.
     */
    public function rejectDriver(Driver $driver, string $reason, User $reviewer): Driver
    {
        if ($driver->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'driver' => 'Drivers cannot reject their own accounts.',
            ]);
        }

        $driver->update([
            'verification_status' => 'rejected',
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $reviewer->id,
            'action' => 'driver.rejected',
            'auditable_type' => Driver::class,
            'auditable_id' => $driver->id,
            'new_values' => ['verification_status' => 'rejected', 'reason' => $reason],
        ]);

        // Notification to driver
        Notification::create([
            'user_id' => $driver->user_id,
            'title' => 'Driver Verification Rejected',
            'body' => "Your driver verification was not approved: {$reason}. Please re-submit your documents.",
            'type' => 'driver_rejected',
        ]);

        return $driver->fresh();
    }

    /**
     * Verify a driver KYC document.
     */
    public function verifyDriverDocument(DriverDocument $document, User $reviewer): DriverDocument
    {
        if ($document->driver->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'document' => 'Drivers cannot verify their own documents.',
            ]);
        }

        $document->update([
            'status' => 'verified',
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        return $document->fresh();
    }

    /**
     * Reject a driver KYC document.
     */
    public function rejectDriverDocument(DriverDocument $document, string $reason, User $reviewer): DriverDocument
    {
        if ($document->driver->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'document' => 'Drivers cannot reject their own documents.',
            ]);
        }

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $document->fresh();
    }

    /**
     * Verify a vehicle.
     */
    public function verifyVehicle(Vehicle $vehicle, User $reviewer): Vehicle
    {
        $vehicle->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => $reviewer->id,
            'action' => 'vehicle.verified',
            'auditable_type' => Vehicle::class,
            'auditable_id' => $vehicle->id,
            'new_values' => ['verification_status' => 'verified'],
        ]);

        return $vehicle->fresh();
    }

    /**
     * Reject a vehicle.
     */
    public function rejectVehicle(Vehicle $vehicle, string $reason, User $reviewer): Vehicle
    {
        $vehicle->update([
            'verification_status' => 'rejected',
        ]);

        AuditLog::create([
            'user_id' => $reviewer->id,
            'action' => 'vehicle.rejected',
            'auditable_type' => Vehicle::class,
            'auditable_id' => $vehicle->id,
            'new_values' => ['verification_status' => 'rejected', 'reason' => $reason],
        ]);

        return $vehicle->fresh();
    }

    /**
     * Verify a vehicle document.
     */
    public function verifyVehicleDocument(VehicleDocument $document, User $reviewer): VehicleDocument
    {
        $document->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        return $document->fresh();
    }

    /**
     * Reject a vehicle document.
     */
    public function rejectVehicleDocument(VehicleDocument $document, string $reason, User $reviewer): VehicleDocument
    {
        $document->update([
            'status' => 'rejected',
        ]);

        return $document->fresh();
    }

    /**
     * Check document expiry dates and flag expired documents.
     *
     * @return array{driver_docs_expired: int, vehicle_docs_expired: int}
     */
    public function checkExpiries(): array
    {
        $today = now()->toDateString();

        // Expire driver documents
        $expiredDriverDocs = DriverDocument::where('status', 'verified')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $today)
            ->get();

        $driverDocsCount = 0;
        foreach ($expiredDriverDocs as $doc) {
            $doc->update(['status' => 'expired']);
            $driverDocsCount++;

            // Move driver to under_review if critical document expired
            $driver = $doc->driver;
            if ($driver && $driver->verification_status === 'verified') {
                $driver->update(['verification_status' => 'under_review']);
            }
        }

        // Expire vehicle documents
        $expiredVehicleDocs = VehicleDocument::where('status', 'verified')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $today)
            ->get();

        $vehicleDocsCount = 0;
        foreach ($expiredVehicleDocs as $doc) {
            $doc->update(['status' => 'expired']);
            $vehicleDocsCount++;

            $vehicle = $doc->vehicle;
            if ($vehicle && $vehicle->verification_status === 'verified') {
                $vehicle->update(['verification_status' => 'under_review']);
            }
        }

        return [
            'driver_docs_expired' => $driverDocsCount,
            'vehicle_docs_expired' => $vehicleDocsCount,
        ];
    }
}
