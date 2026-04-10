<?php

namespace App\Services\Google;

use App\Models\GoogleOAuthToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Google OAuth 2.0 service — pure HTTP, no SDK dependency.
 *
 * Per-tenant model: at most one connection per tenant. The first user from a
 * tenant who clicks "Connect Google Drive" creates the row; all bots in that
 * tenant share it.
 */
class GoogleOAuthService
{
    public const SCOPE_DRIVE_FILE = 'https://www.googleapis.com/auth/drive.file';
    public const SCOPE_USERINFO_EMAIL = 'https://www.googleapis.com/auth/userinfo.email';

    private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const REVOKE_ENDPOINT = 'https://oauth2.googleapis.com/revoke';
    private const USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /**
     * Default scopes for the Drive integration. We start with drive.file
     * (no Google verification needed) + email (so we can show which account
     * is connected in the UI).
     */
    public function defaultScopes(): array
    {
        return [
            self::SCOPE_DRIVE_FILE,
            self::SCOPE_USERINFO_EMAIL,
        ];
    }

    /**
     * Build the consent URL the user is redirected to.
     *
     * @param  string  $state CSRF nonce stored in session
     * @param  array<string>  $scopes Override default scopes (for incremental auth)
     */
    public function buildAuthUrl(string $state, array $scopes = null): string
    {
        $params = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes ?? $this->defaultScopes()),
            'access_type' => 'offline',         // ask for a refresh token
            'prompt' => 'consent',              // force refresh_token return on re-auth
            'include_granted_scopes' => 'true', // incremental authorization
            'state' => $state,
        ];

        return self::AUTH_ENDPOINT . '?' . http_build_query($params);
    }

    /**
     * Exchange the authorization code for tokens, persist them for the tenant,
     * and return the saved row.
     */
    public function handleCallback(string $code, Tenant $tenant, ?User $user = null): GoogleOAuthToken
    {
        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            Log::error('Google OAuth token exchange failed', [
                'tenant_id' => $tenant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Schimbul de token cu Google a eșuat: ' . $response->json('error_description', $response->body()));
        }

        $payload = $response->json();

        $accessToken = $payload['access_token'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;
        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $scopes = $payload['scope'] ?? implode(' ', $this->defaultScopes());

        if (! $accessToken) {
            throw new RuntimeException('Google nu a returnat un access_token.');
        }

        // Fetch identity for display purposes (best-effort).
        $identity = $this->fetchUserInfo($accessToken);

        // Per-tenant: replace any prior token row for this tenant.
        $existing = GoogleOAuthToken::where('tenant_id', $tenant->id)->first();

        // If Google didn't return a refresh_token (happens when the user has
        // already granted consent before), keep the previous one so we don't
        // lose long-term access.
        if (! $refreshToken && $existing && $existing->refresh_token) {
            $refreshToken = $existing->refresh_token;
        }

        $token = GoogleOAuthToken::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'user_id' => $user?->id,
                'google_user_id' => $identity['sub'] ?? null,
                'google_email' => $identity['email'] ?? null,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => now()->addSeconds($expiresIn),
                'scopes' => $scopes,
            ]
        );

        return $token;
    }

    /**
     * Return a non-expired access token, refreshing if necessary.
     * Throws if the token cannot be refreshed (user must reconnect).
     */
    public function getValidAccessToken(GoogleOAuthToken $token): string
    {
        if (! $token->isExpired()) {
            return $token->access_token;
        }

        if (! $token->refresh_token) {
            throw new RuntimeException('Token Google expirat și nu există refresh_token. Userul trebuie să reconnecteze.');
        }

        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $token->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            Log::error('Google OAuth token refresh failed', [
                'tenant_id' => $token->tenant_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Refresh-ul tokenului Google a eșuat. Reconnectează contul.');
        }

        $payload = $response->json();
        $newAccessToken = $payload['access_token'] ?? null;
        $expiresIn = (int) ($payload['expires_in'] ?? 3600);

        if (! $newAccessToken) {
            throw new RuntimeException('Google nu a returnat un access_token la refresh.');
        }

        $token->forceFill([
            'access_token' => $newAccessToken,
            'expires_at' => now()->addSeconds($expiresIn),
        ])->save();

        return $newAccessToken;
    }

    /**
     * Revoke the refresh token at Google and delete the local row.
     */
    public function disconnect(GoogleOAuthToken $token): void
    {
        $tokenToRevoke = $token->refresh_token ?: $token->access_token;

        try {
            Http::asForm()->post(self::REVOKE_ENDPOINT, ['token' => $tokenToRevoke]);
        } catch (\Throwable $e) {
            // Best effort — proceed with local deletion regardless.
            Log::warning('Google OAuth revoke call failed', [
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
            ]);
        }

        $token->delete();
    }

    /**
     * Generate a fresh CSRF state token for the OAuth flow.
     */
    public function generateState(): string
    {
        return Str::random(40);
    }

    private function fetchUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)->get(self::USERINFO_ENDPOINT);
            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable $e) {
            Log::debug('Google userinfo fetch failed', ['error' => $e->getMessage()]);
        }
        return [];
    }

    private function clientId(): string
    {
        $id = config('services.google.client_id');
        if (! $id) {
            throw new RuntimeException('GOOGLE_CLIENT_ID lipsește din .env');
        }
        return $id;
    }

    private function clientSecret(): string
    {
        $secret = config('services.google.client_secret');
        if (! $secret) {
            throw new RuntimeException('GOOGLE_CLIENT_SECRET lipsește din .env');
        }
        return $secret;
    }

    private function redirectUri(): string
    {
        return config('services.google.redirect_uri');
    }
}
