<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a venue is willing to do about orders.
 *
 * Every question the ordering tools ask about policy — do you deliver, what
 * does it cost, is there a minimum, how long does it take — is answered from
 * here, in PHP. None of it is ever inferred by the language model, because a
 * model that guesses a delivery fee quotes it with total confidence.
 */
class RestaurantSetting extends Model
{
    use BelongsToTenant;

    public const PAYMENT_CASH             = 'cash';
    public const PAYMENT_CARD_ON_DELIVERY = 'card_on_delivery';

    protected $fillable = [
        'tenant_id', 'bot_id',
        'ordering_enabled', 'delivery_enabled', 'pickup_enabled',
        'delivery_fee_cents', 'free_delivery_threshold_cents', 'min_order_cents',
        'delivery_minutes', 'pickup_minutes',
        'delivery_zones', 'delivery_zones_only', 'payment_methods',
        'currency', 'order_notice', 'featured_item_ids',
    ];

    /**
     * Mirrors the column defaults in the migration.
     *
     * Without these a freshly instantiated (not yet saved) settings object has
     * null for every flag, so code that reads `delivery_enabled` before the
     * first save sees false and concludes the venue delivers nothing. The
     * database defaults only apply on INSERT, which is too late for anything
     * inspecting the object while it is being built.
     */
    protected $attributes = [
        'ordering_enabled'    => false,
        'delivery_enabled'    => true,
        'pickup_enabled'      => true,
        'delivery_zones_only' => false,
        'delivery_fee_cents' => 0,
        'min_order_cents'    => 0,
        'delivery_minutes'   => 45,
        'pickup_minutes'     => 20,
        'currency'           => 'RON',
    ];

    protected $casts = [
        'ordering_enabled'             => 'boolean',
        'delivery_enabled'             => 'boolean',
        'pickup_enabled'               => 'boolean',
        'delivery_fee_cents'           => 'integer',
        'free_delivery_threshold_cents'=> 'integer',
        'min_order_cents'              => 'integer',
        'delivery_minutes'             => 'integer',
        'pickup_minutes'               => 'integer',
        'delivery_zones'               => 'array',
        'delivery_zones_only'          => 'boolean',
        'payment_methods'              => 'array',
        'featured_item_ids'            => 'array',
    ];

    /**
     * Does this venue deliver only to the zones it listed?
     *
     * True means an address matching no zone is out of range, not merely
     * un-zoned. Meaningless without zones, so an empty list reads as false —
     * otherwise a misconfigured venue would refuse every delivery.
     */
    public function deliversOnlyToZones(): bool
    {
        return (bool) $this->delivery_zones_only && $this->zoneNames() !== [];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Settings for a bot, or null when the venue has never configured
     * ordering.
     *
     * Returning null rather than a populated default is the point: a bot with
     * no row must say "we don't take orders here", not quote a made-up 45
     * minute delivery on a zero fee. Written as an explicit finder because the
     * tenant global scope would hide the row on a voice call, where nobody is
     * authenticated.
     */
    public static function forBot(int $botId): ?self
    {
        return static::withoutGlobalScopes()->where('bot_id', $botId)->first();
    }

    /** @return list<string> */
    public function paymentMethods(): array
    {
        $methods = array_values(array_filter(
            array_map('strval', (array) $this->payment_methods),
        ));

        // Cash is the floor. A venue that takes orders but recorded no payment
        // method still gets paid at the door — assuming otherwise would have
        // the bot tell a customer there is no way to pay.
        return $methods !== [] ? $methods : [self::PAYMENT_CASH];
    }

    public function acceptsPayment(string $method): bool
    {
        return in_array($method, $this->paymentMethods(), true);
    }

    /**
     * Match what the caller said against the configured zones.
     *
     * Loose substring matching in both directions: a caller says "sunt în
     * Rogerius" or just "Rogerius", and an address line says "str. X, cartier
     * Rogerius". Returns null when nothing matches, which the pricing code
     * reads as "flat fee applies" rather than as an error — an unrecognised
     * neighbourhood is not a reason to refuse an order, it is a reason for the
     * venue to check the address.
     *
     * @return array{name: string, fee_cents: int, min_order_cents: int|null}|null
     */
    public function matchZone(?string $spoken): ?array
    {
        if ($spoken === null || trim($spoken) === '') {
            return null;
        }

        $needle = $this->fold($spoken);

        $match = null;
        $matchedLength = 0;

        foreach ((array) $this->delivery_zones as $zone) {
            if (!is_array($zone) || empty($zone['name'])) {
                continue;
            }

            $name = $this->fold((string) $zone['name']);
            if ($name === '') {
                continue;
            }

            /*
             * Whole words in both directions, not bare substrings. The usual
             * input here is a whole dictated address — "Str. Republicii 12,
             * Gherla" — so the zone has to be found inside it; the reverse
             * covers the caller who answers the zone question with just the
             * neighbourhood.
             *
             * Plain str_contains() was doing both, which let a two-letter
             * fragment match: a mis-heard zone of "la" is inside "Gherla", and
             * the order would be quoted at that zone's fee. The reverse
             * direction now also demands four characters, below which nothing
             * spoken is specific enough to price an address on.
             */
            $found = $this->mentions($needle, $name)
                || (mb_strlen($needle) >= 4 && $this->mentions($name, $needle));

            if (!$found) {
                continue;
            }

            // Longest name wins, so "Gherla Nouă" is not shadowed by "Gherla"
            // when a venue prices the two differently.
            if (mb_strlen($name) > $matchedLength) {
                $matchedLength = mb_strlen($name);
                $match = [
                    'name'            => (string) $zone['name'],
                    'fee_cents'       => (int) ($zone['fee_cents'] ?? $this->delivery_fee_cents),
                    'min_order_cents' => isset($zone['min_order_cents']) ? (int) $zone['min_order_cents'] : null,
                ];
            }
        }

        return $match;
    }

    /** Whether $needle appears in $haystack as a whole word. */
    private function mentions(string $haystack, string $needle): bool
    {
        return (bool) preg_match(
            '/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u',
            $haystack,
        );
    }

    /** @return list<string> */
    public function zoneNames(): array
    {
        $names = [];
        foreach ((array) $this->delivery_zones as $zone) {
            if (is_array($zone) && !empty($zone['name'])) {
                $names[] = (string) $zone['name'];
            }
        }
        return $names;
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        ]);
    }
}
