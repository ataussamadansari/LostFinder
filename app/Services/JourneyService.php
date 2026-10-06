<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\QrCode;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class JourneyService
{
    /**
     * Start a new active journey for a verified driver and vehicle.
     */
    public function start(Driver $driver, Vehicle $vehicle): Journey
    {
        if (!$driver->isVerified() || $driver->user?->status !== 'active') {
            throw new \DomainException('Driver must be verified and active to start a journey.');
        }

        if (!$vehicle->isVerified() || $vehicle->status !== 'active') {
            throw new \DomainException('Vehicle must be verified and active to participate in a journey.');
        }

        $activeAssignment = $vehicle->activeAssignment;
        if (!$activeAssignment || $activeAssignment->driver_id !== $driver->id) {
            throw new \DomainException('Driver is not actively assigned to this vehicle.');
        }

        $activeQr = $vehicle->activeQrCode;
        if (!$activeQr) {
            throw new \DomainException('Vehicle does not have an active QR code.');
        }

        // Check for conflicting active journeys
        $existingVehicleJourney = Journey::where('vehicle_id', $vehicle->id)
            ->where('status', 'active')
            ->first();

        if ($existingVehicleJourney) {
            throw new \DomainException('An active journey is already in progress for this vehicle.');
        }

        $existingDriverJourney = Journey::where('driver_id', $driver->id)
            ->where('status', 'active')
            ->first();

        if ($existingDriverJourney) {
            throw new \DomainException('You already have an active journey in progress.');
        }

        return DB::transaction(function () use ($driver, $vehicle) {
            $startedAt = now();
            $ttlHours = (int) config('lostfinder.journey_ttl_hours', 72);
            $expiresAt = $startedAt->copy()->addHours($ttlHours);

            $journey = Journey::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'status' => 'active',
            ]);

            AuditLog::create([
                'user_id' => $driver->user_id,
                'action' => 'journey.started',
                'auditable_type' => Journey::class,
                'auditable_id' => $journey->id,
                'new_values' => [
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driver->id,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]);

            return $journey;
        });
    }

    /**
     * Mark an active journey as completed.
     */
    public function complete(Journey $journey, User $user): Journey
    {
        if ($journey->status !== 'active') {
            throw new \DomainException('Only active journeys can be completed.');
        }

        return DB::transaction(function () use ($journey, $user) {
            $journey->update([
                'status' => 'completed',
                'ended_at' => now(),
            ]);

            // Complete active passenger connections
            $journey->journeyPassengers()
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'disconnected_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'journey.completed',
                'auditable_type' => Journey::class,
                'auditable_id' => $journey->id,
                'new_values' => ['status' => 'completed'],
            ]);

            return $journey->fresh();
        });
    }

    /**
     * Cancel an active journey.
     */
    public function cancel(Journey $journey, User $user, ?string $reason = null): Journey
    {
        if ($journey->status !== 'active') {
            throw new \DomainException('Only active journeys can be cancelled.');
        }

        return DB::transaction(function () use ($journey, $user, $reason) {
            $journey->update([
                'status' => 'cancelled',
                'ended_at' => now(),
            ]);

            $journey->journeyPassengers()
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'disconnected_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'journey.cancelled',
                'auditable_type' => Journey::class,
                'auditable_id' => $journey->id,
                'new_values' => ['status' => 'cancelled', 'reason' => $reason],
            ]);

            return $journey->fresh();
        });
    }

    /**
     * Connect a passenger to an active journey via QR token or journey UUID.
     */
    public function connectPassenger(User $passenger, ?string $qrToken = null, ?string $journeyUuid = null): JourneyPassenger
    {
        if ($passenger->status !== 'active') {
            throw new \DomainException('User account is not active.');
        }

        $journey = null;

        if ($qrToken) {
            $qrCode = QrCode::where('token', $qrToken)->where('status', 'active')->first();
            if (!$qrCode) {
                throw new \DomainException('Invalid or inactive QR code.');
            }

            $vehicle = $qrCode->vehicle;
            if (!$vehicle || $vehicle->status !== 'active' || !$vehicle->isVerified()) {
                throw new \DomainException('Vehicle is not eligible for active journeys.');
            }

            // Look for existing active journey
            $journey = Journey::where('vehicle_id', $vehicle->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            // Auto-initiate controlled journey if active assigned driver is present
            if (!$journey) {
                $assignment = $vehicle->activeAssignment()->with('driver.user')->first();
                if (!$assignment || !$assignment->driver?->isVerified() || $assignment->driver->user?->status !== 'active') {
                    throw new \DomainException('No verified active driver is currently operating this vehicle.');
                }

                $journey = $this->start($assignment->driver, $vehicle);
            }
        } elseif ($journeyUuid) {
            $journey = Journey::where('uuid', $journeyUuid)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$journey) {
                throw new \DomainException('Journey not found or is no longer active.');
            }
        } else {
            throw new \InvalidArgumentException('Either qr_token or journey_uuid must be provided.');
        }

        return DB::transaction(function () use ($journey, $passenger) {
            $journeyPassenger = JourneyPassenger::where('journey_id', $journey->id)
                ->where('passenger_id', $passenger->id)
                ->first();

            if ($journeyPassenger) {
                if ($journeyPassenger->status === 'active') {
                    return $journeyPassenger;
                }

                $journeyPassenger->update([
                    'status' => 'active',
                    'connected_at' => now(),
                    'disconnected_at' => null,
                ]);

                return $journeyPassenger->fresh();
            }

            $journeyPassenger = JourneyPassenger::create([
                'journey_id' => $journey->id,
                'passenger_id' => $passenger->id,
                'status' => 'active',
                'connected_at' => now(),
                'share_details' => false,
                'shared_fields' => [],
            ]);

            AuditLog::create([
                'user_id' => $passenger->id,
                'action' => 'journey.passenger_connected',
                'auditable_type' => Journey::class,
                'auditable_id' => $journey->id,
                'new_values' => ['passenger_id' => $passenger->id],
            ]);

            return $journeyPassenger;
        });
    }

    /**
     * Disconnect a passenger from an active journey.
     */
    public function disconnectPassenger(Journey $journey, User $passenger): JourneyPassenger
    {
        $journeyPassenger = JourneyPassenger::where('journey_id', $journey->id)
            ->where('passenger_id', $passenger->id)
            ->where('status', 'active')
            ->first();

        if (!$journeyPassenger) {
            throw new \DomainException('Passenger is not actively connected to this journey.');
        }

        $journeyPassenger->update([
            'status' => 'completed',
            'disconnected_at' => now(),
        ]);

        return $journeyPassenger->fresh();
    }

    /**
     * Update passenger "Share My Details" disclosure toggle.
     */
    public function updateShareDetails(Journey $journey, User $passenger, bool $shareContact, array $fields = []): JourneyPassenger
    {
        $journeyPassenger = JourneyPassenger::where('journey_id', $journey->id)
            ->where('passenger_id', $passenger->id)
            ->first();

        if (!$journeyPassenger) {
            throw new \DomainException('Passenger is not part of this journey.');
        }

        $journeyPassenger->update([
            'share_details' => $shareContact,
            'shared_fields' => $shareContact ? ($fields ?: ['name', 'phone']) : [],
        ]);

        return $journeyPassenger->fresh();
    }

    /**
     * Automatically expire inactive journeys that have exceeded their 72-hour TTL.
     */
    public function expireInactiveJourneys(): int
    {
        $expiredJourneys = Journey::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredJourneys as $journey) {
            DB::transaction(function () use ($journey) {
                $journey->update([
                    'status' => 'expired',
                    'ended_at' => now(),
                ]);

                $journey->journeyPassengers()
                    ->where('status', 'active')
                    ->update([
                        'status' => 'expired',
                        'disconnected_at' => now(),
                    ]);

                AuditLog::create([
                    'user_id' => null,
                    'action' => 'journey.expired',
                    'auditable_type' => Journey::class,
                    'auditable_id' => $journey->id,
                    'new_values' => ['status' => 'expired', 'expires_at' => $journey->expires_at?->toIso8601String()],
                ]);
            });

            $count++;
        }

        return $count;
    }

    /**
     * Format a passenger for display to the driver with strict privacy controls.
     * Only displays personal details if passenger explicitly granted "Share My Details".
     *
     * @return array<string, mixed>
     */
    public function formatPassengerForDriver(JourneyPassenger $jp): array
    {
        $passenger = $jp->passenger;
        $shareDetails = (bool) $jp->share_details;
        $fields = $jp->shared_fields ?? [];

        $displayName = 'Passenger #' . substr((string) $jp->id, -4);
        $phone = null;

        if ($shareDetails) {
            if (in_array('name', $fields) || empty($fields)) {
                $displayName = $passenger->name;
            }
            if (in_array('phone', $fields) || empty($fields)) {
                $phone = $passenger->phone;
            }
        }

        return [
            'id' => $jp->id,
            'passenger_id' => $passenger->id,
            'display_name' => $displayName,
            'phone' => $phone,
            'share_details' => $shareDetails,
            'status' => $jp->status,
            'connected_at' => $jp->connected_at?->toIso8601String(),
        ];
    }
}
