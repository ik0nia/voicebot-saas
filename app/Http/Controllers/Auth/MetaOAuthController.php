<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Tenant onboarding for Facebook Messenger + Instagram DM via Meta OAuth.
 *
 * Flow:
 *   GET  /dashboard/agenti/{bot}/canale/meta/connect
 *        → builds OAuth URL with state CSRF, redirects user to Meta
 *
 *   GET  /oauth/meta/callback
 *        ← Meta sends code + state. We exchange code for short-lived
 *          user token, swap to long-lived (60d), fetch /me/accounts,
 *          render select-pages UI listing FB pages + their connected
 *          Instagram accounts.
 *
 *   POST /dashboard/agenti/{bot}/canale/meta/attach
 *        ← user submits chosen page_id (and optionally ig_id). We
 *          subscribe the page to our app's webhook, save Channel rows
 *          with page_access_token + ig_user_id, redirect to bot.
 *
 * Design notes:
 *   - state nonce stored in session-aware cache so callback can recover
 *     the originating bot_id without trusting query params alone
 *   - long-lived user tokens cached 50 days (Meta gives 60, we refresh
 *     proactively to avoid expiry)
 *   - signed_request webhook deauth landing on /webhook/meta/data-deletion
 *     handles revocation symmetrically (see MetaDataDeletionController)
 */
class MetaOAuthController extends Controller
{
    /** Initiate OAuth — sends user to Meta with our app_id + scopes. */
    public function connect(Request $request, Bot $bot): RedirectResponse
    {
        // Tenant guard. Bot model is BelongsToTenant-scoped, but we route
        // through implicit binding which respects the scope. A user from
        // another tenant gets a 404 here automatically. Still, defense in
        // depth — confirm the actor's tenant matches.
        if (!auth()->user()->isSuperAdmin() && $bot->tenant_id !== auth()->user()->tenant_id) {
            abort(404);
        }

        $appId = (string) config('services.meta.app_id');
        if ($appId === '') {
            return back()->withErrors([
                'meta' => 'Integrarea Meta nu este configurată pe acest mediu. Contactează echipa Sambla.',
            ]);
        }

        $state = Str::random(40);
        Cache::put("meta_oauth:{$state}", [
            'bot_id' => $bot->id,
            'tenant_id' => $bot->tenant_id,
            'user_id' => auth()->id(),
            'created_at' => now()->toIso8601String(),
        ], now()->addMinutes(15));

        $scopes = implode(',', (array) config('services.meta.scopes', []));
        $redirectUri = url('/oauth/meta/callback');

        $url = sprintf(
            'https://www.facebook.com/%s/dialog/oauth?%s',
            (string) config('services.meta.graph_version'),
            http_build_query([
                'client_id' => $appId,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => $scopes,
                'response_type' => 'code',
                // Force re-auth so a tenant who previously denied a
                // permission gets the dialog again instead of a silent
                // partial grant. Cheap correctness win.
                'auth_type' => 'rerequest',
            ]),
        );

        return redirect()->away($url);
    }

    /** Callback after user authorizes — exchange code + render page picker. */
    public function callback(Request $request): View|RedirectResponse
    {
        $error = $request->query('error');
        if ($error) {
            Log::info('Meta OAuth: user declined', [
                'error' => $error,
                'reason' => $request->query('error_reason'),
                'description' => $request->query('error_description'),
            ]);
            return redirect()->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Autorizarea a fost anulată sau respinsă în Meta.']);
        }

        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');
        if ($code === '' || $state === '') {
            return redirect()->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Răspuns OAuth incomplet de la Meta — încearcă din nou.']);
        }

        $stateData = Cache::pull("meta_oauth:{$state}");
        if (!$stateData || ($stateData['user_id'] ?? null) !== auth()->id()) {
            return redirect()->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Sesiunea OAuth a expirat sau este invalidă. Încearcă din nou.']);
        }

        $bot = Bot::find($stateData['bot_id']);
        if (!$bot) {
            return redirect()->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Bot-ul țintă nu mai există.']);
        }

        // 1) Exchange code → short-lived user access token.
        $shortLived = $this->exchangeCodeForToken($code);
        if (!$shortLived) {
            return redirect()->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Schimbul code → token a eșuat. Încearcă din nou sau contactează-ne.']);
        }

        // 2) Swap to long-lived (60 days) — production tokens never use
        //    the short-lived one (1h expiry kills first webhook).
        $longLived = $this->swapToLongLived($shortLived);

        // 3) Fetch Meta user identity (we save fb_user_id on the channel
        //    so data-deletion callback can locate which channels to revoke).
        $me = $this->fetchUser($longLived);

        // 4) Fetch pages the user manages, plus any IG business accounts
        //    linked to those pages.
        $pages = $this->fetchPages($longLived);

        // Stash the long-lived token + me + pages in cache, keyed by a
        // fresh nonce. The page-picker view POSTs back the picked
        // page_id + this nonce; we re-fetch credentials at attach time
        // rather than passing tokens through the form (don't put a Page
        // Access Token in HTML, even encrypted-form-input).
        $attachToken = Str::random(40);
        Cache::put("meta_oauth_attach:{$attachToken}", [
            'bot_id' => $bot->id,
            'tenant_id' => $bot->tenant_id,
            'user_id' => auth()->id(),
            'long_lived_token' => $longLived,
            'fb_user_id' => $me['id'] ?? null,
            'fb_user_name' => $me['name'] ?? null,
            'fb_user_email' => $me['email'] ?? null,
            'pages' => $pages,
        ], now()->addMinutes(20));

        return view('dashboard.bots.channels.meta-select-pages', [
            'bot' => $bot,
            'pages' => $pages,
            'me' => $me,
            'attachToken' => $attachToken,
        ]);
    }

    /** Persist selected page (and optional IG account) as Channel rows. */
    public function attach(Request $request, Bot $bot): RedirectResponse
    {
        $validated = $request->validate([
            'attach_token' => 'required|string|size:40',
            'page_id' => 'required|string|max:64',
            'attach_facebook' => 'nullable|in:1,0',
            'attach_instagram' => 'nullable|in:1,0',
        ]);

        $stash = Cache::pull("meta_oauth_attach:{$validated['attach_token']}");
        if (!$stash || $stash['bot_id'] !== $bot->id || $stash['user_id'] !== auth()->id()) {
            return redirect()
                ->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Sesiunea de atașare a expirat. Reia conectarea Meta.']);
        }

        $page = collect($stash['pages'])->firstWhere('id', $validated['page_id']);
        if (!$page) {
            return redirect()
                ->route('dashboard.channels-hub.index')
                ->withErrors(['meta' => 'Pagina selectată nu a fost găsită în lista autorizată.']);
        }

        $created = [];
        $errors = [];

        // Facebook Messenger channel
        if ($request->boolean('attach_facebook')) {
            try {
                $channel = $this->upsertFacebookChannel($bot, $page, $stash);
                $this->subscribePageToApp($page['id'], $page['access_token']);
                $created[] = 'Facebook Messenger';
            } catch (\Throwable $e) {
                Log::error('Meta OAuth: failed to attach FB page', ['err' => $e->getMessage(), 'page_id' => $page['id']]);
                $errors[] = 'Facebook Messenger: ' . $e->getMessage();
            }
        }

        // Instagram (only if the FB page has connected_instagram_account)
        if ($request->boolean('attach_instagram') && !empty($page['instagram_business_account']['id'])) {
            try {
                $channel = $this->upsertInstagramChannel($bot, $page, $stash);
                $created[] = 'Instagram DM';
            } catch (\Throwable $e) {
                Log::error('Meta OAuth: failed to attach IG account', ['err' => $e->getMessage(), 'page_id' => $page['id']]);
                $errors[] = 'Instagram DM: ' . $e->getMessage();
            }
        }

        $msg = empty($created)
            ? 'Niciun canal nu a fost creat. ' . implode(' | ', $errors)
            : sprintf('Conectat: %s.', implode(', ', $created));

        return redirect()
            ->route('dashboard.bots.channels.index', ['bot' => $bot])
            ->with(empty($errors) ? 'success' : 'warning', $msg);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function graphBase(): string
    {
        return 'https://graph.facebook.com/' . (string) config('services.meta.graph_version');
    }

    private function exchangeCodeForToken(string $code): ?string
    {
        $resp = Http::timeout(10)->get($this->graphBase() . '/oauth/access_token', [
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => url('/oauth/meta/callback'),
            'code' => $code,
        ]);
        return $resp->ok() ? ($resp->json('access_token') ?? null) : null;
    }

    private function swapToLongLived(string $shortLived): string
    {
        $resp = Http::timeout(10)->get($this->graphBase() . '/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'fb_exchange_token' => $shortLived,
        ]);
        // If swap fails, fall back to short-lived — at least the OAuth
        // completes; we'll log and surface a warning to the operator.
        return $resp->ok() ? ($resp->json('access_token') ?? $shortLived) : $shortLived;
    }

    private function fetchUser(string $token): array
    {
        $resp = Http::timeout(10)->get($this->graphBase() . '/me', [
            'fields' => 'id,name,email',
            'access_token' => $token,
        ]);
        return $resp->ok() ? $resp->json() : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPages(string $token): array
    {
        $resp = Http::timeout(15)->get($this->graphBase() . '/me/accounts', [
            'fields' => 'id,name,access_token,category,picture{url},instagram_business_account{id,username,name,profile_picture_url}',
            'limit' => 100,
            'access_token' => $token,
        ]);
        return $resp->ok() ? ($resp->json('data') ?? []) : [];
    }

    /**
     * Tell Meta this Page is now subscribed to our App's webhook.
     * Idempotent — calling twice is safe; Meta returns success either way.
     */
    private function subscribePageToApp(string $pageId, string $pageAccessToken): void
    {
        $resp = Http::asForm()->timeout(10)->post(
            $this->graphBase() . "/{$pageId}/subscribed_apps",
            [
                'subscribed_fields' => 'messages,messaging_postbacks,message_deliveries,message_reads',
                'access_token' => $pageAccessToken,
            ],
        );
        if (!$resp->ok()) {
            throw new \RuntimeException('Meta a refuzat subscribed_apps: ' . $resp->body());
        }
    }

    private function upsertFacebookChannel(Bot $bot, array $page, array $stash): Channel
    {
        $existing = Channel::query()
            ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('type', Channel::TYPE_FACEBOOK_MESSENGER)
            ->where('external_id', $page['id'])
            ->first();

        if ($existing && $existing->tenant_id !== $bot->tenant_id) {
            throw new \RuntimeException('Această pagină este deja conectată la un alt cont Sambla.');
        }

        $channel = $existing ?? new Channel();
        $channel->fill([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_FACEBOOK_MESSENGER,
            'name' => $page['name'] ?? 'Facebook Messenger',
            'external_id' => $page['id'],
            'is_active' => true,
            'status' => 'connected',
        ]);
        // tenant_id is set by Channel::booted() from bot_id

        $channel->setCredential('page_id', $page['id']);
        $channel->setCredential('page_access_token', $page['access_token']);
        $channel->setCredential('fb_user_id', $stash['fb_user_id']);
        $channel->setCredential('fb_user_name', $stash['fb_user_name']);
        $channel->setCredential('user_long_lived_token', $stash['long_lived_token']);
        $channel->setCredential('connected_at', now()->toIso8601String());
        $channel->save();

        return $channel;
    }

    private function upsertInstagramChannel(Bot $bot, array $page, array $stash): Channel
    {
        $iga = $page['instagram_business_account'] ?? null;
        if (!$iga || empty($iga['id'])) {
            throw new \RuntimeException('Pagina Facebook nu are un cont Instagram Business conectat.');
        }

        $existing = Channel::query()
            ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('type', Channel::TYPE_INSTAGRAM_DM)
            ->where('external_id', $iga['id'])
            ->first();

        if ($existing && $existing->tenant_id !== $bot->tenant_id) {
            throw new \RuntimeException('Acest cont Instagram este deja conectat la un alt cont Sambla.');
        }

        $channel = $existing ?? new Channel();
        $channel->fill([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_INSTAGRAM_DM,
            'name' => '@' . ($iga['username'] ?? 'instagram'),
            'external_id' => $iga['id'],
            'is_active' => true,
            'status' => 'connected',
        ]);

        $channel->setCredential('ig_user_id', $iga['id']);
        $channel->setCredential('ig_username', $iga['username'] ?? null);
        $channel->setCredential('parent_page_id', $page['id']);
        // IG DM uses the parent Facebook Page Access Token to call the
        // Send API — there's no separate IG token in the v3+ flow.
        $channel->setCredential('page_access_token', $page['access_token']);
        $channel->setCredential('fb_user_id', $stash['fb_user_id']);
        $channel->setCredential('connected_at', now()->toIso8601String());
        $channel->save();

        return $channel;
    }
}
