<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_url',
        'action_type',
        'action_target',
        'placement',
        'priority',
        'is_active',
        'clicks',
        'impressions',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'priority'    => 'integer',
        'clicks'      => 'integer',
        'impressions' => 'integer',
        'starts_at'   => 'datetime',
        'expires_at'  => 'datetime',
    ];

    protected $appends = [
        'image_full_url',
    ];

    public function getImageFullUrlAttribute(): ?string
    {
        if (!$this->image_url) {
            return null;
        }

        if (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://')) {
            return $this->image_url;
        }

        return asset('storage/' . $this->image_url);
    }

    /**
     * Scope for active promotions within valid date range.
     */
    public function scopeActive(Builder $query, ?string $placement = null): Builder
    {
        $now = now();

        $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });

        if ($placement) {
            $query->where('placement', $placement);
        }

        return $query->orderByDesc('priority')->latest();
    }
}
