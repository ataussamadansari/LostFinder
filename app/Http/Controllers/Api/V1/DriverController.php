<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterDriverRequest;
use App\Http\Requests\Api\V1\UpdateClaimStatusRequest;
use App\Http\Requests\Api\V1\UpdateDriverProfileRequest;
use App\Http\Requests\Api\V1\VerifyHandoverOtpRequest;
use App\Models\DriverProfile;
use App\Models\LostClaim;
use App\Models\RideSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    /**
     * Helper to get authenticated driver profile.
     */
    private function getAuthenticatedDriver(Request $request): ?DriverProfile
    {
        $user = $request->user();
        return $user->driverProfile ?: DriverProfile::where('user_id', $user->id)->first();
    }

    /**
     * 1. Register Driver Profile & Generate QR Code
     */
    public function register(RegisterDriverRequest $request)
    {
        $user = $request->user();

        if ($user->driverProfile()->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Driver profile already exists.',
                'data'    => $user->driverProfile,
            ], 400);
        }

        $rcPhotoPath = null;
        if ($request->hasFile('rc_photo')) {
            $rcPhotoPath = $request->file('rc_photo')->store('drivers/rc', 'public');
        }

        $qrToken = 'DRV_' . Str::upper(Str::random(12));

        $driver = $user->driverProfile()->create([
            'vehicle_number' => strtoupper(trim($request->vehicle_number)),
            'vehicle_type'   => $request->vehicle_type,
            'license_number' => strtoupper(trim($request->license_number)),
            'rc_photo_url'   => $rcPhotoPath,
            'qr_code_token'  => $qrToken,
            'fcm_token'      => $request->fcm_token,
            'is_verified'    => false,
            'total_trips'    => 0,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Driver profile created successfully. QR generated.',
            'data'    => [
                'driver'       => $driver,
                'scan_url'     => url('/ride/qr/' . $driver->qr_code_token),
                'qr_image_url' => $driver->qr_image_url,
            ],
        ], 201);
    }

    /**
     * 2. View Driver Profile Details
     */
    public function profile(Request $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $driver->load('user:id,name,phone,email,profile_picture'),
        ]);
    }

    /**
     * 3. Update Driver Profile Details
     */
    public function updateProfile(UpdateDriverProfileRequest $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        if ($request->hasFile('rc_photo')) {
            if ($driver->rc_photo_url && Storage::disk('public')->exists($driver->rc_photo_url)) {
                Storage::disk('public')->delete($driver->rc_photo_url);
            }
            $driver->rc_photo_url = $request->file('rc_photo')->store('drivers/rc', 'public');
        }

        if ($request->filled('vehicle_number')) {
            $driver->vehicle_number = strtoupper(trim($request->vehicle_number));
        }

        if ($request->filled('vehicle_type')) {
            $driver->vehicle_type = $request->vehicle_type;
        }

        if ($request->filled('license_number')) {
            $driver->license_number = strtoupper(trim($request->license_number));
        }

        if ($request->has('fcm_token')) {
            $driver->fcm_token = $request->fcm_token;
        }

        $driver->save();

        if ($request->filled('name')) {
            $driver->user->update(['name' => $request->name]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Driver profile updated successfully',
            'data'    => $driver->fresh()->load('user:id,name,phone,email,profile_picture'),
        ]);
    }

    /**
     * 4. Get Driver QR Details
     */
    public function getQr(Request $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $scanUrl = url('/ride/qr/' . $driver->qr_code_token);

        return response()->json([
            'status' => true,
            'data'   => [
                'vehicle_number' => $driver->vehicle_number,
                'vehicle_type'   => $driver->vehicle_type,
                'qr_token'       => $driver->qr_code_token,
                'scan_url'       => $scanUrl,
                'qr_image_url'   => $driver->qr_image_url,
                'is_verified'    => (bool) $driver->is_verified,
            ],
        ]);
    }

    /**
     * 5. Regenerate Driver QR Code
     */
    public function regenerateQr(Request $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $driver->update([
            'qr_code_token' => 'DRV_' . Str::upper(Str::random(12)),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'New QR code generated successfully',
            'data'    => [
                'qr_token'     => $driver->qr_code_token,
                'scan_url'     => url('/ride/qr/' . $driver->qr_code_token),
                'qr_image_url' => $driver->qr_image_url,
            ],
        ]);
    }

    /**
     * 6. Masked Ride Logs for Driver
     */
    public function rideHistory(Request $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $query = $driver->rideSessions()
            ->with([
                'passenger:id,masked_alias',
                'lostClaims:id,ride_session_id,claim_status,item_category,bounty_amount,created_at'
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rides = $query->latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $rides,
        ]);
    }

    /**
     * 7. Show Single Ride Details
     */
    public function showRide(Request $request, int $id)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $ride = RideSession::with([
            'passenger:id,masked_alias',
            'lostClaims',
        ])
            ->where('driver_profile_id', $driver->id)
            ->find($id);

        if (!$ride) {
            return response()->json(['status' => false, 'message' => 'Ride session not found'], 404);
        }

        // Hide sensitive handover OTP from driver ride details
        $ride->lostClaims->makeHidden(['handover_otp']);

        return response()->json([
            'status' => true,
            'data'   => $ride,
        ]);
    }

    /**
     * 8. Lost Claims Feed for Driver's Rides
     */
    public function listClaims(Request $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $query = LostClaim::whereHas('rideSession', function ($q) use ($driver) {
            $q->where('driver_profile_id', $driver->id);
        })->with([
            'rideSession.passenger:id,masked_alias',
        ]);

        if ($request->filled('status')) {
            $query->where('claim_status', $request->status);
        }

        $claims = $query->latest()->paginate(15);

        // Security: Never reveal the secret handover OTP to driver in listing!
        $claims->getCollection()->transform(function ($claim) {
            return $claim->makeHidden(['handover_otp']);
        });

        return response()->json([
            'status' => true,
            'data'   => $claims,
        ]);
    }

    /**
     * 9. Show Single Claim Details
     */
    public function showClaim(Request $request, int $id)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $claim = LostClaim::whereHas('rideSession', function ($q) use ($driver) {
            $q->where('driver_profile_id', $driver->id);
        })
            ->with(['rideSession.passenger:id,masked_alias'])
            ->find($id);

        if (!$claim) {
            return response()->json(['status' => false, 'message' => 'Claim not found or not linked to your rides'], 404);
        }

        // Hide handover OTP
        $claim->makeHidden(['handover_otp']);

        return response()->json([
            'status' => true,
            'data'   => $claim,
        ]);
    }

    /**
     * 10. Update Claim Status (searching, found, disputed)
     */
    public function updateClaimStatus(UpdateClaimStatusRequest $request, int $id)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $claim = LostClaim::whereHas('rideSession', function ($q) use ($driver) {
            $q->where('driver_profile_id', $driver->id);
        })->find($id);

        if (!$claim) {
            return response()->json(['status' => false, 'message' => 'Claim not found'], 404);
        }

        if ($claim->claim_status === 'returned') {
            return response()->json([
                'status'  => false,
                'message' => 'Item is already returned and resolved.',
            ], 400);
        }

        $claim->update(['claim_status' => $request->claim_status]);

        $message = match ($request->claim_status) {
            'searching' => 'Status updated to searching. Please check your vehicle carefully.',
            'found'     => 'Great! Status updated to found. Please meet the passenger and verify the 6-digit OTP to complete handover.',
            'disputed'  => 'Status marked as disputed. Admin will investigate.',
            default     => 'Status updated successfully.',
        };

        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $claim->makeHidden(['handover_otp']),
        ]);
    }

    /**
     * 11. Verify Handover OTP & Complete Item Return
     */
    public function verifyHandover(VerifyHandoverOtpRequest $request, int $id)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $claim = LostClaim::whereHas('rideSession', function ($q) use ($driver) {
            $q->where('driver_profile_id', $driver->id);
        })->find($id);

        if (!$claim) {
            return response()->json(['status' => false, 'message' => 'Claim not found'], 404);
        }

        if ($claim->claim_status === 'returned') {
            return response()->json([
                'status'  => false,
                'message' => 'Item has already been handed over and resolved.',
            ], 400);
        }

        // Validate the secret OTP shared by the passenger
        if ($claim->handover_otp !== $request->handover_otp) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid handover OTP. Please verify the 6-digit code with the passenger.',
            ], 422);
        }

        $claim->update([
            'claim_status' => 'returned',
            'resolved_at'  => now(),
        ]);

        // Unflag ride session if no unresolved claims remain
        $unresolvedCount = $claim->rideSession->lostClaims()
            ->whereNotIn('claim_status', ['returned'])
            ->count();

        if ($unresolvedCount === 0) {
            $claim->rideSession->update(['status' => 'completed']);
        }

        // Notify passenger that their item was successfully handed over
        $passengerPhone = $claim->rideSession?->passenger?->user?->phone;
        if ($passengerPhone) {
            app(\App\Services\SmsService::class)->sendMessage(
                $passengerPhone,
                "Your lost {$claim->item_category} has been returned and verified! Thank you for using LostFinder."
            );
        }

        return response()->json([
            'status'  => true,
            'message' => 'Handover verified successfully! Item has been marked returned.',
            'data'    => [
                'claim_id'       => $claim->id,
                'claim_status'   => $claim->claim_status,
                'bounty_amount'  => $claim->bounty_amount,
                'resolved_at'    => $claim->resolved_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * 12. Update FCM Token
     */
    public function updateFcm(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);

        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $driver->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['status' => true, 'message' => 'FCM token updated successfully']);
    }

    /**
     * 13. Driver Dashboard Statistics
     */
    public function stats(Request $request)
    {
        $driver = $this->getAuthenticatedDriver($request);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Driver profile not found'], 404);
        }

        $totalTrips = (int) $driver->total_trips;
        $activeRides = $driver->rideSessions()->where('status', 'active')->count();
        $pendingClaims = LostClaim::whereHas('rideSession', fn ($q) => $q->where('driver_profile_id', $driver->id))
            ->whereIn('claim_status', ['reported', 'searching', 'found'])
            ->count();
        $returnedClaims = LostClaim::whereHas('rideSession', fn ($q) => $q->where('driver_profile_id', $driver->id))
            ->where('claim_status', 'returned')
            ->count();
        $totalBounties = LostClaim::whereHas('rideSession', fn ($q) => $q->where('driver_profile_id', $driver->id))
            ->where('claim_status', 'returned')
            ->sum('bounty_amount');

        return response()->json([
            'status' => true,
            'data'   => [
                'total_trips'           => $totalTrips,
                'active_rides'          => $activeRides,
                'pending_claims'        => $pendingClaims,
                'returned_claims'       => $returnedClaims,
                'total_bounties_earned' => (float) $totalBounties,
                'is_verified'           => (bool) $driver->is_verified,
            ],
        ]);
    }
}
