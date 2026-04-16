<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provisions the GTM container for sambla.ro via the Tag Manager
 * API v2. Creates data-layer variables, constant variables,
 * custom-event triggers, and tags (GA4 Configuration, GA4 Events,
 * Google Ads Conversion Linker + conversions, Meta Pixel base +
 * custom events) to match the events our code pushes to
 * window.dataLayer. Each created item is name-prefixed with
 * "sambla/" so re-running is idempotent — the provisioner
 * upserts by name and leaves unrelated tags intact.
 *
 * Consent Mode v2: every tag has consentSettings matching the
 * category that applies (analytics_storage for GA4, ad_* for
 * Google Ads + Meta), so tags stay dormant until the visitor
 * opts in through the cookie widget.
 *
 * Credentials: service-account JSON at
 * storage/app/private/gtm-service-account.json with
 * tagmanager.edit.containers + tagmanager.publish scopes.
 */
class GtmProvisioner
{
    // Hard-coded IDs for the sambla.ro container — we only have one.
    // Re-discovery via API is trivial but avoids extra 404 handling.
    public const ACCOUNT_ID = '6350231815';
    public const CONTAINER_ID = '249603368';
    // Workspace ID is resolved dynamically from the Default
    // Workspace at run-time — GTM auto-rolls the default whenever
    // a version is published (2 → 3 → 6 → …), so hard-coding
    // doesn't survive across provisioning runs.
    public const NAME_PREFIX = 'sambla/';

    // Built-in "All Pages" trigger ID in every GTM container.
    public const TRIGGER_ALL_PAGES = '2147479553';

    private ?string $accessToken = null;
    private ?string $workspacePath = null;

    // GTM API limit is 30 QPM/user. We leave 10% headroom by
    // pacing to 27/min — 2.2s between calls.
    private const API_CALL_SPACING_MS = 2300;
    private float $lastApiCallAt = 0;

    public function __construct(private AnalyticsConfig $cfg) {}

    private function resolveWorkspace(): void
    {
        $ws = $this->api('GET', sprintf(
            'accounts/%s/containers/%s/workspaces',
            self::ACCOUNT_ID, self::CONTAINER_ID
        ));
        $default = collect($ws['workspace'] ?? [])
            ->firstWhere('name', 'Default Workspace');
        if (!$default) {
            throw new \RuntimeException('Default Workspace not found in container');
        }
        $this->workspacePath = $default['path'];
    }

    /**
     * Full provisioning run. Returns a summary the command prints.
     */
    public function run(?callable $log = null): array
    {
        $log ??= fn($m) => null;
        $this->authenticate();
        $this->resolveWorkspace();
        $log('• Using workspace: ' . $this->workspacePath);

        $log('• Fetching existing state');
        $existingVars = $this->listAll('variables', 'variable');
        $existingTriggers = $this->listAll('triggers', 'trigger');
        $existingTags = $this->listAll('tags', 'tag');

        $log('• Upserting variables');
        $vars = $this->upsertVariables($existingVars, $log);

        $log('• Upserting triggers');
        $triggers = $this->upsertTriggers($existingTriggers, $log);

        $log('• Upserting tags');
        $tags = $this->upsertTags($existingTags, $triggers, $log);

        $log('• Creating version + publishing');
        $version = $this->createVersionAndPublish();

        return [
            'variables' => count($vars),
            'triggers' => count($triggers),
            'tags' => count($tags),
            'version' => $version['containerVersion']['name'] ?? '?',
            'version_id' => $version['containerVersion']['containerVersionId'] ?? '?',
        ];
    }

    // ───────────────────────────────────────────────────────────
    // Entity definitions — single source of truth for everything
    // we create. Reading bottom-up from here reconstructs the full
    // container config.
    // ───────────────────────────────────────────────────────────

    /**
     * Data layer variables we read in triggers + tags. Each pulls
     * the matching key from the dataLayer pushes emitted by our
     * code (see AnalyticsTracker + preturi.blade.php + layouts).
     */
    private function dataLayerVariableNames(): array
    {
        return [
            'user_id', 'user_role', 'tenant_id',
            'method', 'form_name',
            'lead_source', 'niche',
            'value', 'currency', 'transaction_id',
            'item_list_name', 'items',
            // ecommerce per-item fields the tag reads when firing
            // select_item / begin_checkout as a fallback when the
            // items array isn't present.
            'item_id', 'item_name', 'item_category', 'price', 'quantity',
            // Enterprise interaction tracking
            'link_url', 'phone_number', 'email', 'social_network',
            'scroll_depth', 'engagement_seconds',
            'error_message', 'error_source', 'error_line',
            'message_count', 'duration',
        ];
    }

    /**
     * Constant variables — IDs we reference in multiple tags.
     * Read from PlatformSetting at provision time; skipped if
     * the operator hasn't set them yet (re-run after saving).
     */
    private function constantVariables(): array
    {
        $out = [];
        if ($id = $this->cfg->ga4Id()) {
            $out[] = ['name' => self::NAME_PREFIX . 'const/ga4-id', 'value' => $id];
        }
        if ($id = $this->cfg->googleAdsId()) {
            $out[] = ['name' => self::NAME_PREFIX . 'const/google-ads-id', 'value' => $id];
        }
        if ($id = $this->cfg->metaPixelId()) {
            $out[] = ['name' => self::NAME_PREFIX . 'const/meta-pixel-id', 'value' => $id];
        }
        return $out;
    }

    /**
     * Every custom event we push to dataLayer gets its own
     * customEvent trigger. Names are predictable (sambla/event/X).
     */
    private function eventTriggerNames(): array
    {
        return [
            // Auth + onboarding
            'sign_up', 'login', 'tenant_created',
            // Lead + contact
            'generate_lead', 'callback_requested', 'form_submit',
            // Ecommerce
            'view_item_list', 'select_item', 'begin_checkout',
            'add_payment_info', 'purchase',
            // Subscription lifecycle
            'trial_start', 'trial_convert',
            'subscription_renewed', 'subscription_canceled',
            // Product engagement
            'chat_session_start', 'chat_session_end',
            'voice_call_started', 'voice_call_completed',
            // Enterprise interaction tracking
            'phone_click', 'email_click', 'social_click',
            'niche_view',
            'scroll_milestone', 'engagement_time', 'js_error',
            'chat_widget_opened', 'chat_widget_closed', 'chat_engaged',
        ];
    }

    // ───────────────────────────────────────────────────────────
    // Upsert helpers
    // ───────────────────────────────────────────────────────────

    private function upsertVariables(array $existing, callable $log): array
    {
        $byName = [];
        foreach ($existing as $v) $byName[$v['name']] = $v;
        $out = [];

        // Data layer variables.
        foreach ($this->dataLayerVariableNames() as $dlKey) {
            $name = self::NAME_PREFIX . 'dl/' . $dlKey;
            $body = [
                'name' => $name,
                'type' => 'v',
                'parameter' => [
                    ['type' => 'integer',  'key' => 'dataLayerVersion', 'value' => '2'],
                    ['type' => 'boolean',  'key' => 'setDefaultValue',  'value' => 'false'],
                    ['type' => 'template', 'key' => 'name',              'value' => $dlKey],
                ],
            ];
            $out[$name] = $this->upsertEntity('variables', $byName[$name] ?? null, $body);
            $log('  - variable: ' . $name);
        }

        // Constants.
        foreach ($this->constantVariables() as $c) {
            $body = [
                'name' => $c['name'],
                'type' => 'c',
                'parameter' => [
                    ['type' => 'template', 'key' => 'value', 'value' => $c['value']],
                ],
            ];
            $out[$c['name']] = $this->upsertEntity('variables', $byName[$c['name']] ?? null, $body);
            $log('  - variable: ' . $c['name']);
        }

        return $out;
    }

    private function upsertTriggers(array $existing, callable $log): array
    {
        $byName = [];
        foreach ($existing as $t) $byName[$t['name']] = $t;
        $out = [];

        foreach ($this->eventTriggerNames() as $eventName) {
            $name = self::NAME_PREFIX . 'event/' . $eventName;
            $body = [
                'name' => $name,
                'type' => 'customEvent',
                'customEventFilter' => [[
                    'type' => 'equals',
                    'parameter' => [
                        ['type' => 'template', 'key' => 'arg0', 'value' => '{{_event}}'],
                        ['type' => 'template', 'key' => 'arg1', 'value' => $eventName],
                    ],
                ]],
            ];
            $out[$eventName] = $this->upsertEntity('triggers', $byName[$name] ?? null, $body);
            $log('  - trigger: ' . $name);
        }

        return $out;
    }

    private function upsertTags(array $existing, array $triggers, callable $log): array
    {
        $byName = [];
        foreach ($existing as $t) $byName[$t['name']] = $t;
        $out = [];

        // ──────────────────────────────────────────────
        // GA4 Configuration (All Pages)
        // ──────────────────────────────────────────────
        if ($ga4Id = $this->cfg->ga4Id()) {
            $name = self::NAME_PREFIX . 'ga4/config';
            $body = [
                'name' => $name,
                'type' => 'gaawc',
                'parameter' => [
                    ['type' => 'template', 'key' => 'tagId', 'value' => $ga4Id],
                    ['type' => 'boolean',  'key' => 'sendPageView',          'value' => 'true'],
                    ['type' => 'list', 'key' => 'userProperties', 'list' => [
                        ['type' => 'map', 'map' => [
                            ['type' => 'template', 'key' => 'name',  'value' => 'user_role'],
                            ['type' => 'template', 'key' => 'value', 'value' => '{{' . self::NAME_PREFIX . 'dl/user_role}}'],
                        ]],
                        ['type' => 'map', 'map' => [
                            ['type' => 'template', 'key' => 'name',  'value' => 'tenant_id'],
                            ['type' => 'template', 'key' => 'value', 'value' => '{{' . self::NAME_PREFIX . 'dl/tenant_id}}'],
                        ]],
                    ]],
                ],
                'firingTriggerId' => [self::TRIGGER_ALL_PAGES],
                // Advanced Consent Mode: GA4 tag fires on every
                // pageview regardless of consent. The gtag SDK sends
                // cookieless / redacted pings when analytics_storage
                // is denied — which is both compliant and still
                // measurable (Google uses these for conversion
                // modeling in GA4 + Ads).
                'consentSettings' => ['consentStatus' => 'notSet'],
            ];
            $out[$name] = $this->upsertEntity('tags', $byName[$name] ?? null, $body);
            $log('  - tag: ' . $name);
        }

        // ──────────────────────────────────────────────
        // GA4 Event tags — one per custom event trigger
        // ──────────────────────────────────────────────
        if ($ga4Id = $this->cfg->ga4Id()) {
            foreach ($this->eventTriggerNames() as $eventName) {
                $trigger = $triggers[$eventName] ?? null;
                if (!$trigger) continue;
                $name = self::NAME_PREFIX . 'ga4/' . $eventName;

                $eventParams = $this->ga4EventParamsFor($eventName);
                $body = [
                    'name' => $name,
                    'type' => 'gaawe',
                    'parameter' => array_filter([
                        ['type' => 'template', 'key' => 'measurementIdOverride', 'value' => $ga4Id],
                        ['type' => 'template', 'key' => 'eventName',             'value' => $eventName],
                        empty($eventParams) ? null : ['type' => 'list', 'key' => 'eventParameters', 'list' => $eventParams],
                    ]),
                    'firingTriggerId' => [(string) $trigger['triggerId']],
                    // Advanced Consent Mode — see GA4 Config note.
                    'consentSettings' => ['consentStatus' => 'notSet'],
                ];
                $out[$name] = $this->upsertEntity('tags', $byName[$name] ?? null, $body);
                $log('  - tag: ' . $name);
            }
        }

        // ──────────────────────────────────────────────
        // Google Ads — Conversion Linker + per-event tags
        // ──────────────────────────────────────────────
        if ($adsId = $this->cfg->googleAdsId()) {
            $linkerName = self::NAME_PREFIX . 'ads/conversion-linker';
            $linkerBody = [
                'name' => $linkerName,
                'type' => 'gclidw',
                'parameter' => [
                    ['type' => 'boolean', 'key' => 'enableCrossDomain', 'value' => 'false'],
                ],
                'firingTriggerId' => [self::TRIGGER_ALL_PAGES],
                'consentSettings' => [
                    'consentStatus' => 'needed',
                    'consentType' => ['type' => 'list', 'list' => [
                        ['type' => 'template', 'value' => 'ad_storage'],
                    ]],
                ],
            ];
            $out[$linkerName] = $this->upsertEntity('tags', $byName[$linkerName] ?? null, $linkerBody);
            $log('  - tag: ' . $linkerName);
            // Conversion action tags themselves need per-event labels
            // (AW-xxx/yyy) from Google Ads. Those live as per-event
            // settings the operator will fill; we flag them as a
            // follow-up rather than creating half-configured tags.
            $log('    (Google Ads conversion tags skipped — need per-event labels from Ads account)');
        }

        // ──────────────────────────────────────────────
        // Meta Pixel — base (All Pages) + key events
        // ──────────────────────────────────────────────
        if ($pixelId = $this->cfg->metaPixelId()) {
            $baseName = self::NAME_PREFIX . 'meta/pixel-base';
            $baseBody = [
                'name' => $baseName,
                'type' => 'html',
                'parameter' => [[
                    'type' => 'template', 'key' => 'html',
                    'value' => $this->metaPixelBaseHtml($pixelId),
                ], ['type' => 'boolean', 'key' => 'supportDocumentWrite', 'value' => 'false']],
                'firingTriggerId' => [self::TRIGGER_ALL_PAGES],
                'consentSettings' => [
                    'consentStatus' => 'needed',
                    'consentType' => ['type' => 'list', 'list' => [
                        ['type' => 'template', 'value' => 'ad_storage'],
                        ['type' => 'template', 'value' => 'ad_user_data'],
                        ['type' => 'template', 'value' => 'ad_personalization'],
                    ]],
                ],
            ];
            $out[$baseName] = $this->upsertEntity('tags', $byName[$baseName] ?? null, $baseBody);
            $log('  - tag: ' . $baseName);

            // Map our dataLayer events → Meta Standard Events.
            $metaMap = [
                'sign_up'       => 'CompleteRegistration',
                'generate_lead' => 'Lead',
                'begin_checkout'=> 'InitiateCheckout',
                'add_payment_info' => 'AddPaymentInfo',
                'purchase'      => 'Purchase',
                'view_item_list'=> 'ViewContent',
            ];
            foreach ($metaMap as $ourEvent => $metaEvent) {
                $trigger = $triggers[$ourEvent] ?? null;
                if (!$trigger) continue;
                $name = self::NAME_PREFIX . 'meta/' . $ourEvent;
                $body = [
                    'name' => $name,
                    'type' => 'html',
                    'parameter' => [[
                        'type' => 'template', 'key' => 'html',
                        'value' => $this->metaPixelEventHtml($metaEvent, $ourEvent),
                    ], ['type' => 'boolean', 'key' => 'supportDocumentWrite', 'value' => 'false']],
                    'firingTriggerId' => [(string) $trigger['triggerId']],
                    'consentSettings' => [
                        'consentStatus' => 'needed',
                        'consentType' => ['type' => 'list', 'list' => [
                            ['type' => 'template', 'value' => 'ad_storage'],
                            ['type' => 'template', 'value' => 'ad_user_data'],
                            ['type' => 'template', 'value' => 'ad_personalization'],
                        ]],
                    ],
                ];
                $out[$name] = $this->upsertEntity('tags', $byName[$name] ?? null, $body);
                $log('  - tag: ' . $name);
            }
        }

        return $out;
    }

    /**
     * GA4 event parameters — shapes the eventParameters list per
     * event so GA4 gets rich context (value + currency for
     * ecommerce, method for auth events, lead_source for leads).
     */
    private function ga4EventParamsFor(string $event): array
    {
        $dl = fn(string $k) => '{{' . self::NAME_PREFIX . 'dl/' . $k . '}}';
        $mk = fn(string $name, string $val) => [
            'type' => 'map', 'map' => [
                ['type' => 'template', 'key' => 'name',  'value' => $name],
                ['type' => 'template', 'key' => 'value', 'value' => $val],
            ],
        ];

        $ecommerce = [
            $mk('currency',       $dl('currency')),
            $mk('value',          $dl('value')),
            $mk('transaction_id', $dl('transaction_id')),
        ];

        return match ($event) {
            'sign_up', 'login' => [$mk('method', $dl('method'))],
            'generate_lead'    => [$mk('lead_source', $dl('lead_source')), $mk('niche', $dl('niche')), ...$ecommerce],
            'begin_checkout', 'add_payment_info', 'purchase' => $ecommerce,
            'view_item_list', 'select_item' => [$mk('item_list_name', $dl('item_list_name'))],
            'form_submit' => [$mk('form_name', $dl('form_name'))],
            'phone_click' => [$mk('link_url', $dl('link_url')), $mk('phone_number', $dl('phone_number'))],
            'email_click' => [$mk('link_url', $dl('link_url')), $mk('email', $dl('email'))],
            'social_click' => [$mk('link_url', $dl('link_url')), $mk('social_network', $dl('social_network'))],
            'niche_view' => [$mk('niche', $dl('niche'))],
            'scroll_milestone' => [$mk('scroll_depth', $dl('scroll_depth'))],
            'engagement_time' => [$mk('engagement_seconds', $dl('engagement_seconds'))],
            'js_error' => [$mk('error_message', $dl('error_message')), $mk('error_source', $dl('error_source'))],
            'chat_widget_closed' => [$mk('duration', $dl('duration'))],
            'chat_engaged' => [$mk('message_count', $dl('message_count'))],
            default => [],
        };
    }

    private function metaPixelBaseHtml(string $pixelId): string
    {
        return <<<HTML
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$pixelId}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1"/></noscript>
HTML;
    }

    private function metaPixelEventHtml(string $metaEvent, string $dlEvent): string
    {
        // Ship a generic payload that Meta understands — value +
        // currency for ecommerce events, content_name for lead/
        // registration events. fbq() reads dataLayer indirectly via
        // GTM's own ${{}} templating at tag evaluation time.
        $prefix = self::NAME_PREFIX;
        $dl = fn(string $k) => '{{' . $prefix . 'dl/' . $k . '}}';
        $valueVar = $dl('value');
        $currencyVar = $dl('currency');
        $txIdVar = $dl('transaction_id');
        return <<<HTML
<script>
if (typeof fbq === 'function') {
    var params = {};
    var value = {$valueVar};
    var currency = {$currencyVar};
    var txId = {$txIdVar};
    if (value)    params.value = parseFloat(value) || 0;
    if (currency) params.currency = currency;
    if (txId)     params.event_id = String(txId);
    fbq('track', '{$metaEvent}', params);
}
</script>
HTML;
    }

    // ───────────────────────────────────────────────────────────
    // GTM API primitives
    // ───────────────────────────────────────────────────────────

    private function upsertEntity(string $kind, ?array $existing, array $body): array
    {
        if ($existing) {
            // PATCH by path. Fingerprint avoids optimistic-concurrency errors.
            $body['fingerprint'] = $existing['fingerprint'] ?? '';
            return $this->api('PUT', $existing['path'], $body);
        }
        return $this->api('POST', $this->workspacePath . '/' . $kind, $body);
    }

    private function listAll(string $kind, string $itemKey): array
    {
        $items = [];
        $url = $this->workspacePath . '/' . $kind;
        do {
            $resp = $this->api('GET', $url);
            foreach ($resp[$itemKey] ?? [] as $it) $items[] = $it;
            $next = $resp['nextPageToken'] ?? null;
            $url = $next ? $this->workspacePath . '/' . $kind . '?pageToken=' . $next : null;
        } while ($url);
        return $items;
    }

    private function createVersionAndPublish(): array
    {
        // Create a named version.
        $version = $this->api('POST', $this->workspacePath . ':create_version', [
            'name' => 'Sambla auto-provision ' . now()->toIso8601String(),
            'notes' => 'Created by gtm:provision artisan command',
        ]);
        $vid = $version['containerVersion']['containerVersionId'] ?? null;
        if ($vid) {
            $versionPath = sprintf(
                'accounts/%s/containers/%s/versions/%s',
                self::ACCOUNT_ID, self::CONTAINER_ID, $vid
            );
            $this->api('POST', $versionPath . ':publish');
        }
        return $version;
    }

    private function api(string $method, string $pathOrUrl, ?array $body = null): array
    {
        $this->throttle();

        $url = str_starts_with($pathOrUrl, 'http')
            ? $pathOrUrl
            : 'https://tagmanager.googleapis.com/tagmanager/v2/' . ltrim($pathOrUrl, '/');

        $req = Http::timeout(15)
            ->withHeaders(['Authorization' => 'Bearer ' . $this->accessToken])
            ->acceptJson();

        // Bodyless POST (like :publish) needs no body at all —
        // sending [] serializes to a JSON array, which GTM rejects
        // with "Root element must be a message".
        $send = function () use ($method, $req, $url, $body) {
            return match ($method) {
                'GET'    => $req->get($url),
                'POST'   => $body === null ? $req->send('POST', $url) : $req->asJson()->post($url, $body),
                'PUT'    => $body === null ? $req->send('PUT', $url)  : $req->asJson()->put($url, $body),
                'DELETE' => $req->delete($url),
            };
        };
        $resp = $send();

        // If GTM still hits us with 429 despite pacing (e.g. fresh
        // minute boundary), back off 60s and retry once.
        if ($resp->status() === 429) {
            Log::info('GtmProvisioner: 429 — backing off 60s');
            sleep(60);
            $this->throttle();
            $resp = $send();
        }

        if (!$resp->successful()) {
            Log::warning('GtmProvisioner: API error', [
                'method' => $method, 'url' => $url, 'status' => $resp->status(),
                'body' => $body, 'resp' => $resp->body(),
            ]);
            throw new \RuntimeException("GTM API {$method} {$url} → HTTP {$resp->status()}: " . $resp->body());
        }
        return $resp->json() ?? [];
    }

    private function throttle(): void
    {
        $elapsedMs = (microtime(true) - $this->lastApiCallAt) * 1000;
        $waitMs = self::API_CALL_SPACING_MS - $elapsedMs;
        if ($waitMs > 0) usleep((int) ($waitMs * 1000));
        $this->lastApiCallAt = microtime(true);
    }

    private function authenticate(): void
    {
        $path = storage_path('app/private/gtm-service-account.json');
        if (!is_file($path)) {
            throw new \RuntimeException("Missing service account key at {$path}");
        }
        $sa = json_decode(file_get_contents($path), true);

        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claims = [
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/tagmanager.edit.containers '
                     . 'https://www.googleapis.com/auth/tagmanager.edit.containerversions '
                     . 'https://www.googleapis.com/auth/tagmanager.publish '
                     . 'https://www.googleapis.com/auth/tagmanager.manage.accounts',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];
        $claimsEncoded = $this->b64url(json_encode($claims));
        $signInput = $header . '.' . $claimsEncoded;

        openssl_sign($signInput, $sig, $sa['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $signInput . '.' . $this->b64url($sig);

        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);
        if (!$resp->successful() || empty($resp->json('access_token'))) {
            throw new \RuntimeException('GTM auth failed: ' . $resp->body());
        }
        $this->accessToken = $resp->json('access_token');
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
