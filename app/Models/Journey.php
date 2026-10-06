<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Journey extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'vehicle_id',
        'driver_id',
        'started_at',
        'ended_at',
        'expires_at',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function ($journey) {
            if (empty($journey->uuid)) {
                $journey->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'journey_passengers', 'journey_id', 'passenger_id')
            ->withPivot('connected_at', 'disconnected_at', 'status')
            ->withTimestamps();
    }

    public function journeyPassengers(): HasMany
    {
        return $this->hasMany(JourneyPassenger::class);
    }

    public function lostItemTickets(): HasMany
    {
        return $this->hasMany(LostItemTicket::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->expires_at ? $this->expires_at->isFuture() : true);
    }
}
