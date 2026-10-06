<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'is_public',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Get the parsed value based on type and encryption.
     */
    public function getParsedValue(): mixed
    {
        $raw = $this->value;

        if ($raw === null) {
            return null;
        }

        if ($this->is_encrypted) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Throwable) {
                return '[Encrypted]';
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'float' => (float) $raw,
            'json' => is_string($raw) ? json_decode($raw, true) : $raw,
            default => (string) $raw,
        };
    }

    /**
     * Set the formatted value based on type and encryption.
     */
    public function setFormattedValue(mixed $value): void
    {
        if ($value === null) {
            $this->value = null;
            return;
        }

        $raw = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_array($value) ? json_encode($value) : (string) $value,
            default => (string) $value,
        };

        if ($this->is_encrypted) {
            $raw = Crypt::encryptString($raw);
        }

        $this->value = $raw;
    }

    /**
     * Scope for public settings.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for specific setting group.
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
