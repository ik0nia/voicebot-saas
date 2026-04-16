<?php

namespace App\Support;

/**
 * Centralized list of Sanctum token abilities (scopes) available to
 * user-generated API tokens.
 *
 * Single source of truth consumed by:
 *   - Dashboard\SettingsController::generateApiKey — validates the
 *     scope list a user tries to mint.
 *   - routes/api.php — attaches `abilities:{scope}` middleware per
 *     route so the token's stored abilities are actually checked on
 *     every request, not just at creation.
 *
 * Legacy tokens minted before iter 1 may carry `*` (wildcard).
 * Sanctum's `abilities:` middleware treats `*` as "all abilities", so
 * those keep working. New tokens are capped to this list.
 */
final class ApiScopes
{
    public const BOTS_READ          = 'bots:read';
    public const BOTS_WRITE         = 'bots:write';
    public const CALLS_READ         = 'calls:read';
    public const CALLS_WRITE        = 'calls:write';
    public const CONVERSATIONS_READ = 'conversations:read';
    public const PHONE_NUMBERS_READ = 'phone-numbers:read';
    public const PHONE_NUMBERS_WRITE = 'phone-numbers:write';
    public const ANALYTICS_READ     = 'analytics:read';
    public const INTEGRATIONS_READ  = 'integrations:read';
    public const INTEGRATIONS_WRITE = 'integrations:write';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::BOTS_READ,
            self::BOTS_WRITE,
            self::CALLS_READ,
            self::CALLS_WRITE,
            self::CONVERSATIONS_READ,
            self::PHONE_NUMBERS_READ,
            self::PHONE_NUMBERS_WRITE,
            self::ANALYTICS_READ,
            self::INTEGRATIONS_READ,
            self::INTEGRATIONS_WRITE,
        ];
    }
}
