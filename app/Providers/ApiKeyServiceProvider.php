<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use Illuminate\Support\ServiceProvider;

/**
 * Override API keys from platform_settings (database) over .env values.
 *
 * This ensures that API keys configured in the admin dashboard take
 * precedence over .env file values. Critical for Docker deployments
 * where .env may have placeholders but real keys are in the database.
 *
 * Priority: platform_settings (DB) > .env > default
 */
class ApiKeyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Only override if the database is available (skip during migrations, etc.)
        try {
            $this->overrideApiKeys();
        } catch (\Throwable $e) {
            // Database not available yet (migration, fresh install) — skip silently
        }
    }

    private function overrideApiKeys(): void
    {
        $keyMap = [
            'openai_api_key' => 'openai.api_key',
            'openai_organization' => 'openai.organization',
            'anthropic_api_key' => 'services.anthropic.api_key',
            'elevenlabs_api_key' => 'services.elevenlabs.api_key',
            'twilio_sid' => 'services.twilio.sid',
            'twilio_auth_token' => 'services.twilio.token',
            'twilio_phone_number' => 'services.twilio.from',
        ];

        foreach ($keyMap as $settingKey => $configKey) {
            $dbValue = PlatformSetting::get($settingKey);

            if ($dbValue && $dbValue !== '' && !str_starts_with($dbValue, 'sk-your-')) {
                config([$configKey => $dbValue]);
            }
        }
    }
}
