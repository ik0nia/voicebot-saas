<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Lead;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Receives analytics events from the chat widget.
 *
 * Events are tracked with idempotency — duplicates are silently ignored.
 * All events are validated against EventTaxonomy before storage.
 */
class EventTrackingController extends Controller
{
    public function __construct(
        private readonly ConversationEventService $events,
    ) {}

    /**
     * POST /api/chatbot/{channelId}/events
     *
     * Receives a batch of events from the widget.
     * Payload: { events: [{event_name, properties, session_id, visitor_id, ...}] }
     */
    public function trackBatch(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Invalid channel'], 404);
        }

        // Rate limit: 60 event batches per minute per IP
        $rateLimitKey = 'events:' . $request->ip() . ':' . $channelId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            return response()->json(['error' => 'Rate limited'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $validated = $request->validate([
            'events' => 'required|array|max:50',
            'events.*.event_name' => 'required|string|max:80',
            'events.*.properties' => 'nullable|array',
            'events.*.session_id' => 'nullable|string|max:100',
            'events.*.visitor_id' => 'nullable|string|max:100',
            'events.*.conversation_id' => 'nullable|integer',
            'events.*.idempotency_key' => 'nullable|string|max:120',
            'events.*.occurred_at' => 'nullable|date',
        ]);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot) {
            return response()->json(['error' => 'Bot not found'], 404);
        }

        // Inject channel/bot context into each event
        $events = array_map(function ($event) use ($channel, $bot) {
            $event['bot_id'] = $bot->id;
            $event['channel_id'] = $channel->id;
            return $event;
        }, $validated['events']);

        $inserted = $this->events->trackBatch($events, $bot->tenant_id);

        // V2: If session_ended was in this batch, mark conversation completed + trigger outcome derivation
        foreach ($events as $event) {
            if (($event['event_name'] ?? '') === 'session_ended' && !empty($event['conversation_id'])) {
                $conversationId = (int) $event['conversation_id'];

                // Mark conversation as completed if it exists and isn't already completed
                \App\Models\Conversation::withoutGlobalScopes()
                    ->where('id', $conversationId)
                    ->where('status', '!=', 'completed')
                    ->update([
                        'status' => 'completed',
                        'ended_at' => now(),
                    ]);

                \App\Jobs\DeriveConversationOutcomes::dispatch($conversationId)
                    ->delay(now()->addSeconds(10)); // slight delay to ensure all events are stored
                break; // only once per batch
            }
        }

        return response()->json([
            'tracked' => $inserted,
            'total' => count($events),
        ]);
    }

    /**
     * GET /api/chatbot/{channelId}/capabilities
     *
     * Returns bot capabilities for the widget to adapt its UI.
     */
    public function capabilities($channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Invalid channel'], 404);
        }

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot) {
            return response()->json(['error' => 'Bot not found'], 404);
        }

        $wcCapabilities = $bot->woocommerce_capabilities ?? [];
        $hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->exists();

        return response()->json([
            'has_products' => $hasProducts,
            'cart_enabled' => (bool) ($wcCapabilities['cart_enabled'] ?? false),
            'order_lookup_enabled' => (bool) ($wcCapabilities['order_lookup_enabled'] ?? true),
            'lead_enabled' => true, // Phase 3 will make this tenant-configurable
            'handoff_enabled' => true,
            'voice_enabled' => $channel->type === 'voice',
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * POST /api/v1/chatbot/{channelId}/lead
     *
     * Captures lead data submitted from the widget.
     * Supports partial leads (only some fields provided).
     */
    public function captureLead(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()->where('id', $channelId)->where('is_active', true)->first();
        if (!$channel) return response()->json(['error' => 'Invalid channel'], 404);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot) return response()->json(['error' => 'Bot not found'], 404);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'project_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'consent' => 'required|boolean',
            'session_id' => 'nullable|string|max:100',
            'conversation_id' => 'nullable|integer',
            'visitor_id' => 'nullable|string|max:100',
        ]);

        if (!$validated['consent']) {
            return response()->json(['error' => 'Consent required'], 422);
        }

        // Find or create lead for this conversation
        $lead = Lead::updateOrCreate(
            [
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $validated['conversation_id'] ?? null,
            ],
            [
                'session_id' => $validated['session_id'] ?? null,
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'project_type' => $validated['project_type'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'gdpr_consent' => true,
                'capture_source' => 'chat',
                'capture_reason' => 'widget_form',
                'status' => ($validated['email'] || $validated['phone']) ? 'qualified' : 'partial',
                'qualification_score' => ($validated['email'] ? 30 : 0) + ($validated['phone'] ? 20 : 0) + ($validated['name'] ? 10 : 0),
            ]
        );

        // Track event
        $this->events->track(EventTaxonomy::LEAD_COMPLETED, [
            'lead_id' => $lead->id,
            'fields_count' => count(array_filter([$validated['name'], $validated['email'], $validated['phone']])),
            'qualification_score' => $lead->qualification_score,
        ], [
            'tenant_id' => $bot->tenant_id,
            'bot_id' => $bot->id,
            'conversation_id' => $validated['conversation_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'event_source' => EventTaxonomy::SOURCE_WIDGET,
            'idempotency_key' => "lead_completed:{$lead->id}",
        ]);

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'message' => 'Mulțumim! Datele au fost salvate.',
        ]);
    }
}
