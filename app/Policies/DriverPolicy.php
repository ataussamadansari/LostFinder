<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    /**
     * Determine whether the user can view the driver profile.
     */
    public function view(User $user, Driver $driver): bool
    {
        return $driver->user_id === $user->id || $user->hasPermission('drivers.view');
    }

    /**
     * Determine whether the user can verify the driver.
     * Rule: Driver cannot verify their own account.
     */
    public function verify(User $user, Driver $driver): bool
    {
        if ($driver->user_id === $user->id) {
            return false;
        }

        return $user->hasPermission('drivers.verify');
    }

    /**
     * Determine whether the user can reject the driver.
     */
    public function reject(User $user, Driver $driver): bool
    {
        if ($driver->user_id === $user->id) {
            return false;
        }

        return $user->hasPermission('drivers.reject');
    }

    /**
     * Determine whether the user can suspend the driver.
     */
    public function suspend(User $user, Driver $driver): bool
    {
        return $user->hasPermission('drivers.suspend');
    }
}
