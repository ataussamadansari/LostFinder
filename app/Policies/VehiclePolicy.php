<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Determine whether the user can view the vehicle.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        if ($user->hasPermission('vehicles.view')) {
            return true;
        }

        $driver = $user->driver;
        if (!$driver) {
            return false;
        }

        return $vehicle->assignments()->where('driver_id', $driver->id)->exists();
    }

    /**
     * Determine whether the user can verify the vehicle.
     */
    public function verify(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.verify');
    }

    /**
     * Determine whether the user can reject the vehicle.
     */
    public function reject(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.reject');
    }

    /**
     * Determine whether the user can suspend the vehicle.
     */
    public function suspend(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.suspend');
    }
}
