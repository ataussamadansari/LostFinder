<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrPublicController extends Controller
{
    use ApiResponse;

    public function __construct(protected QrCodeService $qrCodeService)
    {
    }

    /**
     * Resolve a public QR code token.
     * Rate limited to prevent token brute-forcing (throttle:30,1).
     * Strictly delivers zero-PII metadata for verified vehicles.
     */
    public function resolve(Request $request, string $token): JsonResponse
    {
        $payload = $this->qrCodeService->resolveToken($token, $request);

        if (!$payload) {
            return $this->errorResponse('This LostFinder QR is unavailable.', 404);
        }

        return $this->successResponse($payload, 'QR code resolved successfully.');
    }

    /**
     * Web landing resolver for camera scanners or universal links.
     */
    public function showWeb(Request $request, string $token): \Illuminate\Contracts\View\View|JsonResponse
    {
        $payload = $this->qrCodeService->resolveToken($token, $request);

        if ($request->wantsJson()) {
            if (!$payload) {
                return $this->errorResponse('This LostFinder QR is unavailable.', 404);
            }

            return $this->successResponse($payload, 'QR code resolved successfully.');
        }

        return view('qr.scan', [
            'payload' => $payload,
            'token' => $token,
        ]);
    }
}
