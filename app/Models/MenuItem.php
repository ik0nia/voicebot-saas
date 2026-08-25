<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single orderable dish.
 *
 * This class owns the price arithmetic for one line of an order. It is the
 * reason the model is never asked to compute anything: an LLM will confidently
 * mis-add modifier prices, and on a phone call nobody sees the mistake until
 * the food arrives with the wrong bill. Everything monetary here is integer
 * cents.
 */
class MenuItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bot_id', 'menu_category_id',
        'name', 'aliases', 'description', 'ingredients', 'price_cents', 'currency', 'portion',
        'allergens', 'is_vegetarian', 'is_vegan', 'is_gluten_free', 'is_spicy',
        'options', 'prep_time_minutes', 'is_available',
        'available_from', 'available_until', 'available_days',
        'sort_order',
    ];

    protected $casts = [
        'price_cents'       => 'integer',
        'aliases'           => 'array',
        'ingredients'       => 'array',
        'allergens'         => 'array',
        'options'           => 'array',
        'available_days'    => 'array',
        'is_vegetarian'     => 'boolean',
        'is_vegan'          => 'boolean',
        'is_gluten_free'    => 'boolean',
        'is_spicy'          => 'boolean',
        'is_available'      => 'boolean',
        'prep_time_minutes' => 'integer',
        'sort_order'        => 'integer',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    /** Items the operator has switched on. Serving window is checked separately. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Is this dish servable at a given moment?
     *
     * Separate from the `available` scope because the window is time-of-day
     * logic that SQL would express awkwardly across the null cases, and
     * because the caller sometimes wants to say "the lunch menu starts at
     * 12:00" rather than simply hide the dish.
     */
    public function isServableAt(CarbonInterface $when): bool
    {
        if (!$this->is_available) {
            return false;
        }

        // ISO-8601 weekday: 1 = Monday … 7 = Sunday. Null = every day.
        if (is_array($this->available_days) && $this->available_days !== []) {
            if (!in_array($when->isoWeekday(), array_map('intval', $this->available_days), true)) {
                return false;
            }
        }

        if ($this->available_from === null && $this->available_until === null) {
            return true;
        }

        $minutes = $when->hour * 60 + $when->minute;
        $from = $this->minutesOfDay($this->available_from) ?? 0;
        $until = $this->minutesOfDay($this->available_until) ?? (24 * 60);

        // A window that wraps midnight (22:00 → 02:00) is two ranges, not one.
        // Late-night menus are a real case and the naive comparison would
        // report the dish as never servable.
        if ($from > $until) {
            return $minutes >= $from || $minutes < $until;
        }

        return $minutes >= $from && $minutes < $until;
    }

    /**
     * Resolve a caller's modifier choices into a price delta.
     *
     * Returns the delta in cents plus the choices as understood, so the order
     * can be read back in the caller's own terms ("pizza mare, cu cașcaval").
     * Unknown groups or choices are reported rather than ignored: silently
     * dropping "fără ceapă" produces a wrong plate, and silently dropping a
     * paid extra produces a wrong bill.
     *
     * @param  list<array{group?: string, choice?: string}>  $selections
     * @return array{delta_cents: int, resolved: list<array{group: string, choice: string, price_delta_cents: int}>, unknown: list<string>}
     */
    public function resolveOptions(array $selections): array
    {
        $groups = is_array($this->options) ? $this->options : [];
        $deltaCents = 0;
        $resolved = [];
        $unknown = [];

        foreach ($selections as $selection) {
            $groupName = trim((string) ($selection['group'] ?? ''));
            $choiceLabel = trim((string) ($selection['choice'] ?? ''));
            if ($choiceLabel === '') {
                continue;
            }

            $match = $this->findChoice($groups, $groupName, $choiceLabel);
            if ($match === null) {
                $unknown[] = $groupName !== '' ? "{$groupName}: {$choiceLabel}" : $choiceLabel;
                continue;
            }

            $deltaCents += $match['price_delta_cents'];
            $resolved[] = $match;
        }

        return ['delta_cents' => $deltaCents, 'resolved' => $resolved, 'unknown' => $unknown];
    }

    /**
     * Unit price including resolved modifiers, in cents.
     *
     * Clamped at zero: a modifier set with negative deltas must never produce
     * a dish the restaurant pays the customer to eat.
     *
     * @param  list<array{group?: string, choice?: string}>  $selections
     */
    public function unitPriceCents(array $selections = []): int
    {
        return max(0, $this->price_cents + $this->resolveOptions($selections)['delta_cents']);
    }

    /**
     * Shape handed to the language model. Prices are pre-formatted strings so
     * the model reads them out rather than recomputing anything, and the id is
     * included because ordering references it, not the name — two dishes can
     * legitimately share a name across categories.
     *
     * @return array<string, mixed>
     */
    public function toToolPayload(): array
    {
        $dietary = [];
        if ($this->is_vegan) $dietary[] = 'vegan';
        if ($this->is_vegetarian) $dietary[] = 'vegetarian';
        if ($this->is_gluten_free) $dietary[] = 'fără gluten';
        if ($this->is_spicy) $dietary[] = 'picant';

        return array_filter([
            'id'          => $this->id,
            'name'        => $this->name,
            'category'    => $this->relationLoaded('category') && $this->category ? $this->category->name : null,
            'description' => $this->description,
            'ingredients' => $this->ingredients ?: null,
            'price'       => $this->formattedPrice(),
            'price_cents' => $this->price_cents,
            'portion'     => $this->portion,
            'allergens'   => $this->allergens ?: null,
            'dietary'     => $dietary ?: null,
            'options'     => $this->options ?: null,
            'prep_time_minutes' => $this->prep_time_minutes,
        ], static fn ($v) => $v !== null && $v !== []);
    }

    public function formattedPrice(): string
    {
        return number_format($this->price_cents / 100, 2, ',', '.') . ' ' . $this->currency;
    }

    /**
     * @param  array<int, mixed>  $groups
     * @return array{group: string, choice: string, price_delta_cents: int}|null
     */
    private function findChoice(array $groups, string $groupName, string $choiceLabel): ?array
    {
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $currentGroup = (string) ($group['name'] ?? '');

            // A caller says "mare", not "Mărime: mare" — so when the model
            // omits the group we still match on the choice alone.
            if ($groupName !== '' && !$this->looseEquals($currentGroup, $groupName)) {
                continue;
            }

            foreach ($group['choices'] ?? [] as $choice) {
                if (!is_array($choice)) {
                    continue;
                }
                if ($this->looseEquals((string) ($choice['label'] ?? ''), $choiceLabel)) {
                    return [
                        'group'             => $currentGroup,
                        'choice'            => (string) $choice['label'],
                        'price_delta_cents' => (int) ($choice['price_delta_cents'] ?? 0),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Case- and diacritic-insensitive comparison. ASR transcripts drop
     * diacritics unpredictably ("Marime" vs "Mărime"), and rejecting a match
     * over a missing comma-below would make the caller repeat themselves.
     */
    private function looseEquals(string $a, string $b): bool
    {
        return $this->normalise($a) === $this->normalise($b);
    }

    private function normalise(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        ]);
    }

    private function minutesOfDay(?string $time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }
        [$h, $m] = array_pad(explode(':', $time), 2, '0');
        return ((int) $h) * 60 + (int) $m;
    }
}
