<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an order: a dish, a quantity, and what was done to it.
 *
 * The name and unit price are snapshots taken when the line was added. The
 * `menu_item_id` link is for reporting only. If the venue raises the price of
 * a pizza while a caller is still on the phone, the line keeps the price the
 * caller was quoted — the alternative is a bill that changed after it was
 * agreed, which is a chargeback and an angry phone call.
 */
class RestaurantOrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'restaurant_order_id', 'menu_item_id',
        'name_snapshot', 'unit_price_cents', 'quantity', 'line_total_cents',
        'options', 'options_label', 'notes',
    ];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'quantity'         => 'integer',
        'line_total_cents' => 'integer',
        'options'          => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * How this line is read back on the phone.
     *
     * "2 x Pizza Quattro Stagioni (Mare, cu cașcaval), fără ceapă". Assembled
     * here rather than by the model so that reading back an order cannot
     * quietly drop the modifier the caller cares about most.
     */
    public function spokenLine(): string
    {
        $line = $this->quantity > 1
            ? "{$this->quantity} x {$this->name_snapshot}"
            : $this->name_snapshot;

        if ($this->options_label) {
            $line .= " ({$this->options_label})";
        }

        // Em dash, not a comma: read aloud, "Shaorma, sos de usturoi" is
        // indistinguishable from two separate things on the order.
        if ($this->notes) {
            $line .= " — {$this->notes}";
        }

        return $line;
    }

    /** @return array<string, mixed> */
    public function toToolPayload(): array
    {
        return array_filter([
            'line_id'          => $this->id,
            'menu_item_id'     => $this->menu_item_id,
            'name'             => $this->name_snapshot,
            'quantity'         => $this->quantity,
            'options'          => $this->options_label,
            'notes'            => $this->notes,
            'unit_price'       => $this->formatCents($this->unit_price_cents),
            'line_total'       => $this->formatCents($this->line_total_cents),
            'line_total_cents' => $this->line_total_cents,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    private function formatCents(int $cents): string
    {
        $currency = $this->relationLoaded('order') && $this->order
            ? ($this->order->currency ?: 'RON')
            : 'RON';

        return number_format($cents / 100, 2, ',', '.') . ' ' . $currency;
    }
}
