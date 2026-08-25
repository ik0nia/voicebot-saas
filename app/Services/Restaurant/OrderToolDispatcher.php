<?php

declare(strict_types=1);

namespace App\Services\Restaurant;

use App\Models\Bot;
use Illuminate\Support\Facades\Log;

/**
 * Entry point for the four ordering tools.
 *
 * Mirrors HospitalityToolDispatcher: resolve the bot, check it is allowed to
 * do this at all, normalise whatever shape the model sent, hand off. The
 * defence-in-depth engine check is repeated here even though the voice
 * controller already checks the manifest, because the chat path reaches
 * ToolRegistry by a different route and neither should be the only guard.
 */
class OrderToolDispatcher
{
    public function __construct(
        private OrderCartService $cart,
    ) {}

    /** @param  array<string, mixed>  $params */
    public function addToOrder(int $botId, array $params): array
    {
        return $this->withPolicy($botId, function (Bot $bot, $settings) use ($params) {
            /*
             * Accepts both shapes the model produces. Given a schema with an
             * `items` array it usually sends one; given "adaugă o pizza" it
             * sometimes flattens to a bare menu_item_id at the top level.
             * Rejecting the flat form would cost a round-trip on a live call
             * to fix something we can simply understand.
             */
            $items = $params['items'] ?? null;

            if (!is_array($items) || $items === []) {
                $items = isset($params['menu_item_id']) ? [$params] : [];
            }

            if ($items === []) {
                return [
                    'error'   => 'no_items',
                    'message' => 'Niciun preparat primit. Caută-l cu search_menu și trimite menu_item_id.',
                ];
            }

            return $this->cart->addItems($bot, $settings, array_values($items));
        });
    }

    /** @param  array<string, mixed>  $params */
    public function removeFromOrder(int $botId, array $params): array
    {
        return $this->withPolicy($botId, function (Bot $bot, $settings) use ($params) {
            $lineId = (int) ($params['line_id'] ?? 0);

            if ($lineId <= 0) {
                return [
                    'error'   => 'line_id_required',
                    'message' => 'Citește comanda cu review_order și folosește line_id-ul poziției de șters.',
                ];
            }

            $quantity = isset($params['quantity']) ? (int) $params['quantity'] : null;

            return $this->cart->removeItem($bot, $settings, $lineId, $quantity);
        });
    }

    /** @param  array<string, mixed>  $params */
    public function reviewOrder(int $botId, array $params): array
    {
        return $this->withPolicy($botId, fn (Bot $bot, $settings) => $this->cart->review(
            $bot,
            $settings,
            isset($params['fulfilment']) ? (string) $params['fulfilment'] : null,
            isset($params['delivery_zone']) ? (string) $params['delivery_zone'] : null,
        ));
    }

    /** @param  array<string, mixed>  $params */
    public function placeOrder(int $botId, array $params): array
    {
        return $this->withPolicy($botId, fn (Bot $bot, $settings) => $this->cart->place($bot, $settings, $params));
    }

    /**
     * Resolve the bot, confirm it takes orders, then run the handler.
     *
     * Every failure comes back as a payload with a `message` written for the
     * model to paraphrase, because all four of these run mid-conversation.
     * An exception here is silence on a live phone call.
     *
     * @param  callable(Bot, \App\Models\RestaurantSetting): array<string, mixed>  $handler
     * @return array<string, mixed>
     */
    private function withPolicy(int $botId, callable $handler): array
    {
        // Voice calls are unauthenticated, so the tenant scope would hide the
        // bot entirely. Scoping is enforced instead by every downstream query
        // filtering on this bot's id and tenant_id.
        $bot = Bot::withoutGlobalScopes()->find($botId);

        if ($bot === null) {
            return ['error' => 'bot_not_found'];
        }

        if (($bot->engine_type ?? null) !== 'hospitality') {
            Log::warning('OrderToolDispatcher: non-hospitality bot reached ordering tools', [
                'bot_id' => $botId,
                'engine' => $bot->engine_type ?? null,
            ]);

            return ['error' => 'bot not on hospitality engine'];
        }

        $policy = $this->cart->policy($botId);

        if (isset($policy['error'])) {
            return $policy;
        }

        return $handler($bot, $policy['settings']);
    }
}
