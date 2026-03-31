<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
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
            'company_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Numele este obligatoriu.',
            'email.required' => 'Adresa de email este obligatorie.',
            'email.email' => 'Adresa de email nu este validă.',
            'email.unique' => 'Această adresă de email este deja înregistrată.',
            'company_name.required' => 'Numele companiei este obligatoriu.',
            'password.required' => 'Parola este obligatorie.',
            'password.min' => 'Parola trebuie să aibă cel puțin 8 caractere.',
            'password.confirmed' => 'Confirmarea parolei nu se potrivește.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'slug' => Str::slug($validated['company_name']),
                'plan' => 'starter',
                'trial_ends_at' => now()->addDays(14),
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

        // 4. Login the user
        Auth::login($user);

        // 5. Redirect to setup wizard (new users) or dashboard (existing)
        return redirect('/dashboard/setup');
    }
}
