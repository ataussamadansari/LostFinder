<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\QrCode;
use App\Models\QrScanLog;
use App\Models\User;
use App\Models\Vehicle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate a new pending QR code record and printable SVG sticker for a vehicle.
     */
    public function generate(Vehicle $vehicle, User $requester): QrCode
    {
        return DB::transaction(function () use ($vehicle, $requester) {
            $token = Str::random(32);
            $maxVersion = (int) $vehicle->qrCodes()->max('version');
            $version = $maxVersion + 1;

            $qrCode = QrCode::create([
                'vehicle_id' => $vehicle->id,
                'token' => $token,
                'version' => $version,
                'status' => 'pending',
            ]);

            // Render and store the SVG sticker
            $svgContent = $this->renderStickerSvg($qrCode);
            $storagePath = $this->getStickerStoragePath($qrCode);
            Storage::disk('public')->put($storagePath, $svgContent);

            // Audit log
            AuditLog::create([
                'user_id' => $requester->id,
                'action' => 'qr.generated',
                'auditable_type' => QrCode::class,
                'auditable_id' => $qrCode->id,
                'new_values' => [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_code' => $vehicle->vehicle_code,
                    'token' => $token,
                    'version' => $version,
                    'status' => 'pending',
                ],
            ]);

            return $qrCode;
        });
    }

    /**
     * Render a raw vector SVG string for a given payload URL.
     */
    public function renderRawSvg(string $url, int $size = 240): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    /**
     * Render a complete branded, print-ready SVG sticker for a vehicle QR code.
     */
    public function renderStickerSvg(QrCode $qrCode): string
    {
        $vehicle = $qrCode->vehicle;
        $url = config('app.url') . '/q/' . $qrCode->token;
        $rawSvg = $this->renderRawSvg($url, 240);

        // Extract inner elements of the raw QR SVG
        $innerSvg = '';
        if (preg_match('/<svg[^>]*>(.*)<\/svg>/is', $rawSvg, $matches)) {
            $innerSvg = trim($matches[1]);
        } else {
            $innerSvg = $rawSvg;
        }

        $regNumber = htmlspecialchars($vehicle->registration_number ?? 'UNREGISTERED', ENT_XML1);
        $makeModel = htmlspecialchars(trim("{$vehicle->make} {$vehicle->model}"), ENT_XML1);
        $vehicleCode = htmlspecialchars($vehicle->vehicle_code ?? '', ENT_XML1);
        $versionLabel = "v{$qrCode->version}";

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="360" height="500" viewBox="0 0 360 500">
  <defs>
    <style>
      .font-brand { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    </style>
  </defs>

  <!-- Background Card -->
  <rect x="0" y="0" width="360" height="500" rx="20" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="2"/>

  <!-- Top Blue Accent Bar -->
  <path d="M 0 20 Q 0 0 20 0 L 340 0 Q 360 0 360 20 L 360 8 L 0 8 Z" fill="#2563EB" />

  <!-- Brand Typography -->
  <text class="font-brand" x="180" y="42" text-anchor="middle" fill="#2563EB" font-size="13" font-weight="800" letter-spacing="3">LOSTFINDER</text>
  <text class="font-brand" x="180" y="68" text-anchor="middle" fill="#0B0F17" font-size="20" font-weight="900">LOST SOMETHING?</text>
  <text class="font-brand" x="180" y="88" text-anchor="middle" fill="#64748B" font-size="12">Scan QR to connect with your ride</text>

  <!-- QR Code Matrix -->
  <g transform="translate(60, 105)">
    {$innerSvg}
  </g>

  <!-- Verified Badge Pill -->
  <rect x="100" y="360" width="160" height="26" rx="13" fill="#EFF6FF" stroke="#2563EB" stroke-width="1.5" />
  <text class="font-brand" x="180" y="377" text-anchor="middle" fill="#2563EB" font-size="11" font-weight="700">✓ VERIFIED VEHICLE</text>

  <!-- Vehicle Identification -->
  <text class="font-brand" x="180" y="412" text-anchor="middle" fill="#0B0F17" font-size="17" font-weight="800">{$regNumber}</text>
  <text class="font-brand" x="180" y="432" text-anchor="middle" fill="#64748B" font-size="12">{$makeModel} • {$vehicleCode}</text>

  <!-- Footer Link & Version -->
  <text class="font-brand" x="180" y="468" text-anchor="middle" fill="#94A3B8" font-size="10">lostfinder.in • {$versionLabel}</text>
</svg>
SVG;
    }

    /**
     * Activate a pending or disabled QR code.
     * Enforces the Single-Active Invariant: any previously active QR for the vehicle is revoked.
     */
    public function activate(QrCode $qrCode, User $admin): QrCode
    {
        $vehicle = $qrCode->vehicle;

        if (!$vehicle->isVerified() || $vehicle->status !== 'active') {
            throw new \DomainException('Cannot activate QR code for an unverified or inactive vehicle.');
        }

        return DB::transaction(function () use ($qrCode, $vehicle, $admin) {
            // Revoke any currently active QR codes for this vehicle
            $activeQrs = QrCode::where('vehicle_id', $vehicle->id)
                ->where('status', 'active')
                ->where('id', '!=', $qrCode->id)
                ->get();

            foreach ($activeQrs as $activeQr) {
                $activeQr->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                ]);

                AuditLog::create([
                    'user_id' => $admin->id,
                    'action' => 'qr.auto_revoked',
                    'auditable_type' => QrCode::class,
                    'auditable_id' => $activeQr->id,
                    'old_values' => ['status' => 'active'],
                    'new_values' => ['status' => 'revoked', 'reason' => "Superceded by QR v{$qrCode->version}"],
                ]);
            }

            $oldStatus = $qrCode->status;
            $qrCode->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'qr.activated',
                'auditable_type' => QrCode::class,
                'auditable_id' => $qrCode->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'active', 'activated_at' => $qrCode->activated_at->toIso8601String()],
            ]);

            return $qrCode->fresh();
        });
    }

    /**
     * Disable an active QR code (temporary suspension).
     */
    public function disable(QrCode $qrCode, User $admin, ?string $reason = null): QrCode
    {
        if ($qrCode->status === 'revoked') {
            throw new \DomainException('Cannot disable a revoked QR code.');
        }

        $oldStatus = $qrCode->status;
        $qrCode->update([
            'status' => 'disabled',
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'qr.disabled',
            'auditable_type' => QrCode::class,
            'auditable_id' => $qrCode->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'disabled', 'reason' => $reason],
        ]);

        return $qrCode->fresh();
    }

    /**
     * Permanently revoke a QR code.
     */
    public function revoke(QrCode $qrCode, User $admin, ?string $reason = null): QrCode
    {
        if ($qrCode->status === 'revoked') {
            return $qrCode;
        }

        $oldStatus = $qrCode->status;
        $qrCode->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'qr.revoked',
            'auditable_type' => QrCode::class,
            'auditable_id' => $qrCode->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'revoked', 'reason' => $reason],
        ]);

        return $qrCode->fresh();
    }

    /**
     * Resolve a scanned QR token with privacy sanitization and telemetry logging.
     *
     * @return array<string, mixed>|null Returns sanitized payload array or null if invalid/unavailable.
     */
    public function resolveToken(string $token, Request $request): ?array
    {
        $qrCode = QrCode::with(['vehicle.activeAssignment.driver.user'])
            ->where('token', $token)
            ->first();

        if (!$qrCode || $qrCode->status !== 'active') {
            return null;
        }

        $vehicle = $qrCode->vehicle;
        if (!$vehicle || $vehicle->status !== 'active' || !$vehicle->isVerified()) {
            return null;
        }

        $activeAssignment = $vehicle->activeAssignment;
        $driver = $activeAssignment?->driver;
        $isDriverActiveAndVerified = $driver && $driver->isVerified() && $driver->user?->status === 'active';
        $isJourneyAvailable = (bool) $isDriverActiveAndVerified;

        // Log scan telemetry
        QrScanLog::create([
            'qr_code_id' => $qrCode->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $request->user()?->id,
            'journey_id' => $vehicle->journeys()->where('status', 'in_progress')->first()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'scanned_at' => now(),
        ]);

        // Zero-PII sanitized payload
        return [
            'token' => $qrCode->token,
            'qr_status' => $qrCode->status,
            'vehicle' => [
                'vehicle_code' => $vehicle->vehicle_code,
                'registration_number' => $vehicle->registration_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'is_verified' => $vehicle->isVerified(),
            ],
            'driver' => $driver ? [
                'driver_code' => $driver->driver_code,
                'rating_avg' => (float) $driver->rating_avg,
                'is_verified' => $driver->isVerified(),
            ] : null,
            'is_journey_available' => $isJourneyAvailable,
        ];
    }

    /**
     * Storage path for the public SVG sticker.
     */
    public function getStickerStoragePath(QrCode $qrCode): string
    {
        $vehicleCode = $qrCode->vehicle->vehicle_code;
        return "qr/vehicles/{$vehicleCode}/qr-v{$qrCode->version}.svg";
    }

    /**
     * Retrieve or regenerate the SVG sticker content.
     */
    public function getStickerSvg(QrCode $qrCode): string
    {
        $storagePath = $this->getStickerStoragePath($qrCode);
        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->get($storagePath);
        }

        $svgContent = $this->renderStickerSvg($qrCode);
        Storage::disk('public')->put($storagePath, $svgContent);

        return $svgContent;
    }
}
