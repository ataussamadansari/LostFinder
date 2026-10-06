<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Media;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const ALLOWED_TYPES = [
        'misconduct',
        'fraud',
        'harassment',
        'fake_item',
        'vehicle_condition',
        'other',
    ];

    public const ALLOWED_STATUSES = [
        'open',
        'investigating',
        'resolved',
        'rejected',
    ];

    /**
     * File a new safety or fraud report.
     */
    public function createReport(User $reporter, array $data): Report
    {
        $type = strtolower($data['type'] ?? '');
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \DomainException("Invalid report type '{$type}'. Allowed types: " . implode(', ', self::ALLOWED_TYPES));
        }

        $description = trim($data['description'] ?? '');
        if (strlen($description) < 10) {
            throw new \DomainException('Description must be at least 10 characters long.');
        }

        // Verify media evidence ownership if provided
        $mediaIds = $data['evidence_media_ids'] ?? [];
        if (!empty($mediaIds)) {
            $validCount = Media::whereIn('id', $mediaIds)
                ->where('uploaded_by', $reporter->id)
                ->count();

            if ($validCount !== count($mediaIds)) {
                throw new \DomainException('One or more evidence media files do not belong to you or do not exist.');
            }
        }

        return DB::transaction(function () use ($reporter, $data, $type, $description, $mediaIds) {
            $report = Report::create([
                'reporter_id' => $reporter->id,
                'reported_user_id' => $data['reported_user_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'ticket_id' => $data['ticket_id'] ?? null,
                'type' => $type,
                'description' => $description,
                'status' => 'open',
            ]);

            foreach ($mediaIds as $mediaId) {
                ReportEvidence::create([
                    'report_id' => $report->id,
                    'media_id' => $mediaId,
                    'created_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $reporter->id,
                'action' => 'report.created',
                'auditable_type' => Report::class,
                'auditable_id' => $report->id,
                'old_values' => null,
                'new_values' => [
                    'type' => $type,
                    'reported_user_id' => $data['reported_user_id'] ?? null,
                ],
                'created_at' => now(),
            ]);

            return $report->load(['reporter', 'reportedUser', 'vehicle', 'ticket', 'evidence.media']);
        });
    }

    /**
     * Query reports with pagination and role-aware filtering.
     */
    public function getReports(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Report::with(['reporter', 'reportedUser', 'vehicle', 'ticket', 'resolver', 'evidence.media']);

        // Non-staff only see reports they submitted
        if (!$user->isAdmin() && !$user->hasPermission('reports.view')) {
            $query->where('reporter_id', $user->id);
        } else {
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (!empty($filters['reported_user_id'])) {
                $query->where('reported_user_id', $filters['reported_user_id']);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Staff resolution of an incident report.
     */
    public function resolveReport(Report $report, User $staff, string $status, ?string $resolutionNotes = null): Report
    {
        if (!$staff->isAdmin() && !$staff->hasPermission('reports.resolve')) {
            throw new \DomainException('Unauthorized. You do not have permission to resolve reports.');
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \DomainException("Invalid status '{$status}'. Allowed: " . implode(', ', self::ALLOWED_STATUSES));
        }

        return DB::transaction(function () use ($report, $staff, $status, $resolutionNotes) {
            $oldStatus = $report->status;

            $report->update([
                'status' => $status,
                'resolved_by' => $staff->id,
                'resolved_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $staff->id,
                'action' => 'report.resolved',
                'auditable_type' => Report::class,
                'auditable_id' => $report->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => $status,
                    'notes' => $resolutionNotes,
                ],
                'created_at' => now(),
            ]);

            return $report->load(['reporter', 'reportedUser', 'vehicle', 'ticket', 'resolver', 'evidence.media']);
        });
    }
}
