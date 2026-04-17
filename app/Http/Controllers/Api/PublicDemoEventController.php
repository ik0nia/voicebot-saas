<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use App\Support\NicheDemoResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public endpoint for niche-landing demo funnel events.
 *
 * Receives:
 *   - demo_viewed         (iframe loaded on /pentru/{niche})
 *   - demo_message_sent   (visitor sent a message in the demo)
 *   - demo_qualified      (3+ messages in the same demo session)
 *
 * Design notes:
 *   - No auth required; this fires from the public landing page.
 *   - Rate limit: 10 events/min per IP. The widget debounces so a
 *     normal visitor produces 1 demo_viewed + a handful of message
 *     events — 10 is well above legitimate usage.
 *   - Session dedupe happens client-side (first load sets a sambla
 *     demo-session cookie; duplicate demo_viewed beacons within the
 *     same session for the same niche are suppressed). Cross-niche
 *     visits DO emit a second demo_viewed, keyed by idempotency
 *     'session_id + niche'.
 *   - We only write to chat_events when the referenced channel
 *     actually belongs to the Sambla demo tenant. This stops a
 *     random caller from seeding demo events on a real tenant's
 *     analytics.
 */
class PublicDemoEventController extends Controller
{
    public function __construct(private readonly ConversationEventService $events) {}

    private const ALLOWED = [
        EventTaxonomy::DEMO_VIEWED,
        EventTaxonomy::DEMO_MESSAGE_SENT,
        EventTaxonomy::DEMO_QUALIFIED,
    ];

    public function store(Request $request): JsonResponse
    {
        $ip = $request->ip() ?? '0.0.0.0';
        $key = 'demo-event:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'Rate limited'], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'event_name'  => 'required|string|in:' . implode(',', self::ALLOWED),
            'niche_slug'  => 'required|string|max:80',
            'session_id'  => 'required|string|min:8|max:100',
            'landing_path'=> 'nullable|string|max:255',
            'properties'  => 'nullable|array',
        ]);

        // Resolve the demo channel for this niche — if it doesn't
        // exist, silently accept + noop. Landing pages that fire
        // events when the feature is "READY but not wired" should
        // not 500.
        $demo = NicheDemoResolver::forNiche($validated['niche_slug']);
        if (!$demo) {
            return response()->json(['accepted' => false, 'reason' => 'no_demo_configured']);
        }

        $properties = array_merge($validated['properties'] ?? [], [
            'niche_slug'   => $validated['niche_slug'],
            'landing_path' => $validated['landing_path'] ?? null,
        ]);

        // Idempotency: one record per (session, niche, event) so a
        // double-POST from a flaky network doesn't inflate counts.
        $idemp = sprintf(
            'demo:%s:%s:%s',
            $validated['event_name'],
            $validated['session_id'],
            $validated['niche_slug']
        );

        $tenantId = \App\Models\Bot::withoutGlobalScopes()
            ->where('id', $demo['bot_id'])
            ->value('tenant_id');

        $this->events->track(
            $validated['event_name'],
            $properties,
            [
                'tenant_id'       => $tenantId,
                'bot_id'          => $demo['bot_id'],
                'channel_id'      => $demo['channel_id'],
                'session_id'      => $validated['session_id'],
                'event_source'    => EventTaxonomy::SOURCE_WIDGET,
                'idempotency_key' => $idemp,
            ]
        );

        return response()->json(['accepted' => true]);
    }
}
