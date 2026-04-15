<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use Illuminate\Support\ServiceProvider;

/**
 * Override config values from platform_settings (DB) over .env.
 *
 * Priority: platform_settings (DB) > .env > default.
 * Sensitive values (api keys, secrets, passwords) are stored encrypted
 * in the DB and decrypted on read by the PlatformSetting model.
 */
class ApiKeyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $this->overrideFromSettings();
        } catch (\Throwable $e) {
            // DB unavailable (migrations, fresh install) — silent skip
        }
    }

    private function overrideFromSettings(): void
    {
        $keyMap = [
            // OpenAI / LLM
            'openai_api_key' => ['openai.api_key'],
            'openai_organization' => ['openai.organization'],
            'anthropic_api_key' => ['services.anthropic.api_key'],
            'elevenlabs_api_key' => ['services.elevenlabs.api_key'],

            // Telnyx
            'telnyx_api_key' => ['services.telnyx.api_key'],
            'telnyx_connection_id' => ['services.telnyx.connection_id'],
            'telnyx_public_key' => ['services.telnyx.public_key'],

            // Stripe / Cashier
            'stripe_public_key' => ['cashier.key', 'services.stripe.key'],
            'stripe_secret_key' => ['cashier.secret', 'services.stripe.secret', 'stripe.api_key'],
            'stripe_webhook_secret' => ['cashier.webhook.secret', 'services.stripe.webhook.secret'],
            'stripe_currency' => ['cashier.currency'],

            // Mail
            'mail_host' => ['mail.mailers.smtp.host'],
            'mail_port' => ['mail.mailers.smtp.port'],
            'mail_username' => ['mail.mailers.smtp.username'],
            'mail_password' => ['mail.mailers.smtp.password'],
            'mail_encryption' => ['mail.mailers.smtp.encryption'],
            'mail_from_address' => ['mail.from.address'],
            'mail_from_name' => ['mail.from.name'],
        ];

        foreach ($keyMap as $settingKey => $configKeys) {
            $value = PlatformSetting::get($settingKey);

            if ($value === null || $value === '' || $this->isPlaceholder((string) $value)) {
                continue;
            }

            foreach ($configKeys as $configKey) {
                config([$configKey => $value]);
            }
        }
    }

    private function isPlaceholder(string $value): bool
    {
        return str_starts_with($value, 'sk-your-')
            || str_starts_with($value, 'sk_live_your')
            || str_starts_with($value, 'pk_live_your')
            || str_starts_with($value, 'whsec_your')
            || str_contains($value, 'your-');
    }
}
