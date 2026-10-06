<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RatingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RatingService $ratingService
    ) {}

    /**
     * Submit a rating for a journey driver.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'journey_uuid' => 'nullable|uuid',
            'journey_id' => 'nullable|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['journey_uuid']) && empty($validated['journey_id'])) {
            return $this->errorResponse('Either journey_uuid or journey_id is required.', 422);
        }

        try {
            $rating = $this->ratingService->submitRating($request->user(), $validated);

            return $this->successResponse([
                'id' => $rating->id,
                'journey_id' => $rating->journey_id,
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at?->toIso8601String(),
            ], 'Rating submitted successfully.', 201);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Get list of ratings (received or given).
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type', 'received');
        $ratings = $this->ratingService->getUserRatings(
            $request->user(),
            $type,
            (int) $request->query('per_page', 15)
        );

        $formatted = collect($ratings->items())->map(fn($r) => [
            'id' => $r->id,
            'journey_id' => $r->journey_id,
            'from_user' => [
                'id' => $r->fromUser?->id,
                'name' => $r->fromUser?->name,
            ],
            'rating' => $r->rating,
            'comment' => $r->comment,
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        return $this->successResponse([
            'ratings' => $formatted,
            'pagination' => [
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total(),
            ],
        ], 'Ratings retrieved successfully.');
    }
}
