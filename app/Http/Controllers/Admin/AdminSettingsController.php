<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'general');

        // pluck() bypasses the model accessor and would expose encrypted
        // ciphertext to the view. Fetch models so the value accessor
        // decrypts secrets in memory, then mask sensitive fields below.
        $settings = [];
        $secretSuffixes = ['_secret_key', '_api_key', '_webhook_secret', '_password', '_secret', '_token'];
        foreach (PlatformSetting::all() as $row) {
            $isSecret = false;
            foreach ($secretSuffixes as $suffix) {
                if (str_ends_with($row->key, $suffix)) {
                    $isSecret = true;
                    break;
                }
            }
            $value = $isSecret ? '' : $row->value;
            $settings[$row->group][$row->key] = $value;
            // Tell the view whether a saved value exists, without leaking it.
            $settings[$row->group][$row->key . '__present'] = ! empty($row->getRawOriginal('value'));
        }

        // Extra data for specific tabs
        $extra = [];

        if ($tab === 'planuri') {
            $extra['plans'] = config('plans');
        }

        if ($tab === 'mentenanta') {
            $extra['systemInfo'] = $this->getSystemInfo();
        }

        if ($tab === 'tenanti') {
            $extra['tenants'] = Tenant::withCount(['users', 'bots', 'calls'])
                ->latest()
                ->get();
        }

        return view('admin.settings', compact('tab', 'settings', 'extra'));
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => 'required|string|max:255',
            'platform_url' => 'required|url|max:255',
            'support_email' => 'required|email|max:255',
            'default_timezone' => 'required|string',
            'default_language' => 'required|string|in:ro,en',
            'maintenance_mode' => 'nullable',
            'registration_enabled' => 'nullable',
        ]);

        PlatformSetting::set('platform_name', $validated['platform_name'], 'string', 'general');
        PlatformSetting::set('platform_url', $validated['platform_url'], 'string', 'general');
        PlatformSetting::set('support_email', $validated['support_email'], 'string', 'general');
        PlatformSetting::set('default_timezone', $validated['default_timezone'], 'string', 'general');
        PlatformSetting::set('default_language', $validated['default_language'], 'string', 'general');
        PlatformSetting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0', 'boolean', 'general');
        PlatformSetting::set('registration_enabled', $request->boolean('registration_enabled') ? '1' : '0', 'boolean', 'general');

        return back()->with('success', 'Setările generale au fost actualizate.');
    }

    public function updateOpenai(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => 'required|string',
            'openai_organization' => 'nullable|string|max:255',
            'openai_realtime_model' => 'required|string|max:255',
            'openai_max_tokens' => 'required|integer|min:256|max:32768',
            'openai_temperature' => 'required|numeric|min:0|max:2',
        ]);

        foreach ($validated as $key => $value) {
            $type = in_array($key, ['openai_max_tokens']) ? 'integer' : (in_array($key, ['openai_temperature']) ? 'float' : 'string');
            PlatformSetting::set($key, $value ?? '', $type, 'openai');
        }

        return back()->with('success', 'Setările OpenAI au fost actualizate.');
    }

    public function updateTelnyx(Request $request)
    {
        $validated = $request->validate([
            'telnyx_api_key' => 'required|string|max:255',
            'telnyx_connection_id' => 'required|string|max:255',
            'telnyx_public_key' => 'required|string|max:255',
            'telnyx_webhook_url' => 'required|url|max:255',
            'phone_number_monthly_cost_lei' => 'required|numeric|min:0',
        ]);

        $costLei = $validated['phone_number_monthly_cost_lei'];
        unset($validated['phone_number_monthly_cost_lei']);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value, 'string', 'telnyx');
        }

        PlatformSetting::set('phone_number_monthly_cost_lei', $costLei, 'float', 'telnyx');

        return back()->with('success', 'Setările Telnyx au fost actualizate.');
    }

    public function updateTwilio(Request $request)
    {
        $validated = $request->validate([
            'twilio_account_sid' => 'required|string|max:255',
            // auth_token is optional on update — the form shows "••• (saved)"
            // placeholder when a value already exists, so an empty submission
            // means "keep what's there" rather than "clear it".
            'twilio_auth_token' => 'nullable|string|max:255',
            'twilio_twiml_app_sid' => 'nullable|string|max:255',
            'twilio_webhook_url' => 'nullable|url|max:255',
            // Regulatory Bundle + Address (RO) — shared by all subaccounts
            // per Twilio's subaccount regulatory inheritance.
            'twilio_ro_bundle_sid' => 'nullable|string|starts_with:BU|max:64',
            'twilio_ro_address_sid' => 'nullable|string|starts_with:AD|max:64',
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'twilio_auth_token' && ($value === null || $value === '')) {
                continue; // preserve existing token when form submitted blank
            }
            PlatformSetting::set($key, $value ?? '', 'string', 'twilio');
        }

        return back()->with('success', 'Setările Twilio au fost actualizate.');
    }

    public function updateStripe(Request $request)
    {
        $validated = $request->validate([
            'stripe_mode' => 'required|string|in:live,test',
            'stripe_currency' => 'required|string|in:eur,usd,ron,gbp',
            'stripe_public_key' => 'nullable|string',
            'stripe_secret_key' => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',
            'stripe_test_public_key' => 'nullable|string',
            'stripe_test_secret_key' => 'nullable|string',
            'stripe_test_webhook_secret' => 'nullable|string',
        ]);

        $mode = $validated['stripe_mode'];
        $required = $mode === 'test'
            ? ['stripe_test_public_key', 'stripe_test_secret_key', 'stripe_test_webhook_secret']
            : ['stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret'];

        foreach ($required as $field) {
            $existing = PlatformSetting::get($field);
            if (empty($validated[$field]) && empty($existing)) {
                return back()->withErrors([
                    $field => "Câmpul este obligatoriu pentru modul {$mode}.",
                ])->withInput();
            }
        }

        // Always persist mode + currency. For secrets, only persist if user
        // actually typed something new (empty submit means "keep existing").
        PlatformSetting::set('stripe_mode', $validated['stripe_mode'], 'string', 'stripe');
        PlatformSetting::set('stripe_currency', $validated['stripe_currency'], 'string', 'stripe');

        $secretFields = [
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret',
            'stripe_test_public_key', 'stripe_test_secret_key', 'stripe_test_webhook_secret',
        ];
        foreach ($secretFields as $field) {
            $value = $validated[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            PlatformSetting::set($field, $value, 'string', 'stripe');
        }

        return back()->with('success', "Setările Stripe au fost actualizate (mod: {$mode}).");
    }

    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|string|in:smtp,sendmail,mailgun,ses,postmark,resend,log',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'required|string|in:tls,ssl,none',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            $type = $key === 'mail_port' ? 'integer' : 'string';
            PlatformSetting::set($key, $value ?? '', $type, 'email');
        }

        return back()->with('success', 'Setările de email au fost actualizate.');
    }

    public function updateWhatsapp(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_provider' => 'required|string|in:telnyx_whatsapp,meta_cloud_api',
            'whatsapp_api_key' => 'required|string',
            'whatsapp_phone_number_id' => 'nullable|string|max:255',
            'whatsapp_business_account_id' => 'nullable|string|max:255',
            'whatsapp_verify_token' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value ?? '', 'string', 'whatsapp');
        }

        return back()->with('success', 'Setările WhatsApp au fost actualizate.');
    }

    public function updateFacebook(Request $request)
    {
        $validated = $request->validate([
            'facebook_app_id' => 'nullable|string|max:255',
            'facebook_app_secret' => 'nullable|string',
            'facebook_page_access_token' => 'nullable|string',
            'facebook_verify_token' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value ?? '', 'string', 'facebook');
        }

        return back()->with('success', 'Setările Facebook au fost actualizate.');
    }

    public function updateInstagram(Request $request)
    {
        $validated = $request->validate([
            'instagram_app_id' => 'nullable|string|max:255',
            'instagram_app_secret' => 'nullable|string',
            'instagram_access_token' => 'nullable|string',
            'instagram_verify_token' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value ?? '', 'string', 'instagram');
        }

        return back()->with('success', 'Setările Instagram au fost actualizate.');
    }

    public function updateElevenlabs(Request $request)
    {
        $validated = $request->validate([
            'elevenlabs_api_key' => 'required|string',
            'elevenlabs_model_id' => 'required|string|max:255',
            'elevenlabs_stability' => 'required|numeric|min:0|max:1',
            'elevenlabs_similarity_boost' => 'required|numeric|min:0|max:1',
        ]);

        PlatformSetting::set('elevenlabs_api_key', $validated['elevenlabs_api_key'], 'string', 'elevenlabs');
        PlatformSetting::set('elevenlabs_model_id', $validated['elevenlabs_model_id'], 'string', 'elevenlabs');
        PlatformSetting::set('elevenlabs_stability', $validated['elevenlabs_stability'], 'float', 'elevenlabs');
        PlatformSetting::set('elevenlabs_similarity_boost', $validated['elevenlabs_similarity_boost'], 'float', 'elevenlabs');

        return back()->with('success', 'Setarile ElevenLabs au fost actualizate.');
    }

    public function updateAnthropic(Request $request)
    {
        $validated = $request->validate([
            'anthropic_api_key' => 'required|string',
        ]);

        PlatformSetting::set('anthropic_api_key', $validated['anthropic_api_key'], 'string', 'anthropic');

        // Also sync to .env for config() access
        $this->updateEnvKey('ANTHROPIC_API_KEY', $validated['anthropic_api_key']);

        return back()->with('success', 'Setările Anthropic au fost actualizate.');
    }

    public function updateSentry(Request $request)
    {
        $validated = $request->validate([
            'sentry_dsn' => 'nullable|string|max:500',
        ]);

        PlatformSetting::set('sentry_dsn', $validated['sentry_dsn'] ?? '', 'string', 'sentry');

        // Sync to .env
        $this->updateEnvKey('SENTRY_LARAVEL_DSN', $validated['sentry_dsn'] ?? '');

        return back()->with('success', 'Setările Sentry au fost actualizate.');
    }

    /**
     * Marketing + analytics IDs (GTM / GA4 / Google Ads / Meta / sGTM).
     * Everything lives in PlatformSetting + gets read at runtime by
     * AnalyticsConfig — changes reflect on the next page load without
     * a deploy. Secrets (CAPI tokens, GA4 API secrets) go through the
     * encrypted PlatformSetting accessor.
     */
    public function updateMarketing(Request $request)
    {
        $validated = $request->validate([
            'gtm_container_id'            => 'nullable|string|max:32',
            'ga4_measurement_id'          => 'nullable|string|max:32',
            'ga4_api_secret'              => 'nullable|string|max:100',
            'google_ads_conversion_id'    => 'nullable|string|max:32',
            'meta_pixel_id'               => 'nullable|string|max:32',
            'meta_capi_access_token'      => 'nullable|string|max:300',
            'meta_test_event_code'        => 'nullable|string|max:40',
            'sgtm_url'                    => 'nullable|url|max:200',
            'analytics_enabled'           => 'nullable|boolean',
            'cookie_consent_enabled'      => 'nullable|boolean',
        ]);

        $plain = ['gtm_container_id','ga4_measurement_id','google_ads_conversion_id','meta_pixel_id','sgtm_url','meta_test_event_code'];
        $secrets = ['ga4_api_secret','meta_capi_access_token'];
        $bools = ['analytics_enabled','cookie_consent_enabled'];

        foreach ($plain as $key) {
            PlatformSetting::set($key, $validated[$key] ?? '', 'string', 'marketing');
        }
        foreach ($secrets as $key) {
            // Only overwrite secrets if the operator actually typed a
            // new value — an empty input means "leave the saved secret
            // alone" (matches the masked-secret UX elsewhere).
            if (!empty($validated[$key])) {
                PlatformSetting::set($key, $validated[$key], 'string', 'marketing');
            }
        }
        foreach ($bools as $key) {
            PlatformSetting::set($key, $validated[$key] ?? false ? '1' : '0', 'boolean', 'marketing');
        }

        return back()->with('success', 'Setările Marketing & Analytics au fost actualizate.');
    }

    /**
     * Sync a key to .env if writable, otherwise just log.
     * Platform settings (DB) is the primary source — .env is optional sync.
     */
    private function updateEnvKey(string $key, string $value): void
    {
        $envPath = base_path('.env');

        try {
            if (!is_writable($envPath)) {
                \Log::info("Cannot sync {$key} to .env (not writable). Value saved in platform_settings only.");
                return;
            }

            $content = file_get_contents($envPath);

            if (str_contains($content, "{$key}=")) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }

            file_put_contents($envPath, $content);
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            \Log::warning("Failed to sync {$key} to .env: {$e->getMessage()}. Value saved in platform_settings.");
        }
    }

    public function updateSecurity(Request $request)
    {
        $validated = $request->validate([
            'bcrypt_rounds' => 'required|integer|min:4|max:31',
            'session_lifetime' => 'required|integer|min:5|max:1440',
            'api_rate_limit' => 'required|integer|min:10|max:1000',
            'max_login_attempts' => 'required|integer|min:3|max:20',
            'password_min_length' => 'required|integer|min:6|max:32',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value, 'integer', 'security');
        }

        return back()->with('success', 'Setările de securitate au fost actualizate.');
    }

    public function clearCache()
    {
        Cache::flush();
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'Cache-ul a fost șters cu succes.');
    }

    public function updateTenant(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'required|string|in:starter,professional,enterprise',
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Tenantul "' . $tenant->name . '" a fost actualizat.');
    }

    public function toggleTenant(Tenant $tenant)
    {
        $settings = $tenant->settings ?? [];
        $settings['suspended'] = !($settings['suspended'] ?? false);
        $tenant->update(['settings' => $settings]);

        $status = $settings['suspended'] ? 'suspendat' : 'reactivat';
        return back()->with('success', 'Tenantul "' . $tenant->name . '" a fost ' . $status . '.');
    }

    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_os' => php_uname('s') . ' ' . php_uname('r'),
            'database' => DB::connection()->getDriverName() . ' ' . DB::selectOne('SELECT version() as v')->v ?? '',
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'disk_free' => round(disk_free_space('/') / 1073741824, 2) . ' GB',
            'disk_total' => round(disk_total_space('/') / 1073741824, 2) . ' GB',
            'memory_usage' => round(memory_get_usage(true) / 1048576, 2) . ' MB',
            'uptime' => trim(shell_exec('uptime -p') ?? 'N/A'),
            'total_tenants' => Tenant::count(),
            'total_users' => User::count(),
            'total_bots' => Bot::withoutGlobalScopes()->count(),
            'total_calls' => Call::withoutGlobalScopes()->count(),
        ];
    }
}
