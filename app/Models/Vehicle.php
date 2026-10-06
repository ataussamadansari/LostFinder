<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'vehicle_code',
        'registration_number',
        'vehicle_type',
        'make',
        'model',
        'color',
        'verification_status',
        'verified_at',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function ($vehicle) {
            if (empty($vehicle->uuid)) {
                $vehicle->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(VehicleDriverAssignment::class)->where('status', 'active');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function activeQrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)->where('status', 'active');
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
