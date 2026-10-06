<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RatingService
{
    /**
     * Submit a rating from a passenger to the driver of a journey.
     */
    public function submitRating(User $fromUser, array $data): Rating
    {
        // 1. Resolve journey
        $journey = null;
        if (!empty($data['journey_uuid'])) {
            $journey = Journey::where('uuid', $data['journey_uuid'])->with('driver.user')->first();
        } elseif (!empty($data['journey_id'])) {
            $journey = Journey::with('driver.user')->find($data['journey_id']);
        }

        if (!$journey) {
            throw new \DomainException('Journey not found.');
        }

        // 2. Validate passenger participation
        $isPassenger = JourneyPassenger::where('journey_id', $journey->id)
            ->where('passenger_id', $fromUser->id)
            ->exists();

        if (!$isPassenger) {
            throw new \DomainException('You were not a registered passenger on this journey.');
        }

        // 3. Resolve driver recipient
        $driver = $journey->driver;
        if (!$driver || !$driver->user_id) {
            throw new \DomainException('No driver associated with this journey.');
        }

        if ($driver->user_id === $fromUser->id) {
            throw new \DomainException('You cannot rate yourself.');
        }

        // 4. Duplicate rating prevention
        $existing = Rating::where('journey_id', $journey->id)
            ->where('from_user_id', $fromUser->id)
            ->first();

        if ($existing) {
            throw new \DomainException('You have already submitted a rating for this journey.');
        }

        $ratingScore = (int) $data['rating'];
        if ($ratingScore < 1 || $ratingScore > 5) {
            throw new \DomainException('Rating score must be between 1 and 5.');
        }

        return DB::transaction(function () use ($journey, $fromUser, $driver, $ratingScore, $data) {
            $rating = Rating::create([
                'journey_id' => $journey->id,
                'from_user_id' => $fromUser->id,
                'to_user_id' => $driver->user_id,
                'rating' => $ratingScore,
                'comment' => !empty($data['comment']) ? trim($data['comment']) : null,
            ]);

            // Recalculate driver's overall rating
            $ratings = Rating::where('to_user_id', $driver->user_id)->get();
            $driver->update([
                'rating_avg' => round($ratings->avg('rating'), 2),
                'rating_count' => $ratings->count(),
            ]);

            return $rating->load(['journey', 'fromUser', 'toUser']);
        });
    }

    /**
     * Get paginated ratings for a user (given or received).
     */
    public function getUserRatings(User $user, string $type = 'received', int $perPage = 15): LengthAwarePaginator
    {
        $query = Rating::with(['journey', 'fromUser', 'toUser']);

        if ($type === 'given') {
            $query->where('from_user_id', $user->id);
        } else {
            $query->where('to_user_id', $user->id);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
