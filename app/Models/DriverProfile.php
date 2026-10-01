<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_number',
        'vehicle_type',
        'license_number',
        'rc_photo_url',
        'qr_code_token',
        'is_verified',
        'fcm_token',
        'total_trips',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'total_trips' => 'integer',
    ];

    protected $appends = [
        'rc_photo_full_url',
        'qr_image_url',
    ];

    public function getRcPhotoFullUrlAttribute(): ?string
    {
        return $this->rc_photo_url ? asset('storage/' . $this->rc_photo_url) : null;
    }

    public function getQrImageUrlAttribute(): ?string
    {
        if (!$this->qr_code_token) {
            return null;
        }

        $scanUrl = url('/ride/qr/' . $this->qr_code_token);

        return "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=" . urlencode($scanUrl);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rideSessions()
    {
        return $this->hasMany(RideSession::class, 'driver_profile_id');
    }

    public function lostClaims()
    {
        return $this->hasManyThrough(LostClaim::class, RideSession::class, 'driver_profile_id', 'ride_session_id');
    }
}
