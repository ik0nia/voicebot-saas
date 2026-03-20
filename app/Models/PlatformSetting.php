<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('platform_settings');
        });

        static::deleted(function () {
            Cache::forget('platform_settings');
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('platform_settings', 3600, function () {
            return static::all()->pluck('value', 'key')->toArray();
        });

        $value = $settings[$key] ?? $default;

        // Find type for casting
        if (isset($settings[$key])) {
            $setting = static::where('key', $key)->first();
            if ($setting) {
                return self::castValue($value, $setting->type);
            }
        }

        return $value;
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type, 'group' => $group]
        );
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }
}
