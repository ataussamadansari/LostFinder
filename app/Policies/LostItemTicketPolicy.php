<?php

namespace App\Policies;

use App\Models\LostItemTicket;
use App\Models\User;

class LostItemTicketPolicy
{
    /**
     * Determine whether the user can view the ticket.
     * Allowed: Passenger of ticket, Driver assigned to ticket, or Admin with lost_items.view.
     */
    public function view(User $user, LostItemTicket $ticket): bool
    {
        if ($ticket->passenger_id === $user->id) {
            return true;
        }

        if ($user->driver && $ticket->driver_id === $user->driver->id) {
            return true;
        }

        return $user->hasPermission('lost_items.view');
    }

    /**
     * Determine whether the user can update the ticket.
     */
    public function update(User $user, LostItemTicket $ticket): bool
    {
        if ($ticket->passenger_id === $user->id) {
            return true;
        }

        if ($user->driver && $ticket->driver_id === $user->driver->id) {
            return true;
        }

        return $user->hasPermission('lost_items.manage');
    }

    /**
     * Determine whether the user can cancel the ticket.
     * Allowed: Passenger who created it, while status is created or driver_notified.
     */
    public function cancel(User $user, LostItemTicket $ticket): bool
    {
        if ($ticket->passenger_id !== $user->id) {
            return false;
        }

        return in_array($ticket->status, ['created', 'driver_notified', 'searching'], true);
    }

    /**
     * Determine whether the user can respond as the assigned driver.
     */
    public function respondDriver(User $user, LostItemTicket $ticket): bool
    {
        if ($user->driver && $ticket->driver_id === $user->driver->id) {
            return true;
        }

        return $user->hasPermission('lost_items.manage');
    }

    /**
     * Determine whether the user can confirm receipt as the passenger.
     */
    public function confirmPassenger(User $user, LostItemTicket $ticket): bool
    {
        if ($ticket->passenger_id === $user->id) {
            return true;
        }

        return $user->hasPermission('lost_items.manage');
    }

    /**
     * Determine whether the user can dispute the ticket.
     */
    public function dispute(User $user, LostItemTicket $ticket): bool
    {
        if ($ticket->passenger_id === $user->id) {
            return true;
        }

        if ($user->driver && $ticket->driver_id === $user->driver->id) {
            return true;
        }

        return $user->hasPermission('lost_items.manage');
    }

    /**
     * Determine whether the user can escalate the ticket.
     */
    public function escalate(User $user, LostItemTicket $ticket): bool
    {
        if ($ticket->passenger_id === $user->id) {
            return true;
        }

        if ($user->driver && $ticket->driver_id === $user->driver->id) {
            return true;
        }

        return $user->hasPermission('lost_items.manage');
    }

    /**
     * Determine whether the user can resolve or force-close the ticket.
     */
    public function resolve(User $user, LostItemTicket $ticket): bool
    {
        return $user->hasPermission('lost_items.resolve') || $user->isAdmin();
    }
}
