<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_code',
        'verification_status',
        'verified_at',
        'rating_avg',
        'rating_count',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(VehicleDriverAssignment::class)->where('status', 'active');
    }

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }
}
