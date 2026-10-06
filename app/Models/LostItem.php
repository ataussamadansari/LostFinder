<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LostItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'category',
        'name',
        'description',
        'brand',
        'model',
        'color',
        'estimated_value',
        'lost_at',
    ];

    protected static function booted(): void
    {
        static::creating(function ($item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'lost_at' => 'datetime',
        ];
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(LostItemTicket::class);
    }
}
