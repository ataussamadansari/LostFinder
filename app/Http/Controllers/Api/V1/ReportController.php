<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * File a new incident or fraud report.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', ReportService::ALLOWED_TYPES),
            'description' => 'required|string|min:10|max:3000',
            'reported_user_id' => 'nullable|integer|exists:users,id',
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'ticket_id' => 'nullable|integer|exists:lost_item_tickets,id',
            'evidence_media_ids' => 'nullable|array',
            'evidence_media_ids.*' => 'integer|exists:media,id',
        ]);

        try {
            $report = $this->reportService->createReport($request->user(), $validated);

            $evidence = $report->evidence->map(fn($e) => [
                'media_uuid' => $e->media?->uuid,
                'file_name' => $e->media?->original_name,
                'mime_type' => $e->media?->mime_type,
            ]);

            return $this->successResponse([
                'id' => $report->id,
                'type' => $report->type,
                'description' => $report->description,
                'status' => $report->status,
                'reported_user_id' => $report->reported_user_id,
                'evidence' => $evidence,
                'created_at' => $report->created_at?->toIso8601String(),
            ], 'Report submitted successfully. Our safety team will review it.', 201);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List user's submitted reports.
     */
    public function index(Request $request): JsonResponse
    {
        $reports = $this->reportService->getReports(
            $request->user(),
            [],
            (int) $request->query('per_page', 15)
        );

        $formatted = collect($reports->items())->map(fn($r) => [
            'id' => $r->id,
            'type' => $r->type,
            'description' => $r->description,
            'status' => $r->status,
            'resolved_at' => $r->resolved_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        return $this->successResponse([
            'reports' => $formatted,
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ], 'Reports retrieved successfully.');
    }

    /**
     * View single report details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $report = Report::where('id', $id)
            ->where('reporter_id', $request->user()->id)
            ->with(['evidence.media', 'reportedUser', 'vehicle', 'ticket'])
            ->firstOrFail();

        $evidence = $report->evidence->map(fn($e) => [
            'media_uuid' => $e->media?->uuid,
            'file_name' => $e->media?->original_name,
            'mime_type' => $e->media?->mime_type,
        ]);

        return $this->successResponse([
            'id' => $report->id,
            'type' => $report->type,
            'description' => $report->description,
            'status' => $report->status,
            'evidence' => $evidence,
            'resolved_at' => $report->resolved_at?->toIso8601String(),
            'created_at' => $report->created_at?->toIso8601String(),
        ], 'Report details retrieved successfully.');
    }
}
