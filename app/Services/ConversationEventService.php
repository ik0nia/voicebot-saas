<?php

namespace App\Services;

use App\Models\ChatEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Central service for tracking analytics events across all channels.
 *
 * Used by:
 * - ChatbotApiController (backend events: message_sent, products_returned, etc.)
 * - EventTrackingController (widget events: product_click, add_to_cart_click, etc.)
 * - WooCommerce webhooks (purchase_completed, checkout_started)
 * - RealtimeSession (voice events)
 *
 * Handles idempotency via unique idempotency_key — duplicate events silently ignored.
 */
class ConversationEventService
{
    /**
     * Track a single event with full context.
     *
     * @param string $eventName Must be from EventTaxonomy constants
     * @param array  $properties Event-specific data (product_id, price, reason, etc.)
     * @param array  $context Override auto-populated context keys
     * @return ChatEvent|null Null if duplicate (idempotency) or validation failure
     */
    public function track(string $eventName, array $properties = [], array $context = []): ?ChatEvent
    {
        if (!EventTaxonomy::isValid($eventName)) {
            Log::warning('ConversationEventService: invalid event name', ['event' => $eventName]);
            return null;
        }

        try {
            return ChatEvent::create([
                'tenant_id'       => $context['tenant_id'] ?? null,
                'bot_id'          => $context['bot_id'] ?? null,
                'channel_id'      => $context['channel_id'] ?? null,
                'conversation_id' => $context['conversation_id'] ?? null,
                'session_id'      => $context['session_id'] ?? null,
                'visitor_id'      => $context['visitor_id'] ?? null,
                'event_name'      => $eventName,
                'event_source'    => $context['event_source'] ?? EventTaxonomy::SOURCE_BACKEND,
                'properties'      => !empty($properties) ? $properties : null,
                'idempotency_key' => $context['idempotency_key'] ?? null,
                'product_id'      => $properties['product_id'] ?? null,
                'wc_order_id'     => $properties['wc_order_id'] ?? null,
                'occurred_at'     => $context['occurred_at'] ?? now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Duplicate event — silently skip (idempotency)
            return null;
        } catch (\Throwable $e) {
            Log::warning('ConversationEventService: failed to track event', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Track a batch of events from the widget.
     * Returns count of successfully inserted events.
     */
    public function trackBatch(array $events, int $tenantId): int
    {
        $inserted = 0;

        foreach ($events as $event) {
            $eventName = $event['event_name'] ?? '';
            if (!EventTaxonomy::isValid($eventName)) continue;

            $result = $this->track($eventName, $event['properties'] ?? [], [
                'tenant_id'       => $tenantId,
                'bot_id'          => $event['bot_id'] ?? null,
                'channel_id'      => $event['channel_id'] ?? null,
                'conversation_id' => $event['conversation_id'] ?? null,
                'session_id'      => $event['session_id'] ?? null,
                'visitor_id'      => $event['visitor_id'] ?? null,
                'event_source'    => EventTaxonomy::SOURCE_WIDGET,
                'idempotency_key' => $event['idempotency_key'] ?? null,
                'occurred_at'     => $event['occurred_at'] ?? now(),
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Build context array from conversation/session data.
     * Convenience method for backend event tracking.
     */
    public function buildContext(
        int $tenantId,
        ?int $botId = null,
        ?int $channelId = null,
        ?int $conversationId = null,
        ?string $sessionId = null,
        ?string $visitorId = null,
        string $source = EventTaxonomy::SOURCE_BACKEND,
    ): array {
        return array_filter([
            'tenant_id'       => $tenantId,
            'bot_id'          => $botId,
            'channel_id'      => $channelId,
            'conversation_id' => $conversationId,
            'session_id'      => $sessionId,
            'visitor_id'      => $visitorId,
            'event_source'    => $source,
        ], fn($v) => $v !== null);
    }

    /**
     * Generate a standard idempotency key.
     */
    public function idempotencyKey(string ...$parts): string
    {
        return implode(':', array_filter($parts));
    }

    /**
     * Get events for a conversation.
     */
    public function getConversationEvents(int $conversationId, ?string $eventName = null): \Illuminate\Support\Collection
    {
        $query = ChatEvent::where('conversation_id', $conversationId);
        if ($eventName) $query->where('event_name', $eventName);
        return $query->orderBy('occurred_at')->get();
    }

    /**
     * Count events for a conversation by type.
     */
    public function countEvents(int $conversationId, string $eventName): int
    {
        return ChatEvent::where('conversation_id', $conversationId)
            ->where('event_name', $eventName)
            ->count();
    }
}
