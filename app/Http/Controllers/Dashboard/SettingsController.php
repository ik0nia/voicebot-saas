<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'profile');
        $tenant = auth()->user()->tenant;

        return view('dashboard.settings.index', compact('tab', 'tenant'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'timezone' => 'required|string',
        ]);

        $user->update($validated);
        return back()->with('success', 'Profilul a fost actualizat.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Parola a fost schimbată.');
    }

    public function updateCompany(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'settings.website' => 'nullable|url',
            'settings.industry' => 'nullable|string',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'settings' => array_merge($tenant->settings ?? [], $validated['settings'] ?? []),
        ]);

        return back()->with('success', 'Datele companiei au fost actualizate.');
    }

    public function updateNotifications(Request $request)
    {
        $user = auth()->user();
        $user->update([
            'notification_preferences' => [
                'call_failed' => $request->boolean('call_failed'),
                'usage_80' => $request->boolean('usage_80'),
                'usage_100' => $request->boolean('usage_100'),
                'invoice_issued' => $request->boolean('invoice_issued'),
                'weekly_report' => $request->boolean('weekly_report'),
                'each_call_completed' => $request->boolean('each_call_completed'),
            ],
        ]);

        return back()->with('success', 'Preferințele de notificare au fost salvate.');
    }

    public function generateApiKey(Request $request)
    {
        $user = auth()->user();
        // Use Sanctum to create API token
        $token = $user->createToken(
            $request->get('name', 'API Key'),
            $request->get('scopes', ['*'])
        );

        return back()->with('success', 'Cheie API creată: ' . $token->plainTextToken);
    }

    public function revokeApiKey(Request $request, $tokenId)
    {
        auth()->user()->tokens()->where('id', $tokenId)->delete();
        return back()->with('success', 'Cheia API a fost revocată.');
    }

    public function destroyAccount(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:STERGE',
        ]);

        $tenant = auth()->user()->tenant;
        auth()->logout();
        $tenant->delete(); // Cascade deletes users, bots, etc.

        return redirect('/')->with('success', 'Contul a fost șters.');
    }
}
