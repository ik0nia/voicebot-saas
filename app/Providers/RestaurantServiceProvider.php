<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Restaurant\MenuSearchService;
use App\Services\Restaurant\OrderToolDispatcher;
use App\Services\ToolRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the restaurant tools onto ToolRegistry.
 *
 * Kept separate from HospitalityServiceProvider (which owns table and room
 * reservations) because ordering food is a different capability with a
 * different lifecycle: a venue may take reservations without delivering, or
 * deliver without seating anyone.
 *
 * `search_menu` closes a gap that was live in production: config/niches.php
 * lists it under the restaurant niche's chat_tools and the restaurant prompt
 * instructs the model to use it, but no handler existed anywhere. Every
 * restaurant bot was being told to call a tool that was silently dropped from
 * its manifest — so it answered menu questions from whatever it could infer,
 * which for a menu means inventing dishes.
 */
class RestaurantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(ToolRegistry::class, function (ToolRegistry $registry, $app) {
            $menu = $app->make(MenuSearchService::class);

            $registry->register('search_menu', [
                'description' => 'Caută preparate în meniul restaurantului.',
                'parameters'  => [
                    'query'               => ['type' => 'string'],
                    'category'            => ['type' => 'string'],
                    'dietary'             => ['type' => 'array'],
                    'exclude_allergens'   => ['type' => 'array'],
                    'exclude_ingredients' => ['type' => 'array'],
                    'limit'               => ['type' => 'integer'],
                ],
                'handler' => fn (int $botId, array $params) => $menu->search($botId, $params),
            ]);

            /*
             * Ordering. Four tools rather than one because the basket is built
             * across turns and each turn carries one intent — and because the
             * chat path executes a single tool call per turn, so a combined
             * "do everything" tool would have to be called with a full order
             * every time and would lose whatever the model forgot to repeat.
             *
             * None of them takes a price. The model chooses dishes; PHP prices
             * them.
             */
            $orders = $app->make(OrderToolDispatcher::class);

            $registry->register('add_to_order', [
                'description' => 'Adaugă unul sau mai multe preparate în comanda clientului.',
                'parameters'  => [
                    'items' => ['type' => 'array', 'required' => true],
                ],
                'handler' => fn (int $botId, array $params) => $orders->addToOrder($botId, $params),
            ]);

            $registry->register('remove_from_order', [
                'description' => 'Șterge o poziție din comandă sau reduce cantitatea.',
                'parameters'  => [
                    'line_id'  => ['type' => 'integer', 'required' => true],
                    'quantity' => ['type' => 'integer'],
                ],
                'handler' => fn (int $botId, array $params) => $orders->removeFromOrder($botId, $params),
            ]);

            $registry->register('review_order', [
                'description' => 'Citește comanda curentă cu totalul calculat.',
                'parameters'  => [
                    'fulfilment'    => ['type' => 'string'],
                    'delivery_zone' => ['type' => 'string'],
                ],
                'handler' => fn (int $botId, array $params) => $orders->reviewOrder($botId, $params),
            ]);

            $registry->register('place_order', [
                'description' => 'Plasează comanda după confirmarea clientului.',
                'parameters'  => [
                    'fulfilment'       => ['type' => 'string', 'required' => true],
                    'customer_name'    => ['type' => 'string', 'required' => true],
                    'customer_phone'   => ['type' => 'string'],
                    'delivery_address' => ['type' => 'string'],
                    'delivery_zone'    => ['type' => 'string'],
                    'delivery_notes'   => ['type' => 'string'],
                    'payment_method'   => ['type' => 'string'],
                ],
                'handler' => fn (int $botId, array $params) => $orders->placeOrder($botId, $params),
            ]);

            return $registry;
        });
    }
}
