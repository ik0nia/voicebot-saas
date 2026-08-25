<?php

declare(strict_types=1);

namespace App\Services\Restaurant;

use App\Models\RestaurantOrder;
use App\Models\RestaurantSetting;

/**
 * Every sum, fee and threshold comparison in the ordering flow.
 *
 * Isolated in one class with no database writes and no model mutation so the
 * arithmetic can be read in one sitting and tested without a fixture. This is
 * the class the whole vertical exists to protect: the language model may
 * describe what these numbers mean, and may never produce them.
 *
 * Integer cents throughout. No float ever touches a total — `0.1 + 0.2` is
 * not `0.3`, and a restaurant that is one bani off on every order notices at
 * the end of the month.
 */
class OrderPricing
{
    /**
     * Price a basket against the venue's rules.
     *
     * Returns the figures *and* the reasoning behind them, because the useful
     * thing to say on a call is rarely the total on its own — it is "mai
     * adaugi 12 lei și livrarea e gratuită". The model cannot work that out
     * (it would have to subtract), so the subtraction is done here and handed
     * over as a finished sentence.
     *
     * @param  list<array{line_total_cents: int}>|\Illuminate\Support\Collection  $lines
     * @return array{
     *     subtotal_cents: int,
     *     delivery_fee_cents: int,
     *     total_cents: int,
     *     currency: string,
     *     zone: ?string,
     *     min_order_cents: int,
     *     meets_minimum: bool,
     *     missing_for_minimum_cents: int,
     *     free_delivery_applied: bool,
     *     missing_for_free_delivery_cents: ?int,
     *     estimated_minutes: ?int,
     *     out_of_range: bool,
     *     delivery_zone_names: list<string>,
     *     notes: list<string>
     * }
     */
    public function quote(
        RestaurantSetting $settings,
        iterable $lines,
        string $fulfilment = RestaurantOrder::FULFILMENT_DELIVERY,
        ?string $spokenZone = null,
    ): array {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += (int) (is_array($line) ? ($line['line_total_cents'] ?? 0) : $line->line_total_cents);
        }

        $isDelivery = $fulfilment === RestaurantOrder::FULFILMENT_DELIVERY;
        $zone = $isDelivery ? $settings->matchZone($spokenZone) : null;

        /*
         * A venue that delivers only to listed zones treats an unmatched
         * address as out of range rather than as un-zoned. `null` (nothing
         * said yet) is not out of range — the address simply has not been
         * given, and refusing before asking would end the conversation.
         */
        $outOfRange = $isDelivery
            && $settings->deliversOnlyToZones()
            && $spokenZone !== null && trim($spokenZone) !== ''
            && $zone === null;

        // A named zone may charge differently and may demand more. Falling
        // back to the flat values when no zone matched is deliberate: an
        // address we do not recognise still gets a price, and the venue sorts
        // out the geography when they see the order.
        $baseFee = $zone !== null ? $zone['fee_cents'] : (int) $settings->delivery_fee_cents;
        $minOrder = $zone !== null && $zone['min_order_cents'] !== null
            ? $zone['min_order_cents']
            : (int) $settings->min_order_cents;

        $threshold = $settings->free_delivery_threshold_cents;

        // The comparison this whole class exists for. `>=` because a
        // threshold advertised as "livrare gratuită peste 100 lei" is
        // understood by customers as "at 100 lei it's free", and arguing the
        // point at the door costs more than the delivery fee.
        $freeDelivery = $isDelivery && $threshold !== null && $subtotal >= $threshold;

        $deliveryFee = $isDelivery && !$freeDelivery ? $baseFee : 0;

        $notes = [];
        $missingForFree = null;

        if ($isDelivery && $threshold !== null && !$freeDelivery) {
            $missingForFree = $threshold - $subtotal;
            $notes[] = sprintf(
                'Mai are nevoie de %s pentru livrare gratuită.',
                $this->format($missingForFree, $settings->currency),
            );
        }

        if ($freeDelivery) {
            $notes[] = 'Livrarea este gratuită la această valoare.';
        }

        // Minimum applies to the food, not to the food plus the fee —
        // charging someone a delivery fee and then counting it toward the
        // minimum they must spend is the kind of thing customers notice.
        $meetsMinimum = !$isDelivery || $minOrder === 0 || $subtotal >= $minOrder;
        $missingForMinimum = $meetsMinimum ? 0 : $minOrder - $subtotal;

        if (!$meetsMinimum) {
            $notes[] = sprintf(
                'Comanda minimă pentru livrare este %s — mai lipsesc %s.',
                $this->format($minOrder, $settings->currency),
                $this->format($missingForMinimum, $settings->currency),
            );
        }

        if ($outOfRange) {
            $notes[] = sprintf(
                'Adresa este în afara zonei de livrare. Se livrează doar în: %s. Propune ridicare personală.',
                implode(', ', $settings->zoneNames()),
            );
        }

        return [
            'out_of_range'       => $outOfRange,
            'delivery_zone_names'=> $settings->zoneNames(),
            'subtotal_cents'     => $subtotal,
            'delivery_fee_cents' => $deliveryFee,
            'total_cents'        => $subtotal + $deliveryFee,
            'currency'           => $settings->currency ?: 'RON',
            'zone'               => $zone !== null ? $zone['name'] : null,
            'min_order_cents'    => $minOrder,
            'meets_minimum'      => $meetsMinimum,
            'missing_for_minimum_cents' => $missingForMinimum,
            'free_delivery_applied'     => $freeDelivery,
            'missing_for_free_delivery_cents' => $missingForFree,
            'estimated_minutes'  => $isDelivery
                ? (int) $settings->delivery_minutes
                : (int) $settings->pickup_minutes,
            'notes' => $notes,
        ];
    }

    /**
     * The same quote, shaped for the model: money as text it reads aloud
     * verbatim, plus the cents alongside for anything that has to compare.
     *
     * @param  array<string, mixed>  $quote
     * @return array<string, mixed>
     */
    public function toToolPayload(array $quote): array
    {
        $currency = $quote['currency'];

        return array_filter([
            'subtotal'       => $this->format($quote['subtotal_cents'], $currency),
            'subtotal_cents' => $quote['subtotal_cents'],
            'delivery_fee'   => $this->format($quote['delivery_fee_cents'], $currency),
            'delivery_fee_cents' => $quote['delivery_fee_cents'],
            'total'          => $this->format($quote['total_cents'], $currency),
            'total_cents'    => $quote['total_cents'],
            'zone'           => $quote['zone'],
            'estimated_minutes' => $quote['estimated_minutes'],
            'meets_minimum'  => $quote['meets_minimum'],
            'out_of_range'   => $quote['out_of_range'] ?: null,
            'delivers_only_to' => $quote['out_of_range'] ? $quote['delivery_zone_names'] : null,
            'free_delivery_applied' => $quote['free_delivery_applied'],
            // Pre-phrased so the model relays rather than recalculates.
            'notes'          => $quote['notes'] ?: null,
        ], static fn ($v) => $v !== null && $v !== []);
    }

    public function format(int $cents, string $currency = 'RON'): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' ' . $currency;
    }
}
