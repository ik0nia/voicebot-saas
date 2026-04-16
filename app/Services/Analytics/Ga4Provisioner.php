<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Configures the GA4 property via the Analytics Admin API:
 *   - custom dimensions for the event/user params our code pushes
 *     (tenant_id, user_role, lead_source, niche, method, form_name)
 *     so they show up as filterable columns in reports
 *   - marks key events as conversions so GA4 treats them as goals
 *   - provisions a baseline set of audiences (leads, trialers,
 *     customers, cart abandoners, engaged users, paid traffic)
 *     usable as remarketing lists + for Meta/Ads segmentation
 *
 * Credentials: storage/app/private/ga4-service-account.json.
 * The SA must be granted Editor (or higher) on the property.
 */
class Ga4Provisioner
{
    // Property "sambla.ro" — discovered via GET /v1beta/accountSummaries.
    public const PROPERTY = 'properties/533296331';

    private ?string $accessToken = null;

    public function run(?callable $log = null): array
    {
        $log ??= fn($m) => null;
        $this->authenticate();

        $log('• Registering custom dimensions');
        $dims = $this->upsertCustomDimensions($log);

        $log('• Marking conversion events');
        $convs = $this->upsertConversions($log);

        $log('• Creating audiences');
        $aud = $this->upsertAudiences($log);

        $log('• Disabling Enhanced Measurement form tracking (dedup w/ our custom form_submit)');
        $this->disableFormTracking($log);

        return [
            'custom_dimensions' => count($dims),
            'conversions' => count($convs),
            'audiences' => count($aud),
        ];
    }

    /**
     * GA4 Enhanced Measurement has an auto form_submit / form_start
     * detector. We emit our own form_submit with richer params
     * (form_name), so leaving Enhanced on causes duplicate events.
     * Patch the web data stream's enhancedMeasurementSettings to
     * turn just the form signals off.
     */
    private function disableFormTracking(callable $log): void
    {
        // Discover the web stream — there's typically one.
        $streams = $this->api('GET', self::PROPERTY . '/dataStreams')['dataStreams'] ?? [];
        $webStream = collect($streams)->first(fn($s) => ($s['type'] ?? '') === 'WEB_DATA_STREAM');
        if (!$webStream) {
            $log('  - no web data stream found; skipping');
            return;
        }
        $streamName = $webStream['name']; // e.g. properties/X/dataStreams/Y
        try {
            // enhancedMeasurementSettings lives on v1alpha only.
            $this->api(
                'PATCH',
                'https://analyticsadmin.googleapis.com/v1alpha/' . $streamName . '/enhancedMeasurementSettings?updateMask=formInteractionsEnabled',
                ['formInteractionsEnabled' => false]
            );
            $log('  - form interactions disabled on ' . $streamName);
        } catch (\Throwable $e) {
            $log('  - could not disable form tracking: ' . substr($e->getMessage(), 0, 200));
        }
    }

    // ───────────────────────────────────────────────────────────
    // Custom dimensions
    // ───────────────────────────────────────────────────────────

    private function customDimensionSpecs(): array
    {
        return [
            // User-scoped — sticky to the user / session across events.
            ['parameterName' => 'tenant_id',    'displayName' => 'Tenant ID',   'scope' => 'USER',  'description' => 'Sambla tenant the user belongs to'],
            ['parameterName' => 'user_role',    'displayName' => 'User Role',   'scope' => 'USER',  'description' => 'tenant_admin / tenant_manager / tenant_viewer / super_admin'],
            ['parameterName' => 'trial_status', 'displayName' => 'Trial Status','scope' => 'USER',  'description' => 'active / expired / converted'],
            // Event-scoped — per single event, richer detail.
            ['parameterName' => 'lead_source',     'displayName' => 'Lead Source',     'scope' => 'EVENT', 'description' => 'contact_form / niche_landing'],
            ['parameterName' => 'niche',           'displayName' => 'Niche',           'scope' => 'EVENT', 'description' => 'Landing page niche slug (avocati, contabili, …)'],
            ['parameterName' => 'method',          'displayName' => 'Auth Method',     'scope' => 'EVENT', 'description' => 'email / google / etc (sign_up + login)'],
            ['parameterName' => 'form_name',       'displayName' => 'Form Name',       'scope' => 'EVENT', 'description' => 'Identifier of the form that was submitted'],
            ['parameterName' => 'link_url',        'displayName' => 'Link URL',        'scope' => 'EVENT', 'description' => 'Href of the clicked link (phone/email/social)'],
            ['parameterName' => 'phone_number',    'displayName' => 'Phone Number',    'scope' => 'EVENT', 'description' => 'Phone number from a tel: click'],
            ['parameterName' => 'social_network',  'displayName' => 'Social Network',  'scope' => 'EVENT', 'description' => 'facebook / instagram / linkedin / etc'],
            ['parameterName' => 'scroll_depth',    'displayName' => 'Scroll Depth',    'scope' => 'EVENT', 'description' => 'Percentage (25/50/75/100) reached on scroll_milestone'],
            ['parameterName' => 'engagement_seconds','displayName' => 'Engagement Seconds','scope' => 'EVENT', 'description' => 'Milestone (10/30/60/120/300) on engagement_time'],
            ['parameterName' => 'message_count',   'displayName' => 'Chat Message Count','scope' => 'EVENT', 'description' => 'Messages the visitor sent in the chat widget'],
        ];
    }

    private function upsertCustomDimensions(callable $log): array
    {
        $existing = $this->api('GET', self::PROPERTY . '/customDimensions')['customDimensions'] ?? [];
        $byParam = [];
        foreach ($existing as $d) $byParam[$d['parameterName']] = $d;

        $out = [];
        foreach ($this->customDimensionSpecs() as $spec) {
            $existingDim = $byParam[$spec['parameterName']] ?? null;
            if ($existingDim) {
                $log('  - dimension already present: ' . $spec['parameterName']);
                $out[] = $existingDim;
                continue;
            }
            // GA4 scope enum uses DIMENSION_SCOPE_USER / _EVENT.
            $body = [
                'parameterName' => $spec['parameterName'],
                'displayName'   => $spec['displayName'],
                'scope'         => $spec['scope'], // "USER" or "EVENT"
                'description'   => $spec['description'],
            ];
            $created = $this->api('POST', self::PROPERTY . '/customDimensions', $body);
            $out[] = $created;
            $log('  - created: ' . $spec['parameterName'] . ' [' . $spec['scope'] . ']');
        }
        return $out;
    }

    // ───────────────────────────────────────────────────────────
    // Conversion events
    // ───────────────────────────────────────────────────────────

    private function conversionEventNames(): array
    {
        return [
            'sign_up',
            'tenant_created',
            'generate_lead',
            'callback_requested',
            'begin_checkout',
            'purchase', // default conversion in GA4 — API call still fine
            'trial_start',
            'trial_convert',
            // High-intent micro-conversions — worth bidding on even
            // if the visitor doesn't sign up in the same session.
            'phone_click',
            'email_click',
            'chat_engaged',
        ];
    }

    private function upsertConversions(callable $log): array
    {
        $existing = $this->api('GET', self::PROPERTY . '/conversionEvents')['conversionEvents'] ?? [];
        $byName = [];
        foreach ($existing as $c) $byName[$c['eventName']] = $c;

        $out = [];
        foreach ($this->conversionEventNames() as $name) {
            if (isset($byName[$name])) {
                $log('  - already conversion: ' . $name);
                $out[] = $byName[$name];
                continue;
            }
            try {
                $created = $this->api('POST', self::PROPERTY . '/conversionEvents', [
                    'eventName' => $name,
                ]);
                $out[] = $created;
                $log('  - marked conversion: ' . $name);
            } catch (\Throwable $e) {
                // purchase is auto-flagged in new properties and the
                // API returns 409 if you try to re-create it. Log &
                // move on instead of halting the whole provision.
                if (str_contains($e->getMessage(), '409')) {
                    $log('  - conversion already auto-flagged: ' . $name);
                } else {
                    throw $e;
                }
            }
        }
        return $out;
    }

    // ───────────────────────────────────────────────────────────
    // Audiences — remarketing segments usable by GA4 + Ads + Meta
    // ───────────────────────────────────────────────────────────

    private function audienceSpecs(): array
    {
        // Each spec is a minimal wrapper; filter() builds the
        // AudienceFilterClause payload from our shortcut syntax.
        return [
            [
                'displayName' => 'Sambla — Leads',
                'description' => 'Users who submitted contact / niche landing forms',
                'days' => 30,
                'filter' => ['event' => 'generate_lead'],
            ],
            [
                'displayName' => 'Sambla — Callback requested',
                'description' => 'Users who asked for a callback',
                'days' => 30,
                'filter' => ['event' => 'callback_requested'],
            ],
            [
                'displayName' => 'Sambla — Cart abandoners',
                'description' => 'begin_checkout fired in last 14d, no purchase',
                'days' => 14,
                'filter' => ['event' => 'begin_checkout'],
                'exclude' => ['event' => 'purchase'],
            ],
            [
                'displayName' => 'Sambla — Paid customers',
                'description' => 'Purchase event fired',
                'days' => 365,
                'filter' => ['event' => 'purchase'],
            ],
            [
                'displayName' => 'Sambla — Trialers',
                'description' => 'trial_start fired in 14d, no trial_convert yet',
                'days' => 14,
                'filter' => ['event' => 'trial_start'],
                'exclude' => ['event' => 'trial_convert'],
            ],
            [
                'displayName' => 'Sambla — Sign-ups not yet converted',
                'description' => 'sign_up in last 30d, no purchase',
                'days' => 30,
                'filter' => ['event' => 'sign_up'],
                'exclude' => ['event' => 'purchase'],
            ],
            [
                'displayName' => 'Sambla — Paid-traffic visitors',
                'description' => 'Sessions with utm_source=google/facebook (paid)',
                'days' => 30,
                'filter' => ['dimension' => 'firstUserSource', 'regex' => '^(google|facebook|meta)$'],
            ],
            [
                'displayName' => 'Sambla — Niche visitors',
                'description' => 'Any /pentru/* landing-page visit in the last 30 days',
                'days' => 30,
                'filter' => ['event' => 'niche_view'],
            ],
            [
                'displayName' => 'Sambla — Phone clickers',
                'description' => 'Clicked a tel: link — strong purchase intent',
                'days' => 30,
                'filter' => ['event' => 'phone_click'],
            ],
            [
                'displayName' => 'Sambla — Email clickers',
                'description' => 'Clicked a mailto: link',
                'days' => 30,
                'filter' => ['event' => 'email_click'],
            ],
            [
                'displayName' => 'Sambla — Chat engagers',
                'description' => 'Sent at least one message in the web chat widget',
                'days' => 30,
                'filter' => ['event' => 'chat_engaged'],
            ],
            [
                'displayName' => 'Sambla — Engaged sessions (60s+)',
                'description' => 'Stayed on the site with focus for at least 60s',
                'days' => 30,
                'filter' => ['event' => 'engagement_time'],
            ],
        ];
    }

    private function upsertAudiences(callable $log): array
    {
        // Audiences live on the v1alpha surface; v1beta 404s them.
        $existing = $this->api('GET', 'https://analyticsadmin.googleapis.com/v1alpha/' . self::PROPERTY . '/audiences?pageSize=200')['audiences'] ?? [];
        $byName = [];
        foreach ($existing as $a) $byName[$a['displayName']] = $a;

        $out = [];
        foreach ($this->audienceSpecs() as $spec) {
            if (isset($byName[$spec['displayName']])) {
                $log('  - audience already present: ' . $spec['displayName']);
                $out[] = $byName[$spec['displayName']];
                continue;
            }
            $body = $this->buildAudienceBody($spec);
            try {
                $created = $this->api('POST', 'https://analyticsadmin.googleapis.com/v1alpha/' . self::PROPERTY . '/audiences', $body);
                $out[] = $created;
                $log('  - created audience: ' . $spec['displayName']);
            } catch (\Throwable $e) {
                $log('  - audience failed: ' . $spec['displayName'] . ' — ' . substr($e->getMessage(), 0, 200));
            }
        }
        return $out;
    }

    private function buildAudienceBody(array $spec): array
    {
        // GA4 audience filter nesting is strict: andGroup can only
        // contain orGroup nodes, orGroup can only contain the
        // dimensionOrMetricFilter / eventFilter / notExpression leaves.
        // Wrap every leaf in both layers.
        $wrap = function (array $leaf): array {
            return [
                'andGroup' => [
                    'filterExpressions' => [[
                        'orGroup' => [
                            'filterExpressions' => [$leaf],
                        ],
                    ]],
                ],
            ];
        };
        $clauses = [[
            'clauseType' => 'INCLUDE',
            'simpleFilter' => [
                'scope' => 'AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS',
                'filterExpression' => $wrap($this->filterExpression($spec['filter'])),
            ],
        ]];
        if (!empty($spec['exclude'])) {
            $clauses[] = [
                'clauseType' => 'EXCLUDE',
                'simpleFilter' => [
                    'scope' => 'AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS',
                    'filterExpression' => $wrap($this->filterExpression($spec['exclude'])),
                ],
            ];
        }
        return [
            'displayName' => $spec['displayName'],
            'description' => $spec['description'],
            'membershipDurationDays' => $spec['days'],
            'filterClauses' => $clauses,
        ];
    }

    private function filterExpression(array $f): array
    {
        if (!empty($f['event'])) {
            return [
                'dimensionOrMetricFilter' => [
                    'fieldName' => 'eventName',
                    'stringFilter' => [
                        'matchType' => 'EXACT',
                        'value' => $f['event'],
                    ],
                ],
            ];
        }
        if (!empty($f['dimension'])) {
            return [
                'dimensionOrMetricFilter' => [
                    'fieldName' => $f['dimension'],
                    'stringFilter' => [
                        'matchType' => !empty($f['regex']) ? 'FULL_REGEXP' : 'EXACT',
                        'value' => $f['regex'] ?? $f['value'],
                    ],
                ],
            ];
        }
        throw new \InvalidArgumentException('Unknown audience filter shape');
    }

    // ───────────────────────────────────────────────────────────
    // HTTP / auth
    // ───────────────────────────────────────────────────────────

    private function api(string $method, string $path, ?array $body = null): array
    {
        $url = str_starts_with($path, 'http')
            ? $path
            : 'https://analyticsadmin.googleapis.com/v1beta/' . ltrim($path, '/');

        $req = Http::timeout(15)
            ->withHeaders(['Authorization' => 'Bearer ' . $this->accessToken])
            ->acceptJson();

        $resp = match ($method) {
            'GET'  => $req->get($url),
            'POST' => $body === null ? $req->send('POST', $url) : $req->asJson()->post($url, $body),
            'PATCH'=> $req->asJson()->patch($url, $body ?? []),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        if (!$resp->successful()) {
            Log::warning('Ga4Provisioner: API error', [
                'method' => $method, 'url' => $url, 'status' => $resp->status(),
                'body' => $body, 'resp' => $resp->body(),
            ]);
            throw new \RuntimeException("GA4 API {$method} {$url} → HTTP {$resp->status()}: " . $resp->body());
        }
        return $resp->json() ?? [];
    }

    private function authenticate(): void
    {
        $path = storage_path('app/private/ga4-service-account.json');
        if (!is_file($path)) {
            throw new \RuntimeException("Missing GA4 service account key at {$path}");
        }
        $sa = json_decode(file_get_contents($path), true);

        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claims = [
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/analytics.edit',
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
            throw new \RuntimeException('GA4 auth failed: ' . $resp->body());
        }
        $this->accessToken = $resp->json('access_token');
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
