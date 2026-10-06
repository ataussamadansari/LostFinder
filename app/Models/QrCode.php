<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'token',
        'version',
        'status',
        'activated_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'revoked_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(QrScanLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
