<?php

declare(strict_types=1);

namespace App\Services\Restaurant;

use App\Models\Bot;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantSetting;
use App\Services\ToolContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The open basket and everything that happens to it.
 *
 * One draft order per conversation, found by ToolContext::sessionRef(). Every
 * mutation re-prices the whole basket through OrderPricing and writes the
 * result back, so the stored totals and the lines can never disagree — an
 * order read back at the end of a call says the same number the customer
 * heard when they added the last item.
 *
 * Nothing here trusts the language model with arithmetic, with prices, or
 * with what is on the menu. The model supplies ids and quantities; this class
 * decides what those cost.
 */
class OrderCartService
{
    /** Sanity ceiling on a single line. Guards a mis-heard "două" → "douăzeci". */
    private const MAX_QUANTITY_PER_LINE = 50;

    public function __construct(
        private OrderPricing $pricing,
        private ToolContext $context,
    ) {}

    /**
     * Ordering policy for a bot, or an error payload explaining why there
     * isn't any.
     *
     * Returned as a payload rather than thrown because every caller is a tool
     * handler whose job is to tell the customer something useful. "Ordering
     * isn't set up" is an answer; an exception is dead air.
     *
     * @return array{settings: RestaurantSetting}|array{error: string, message: string}
     */
    public function policy(int $botId): array
    {
        $settings = RestaurantSetting::forBot($botId);

        if ($settings === null || !$settings->ordering_enabled) {
            return [
                'error'   => 'ordering_disabled',
                'message' => 'Acest local nu preia comenzi prin asistent. Nu promite livrare — oferă-te să dai numărul de telefon al localului sau să notezi o rezervare.',
            ];
        }

        return ['settings' => $settings];
    }

    /**
     * The open basket for this conversation, created on demand.
     *
     * Returns null when there is no session to key on — an unbound
     * ToolContext means we cannot tell one caller's basket from another's,
     * and sharing one would be worse than refusing.
     */
    public function draft(Bot $bot, RestaurantSetting $settings, bool $create = true): ?RestaurantOrder
    {
        $sessionRef = $this->context->sessionRef();
        if ($sessionRef === null) {
            Log::warning('OrderCartService: no session ref — tool context unbound', ['bot_id' => $bot->id]);
            return null;
        }

        $existing = RestaurantOrder::withoutGlobalScopes()
            ->with('items')
            ->where('bot_id', $bot->id)
            ->where('session_ref', $sessionRef)
            ->where('status', RestaurantOrder::STATUS_DRAFT)
            ->first();

        if ($existing !== null || !$create) {
            return $existing;
        }

        try {
            return RestaurantOrder::create([
                // Stamped from the bot, never from auth(): a voice call has no
                // authenticated user at all, and a super-admin's own tenant_id
                // is usually null. Getting this wrong hides the order from the
                // venue that is supposed to cook it.
                'tenant_id'       => $bot->tenant_id,
                'bot_id'          => $bot->id,
                'conversation_id' => $this->context->conversationId(),
                'call_id'         => $this->context->callId(),
                'session_ref'     => $sessionRef,
                'status'          => RestaurantOrder::STATUS_DRAFT,
                'currency'        => $settings->currency ?: 'RON',
                'customer_phone'  => $this->context->customerPhone(),
                'customer_name'   => $this->context->customerName(),
                'source'          => $this->context->channel(),
            ])->setRelation('items', collect());
        } catch (QueryException $e) {
            /*
             * Only a unique violation means "someone else opened this basket
             * first"; that one is recovered by re-reading, because two
             * baskets for one caller is exactly what the partial index
             * exists to prevent.
             *
             * Anything else — a foreign key pointing at a call row that no
             * longer exists, a column constraint — is a real fault, and
             * swallowing it here would surface as the misleading "I can't
             * remember your order" message while the actual cause stayed
             * invisible. Postgres 23505 = unique_violation.
             */
            if (($e->errorInfo[0] ?? null) !== '23505') {
                Log::error('OrderCartService: could not open draft order', [
                    'bot_id'      => $bot->id,
                    'session_ref' => $sessionRef,
                    'sqlstate'    => $e->errorInfo[0] ?? null,
                    'error'       => $e->getMessage(),
                ]);

                throw $e;
            }

            Log::info('OrderCartService: draft race, re-reading', [
                'bot_id' => $bot->id, 'session_ref' => $sessionRef,
            ]);

            return RestaurantOrder::withoutGlobalScopes()
                ->with('items')
                ->where('bot_id', $bot->id)
                ->where('session_ref', $sessionRef)
                ->where('status', RestaurantOrder::STATUS_DRAFT)
                ->first();
        }
    }

    /**
     * Add one or more dishes to the basket.
     *
     * Batched because the chat path executes exactly one tool call per turn
     * (ChatCompletionService::handleToolCalls takes the first and stops), so
     * "două pizza și o cola" has to arrive as one call or half of it is lost.
     *
     * Unknown ids and unavailable dishes are reported, not skipped. A basket
     * that quietly contains less than the caller asked for is discovered at
     * the door.
     *
     * @param  list<array{menu_item_id?: int|string, quantity?: int, options?: list<array{group?: string, choice?: string}>, notes?: string}>  $requested
     * @return array<string, mixed>
     */
    public function addItems(Bot $bot, RestaurantSetting $settings, array $requested): array
    {
        $order = $this->draft($bot, $settings);
        if ($order === null) {
            return $this->noSessionError();
        }

        $added = [];
        $rejected = [];

        /*
         * The basket's lines are held here for the whole batch so that merging
         * sees rows created earlier in this same call. "trei cola" split by
         * the model into three rows of one must end as one line of three, not
         * three lines the bot then reads back one by one.
         */
        $lines = RestaurantOrderItem::withoutGlobalScopes()
            ->where('restaurant_order_id', $order->id)
            ->get();

        foreach ($requested as $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemId = (int) ($row['menu_item_id'] ?? 0);
            if ($itemId <= 0) {
                $rejected[] = ['reason' => 'missing_id', 'detail' => 'Un preparat a fost cerut fără menu_item_id. Caută-l cu search_menu întâi.'];
                continue;
            }

            /** @var MenuItem|null $menuItem */
            $menuItem = MenuItem::withoutGlobalScopes()
                ->where('bot_id', $bot->id)   // scoping, not a filter: an id from another venue's menu must not resolve
                ->find($itemId);

            if ($menuItem === null) {
                $rejected[] = ['reason' => 'not_found', 'menu_item_id' => $itemId, 'detail' => 'Preparatul nu există în meniul acestui local.'];
                continue;
            }

            if (!$menuItem->isServableAt(Carbon::now())) {
                $rejected[] = [
                    'reason' => 'not_available',
                    'name'   => $menuItem->name,
                    'detail' => 'Preparatul nu se servește la ora aceasta.',
                ];
                continue;
            }

            $quantity = max(1, min(self::MAX_QUANTITY_PER_LINE, (int) ($row['quantity'] ?? 1)));

            $selections = array_values(array_filter(
                (array) ($row['options'] ?? []),
                'is_array',
            ));
            $resolved = $menuItem->resolveOptions($selections);

            // An unrecognised modifier is surfaced rather than dropped: it is
            // usually either a real option the venue spells differently, or
            // the caller asking for something the kitchen must be told about.
            if ($resolved['unknown'] !== []) {
                $rejected[] = [
                    'reason'  => 'unknown_options',
                    'name'    => $menuItem->name,
                    'options' => $resolved['unknown'],
                    'detail'  => 'Opțiuni necunoscute pentru acest preparat — confirmă cu clientul sau notează-le ca observație (notes).',
                ];
                continue;
            }

            $notes = $this->kitchenNotes($row['notes'] ?? null);

            /*
             * Two portions of one dish, prepared differently, are two lines.
             * Order 00016 arrived as a single line of two shaorma whose note
             * described both — garlic sauce on one, tzatziki on the other —
             * and the kitchen has no way to tell which is which. Refused here
             * rather than accepted, because the fix is one the model can make:
             * send a row per variant.
             */
            if ($quantity > 1 && $this->looksMultiVariant($notes)) {
                $rejected[] = [
                    'reason' => 'split_required',
                    'name'   => $menuItem->name,
                    'detail' => 'Bucățile au modificări diferite, deci nu pot sta pe aceeași poziție. '
                        . 'Trimite câte un rând separat, cu quantity 1, pentru fiecare variantă.',
                ];
                continue;
            }

            $unitPrice = $menuItem->unitPriceCents($selections);
            $optionsLabel = $this->optionsLabel($resolved['resolved']);
            $signature = $this->lineSignature($menuItem->id, $optionsLabel, $notes);

            /*
             * Same dish, same options, same note = same line. The model calls
             * add_to_order once per turn and callers add things gradually, so
             * without this the basket accumulates duplicates and the bot reads
             * back "un burger, încă doi burgeri și trei cola" — order 00015.
             */
            $line = $lines->first(
                fn (RestaurantOrderItem $l) => $this->lineSignature($l->menu_item_id, $l->options_label, $l->notes) === $signature
            );

            if ($line !== null) {
                $line->quantity = min(self::MAX_QUANTITY_PER_LINE, $line->quantity + $quantity);
                $line->line_total_cents = $line->unit_price_cents * $line->quantity;
                $line->save();
            } else {
                $line = RestaurantOrderItem::create([
                    'tenant_id'           => $bot->tenant_id,
                    'restaurant_order_id' => $order->id,
                    'menu_item_id'        => $menuItem->id,
                    'name_snapshot'       => $menuItem->name,
                    'unit_price_cents'    => $unitPrice,
                    'quantity'            => $quantity,
                    'line_total_cents'    => $unitPrice * $quantity,
                    'options'             => $resolved['resolved'] ?: null,
                    'options_label'       => $optionsLabel,
                    'notes'               => $notes,
                ]);

                $lines->push($line);
            }

            // What was added this turn, not what the line now holds: the bot
            // confirms the addition out loud, and "3 x Cola" after adding one
            // more to two is a sentence the caller will correct.
            $added[] = $this->spokenAddition($menuItem->name, $optionsLabel, $notes, $quantity);
        }

        $this->bumpCartVersion($order);
        $order = $this->reprice($order, $settings);

        return array_filter([
            'added'    => $added ?: null,
            'rejected' => $rejected ?: null,
            'order'    => $order->toToolPayload(),
            'guidance' => $rejected !== []
                ? 'Spune clientului ce nu s-a putut adăuga și de ce, nu trece peste.'
                : null,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Remove a line, or reduce its quantity.
     *
     * Identified by `line_id` from a previous payload. Matching by dish name
     * was considered and rejected — a basket with two differently-configured
     * pizzas of the same name would delete the wrong one, and the caller
     * would not find out until the food arrived.
     *
     * @return array<string, mixed>
     */
    public function removeItem(Bot $bot, RestaurantSetting $settings, int $lineId, ?int $quantity = null): array
    {
        $order = $this->draft($bot, $settings, create: false);
        if ($order === null) {
            return ['error' => 'empty_order', 'message' => 'Nu există o comandă în lucru.'];
        }

        /** @var RestaurantOrderItem|null $line */
        $line = RestaurantOrderItem::withoutGlobalScopes()
            ->where('restaurant_order_id', $order->id)
            ->find($lineId);

        if ($line === null) {
            return [
                'error'   => 'line_not_found',
                'message' => 'Poziția nu există în comandă. Citește comanda cu review_order și folosește line_id de acolo.',
                'order'   => $this->reprice($order, $settings)->toToolPayload(),
            ];
        }

        $removedLabel = $line->spokenLine();

        if ($quantity !== null && $quantity > 0 && $quantity < $line->quantity) {
            $line->quantity -= $quantity;
            $line->line_total_cents = $line->unit_price_cents * $line->quantity;
            $line->save();
            $removedLabel = "{$quantity} x {$line->name_snapshot}";
        } else {
            $line->delete();
        }

        $this->bumpCartVersion($order);

        return [
            'removed' => $removedLabel,
            'order'   => $this->reprice($order, $settings)->toToolPayload(),
        ];
    }

    /**
     * Read the basket back, priced for the fulfilment method being considered.
     *
     * Also the tool the bot should call before quoting anything, because the
     * delivery fee depends on the method and the zone, and neither is known
     * until the caller says.
     *
     * @return array<string, mixed>
     */
    public function review(Bot $bot, RestaurantSetting $settings, ?string $fulfilment = null, ?string $zone = null): array
    {
        $order = $this->draft($bot, $settings, create: false);

        if ($order === null || $order->items->isEmpty()) {
            return [
                'empty'   => true,
                'message' => 'Comanda este goală. Întreabă clientul ce dorește și adaugă cu add_to_order.',
                'options' => $this->fulfilmentOptions($settings),
            ];
        }

        if ($fulfilment !== null) {
            $order->fulfilment = $this->normaliseFulfilment($fulfilment, $settings);
        }
        if ($zone !== null) {
            $order->delivery_zone = $zone;
        }

        /*
         * Reading the basket back is what makes place_order legal — see the
         * gate in place(). Recorded against the cart version so that anything
         * added after this read-back invalidates it.
         */
        $meta = (array) ($order->metadata ?? []);
        $meta['reviewed_version'] = $this->cartVersion($order);
        $order->metadata = $meta;

        $order = $this->reprice($order, $settings);

        return [
            'order'   => $order->toToolPayload(),
            'pricing' => $this->pricing->toToolPayload($this->quoteFor($order, $settings)),
            'options' => $this->fulfilmentOptions($settings),
            'guidance' => 'Citește-i clientului comanda din spoken_summary, cu totalul exact de mai sus, '
                . 'apoi cere-i confirmarea explicită („Confirmați comanda?"). Nu apela place_order până nu spune da.',
        ];
    }

    /**
     * Turn the basket into a real order.
     *
     * The last gate before the venue owes someone food, so every precondition
     * is checked here rather than trusted from the conversation: the model may
     * be certain the caller gave an address when they did not.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function place(Bot $bot, RestaurantSetting $settings, array $params): array
    {
        $order = $this->draft($bot, $settings, create: false);

        if ($order === null || $order->items->isEmpty()) {
            return [
                'error'   => 'empty_order',
                'message' => 'Nu poți plasa o comandă goală. Adaugă preparatele întâi.',
            ];
        }

        $fulfilment = $this->normaliseFulfilment(
            (string) ($params['fulfilment'] ?? $order->fulfilment ?? ''),
            $settings,
        );

        if ($fulfilment === null) {
            return [
                'error'   => 'fulfilment_required',
                'message' => 'Întreabă clientul dacă dorește livrare sau ridicare personală.',
                'options' => $this->fulfilmentOptions($settings),
            ];
        }

        /*
         * The basket has to have been read back to the customer since the last
         * time it changed. The prompt already asks for an explicit
         * confirmation and the model skipped it on call 140 — it announced "vă
         * pregătesc comanda" and placed it — so the rule lives here, where it
         * cannot be talked out of.
         *
         * The gate is the cart version, not the fulfilment method or the
         * address: those change the total, but place() reports the final total
         * back and the bot states it when confirming the order number. Gating
         * on them too would cost a second tool round-trip on every order, and
         * a phone call is already paying 1,5–2,5 s for each one.
         */
        if (!$this->wasReviewedSinceLastChange($order)) {
            return [
                'error'   => 'review_required',
                'message' => 'Înainte de plasare, citește-i clientului comanda cu review_order '
                    . 'și cere-i confirmarea explicită. Comanda s-a modificat de la ultima citire.',
                'order'   => $this->reprice($order, $settings)->toToolPayload(),
            ];
        }

        $order->fulfilment = $fulfilment;

        $name = $this->customerName($params['customer_name'] ?? null);
        if ($name !== null) {
            $order->customer_name = $name;
        }

        $this->resolvePhone($order, $params);

        $order->customer_email = $this->trimOrNull($params['customer_email'] ?? $order->customer_email, 180);
        $order->delivery_notes = $this->trimOrNull($params['delivery_notes'] ?? $order->delivery_notes, 500);

        if ($fulfilment === RestaurantOrder::FULFILMENT_DELIVERY) {
            $order->delivery_address = $this->trimOrNull($params['delivery_address'] ?? $order->delivery_address, 500);
            $order->delivery_zone = $this->trimOrNull($params['delivery_zone'] ?? $order->delivery_zone, 120);
        }

        if ($missing = $this->missingFields($order, $settings)) {
            // Re-priced before returning so the model can keep talking about
            // an accurate total while it collects what is missing.
            $this->reprice($order, $settings);

            return [
                'error'          => 'missing_details',
                'missing'        => $missing,
                'message'        => 'Mai lipsesc informații. Cere-le clientului pe rând, nu toate deodată.',
                // The first missing field as a sentence to say. A bare field
                // list is what produced customer_name = "Client": the model
                // treated the schema as something to satisfy rather than
                // something to ask about.
                'ask'            => $this->askFor($missing[0], $order),
                'order'          => $order->toToolPayload(),
            ];
        }

        $payment = $this->normalisePayment((string) ($params['payment_method'] ?? ''), $settings);
        if ($payment === null) {
            return [
                'error'   => 'payment_method_required',
                'message' => 'Întreabă clientul cum plătește.',
                'accepted_payment_methods' => $settings->paymentMethods(),
            ];
        }
        $order->payment_method = $payment;

        $quote = $this->quoteFor($order, $settings);

        /*
         * Out of range is refused here rather than merely noted. The venue
         * delivers to a fixed list of places, and an order accepted for an
         * address outside it is one nobody will ever take out — the customer
         * waits, then calls back angry. Pickup is offered instead, because it
         * is the one thing that still works for them.
         */
        if ($quote['out_of_range']) {
            $this->applyQuote($order, $quote)->save();

            return [
                'error'            => 'out_of_delivery_range',
                'message'          => implode(' ', $quote['notes']),
                'delivers_only_to' => $quote['delivery_zone_names'],
                'pickup_available' => (bool) $settings->pickup_enabled,
                'order'            => $order->toToolPayload(),
            ];
        }

        // Checked at the gate, not when the basket was built: the caller may
        // have removed something after the last quote, and the venue's floor
        // applies to what is actually being sent out.
        if (!$quote['meets_minimum']) {
            $this->applyQuote($order, $quote)->save();

            return array_filter([
                'error'   => 'below_minimum',
                'message' => implode(' ', $quote['notes']),
                'missing_for_minimum' => $this->pricing->format($quote['missing_for_minimum_cents'], $quote['currency']),
                'order'   => $order->toToolPayload(),
            ], static fn ($v) => $v !== null && $v !== '');
        }

        $this->applyQuote($order, $quote);
        $order->status = RestaurantOrder::STATUS_PLACED;
        $order->placed_at = Carbon::now();
        $this->persistWithVenueNumber($order, $bot->id);

        Log::info('Restaurant order placed', [
            'order_id'   => $order->id,
            'bot_id'     => $bot->id,
            'tenant_id'  => $bot->tenant_id,
            'channel'    => $this->context->channel(),
            'total_cents'=> $order->total_cents,
            'fulfilment' => $order->fulfilment,
        ]);

        return array_filter([
            'placed'          => true,
            'order_reference' => $order->reference(),
            'order'           => $order->fresh('items')->toToolPayload(),
            'order_notice'    => $settings->order_notice,
            // Spelled out for the model to read verbatim — a reference read
            // as "one thousand two hundred" instead of digit-by-digit is one
            // the customer cannot repeat back.
            'guidance'        => sprintf(
                'Confirmă clientului: numărul comenzii %s (citește cifră cu cifră), totalul %s, timpul estimat %d minute.',
                $order->reference(),
                $order->formattedTotal(),
                (int) $order->estimated_minutes,
            ),
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Recompute and persist the basket's totals from its lines.
     *
     * Called after every mutation. Cheap (a menu basket is a handful of rows)
     * and the alternative — incremental adjustment — is how totals drift out
     * of step with lines.
     */
    private function reprice(RestaurantOrder $order, RestaurantSetting $settings): RestaurantOrder
    {
        $order->load('items');
        $this->applyQuote($order, $this->quoteFor($order, $settings));
        $order->save();

        return $order;
    }

    /** @return array<string, mixed> */
    private function quoteFor(RestaurantOrder $order, RestaurantSetting $settings): array
    {
        return $this->pricing->quote(
            $settings,
            $order->relationLoaded('items') ? $order->items : $order->items()->get(),
            // Delivery is assumed while the method is still unknown so the
            // number quoted mid-conversation is the higher one. A total that
            // goes down when the caller chooses pickup is a pleasant surprise;
            // one that goes up is an argument.
            $order->fulfilment ?? RestaurantOrder::FULFILMENT_DELIVERY,
            /*
             * The zone is matched against the address when no zone was named
             * separately. Callers give one string — "Str. Republicii 12,
             * Gherla" — and rarely answer a separate "which neighbourhood?".
             * Matching only an explicit zone field would let an address well
             * outside the delivery area pass unchecked, which for a venue
             * that delivers to one town is the whole point of the check.
             */
            $order->delivery_zone ?: $order->delivery_address,
        );
    }

    /** @param  array<string, mixed>  $quote */
    private function applyQuote(RestaurantOrder $order, array $quote): RestaurantOrder
    {
        $order->subtotal_cents     = $quote['subtotal_cents'];
        $order->delivery_fee_cents = $quote['delivery_fee_cents'];
        $order->total_cents        = $quote['total_cents'];
        $order->currency           = $quote['currency'];
        $order->estimated_minutes  = $quote['estimated_minutes'];

        if ($quote['zone'] !== null) {
            $order->delivery_zone = $quote['zone'];
        }

        return $order;
    }

    /** @return list<string> */
    private function missingFields(RestaurantOrder $order, RestaurantSetting $settings): array
    {
        $missing = [];

        if ($this->trimOrNull($order->customer_name, 180) === null) {
            $missing[] = 'customer_name';
        }

        // A phone number is the only way the venue can call back about a
        // wrong address or a sold-out dish, so it is required even for pickup.
        if ($this->trimOrNull($order->customer_phone, 32) === null) {
            $missing[] = 'customer_phone';
        }

        if ($order->isDelivery() && $this->trimOrNull($order->delivery_address, 500) === null) {
            $missing[] = 'delivery_address';
        }

        return $missing;
    }

    /** @return array<string, mixed> */
    private function fulfilmentOptions(RestaurantSetting $settings): array
    {
        return array_filter([
            'delivery' => $settings->delivery_enabled ? [
                'fee'               => $this->pricing->format((int) $settings->delivery_fee_cents, $settings->currency),
                'min_order'         => $settings->min_order_cents > 0
                    ? $this->pricing->format((int) $settings->min_order_cents, $settings->currency)
                    : null,
                'free_over'         => $settings->free_delivery_threshold_cents !== null
                    ? $this->pricing->format((int) $settings->free_delivery_threshold_cents, $settings->currency)
                    : null,
                'estimated_minutes' => (int) $settings->delivery_minutes,
                'zones'             => $settings->zoneNames() ?: null,
                // Stated plainly so the bot can say "we only deliver in X"
                // up front, instead of discovering it at the address step.
                'delivers_only_to_zones' => $settings->deliversOnlyToZones() ?: null,
            ] : null,
            'pickup' => $settings->pickup_enabled ? [
                'estimated_minutes' => (int) $settings->pickup_minutes,
            ] : null,
            'payment_methods' => $settings->paymentMethods(),
            'notice'          => $settings->order_notice,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Map what the model said to a method the venue actually offers.
     *
     * Returns null when the venue does not offer it, so the caller is told
     * "we only do pickup" rather than being booked for a delivery that will
     * never leave.
     */
    private function normaliseFulfilment(string $value, RestaurantSetting $settings): ?string
    {
        $value = $this->fold($value);

        $wantsPickup = $value !== '' && (
            str_contains($value, 'pickup') || str_contains($value, 'ridic') || str_contains($value, 'preiau')
            || str_contains($value, 'local') || str_contains($value, 'takeaway')
        );
        $wantsDelivery = $value !== '' && (
            str_contains($value, 'deliver') || str_contains($value, 'livr') || str_contains($value, 'acasa')
            || str_contains($value, 'domiciliu')
        );

        if ($wantsPickup && $settings->pickup_enabled) {
            return RestaurantOrder::FULFILMENT_PICKUP;
        }
        if ($wantsDelivery && $settings->delivery_enabled) {
            return RestaurantOrder::FULFILMENT_DELIVERY;
        }

        // Nothing intelligible was said. If the venue only does one of the
        // two there is nothing to ask about, so choose it.
        if ($value === '') {
            if ($settings->delivery_enabled && !$settings->pickup_enabled) {
                return RestaurantOrder::FULFILMENT_DELIVERY;
            }
            if ($settings->pickup_enabled && !$settings->delivery_enabled) {
                return RestaurantOrder::FULFILMENT_PICKUP;
            }
        }

        return null;
    }

    private function normalisePayment(string $value, RestaurantSetting $settings): ?string
    {
        $accepted = $settings->paymentMethods();
        $value = $this->fold($value);

        if ($value === '' && count($accepted) === 1) {
            return $accepted[0];
        }

        $guess = match (true) {
            str_contains($value, 'cash') || str_contains($value, 'numerar') || str_contains($value, 'lichid')
                => RestaurantSetting::PAYMENT_CASH,
            str_contains($value, 'card') || str_contains($value, 'pos')
                => RestaurantSetting::PAYMENT_CARD_ON_DELIVERY,
            default => null,
        };

        return $guess !== null && in_array($guess, $accepted, true) ? $guess : null;
    }

    /** @param  list<array{group: string, choice: string, price_delta_cents: int}>  $resolved */
    private function optionsLabel(array $resolved): ?string
    {
        if ($resolved === []) {
            return null;
        }

        return mb_substr(implode(', ', array_column($resolved, 'choice')), 0, 255);
    }

    /**
     * What the model should say to collect one missing field.
     *
     * One field per call, phrased as a sentence rather than a field name: a
     * model handed `missing: ["customer_name"]` and nothing else will fill the
     * gap itself if asking feels like an interruption.
     */
    private function askFor(string $field, RestaurantOrder $order): string
    {
        return match ($field) {
            'customer_name' => 'Întreabă pe ce nume notezi comanda'
                . $this->phoneConfirmationClause($order)
                . '. Nu trece mai departe fără un nume real și nu inventa unul.',
            'customer_phone' => 'Numărul nu a venit din apel. Cere-i clientului un număr de telefon '
                . 'și repetă-l înapoi cifră cu cifră ca să-l confirme.',
            'delivery_address' => 'Cere adresa completă de livrare: strada, numărul, blocul, scara și apartamentul, '
                . 'plus cartierul dacă îl știe.',
            default => 'Cere-i clientului informația care lipsește.',
        };
    }

    /**
     * The "and is this number good for you?" half of the name question.
     *
     * Folded into the same breath as the name deliberately. Asked as its own
     * tool round-trip it would cost the caller another 1,5–2,5 s of silence,
     * and it is the one question that has a default good answer already on
     * screen — the line they are calling from.
     */
    private function phoneConfirmationClause(RestaurantOrder $order): string
    {
        if (!$this->context->isVoice() || $order->customer_phone === null) {
            return '';
        }

        if ((bool) (($order->metadata['phone_confirmed'] ?? false)) === true) {
            return '';
        }

        return ', iar în aceeași replică confirmă numărul de contact: „vă sunăm pe '
            . $this->spokenPhone($order->customer_phone)
            . ', e bun?" (citește-l cifră cu cifră). Dacă spune da, trimite phone_confirmed: true; '
            . 'dacă dă alt număr, trimite-l ca customer_phone';
    }

    /** National form, grouped so a read-back does not run the digits together. */
    private function spokenPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        // +40 7xx… → 07xx…, which is the form a Romanian caller recognises.
        if (str_starts_with($digits, '40') && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }

        return strlen($digits) === 10
            ? trim(substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7))
            : $digits;
    }

    /**
     * The name to store, or null when the model made one up.
     *
     * Both real orders so far were taken under the name "Client" — a required
     * schema field the bot never asked about, so it filled it in to make the
     * call valid. Dropping the placeholder here turns that into a question.
     */
    private function customerName(mixed $value): ?string
    {
        $name = $this->trimOrNull($value, 180);

        if ($name === null) {
            return null;
        }

        return $this->isPlaceholderName($name) ? null : $name;
    }

    private function isPlaceholderName(string $name): bool
    {
        $folded = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z ]/', ' ', $this->fold($name)) ?? '') ?? '');

        if (mb_strlen($folded) < 2) {
            return true;
        }

        return in_array($folded, [
            'client', 'clientul', 'clienta', 'client nou', 'client necunoscut', 'clientul nostru',
            'anonim', 'anonima', 'necunoscut', 'fara nume', 'nume', 'nume client', 'numele clientului',
            'domn', 'domnul', 'doamna', 'persoana', 'utilizator', 'test', 'x',
            'customer', 'unknown', 'user', 'guest', 'name', 'n a', 'na', 'nn', 'null',
        ], true);
    }

    /**
     * Notes the kitchen can act on, with the model's own commentary removed.
     *
     * Order 00016 went to the kitchen carrying "Observație: ingredientele și
     * sosurile sunt preluate din solicitarea clientului; meniu confirmat doar
     * pentru lipie" — the model recording its own uncertainty in a field that
     * gets printed. A note is an instruction for whoever cooks the dish;
     * anything else is dropped.
     */
    private function kitchenNotes(mixed $value): ?string
    {
        $notes = $this->trimOrNull($value, 255);

        if ($notes === null) {
            return null;
        }

        $kept = [];

        foreach (preg_split('/(?<=[.;!?])\s+/u', $notes) ?: [] as $sentence) {
            $sentence = trim($sentence);

            if ($sentence === '' || $this->isModelCommentary($sentence)) {
                continue;
            }

            $kept[] = $sentence;
        }

        if ($kept === []) {
            return null;
        }

        return $this->trimOrNull(rtrim(implode(' ', $kept), ' ;,'), 255);
    }

    private function isModelCommentary(string $sentence): bool
    {
        $folded = $this->fold($sentence);

        foreach ([
            'observatie', 'nota:', 'notă:', 'mentionez', 'mentiune', 'precizez', 'precizare',
            'meniu confirmat', 'confirmat doar', 'preluate din', 'preluat din', 'conform solicitarii',
            'conform cererii', 'nu am putut', 'nu apare in meniu', 'nu figureaza', 'nu exista in meniu',
            'presupun', 'probabil', 'neclar', 'de verificat cu', 'ingredientele sunt', 'sosurile sunt',
        ] as $marker) {
            if (str_contains($folded, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether one note is describing several different portions.
     *
     * Deliberately narrow, and only ever consulted when quantity > 1: "una cu
     * usturoi" on a single dish is an ordinary instruction, the same words on
     * two are a line that has to be split.
     */
    private function looksMultiVariant(?string $notes): bool
    {
        if ($notes === null) {
            return false;
        }

        $folded = $this->fold($notes);

        foreach ([
            'una cu', 'unul cu', 'prima cu', 'primul cu', 'a doua', 'al doilea', 'cealalta', 'celalalt',
            'iar cealalta', 'restul cu', 'una fara', 'unul fara', 'una simpla', 'unul simplu',
        ] as $marker) {
            if (str_contains($folded, $marker)) {
                return true;
            }
        }

        return false;
    }

    /** Identity of a basket line: same dish, same options, same note. */
    private function lineSignature(?int $menuItemId, ?string $optionsLabel, ?string $notes): string
    {
        return implode('|', [
            (int) $menuItemId,
            $this->fold((string) $optionsLabel),
            $this->fold((string) $notes),
        ]);
    }

    /** What was added this turn, phrased for the bot to confirm out loud. */
    private function spokenAddition(string $name, ?string $optionsLabel, ?string $notes, int $quantity): string
    {
        $line = $quantity > 1 ? "{$quantity} x {$name}" : $name;

        if ($optionsLabel) {
            $line .= " ({$optionsLabel})";
        }

        if ($notes) {
            $line .= ", {$notes}";
        }

        return $line;
    }

    private function cartVersion(RestaurantOrder $order): int
    {
        return (int) (($order->metadata['cart_version'] ?? 0));
    }

    /** Every basket mutation invalidates the last read-back. */
    private function bumpCartVersion(RestaurantOrder $order): void
    {
        $meta = (array) ($order->metadata ?? []);
        $meta['cart_version'] = $this->cartVersion($order) + 1;
        $order->metadata = $meta;
    }

    private function wasReviewedSinceLastChange(RestaurantOrder $order): bool
    {
        $meta = (array) ($order->metadata ?? []);

        return array_key_exists('reviewed_version', $meta)
            && (int) $meta['reviewed_version'] === $this->cartVersion($order);
    }

    /**
     * Caller ID by default, what the customer dictated when they mean it.
     *
     * Telephony's number beats transcribed speech, because dictated digits do
     * not survive an 8 kHz line. It is not the last word, though: someone
     * ordering from the office for delivery at home gives another number on
     * purpose, and overwriting it with the line they happened to ring from is
     * how a courier ends up calling an empty desk. The number they rang from
     * is kept in metadata either way, so the venue can still reach them.
     *
     * @param  array<string, mixed>  $params
     */
    private function resolvePhone(RestaurantOrder $order, array $params): void
    {
        $callerId = $this->context->customerPhone();
        $dictated = ToolContext::normalisePhone($this->trimOrNull($params['customer_phone'] ?? null, 32));

        $meta = (array) ($order->metadata ?? []);

        if ($callerId !== null) {
            $meta['caller_id'] = $callerId;
        }

        if ($dictated !== null && !$this->sameNumber($dictated, $callerId)) {
            $order->customer_phone = $dictated;
            $meta['phone_source'] = 'dictated';
            // Dictating a different number is itself the confirmation: the
            // customer was asked and answered with a correction.
            $meta['phone_confirmed'] = true;
        } elseif ($order->customer_phone === null) {
            $order->customer_phone = $callerId ?? $dictated;
            $meta['phone_source'] = $callerId !== null ? 'caller_id' : 'dictated';
        } else {
            $meta['phone_source'] ??= $callerId !== null ? 'caller_id' : 'dictated';
        }

        if (filter_var($params['phone_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $meta['phone_confirmed'] = true;
        }

        $meta['phone_confirmed'] ??= false;

        $order->metadata = $meta;
    }

    /** Same person, whether or not they said the country code. */
    private function sameNumber(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        $tail = static fn (string $n): string => substr(preg_replace('/\D/', '', $n) ?? '', -9);

        return $tail($a) !== '' && $tail($a) === $tail($b);
    }

    /**
     * Save a placed order with a per-venue number.
     *
     * The reference the customer writes down used to be the table's global id,
     * so Urban Doner's first ever order was number 00015 — fifteen orders that
     * belonged to other venues. Numbering restarts per bot; the unique index
     * settles a race between two calls placing at the same moment, and the
     * retry re-reads rather than guessing again.
     */
    private function persistWithVenueNumber(RestaurantOrder $order, int $botId): void
    {
        if ($order->order_number !== null) {
            $order->save();

            return;
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $order->order_number = $this->nextOrderNumber($botId);

            try {
                $order->save();

                return;
            } catch (QueryException $e) {
                if (($e->errorInfo[0] ?? null) !== '23505' || $attempt === 3) {
                    // Losing the number is not worth losing the order: fall
                    // back to the id-derived reference, which is what every
                    // order used before this existed.
                    Log::warning('Restaurant order: could not assign venue number', [
                        'bot_id' => $botId,
                        'error'  => $e->getMessage(),
                    ]);

                    $order->order_number = null;
                    $order->save();

                    return;
                }
            }
        }
    }

    private function nextOrderNumber(int $botId): int
    {
        $last = RestaurantOrder::withoutGlobalScopes()
            ->where('bot_id', $botId)
            ->whereNotNull('order_number')
            ->orderByDesc('order_number')
            ->value('order_number');

        return ((int) $last) + 1;
    }

    /** @return array<string, mixed> */
    private function noSessionError(): array
    {
        return [
            'error'   => 'no_session',
            'message' => 'Nu pot ține minte comanda în acest context. Notează ce dorește clientul și transferă la un coleg.',
        ];
    }

    private function trimOrNull(mixed $value, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        ]);
    }
}
