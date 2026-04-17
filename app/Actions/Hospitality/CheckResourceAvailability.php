<?php

namespace App\Actions\Hospitality;

use App\Models\Bot;
use App\Models\Reservation;
use App\Models\ResourceInventory;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Finds available mese/camere/săli for a given time window.
 * Parametric on kind so restaurants (short blocks) and hotels
 * (multi-night ranges) share one action. The LLM tool manifests
 * expose specialised wrappers (check_table_availability,
 * check_room_availability) so prompts stay intuitive — both
 * land here.
 */
class CheckResourceAvailability
{
    /**
     * @return array{
     *   resources: array<int, array{
     *     id: int, name: string, kind: string, capacity: int,
     *     zone: ?string, base_price: ?float, currency: string,
     *     available: bool
     *   }>
     * }
     */
    public function handle(
        Bot $bot,
        string $kind,
        string $startsAtIso,
        string $endsAtIso,
        ?int $partySize = null,
        ?string $zone = null,
    ): array {
        $start = CarbonImmutable::parse($startsAtIso);
        $end   = CarbonImmutable::parse($endsAtIso);
        if ($end->lte($start)) {
            return ['resources' => [], 'error' => 'end must be after start'];
        }
        if ($start->isPast() && !$start->isSameDay(now())) {
            return ['resources' => [], 'error' => 'start is in the past'];
        }

        $query = ResourceInventory::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('kind', $kind)
            ->where('is_active', true);
        if ($partySize) $query->where('capacity', '>=', $partySize);
        if ($zone)      $query->where('zone', $zone);
        $candidates = $query->orderBy('sort_order')->orderBy('capacity')->get();
        if ($candidates->isEmpty()) return ['resources' => []];

        // Pull ALL overlapping reservations in one shot so we
        // don't N+1 the DB.
        $overlapping = Reservation::withoutGlobalScopes()
            ->whereIn('resource_id', $candidates->pluck('id'))
            ->whereIn('status', [
                Reservation::STATUS_REQUESTED,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CHECKED_IN,
            ])
            ->where('ends_at', '>', $start)
            ->where('starts_at', '<', $end)
            ->get(['resource_id']);
        $occupied = $overlapping->pluck('resource_id')->unique()->flip();

        $out = [];
        foreach ($candidates as $r) {
            $out[] = [
                'id'         => $r->id,
                'name'       => $r->name,
                'kind'       => $r->kind,
                'capacity'   => $r->capacity,
                'zone'       => $r->zone,
                'base_price' => $r->base_price,
                'currency'   => $r->currency,
                'available'  => !$occupied->has($r->id),
            ];
        }
        return ['resources' => $out];
    }
}
