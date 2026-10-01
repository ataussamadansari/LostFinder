<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_profile_id',
        'passenger_id',
        'scan_latitude',
        'scan_longitude',
        'status'
    ];

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class, 'driver_profile_id');
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class, 'passenger_id');
    }

    public function lostClaims()
    {
        return $this->hasMany(LostClaim::class, 'ride_session_id');
    }
}
