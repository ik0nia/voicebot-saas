<?php

namespace App\Services;

use App\Models\ChatEvent;
use App\Models\ConversationOutcome;
use App\Models\PurchaseAttribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Attributes WooCommerce purchases to chatbot conversations.
 *
 * Attribution modes:
 * - strict: Order placed during active session (session_id match from plugin)
 * - probable: Within attribution window AND product overlap with shown products
 * - assisted: Within window, products were shown but purchase may be indirect
 */
class AttributionService
{
    /**
     * Process a purchase webhook from the WordPress companion plugin.
     * Returns the created attribution or null if no match.
     */
    public function attributePurchase(array $payload, int $botId, int $tenantId): ?PurchaseAttribution
    {
        $wcOrderId = $payload['wc_order_id'] ?? '';
        $sessionId = $payload['session_id'] ?? '';
        $conversationId = $payload['conversation_id'] ?? null;
        $orderTotal = (float) ($payload['total'] ?? 0);
        $orderItems = $payload['items'] ?? [];
        $orderProductIds = array_column($orderItems, 'product_id');

        if (empty($wcOrderId)) return null;

        // Check for existing attribution (idempotent)
        $existing = PurchaseAttribution::where('wc_order_id', $wcOrderId)
            ->where('tenant_id', $tenantId)
            ->first();
        if ($existing) return $existing;

        // Mode 1: STRICT — direct session_id match from plugin attribution data
        if (!empty($sessionId)) {
            $conversation = DB::table('conversations')
                ->where('tenant_id', $tenantId)
                ->where('bot_id', $botId)
                ->where('external_conversation_id', $sessionId)
                ->first();

            if ($conversation) {
                return $this->createAttribution($tenantId, $botId, $conversation->id, $sessionId, $wcOrderId, $orderTotal, 'strict',
                    'Direct session match from companion plugin', 0, $orderItems, $orderProductIds);
            }
        }

        // Mode 2: PROBABLE — find conversations with product_click/add_to_cart events for these products
        $windowHours = config('product_search.attribution_window_hours', 24);
        $cutoff = now()->subHours($windowHours);

        $matchingEvents = ChatEvent::where('tenant_id', $tenantId)
            ->where('bot_id', $botId)
            ->whereIn('event_name', [EventTaxonomy::PRODUCT_CLICK, EventTaxonomy::ADD_TO_CART_SUCCESS])
            ->whereIn('product_id', $orderProductIds)
            ->where('occurred_at', '>=', $cutoff)
            ->orderByDesc('occurred_at')
            ->first();

        if ($matchingEvents && $matchingEvents->conversation_id) {
            return $this->createAttribution($tenantId, $botId, $matchingEvents->conversation_id, $matchingEvents->session_id,
                $wcOrderId, $orderTotal, 'probable',
                "Product click/ATC event within {$windowHours}h window", $windowHours, $orderItems, $orderProductIds);
        }

        // Mode 3: ASSISTED — product was shown (impression) but no click
        $impressionMatch = ChatEvent::where('tenant_id', $tenantId)
            ->where('bot_id', $botId)
            ->where('event_name', EventTaxonomy::PRODUCT_IMPRESSION)
            ->whereIn('product_id', $orderProductIds)
            ->where('occurred_at', '>=', $cutoff)
            ->orderByDesc('occurred_at')
            ->first();

        if ($impressionMatch && $impressionMatch->conversation_id) {
            return $this->createAttribution($tenantId, $botId, $impressionMatch->conversation_id, $impressionMatch->session_id,
                $wcOrderId, $orderTotal, 'assisted',
                "Product impression within {$windowHours}h, no direct click", $windowHours, $orderItems, $orderProductIds);
        }

        Log::info('AttributionService: no matching conversation for order', [
            'wc_order_id' => $wcOrderId, 'bot_id' => $botId,
        ]);
        return null;
    }

    private function createAttribution(int $tenantId, int $botId, int $conversationId, ?string $sessionId,
        string $wcOrderId, float $orderTotal, string $mode, string $reason, int $windowHours,
        array $orderItems, array $shownProductIds): PurchaseAttribution
    {
        $attribution = PurchaseAttribution::create([
            'tenant_id' => $tenantId,
            'bot_id' => $botId,
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'wc_order_id' => $wcOrderId,
            'order_total_cents' => (int) round($orderTotal * 100),
            'attribution_mode' => $mode,
            'attribution_reason' => $reason,
            'attribution_window_hours' => $windowHours,
            'products_in_order' => $orderItems,
            'products_shown_in_chat' => $shownProductIds,
        ]);

        // Create outcome
        ConversationOutcome::updateOrCreate(
            ['conversation_id' => $conversationId, 'outcome_type' => 'purchase_completed', 'product_id' => null],
            [
                'tenant_id' => $tenantId,
                'bot_id' => $botId,
                'session_id' => $sessionId,
                'confidence' => $mode,
                'attribution_reason' => $reason,
                'revenue_cents' => (int) round($orderTotal * 100),
                'wc_order_id' => $wcOrderId,
                'metadata' => ['items_count' => count($orderItems)],
            ]
        );

        // Track event
        app(ConversationEventService::class)->track(EventTaxonomy::PURCHASE_COMPLETED, [
            'wc_order_id' => $wcOrderId,
            'total' => $orderTotal,
            'attribution_mode' => $mode,
            'items_count' => count($orderItems),
        ], [
            'tenant_id' => $tenantId,
            'bot_id' => $botId,
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'event_source' => EventTaxonomy::SOURCE_WOOCOMMERCE,
            'idempotency_key' => "purchase:{$wcOrderId}",
        ]);

        return $attribution;
    }
}
