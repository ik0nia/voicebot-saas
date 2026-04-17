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

        $user = DB::transaction(function () use ($validated, $tenantName) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => Str::slug($tenantName),
                'plan' => 'starter',
                'trial_ends_at' => now()->addDays(7),
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
