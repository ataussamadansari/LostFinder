<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ScanRideRequest;
use App\Models\DriverProfile;
use App\Models\Passenger;
use App\Models\RideSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RideController extends Controller
{
    /**
     * 1. Public Info: QR scan preview showing driver and vehicle details.
     */
    public function getDriverByQr(string $qrToken)
    {
        $driver = DriverProfile::with('user:id,name,profile_picture')
            ->where('qr_code_token', $qrToken)
            ->first();

        if (!$driver) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid or expired QR code',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'driver_name'         => $driver->user->name ?? 'Verified Driver',
                'vehicle_number'      => $driver->vehicle_number,
                'vehicle_type'        => $driver->vehicle_type,
                'is_verified'         => (bool) $driver->is_verified,
                'profile_picture_url' => $driver->user->profile_picture_url ?? null,
                'total_trips'         => (int) $driver->total_trips,
                'qr_token'            => $driver->qr_code_token,
            ],
        ]);
    }

    /**
     * 2. Log Ride: Tourist scans QR code to record ride session.
     */
    public function scanRide(ScanRideRequest $request)
    {
        $user = $request->user();

        $passenger = $user->passenger ?: Passenger::where('user_id', $user->id)->first();
        if (!$passenger) {
            $passenger = $user->passenger()->create([
                'masked_alias'   => 'Passenger #' . Str::upper(Str::random(5)),
                'preferred_lang' => 'en',
            ]);
        }

        $driver = DriverProfile::where('qr_code_token', $request->qr_token)->firstOrFail();

        $rideSession = RideSession::create([
            'driver_profile_id' => $driver->id,
            'passenger_id'      => $passenger->id,
            'scan_latitude'     => $request->scan_latitude,
            'scan_longitude'    => $request->scan_longitude,
            'status'            => 'active',
        ]);

        $driver->increment('total_trips');

        return response()->json([
            'status'  => true,
            'message' => 'Ride logged successfully! Details saved to your account.',
            'data'    => [
                'ride_session_id' => $rideSession->id,
                'vehicle_number'  => $driver->vehicle_number,
                'vehicle_type'    => $driver->vehicle_type,
                'driver_name'     => $driver->user->name ?? 'Driver',
                'scan_latitude'   => $rideSession->scan_latitude,
                'scan_longitude'  => $rideSession->scan_longitude,
                'status'          => $rideSession->status,
                'logged_at'       => $rideSession->created_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * 3. Tourist Past Rides History (Paginated + filterable by status).
     */
    public function touristRides(Request $request)
    {
        $user = $request->user();
        $passenger = $user->passenger ?: Passenger::where('user_id', $user->id)->first();

        if (!$passenger) {
            return response()->json([
                'status' => true,
                'data'   => [],
            ]);
        }

        $query = $passenger->rideSessions()
            ->with([
                'driverProfile.user:id,name,phone,profile_picture',
                'lostClaims:id,ride_session_id,item_category,claim_status,handover_otp,bounty_amount,created_at'
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
     * 4. Tourist Single Ride Details.
     */
    public function showRide(Request $request, int $id)
    {
        $user = $request->user();
        $passenger = $user->passenger;

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger record not found'], 404);
        }

        $ride = RideSession::with([
            'driverProfile.user:id,name,phone,profile_picture',
            'lostClaims',
        ])
            ->where('passenger_id', $passenger->id)
            ->find($id);

        if (!$ride) {
            return response()->json([
                'status'  => false,
                'message' => 'Ride session not found or does not belong to you',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $ride,
        ]);
    }

    /**
     * 5. Complete / End Ride Session.
     */
    public function completeRide(Request $request, int $id)
    {
        $user = $request->user();
        $passenger = $user->passenger;

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger record not found'], 404);
        }

        $ride = RideSession::where('passenger_id', $passenger->id)->find($id);

        if (!$ride) {
            return response()->json([
                'status'  => false,
                'message' => 'Ride session not found',
            ], 404);
        }

        if ($ride->status === 'completed') {
            return response()->json([
                'status'  => false,
                'message' => 'Ride session is already marked completed',
            ], 400);
        }

        $ride->update(['status' => 'completed']);

        return response()->json([
            'status'  => true,
            'message' => 'Ride completed successfully',
            'data'    => $ride,
        ]);
    }
}
