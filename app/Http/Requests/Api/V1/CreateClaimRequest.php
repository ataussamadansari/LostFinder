<?php

namespace App\Http\Requests\Api\V1;

use App\Models\RideSession;
use Illuminate\Foundation\Http\FormRequest;

class CreateClaimRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rideSessionId = $this->input('ride_session_id');
        if (!$rideSessionId) {
            return true;
        }

        $passenger = $this->user()?->passenger;
        if (!$passenger) {
            return false;
        }

        return RideSession::where('id', $rideSessionId)
            ->where('passenger_id', $passenger->id)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ride_session_id'  => 'required|integer|exists:ride_sessions,id',
            'item_category'    => 'required|string|max:50',
            'item_description' => 'required|string|min:5|max:1000',
            'item_photo'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'bounty_amount'    => 'nullable|numeric|min:0|max:100000',
        ];
    }

    public function messages(): array
    {
        return [
            'ride_session_id.required' => 'Ride session ID is required.',
            'ride_session_id.exists'   => 'Specified ride session not found.',
            'item_category.required'   => 'Item category is required (e.g. Phone, Bag, Wallet, Keys).',
            'item_description.required'=> 'Please provide a clear description of the lost item.',
            'item_description.min'     => 'Item description must be at least 5 characters.',
            'item_photo.image'         => 'Item photo must be a valid image file.',
            'item_photo.max'           => 'Item photo cannot exceed 5MB.',
        ];
    }
}
