<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LostItemTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'journey_id',
        'passenger_id',
        'driver_id',
        'vehicle_id',
        'lost_item_id',
        'status',
        'reported_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LostItem::class, 'lost_item_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LostItemStatusHistory::class, 'ticket_id');
    }

    public function recovery(): HasOne
    {
        return $this->hasOne(ItemRecovery::class, 'ticket_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'ticket_id');
    }
}
