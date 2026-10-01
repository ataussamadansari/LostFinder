<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    /**
     * Get a setting value with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('app_setting_' . $key, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'json'    => json_decode($setting->value, true) ?? $default,
                default   => $setting->value,
            };
        });
    }

    /**
     * Set/Update a setting value and clear cache.
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $description = null): static
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => $stringValue,
                'group'       => $group,
                'type'        => $type,
                'description' => $description,
            ]
        );

        Cache::forget('app_setting_' . $key);

        return $setting;
    }

    /**
     * Helper for boolean settings.
     */
    public static function getBoolean(string $key, bool $default = false): bool
    {
        return (bool) static::get($key, $default);
    }

    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget('app_setting_' . $setting->key);
        });

        static::deleted(function ($setting) {
            Cache::forget('app_setting_' . $setting->key);
        });
    }
}
