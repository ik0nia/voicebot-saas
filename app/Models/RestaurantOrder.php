<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A food order, from empty basket to delivered.
 *
 * A `draft` row is the basket itself. It exists from the moment the caller
 * says "aș vrea o pizza" and is only promoted to `placed` when they confirm.
 * Keeping it in the database rather than in cache means an order half-built
 * when a call drops is still visible to the venue, who can ring back — which
 * is the difference between a lost order and a saved one.
 */
class RestaurantOrder extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT            = 'draft';
    public const STATUS_PLACED           = 'placed';
    public const STATUS_CONFIRMED        = 'confirmed';
    public const STATUS_PREPARING        = 'preparing';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_COMPLETED        = 'completed';
    public const STATUS_CANCELED         = 'canceled';

    public const FULFILMENT_DELIVERY = 'delivery';
    public const FULFILMENT_PICKUP   = 'pickup';

    protected $fillable = [
        'tenant_id', 'bot_id', 'conversation_id', 'call_id', 'session_ref',
        'order_number', 'status', 'fulfilment',
        'customer_name', 'customer_phone', 'customer_email',
        'delivery_address', 'delivery_zone', 'delivery_notes',
        'subtotal_cents', 'delivery_fee_cents', 'total_cents', 'currency',
        'payment_method', 'estimated_minutes',
        'source', 'metadata',
        'placed_at', 'canceled_at', 'cancel_reason',
    ];

    protected $casts = [
        'order_number'       => 'integer',
        'subtotal_cents'     => 'integer',
        'delivery_fee_cents' => 'integer',
        'total_cents'        => 'integer',
        'estimated_minutes'  => 'integer',
        'metadata'           => 'array',
        'placed_at'          => 'datetime',
        'canceled_at'        => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isDelivery(): bool
    {
        return $this->fulfilment === self::FULFILMENT_DELIVERY;
    }

    /**
     * Human-readable order reference.
     *
     * Digits-only and short, because on a call the customer has to write it
     * down or repeat it back, and letters get lost over a phone line ("B ca
     * Barbu?").
     *
     * Numbered per venue: a customer told "comanda 0003" and a venue looking
     * at its third order of the day are talking about the same thing. Orders
     * placed before the column existed, and drafts that never got a number,
     * keep falling back to the id-derived form so no reference ever comes back
     * empty.
     */
    public function reference(): string
    {
        return $this->order_number !== null
            ? str_pad((string) $this->order_number, 4, '0', STR_PAD_LEFT)
            : str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function formattedTotal(): string
    {
        return $this->formatCents($this->total_cents);
    }

    public function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' ' . ($this->currency ?: 'RON');
    }

    /**
     * The order as the language model should see it.
     *
     * Every monetary figure appears twice — as pre-formatted text for the
     * model to read out, and as cents for anything that needs to compare. The
     * model is never handed two numbers and asked to add them; the sums are
     * already done. `spoken_summary` exists so a voice agent can read the
     * basket back in one breath instead of assembling it itself and dropping a
     * line.
     *
     * @return array<string, mixed>
     */
    public function toToolPayload(): array
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return array_filter([
            'order_reference' => $this->status === self::STATUS_DRAFT ? null : $this->reference(),
            'status'          => $this->status,
            'fulfilment'      => $this->fulfilment,
            'items'           => $items->map(fn (RestaurantOrderItem $i) => $i->toToolPayload())->values()->all(),
            'item_count'      => (int) $items->sum('quantity'),
            'subtotal'        => $this->formatCents($this->subtotal_cents),
            'subtotal_cents'  => $this->subtotal_cents,
            'delivery_fee'    => $this->isDelivery() ? $this->formatCents($this->delivery_fee_cents) : null,
            'delivery_fee_cents' => $this->isDelivery() ? $this->delivery_fee_cents : null,
            'total'           => $this->formatCents($this->total_cents),
            'total_cents'     => $this->total_cents,
            'currency'        => $this->currency,
            'customer_name'   => $this->customer_name,
            'customer_phone'  => $this->customer_phone,
            'delivery_address'=> $this->delivery_address,
            'delivery_zone'   => $this->delivery_zone,
            'payment_method'  => $this->payment_method,
            'estimated_minutes' => $this->estimated_minutes,
            'spoken_summary'  => $this->spokenSummary($items),
        ], static fn ($v) => $v !== null && $v !== []);
    }

    /**
     * One sentence naming every line and the total.
     *
     * @param  iterable<RestaurantOrderItem>  $items
     */
    private function spokenSummary(iterable $items): ?string
    {
        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item->spokenLine();
        }

        if ($parts === []) {
            return null;
        }

        // Semicolons separate the lines, because a line may carry its own
        // comma-free modifier and the caller has to hear where one dish ends.
        return implode('; ', $parts) . '. Total ' . $this->formattedTotal();
    }
}
