<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateClaimRequest;
use App\Models\LostClaim;
use App\Models\Passenger;
use App\Models\RideSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller
{
    /**
     * 1. List all claims filed by the tourist.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $passenger = $user->passenger ?: Passenger::where('user_id', $user->id)->first();

        if (!$passenger) {
            return response()->json([
                'status' => true,
                'data'   => [],
            ]);
        }

        $query = LostClaim::whereHas('rideSession', function ($q) use ($passenger) {
            $q->where('passenger_id', $passenger->id);
        })->with([
            'rideSession.driverProfile.user:id,name,phone,profile_picture',
        ]);

        if ($request->filled('status')) {
            $query->where('claim_status', $request->status);
        }

        $claims = $query->latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $claims,
        ]);
    }

    /**
     * 2. File a new lost item claim.
     */
    public function store(CreateClaimRequest $request)
    {
        $photoPath = null;
        if ($request->hasFile('item_photo')) {
            $photoPath = $request->file('item_photo')->store('claims', 'public');
        }

        // Generate 6-digit secret OTP to be exchanged at item handover
        $handoverOtp = (string) random_int(100000, 999999);

        $claim = LostClaim::create([
            'ride_session_id'  => $request->ride_session_id,
            'item_category'    => $request->item_category,
            'item_description' => $request->item_description,
            'item_photo_url'   => $photoPath,
            'handover_otp'     => $handoverOtp,
            'claim_status'     => 'reported',
            'bounty_amount'    => $request->bounty_amount ?? 0.00,
        ]);

        // Flag the ride session
        $claim->rideSession->update(['status' => 'flagged']);

        // Dispatch Push Notification to the driver
        if ($claim->rideSession?->driverProfile) {
            app(\App\Services\NotificationService::class)->sendToDriver(
                $claim->rideSession->driverProfile,
                'Lost Item Reported on Your Ride!',
                "A passenger reported a lost {$claim->item_category}. Please check your vehicle.",
                ['claim_id' => (string) $claim->id, 'type' => 'new_claim']
            );
        }

        return response()->json([
            'status'  => true,
            'message' => 'Claim filed successfully! Share the handover OTP with the driver only after receiving your item.',
            'data'    => $claim->load('rideSession.driverProfile.user:id,name,phone,profile_picture'),
        ], 201);
    }

    /**
     * 3. Show a specific claim details.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $passenger = $user->passenger;

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger record not found'], 404);
        }

        $claim = LostClaim::whereHas('rideSession', function ($q) use ($passenger) {
            $q->where('passenger_id', $passenger->id);
        })
            ->with(['rideSession.driverProfile.user:id,name,phone,profile_picture'])
            ->find($id);

        if (!$claim) {
            return response()->json([
                'status'  => false,
                'message' => 'Claim not found or unauthorized',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $claim,
        ]);
    }

    /**
     * 4. Cancel a reported claim.
     */
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();
        $passenger = $user->passenger;

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger record not found'], 404);
        }

        $claim = LostClaim::whereHas('rideSession', function ($q) use ($passenger) {
            $q->where('passenger_id', $passenger->id);
        })->find($id);

        if (!$claim) {
            return response()->json(['status' => false, 'message' => 'Claim not found'], 404);
        }

        if (!in_array($claim->claim_status, ['reported', 'searching'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Cannot cancel a claim that is already ' . $claim->claim_status,
            ], 400);
        }

        $rideSession = $claim->rideSession;

        if ($claim->item_photo_url && Storage::disk('public')->exists($claim->item_photo_url)) {
            Storage::disk('public')->delete($claim->item_photo_url);
        }

        $claim->delete();

        // If no other claims exist on this ride, unflag it
        if ($rideSession->lostClaims()->count() === 0) {
            $rideSession->update(['status' => 'completed']);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Claim cancelled successfully',
        ]);
    }
}
