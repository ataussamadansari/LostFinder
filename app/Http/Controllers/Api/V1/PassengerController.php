<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePassengerProfileRequest;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PassengerController extends Controller
{
    /**
     * Get tourist/passenger profile details.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $passenger = $user->passenger ?: Passenger::firstOrCreate(
            ['user_id' => $user->id],
            ['masked_alias' => 'Passenger #' . Str::upper(Str::random(5)), 'preferred_lang' => 'en']
        );

        return response()->json([
            'status' => true,
            'data'   => [
                'name'                    => $user->name,
                'phone'                   => $user->phone,
                'email'                   => $user->email,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'profile_picture_url'     => $user->profile_picture_url,
                'masked_alias'            => $passenger->masked_alias,
                'preferred_lang'          => $passenger->preferred_lang,
                'total_rides'             => $passenger->rideSessions()->count(),
                'total_claims'            => $passenger->lostClaims()->count(),
            ],
        ]);
    }

    /**
     * Update tourist/passenger profile and preferences.
     */
    public function updateProfile(UpdatePassengerProfileRequest $request)
    {
        $user = $request->user();
        $passenger = $user->passenger ?: Passenger::firstOrCreate(
            ['user_id' => $user->id],
            ['masked_alias' => 'Passenger #' . Str::upper(Str::random(5)), 'preferred_lang' => 'en']
        );

        if ($request->filled('masked_alias')) {
            $passenger->masked_alias = $request->masked_alias;
        }

        if ($request->filled('preferred_lang')) {
            $passenger->preferred_lang = $request->preferred_lang;
        }

        $passenger->save();

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->has('emergency_contact_phone')) {
            $user->emergency_contact_phone = $request->emergency_contact_phone;
        }

        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Tourist profile updated successfully',
            'data'    => [
                'name'                    => $user->name,
                'phone'                   => $user->phone,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'masked_alias'            => $passenger->masked_alias,
                'preferred_lang'          => $passenger->preferred_lang,
            ],
        ]);
    }
}
