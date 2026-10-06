<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\Vehicle;
use App\Services\QrCodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QrAdminController extends Controller
{
    use ApiResponse;

    public function __construct(protected QrCodeService $qrCodeService)
    {
    }

    /**
     * Generate a new pending QR code for a vehicle.
     */
    public function generate(Request $request, string $vehicleCode): JsonResponse
    {
        $vehicle = Vehicle::where('vehicle_code', $vehicleCode)->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found.', 404);
        }

        $qrCode = $this->qrCodeService->generate($vehicle, $request->user());

        return $this->successResponse([
            'id' => $qrCode->id,
            'token' => $qrCode->token,
            'version' => $qrCode->version,
            'status' => $qrCode->status,
            'vehicle_code' => $vehicle->vehicle_code,
            'download_url' => url("/api/v1/admin/qr/{$qrCode->id}/download"),
        ], 'QR code generated successfully.', 201);
    }

    /**
     * Activate a pending or disabled QR code.
     * Enforces the Single-Active Invariant: revokes any existing active QRs.
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::with('vehicle')->find($id);

        if (!$qrCode) {
            return $this->errorResponse('QR code not found.', 404);
        }

        try {
            $activatedQr = $this->qrCodeService->activate($qrCode, $request->user());
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'id' => $activatedQr->id,
            'token' => $activatedQr->token,
            'version' => $activatedQr->version,
            'status' => $activatedQr->status,
            'activated_at' => $activatedQr->activated_at?->toIso8601String(),
        ], 'QR code activated successfully.');
    }

    /**
     * Temporarily disable an active QR code.
     */
    public function disable(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::find($id);

        if (!$qrCode) {
            return $this->errorResponse('QR code not found.', 404);
        }

        try {
            $disabledQr = $this->qrCodeService->disable(
                $qrCode,
                $request->user(),
                $request->input('reason')
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'id' => $disabledQr->id,
            'token' => $disabledQr->token,
            'version' => $disabledQr->version,
            'status' => $disabledQr->status,
        ], 'QR code disabled successfully.');
    }

    /**
     * Permanently revoke a QR code.
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::find($id);

        if (!$qrCode) {
            return $this->errorResponse('QR code not found.', 404);
        }

        $revokedQr = $this->qrCodeService->revoke(
            $qrCode,
            $request->user(),
            $request->input('reason')
        );

        return $this->successResponse([
            'id' => $revokedQr->id,
            'token' => $revokedQr->token,
            'version' => $revokedQr->version,
            'status' => $revokedQr->status,
            'revoked_at' => $revokedQr->revoked_at?->toIso8601String(),
        ], 'QR code revoked successfully.');
    }

    /**
     * Download the printable vector SVG sticker asset.
     */
    public function downloadSticker(int $id): Response
    {
        $qrCode = QrCode::with('vehicle')->find($id);

        if (!$qrCode) {
            return response()->json([
                'success' => false,
                'message' => 'QR code not found.',
            ], 404);
        }

        $svgContent = $this->qrCodeService->getStickerSvg($qrCode);
        $filename = "qr-sticker-{$qrCode->vehicle->vehicle_code}-v{$qrCode->version}.svg";

        return response($svgContent, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * View scan telemetry logs for a target QR code.
     */
    public function scanLogs(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::find($id);

        if (!$qrCode) {
            return $this->errorResponse('QR code not found.', 404);
        }

        $logs = $qrCode->scanLogs()
            ->latest('scanned_at')
            ->paginate(20)
            ->through(function ($log) {
                return [
                    'id' => $log->id,
                    'qr_code_id' => $log->qr_code_id,
                    'user_id' => $log->user_id,
                    'vehicle_id' => $log->vehicle_id,
                    'journey_id' => $log->journey_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'scanned_at' => $log->scanned_at?->toIso8601String(),
                ];
            });

        return $this->successResponse($logs, 'Scan logs retrieved successfully.');
    }
}
