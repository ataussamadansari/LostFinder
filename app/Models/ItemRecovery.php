<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRecovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'found_by',
        'found_at',
        'handover_method',
        'handover_at',
        'passenger_confirmed',
        'driver_confirmed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'found_at' => 'datetime',
            'handover_at' => 'datetime',
            'passenger_confirmed' => 'boolean',
            'driver_confirmed' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(LostItemTicket::class, 'ticket_id');
    }

    public function finder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'found_by');
    }

    public function isFullyConfirmed(): bool
    {
        return $this->passenger_confirmed && $this->driver_confirmed;
    }
}
