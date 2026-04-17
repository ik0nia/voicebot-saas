<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'website' => ['required', 'url', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Numele este obligatoriu.',
            'email.required' => 'Adresa de email este obligatorie.',
            'email.email' => 'Adresa de email nu este validă.',
            'email.unique' => 'Această adresă de email este deja înregistrată.',
            'website.required' => 'Adresa site-ului este obligatorie.',
            'website.url' => 'Adresa site-ului nu este validă (ex: https://exemplu.ro).',
            'password.required' => 'Parola este obligatorie.',
            'password.min' => 'Parola trebuie să aibă cel puțin 8 caractere.',
            'password.confirmed' => 'Confirmarea parolei nu se potrivește.',
        ]);

        // Extract domain name for tenant
        $domain = parse_url($validated['website'], PHP_URL_HOST) ?: $validated['website'];
        $tenantName = preg_replace('/^www\./', '', $domain);

        // Niche-demo attribution (Iteration F): if the visitor touched a
        // demo widget before signing up, the landing page set a
        // sambla_demo_session cookie. We match it against any
        // demo_qualified event in the last 30 days and, if found, stamp
        // the originating niche onto the tenant's settings. This powers
        // the "demo → signup" funnel panel in the admin dashboard.
        $demoSessionId = $request->cookie('sambla_demo_session');
        $attributedNiche = null;
        if ($demoSessionId) {
            $attributedNiche = \App\Models\ChatEvent::query()
                ->where('session_id', $demoSessionId)
                ->where('event_name', \App\Services\EventTaxonomy::DEMO_QUALIFIED)
                ->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at')
                ->value('properties->niche_slug');
            // Fall back to demo_viewed attribution when the visitor didn't
            // hit the 3-message qualification bar.
            if (!$attributedNiche) {
                $attributedNiche = \App\Models\ChatEvent::query()
                    ->where('session_id', $demoSessionId)
                    ->where('event_name', \App\Services\EventTaxonomy::DEMO_VIEWED)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->orderByDesc('created_at')
                    ->value('properties->niche_slug');
            }
        }

        $user = DB::transaction(function () use ($validated, $tenantName, $demoSessionId, $attributedNiche) {
            // 1. Create Tenant
            $tenantSettings = [];
            if ($attributedNiche) {
                $tenantSettings['demo_qualified_from_niche'] = $attributedNiche;
                $tenantSettings['demo_session_id'] = $demoSessionId;
                $tenantSettings['demo_attributed_at'] = now()->toIso8601String();
            }

            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => Str::slug($tenantName),
                'plan' => 'starter',
                'trial_ends_at' => now()->addDays(7),
                'settings' => $tenantSettings,
            ]);

            // 2. Create User
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenant->id,
            ]);

            // 3. Assign role
            $user->assignRole('tenant_admin');

            return $user;
        });

        // 4. Fire Registered event (triggers SendWelcomeEmail listener +
        //    Laravel's built-in MustVerifyEmail notification so the user
        //    receives a signed verification link on their email).
        event(new Registered($user));

        // 5. Login the user
        Auth::login($user);

        // Analytics: flash sign_up + tenant_created so the next page's
        // dataLayer picks them up. GA4 maps to the sign_up recommended
        // event; Meta + Google Ads use Lead / CompleteRegistration.
        app(\App\Services\Analytics\AnalyticsTracker::class)->flash('sign_up', [
            'method' => 'email',
            'tenant_id' => $user->tenant_id,
        ]);
        app(\App\Services\Analytics\AnalyticsTracker::class)->flash('tenant_created', [
            'tenant_id' => $user->tenant_id,
        ]);

        // 6. Redirect to setup wizard. New niche-driven wizard
        // is gated behind a platform_setting so the legacy flow
        // stays the default until we're confident in the rollout.
        $useV2 = (bool) \App\Models\PlatformSetting::get('onboarding_v2_enabled', false);
        return redirect($useV2 ? '/dashboard/setup-wow' : '/dashboard/setup');
    }
}
