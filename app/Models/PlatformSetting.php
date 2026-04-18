<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PlatformSetting extends Model
{
    private const SENSITIVE_SUFFIXES = [
        '_secret_key',
        '_api_key',
        '_webhook_secret',
        '_password',
        '_secret',
        '_token',
    ];

    protected $fillable = ['key', 'value', 'type', 'group', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

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

    public function getValueAttribute(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return $stored;
        }
        if (! ($this->attributes['is_encrypted'] ?? false)) {
            return $stored;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            Log::warning('PlatformSetting decrypt failed', ['key' => $this->key]);
            return null;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::cachedSettings();

        if (! isset($settings[$key])) {
            return $default;
        }

        return self::castValue($settings[$key]['value'], $settings[$key]['type']);
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $raw = is_array($value) ? json_encode($value) : (string) $value;
        $shouldEncrypt = self::isSensitiveKey($key) && $raw !== '';
        $stored = $shouldEncrypt ? Crypt::encryptString($raw) : $raw;

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'is_encrypted' => $shouldEncrypt,
            ]
        );
    }

    public static function getGroup(string $group): array
    {
        $settings = self::cachedSettings();
        $out = [];
        foreach ($settings as $key => $row) {
            if ($row['group'] === $group) {
                $out[$key] = self::castValue($row['value'], $row['type']);
            }
        }
        return $out;
    }

    private static function cachedSettings(): array
    {
        // A cache entry with an empty result has bitten us twice:
        // the first successful warm-up after a deploy (or after any
        // manual `Cache::flush`) populated platform_settings=[] when
        // the DB wasn't yet reachable or the transaction was still
        // settling, and we then served that empty map for the full
        // 5-min TTL. Downstream ApiKeyServiceProvider saw every
        // secret as "absent" and silently let env placeholders win
        // — i.e. the OpenAI client got sk-your-openai-key and every
        // stream / embedding job 401'd for minutes. Treat a zero-row
        // query as a transient error and re-query on next call
        // rather than caching it.
        $cached = Cache::get('platform_settings');
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $out = [];
        foreach (static::query()->get() as $row) {
            $out[$row->key] = [
                'value' => $row->value, // accessor decrypts
                'type' => $row->type,
                'group' => $row->group,
            ];
        }

        if ($out !== []) {
            Cache::put('platform_settings', $out, 300);
        }

        return $out;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_SUFFIXES as $suffix) {
            if (str_ends_with($key, $suffix)) {
                return true;
            }
        }
        return false;
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
