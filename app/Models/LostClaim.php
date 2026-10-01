<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_session_id',
        'item_category',
        'item_description',
        'item_photo_url',
        'handover_otp',
        'claim_status',
        'bounty_amount',
        'resolved_at',
    ];

    protected $casts = [
        'bounty_amount' => 'decimal:2',
        'resolved_at'   => 'datetime',
    ];

    protected $appends = [
        'item_photo_full_url',
    ];

    public function getItemPhotoFullUrlAttribute(): ?string
    {
        return $this->item_photo_url ? asset('storage/' . $this->item_photo_url) : null;
    }

    public function rideSession()
    {
        return $this->belongsTo(RideSession::class, 'ride_session_id');
    }
}
