<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'masked_alias',
        'preferred_lang',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rideSessions()
    {
        return $this->hasMany(RideSession::class, 'passenger_id');
    }

    public function lostClaims()
    {
        return $this->hasManyThrough(LostClaim::class, RideSession::class, 'passenger_id', 'ride_session_id');
    }
}
