<?php

namespace App\Services;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SafetyService
{
    /**
     * Block a user.
     */
    public function blockUser(User $user, int $blockedUserId, ?string $reason = null): BlockedUser
    {
        if ($user->id === $blockedUserId) {
            throw new \DomainException('You cannot block yourself.');
        }

        $targetUser = User::find($blockedUserId);
        if (!$targetUser) {
            throw new \DomainException('User to block was not found.');
        }

        return BlockedUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'blocked_user_id' => $blockedUserId,
            ],
            [
                'reason' => $reason ? trim($reason) : null,
                'created_at' => now(),
            ]
        );
    }

    /**
     * Unblock a user.
     */
    public function unblockUser(User $user, int $blockedUserId): bool
    {
        $deleted = BlockedUser::where('user_id', $user->id)
            ->where('blocked_user_id', $blockedUserId)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Get list of users blocked by this user.
     */
    public function getBlockedUsers(User $user): Collection
    {
        return BlockedUser::where('user_id', $user->id)
            ->with('blockedUser')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if user A has blocked user B, or vice-versa.
     */
    public function isBlocked(int $userIdA, int $userIdB): bool
    {
        return BlockedUser::where(function ($q) use ($userIdA, $userIdB) {
            $q->where('user_id', $userIdA)->where('blocked_user_id', $userIdB);
        })->orWhere(function ($q) use ($userIdA, $userIdB) {
            $q->where('user_id', $userIdB)->where('blocked_user_id', $userIdA);
        })->exists();
    }
}
