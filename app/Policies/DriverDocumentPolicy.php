<?php

namespace App\Policies;

use App\Models\DriverDocument;
use App\Models\User;

class DriverDocumentPolicy
{
    /**
     * Determine whether the user can view the sensitive driver document.
     * Allowed: Owner driver or Admin/Verification team with documents.view.
     */
    public function view(User $user, DriverDocument $document): bool
    {
        if ($document->driver->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('documents.view');
    }

    /**
     * Determine whether the user can verify the driver document.
     * Rule: Driver cannot verify their own document.
     */
    public function verify(User $user, DriverDocument $document): bool
    {
        if ($document->driver->user_id === $user->id) {
            return false;
        }

        return $user->hasPermission('documents.verify');
    }

    /**
     * Determine whether the user can reject the driver document.
     */
    public function reject(User $user, DriverDocument $document): bool
    {
        if ($document->driver->user_id === $user->id) {
            return false;
        }

        return $user->hasPermission('documents.reject');
    }
}
