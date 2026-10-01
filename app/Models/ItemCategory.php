<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'suggested_bounty',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'suggested_bounty' => 'decimal:2',
        'priority'         => 'integer',
        'is_active'        => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('priority')->orderBy('name');
    }
}
