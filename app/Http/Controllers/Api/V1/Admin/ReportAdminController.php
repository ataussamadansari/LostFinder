<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportAdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * List all reports with status and type filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'reported_user_id']);
        $reports = $this->reportService->getReports(
            $request->user(),
            $filters,
            (int) $request->query('per_page', 20)
        );

        $formatted = collect($reports->items())->map(fn($r) => [
            'id' => $r->id,
            'type' => $r->type,
            'description' => $r->description,
            'status' => $r->status,
            'reporter' => [
                'id' => $r->reporter?->id,
                'name' => $r->reporter?->name,
                'phone' => $r->reporter?->phone,
            ],
            'reported_user' => $r->reportedUser ? [
                'id' => $r->reportedUser->id,
                'name' => $r->reportedUser->name,
                'phone' => $r->reportedUser->phone,
            ] : null,
            'vehicle_id' => $r->vehicle_id,
            'ticket_id' => $r->ticket_id,
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
        ], 'Admin reports retrieved successfully.');
    }

    /**
     * View report details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $report = Report::with(['reporter', 'reportedUser', 'vehicle', 'ticket', 'resolver', 'evidence.media'])
            ->findOrFail($id);

        $evidence = $report->evidence->map(fn($e) => [
            'media_uuid' => $e->media?->uuid,
            'file_name' => $e->media?->original_name,
            'mime_type' => $e->media?->mime_type,
            'size' => $e->media?->size,
        ]);

        return $this->successResponse([
            'id' => $report->id,
            'type' => $report->type,
            'description' => $report->description,
            'status' => $report->status,
            'reporter' => [
                'id' => $report->reporter?->id,
                'name' => $report->reporter?->name,
                'phone' => $report->reporter?->phone,
            ],
            'reported_user' => $report->reportedUser ? [
                'id' => $report->reportedUser->id,
                'name' => $report->reportedUser->name,
                'phone' => $report->reportedUser->phone,
            ] : null,
            'vehicle' => $report->vehicle ? [
                'id' => $report->vehicle->id,
                'plate' => $report->vehicle->license_plate,
            ] : null,
            'ticket_id' => $report->ticket_id,
            'evidence' => $evidence,
            'resolver' => $report->resolver ? [
                'id' => $report->resolver->id,
                'name' => $report->resolver->name,
            ] : null,
            'resolved_at' => $report->resolved_at?->toIso8601String(),
            'created_at' => $report->created_at?->toIso8601String(),
        ], 'Report details retrieved successfully.');
    }

    /**
     * Resolve or reject an incident report.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', ReportService::ALLOWED_STATUSES),
            'notes' => 'nullable|string|max:1000',
        ]);

        $report = Report::findOrFail($id);

        try {
            $updated = $this->reportService->resolveReport(
                $report,
                $request->user(),
                $validated['status'],
                $validated['notes'] ?? null
            );

            return $this->successResponse([
                'id' => $updated->id,
                'status' => $updated->status,
                'resolved_by' => $updated->resolved_by,
                'resolved_at' => $updated->resolved_at?->toIso8601String(),
            ], 'Report updated successfully.');
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
