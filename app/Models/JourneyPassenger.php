<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'journey_id',
        'passenger_id',
        'connected_at',
        'disconnected_at',
        'status',
        'share_details',
        'shared_fields',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'share_details' => 'boolean',
            'shared_fields' => 'array',
        ];
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }
}
