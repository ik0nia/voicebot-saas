<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GoogleOAuthToken;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleOAuthController extends Controller
{
    public function __construct(
        private GoogleOAuthService $oauth,
        private GoogleDriveService $drive,
    ) {}

    /**
     * Step 1: redirect the user to Google's consent screen.
     * `?scope=calendar` cere și scope-ul de calendar (folosit din UI booking).
     * Fără param: default Drive (KB).
     */
    public function connect(Request $request)
    {
        $state = $this->oauth->generateState();
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_return_to', $request->query('return_to', '/dashboard'));

        // Scope extra opțional — UI booking trimite ?scope=calendar pentru a
        // adăuga acces la Google Calendar (events read/write).
        $scopes = $this->oauth->defaultScopes();
        if ($request->query('scope') === 'calendar') {
            $scopes[] = GoogleOAuthService::SCOPE_CALENDAR_EVENTS;
            $scopes[] = GoogleOAuthService::SCOPE_CALENDAR_READONLY;
        }

        return redirect()->away($this->oauth->buildAuthUrl($state, array_values(array_unique($scopes))));
    }

    /**
     * Step 2: Google redirects here with ?code=...&state=...
     */
    public function callback(Request $request)
    {
        $sessionState = $request->session()->pull('google_oauth_state');
        $returnTo = $request->session()->pull('google_oauth_return_to', '/dashboard');

        if ($request->filled('error')) {
            return redirect($returnTo)->with('error', 'Conectarea Google a fost anulată: ' . $request->query('error'));
        }

        if (! $request->filled('code') || ! $request->filled('state')) {
            return redirect($returnTo)->with('error', 'Răspuns invalid de la Google (lipsește code sau state).');
        }

        if (! hash_equals((string) $sessionState, (string) $request->query('state'))) {
            return redirect($returnTo)->with('error', 'State invalid — încearcă din nou.');
        }

        $user = $request->user();
        $tenant = $user?->tenant;

        if (! $tenant) {
            return redirect($returnTo)->with('error', 'Nu ai un tenant asociat.');
        }

        try {
            $token = $this->oauth->handleCallback($request->query('code'), $tenant, $user);
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return redirect($returnTo)->with('error', 'Conectarea Google a eșuat: ' . $e->getMessage());
        }

        // Best-effort: create the convention KB folder on first connect.
        if (! $token->kb_folder_id) {
            $folderId = $this->drive->createKbFolder($token);
            if ($folderId) {
                $token->update(['kb_folder_id' => $folderId]);
            }
        }

        return redirect($returnTo)->with('success', 'Google Drive conectat cu succes (' . ($token->google_email ?? 'cont necunoscut') . ').');
    }

    /**
     * Disconnect: revoke at Google + delete local token.
     */
    public function disconnect(Request $request)
    {
        $tenant = $request->user()?->tenant;
        if (! $tenant) {
            abort(403);
        }

        $token = GoogleOAuthToken::where('tenant_id', $tenant->id)->first();
        if ($token) {
            $this->oauth->disconnect($token);
        }

        return back()->with('success', 'Google Drive a fost deconectat.');
    }
}
