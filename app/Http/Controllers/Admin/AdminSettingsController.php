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

        $settings = PlatformSetting::all()->groupBy('group')->map(function ($group) {
            return $group->pluck('value', 'key')->toArray();
        })->toArray();

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

        return view('dashboard.admin-settings', compact('tab', 'settings', 'extra'));
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

    public function updateTwilio(Request $request)
    {
        $validated = $request->validate([
            'twilio_sid' => 'required|string|max:255',
            'twilio_auth_token' => 'required|string|max:255',
            'twilio_phone_number' => 'required|string|max:20',
            'twilio_webhook_url' => 'required|url|max:255',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value, 'string', 'twilio');
        }

        return back()->with('success', 'Setările Twilio au fost actualizate.');
    }

    public function updateStripe(Request $request)
    {
        $validated = $request->validate([
            'stripe_public_key' => 'required|string',
            'stripe_secret_key' => 'required|string',
            'stripe_webhook_secret' => 'required|string',
            'stripe_currency' => 'required|string|in:eur,usd,ron,gbp',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value, 'string', 'stripe');
        }

        return back()->with('success', 'Setările Stripe au fost actualizate.');
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
