<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Map of administrative roles to their granted permissions.
     *
     * @var array<string, list<string>>
     */
    protected array $rolePermissions = [
        'super_admin' => [
            '*', // Wildcard: full operational authority
        ],
        'admin' => [
            'users.view',
            'users.update',
            'users.suspend',
            'drivers.view',
            'drivers.verify',
            'drivers.reject',
            'drivers.suspend',
            'vehicles.view',
            'vehicles.verify',
            'vehicles.reject',
            'vehicles.suspend',
            'qr.view',
            'qr.generate',
            'qr.activate',
            'qr.disable',
            'qr.revoke',
            'qr.download',
            'journeys.view',
            'journeys.manage',
            'lost_items.view',
            'lost_items.manage',
            'lost_items.resolve',
            'reports.view',
            'reports.resolve',
            'audit_logs.view',
            'documents.view',
            'documents.verify',
            'documents.reject',
        ],
        'support' => [
            'users.view',
            'journeys.view',
            'lost_items.view',
            'lost_items.manage',
            'reports.view',
            'reports.resolve',
            'conversations.view',
            'conversations.participate',
        ],
        'verification_team' => [
            'drivers.view',
            'drivers.verify',
            'drivers.reject',
            'vehicles.view',
            'vehicles.verify',
            'vehicles.reject',
            'documents.view',
            'documents.verify',
            'documents.reject',
            'qr.view',
            'qr.generate',
            'qr.activate',
        ],
    ];

    /**
     * Determine if a user has a specific permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        $adminUser = $user->adminUser;
        if (!$adminUser || $adminUser->status !== 'active') {
            return false;
        }

        $roles = $adminUser->roles()->get();

        foreach ($roles as $role) {
            $permissions = $this->rolePermissions[$role->name] ?? [];

            // Super admin wildcard check
            if (in_array('*', $permissions, true)) {
                return true;
            }

            if (in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permitted abilities for a given user.
     *
     * @return list<string>
     */
    public function getPermissionsForUser(User $user): array
    {
        if ($user->status !== 'active') {
            return [];
        }

        $adminUser = $user->adminUser;
        if (!$adminUser || $adminUser->status !== 'active') {
            return [];
        }

        $all = [];
        foreach ($adminUser->roles as $role) {
            $perms = $this->rolePermissions[$role->name] ?? [];
            if (in_array('*', $perms, true)) {
                return ['*'];
            }
            $all = array_merge($all, $perms);
        }

        return array_values(array_unique($all));
    }
}
