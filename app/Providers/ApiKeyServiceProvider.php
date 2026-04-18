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

        // Belt + suspenders: if the DB or cache happened to be cold at
        // container boot (common during a fresh deploy — the app
        // container comes up before Redis / Postgres have fully
        // recovered), the initial boot above caches empty and leaves
        // config pointing at env placeholders for this worker's whole
        // lifetime. Re-attempt the override lazily on the first HTTP
        // request and before every queued job — both hooks are zero
        // cost once config already holds the real keys (placeholder
        // probe short-circuits).
        $this->app->make('events')->listen(
            \Illuminate\Foundation\Http\Events\RequestHandled::class,
            fn () => $this->refreshIfStale(),
        );
        \Illuminate\Support\Facades\Queue::before(fn () => $this->refreshIfStale());
    }

    private function refreshIfStale(): void
    {
        $probe = (string) config('openai.api_key', '');
        if ($probe !== '' && !$this->isPlaceholder($probe)) {
            return;
        }
        try {
            $this->overrideFromSettings();
        } catch (\Throwable) {
            // Still cold — try again next request / next job.
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

            // Stripe currency (mode-independent)
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

        $this->applyStripeMode();
    }

    /**
     * Stripe has two key sets (live + test). The active set is selected
     * by the `stripe_mode` PlatformSetting (live | test, default live).
     * If the requested mode lacks keys, fall back to the other set so
     * the app never boots with stripe config in a broken half-state.
     */
    private function applyStripeMode(): void
    {
        $mode = PlatformSetting::get('stripe_mode', 'live');
        if (! in_array($mode, ['live', 'test'], true)) {
            $mode = 'live';
        }

        $modes = [
            'live' => [
                'public' => 'stripe_public_key',
                'secret' => 'stripe_secret_key',
                'webhook' => 'stripe_webhook_secret',
            ],
            'test' => [
                'public' => 'stripe_test_public_key',
                'secret' => 'stripe_test_secret_key',
                'webhook' => 'stripe_test_webhook_secret',
            ],
        ];

        foreach ([$mode, $mode === 'live' ? 'test' : 'live'] as $candidate) {
            $public = PlatformSetting::get($modes[$candidate]['public']);
            $secret = PlatformSetting::get($modes[$candidate]['secret']);
            $webhook = PlatformSetting::get($modes[$candidate]['webhook']);

            if (! $secret || $this->isPlaceholder((string) $secret)) {
                continue;
            }

            config([
                'cashier.key' => $public,
                'services.stripe.key' => $public,
                'cashier.secret' => $secret,
                'services.stripe.secret' => $secret,
                'stripe.api_key' => $secret,
                'cashier.webhook.secret' => $webhook,
                'services.stripe.webhook.secret' => $webhook,
                'cashier.active_mode' => $candidate,
            ]);
            return;
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
