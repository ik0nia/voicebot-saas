<?php

namespace App\Actions\Hospitality;

use App\Models\Bot;
use App\Models\Reservation;
use App\Models\ResourceInventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Creates a reservation against a resource. Uses a SELECT … FOR
 * UPDATE inside a transaction to prevent double-booking; the
 * reservations table doesn't have a partial-unique like
 * appointments does because restaurants can legitimately stack
 * two short bookings back-to-back at the same instant on the
 * same table after a cancellation.
 *
 * Returns conflict=true when the window overlaps any active
 * reservation on the same resource — caller (LLM) retries with
 * a neighbour.
 */
class ReserveResource
{
    public function handle(
        Bot $bot,
        int $resourceId,
        string $startsAtIso,
        string $endsAtIso,
        string $customerName,
        ?string $customerPhone = null,
        ?string $customerEmail = null,
        ?int $partySize = null,
        ?string $notes = null,
        ?string $occasion = null,
        ?int $conversationId = null,
        string $source = 'chat',
    ): array {
        if ($bot->engine_type !== 'hospitality') {
            return ['ok' => false, 'message' => 'bot not on hospitality engine'];
        }
        $starts = Carbon::parse($startsAtIso);
        $ends = Carbon::parse($endsAtIso);
        if ($ends->lte($starts)) {
            return ['ok' => false, 'message' => 'end must be after start'];
        }

        return DB::transaction(function () use (
            $bot, $resourceId, $starts, $ends, $customerName, $customerPhone,
            $customerEmail, $partySize, $notes, $occasion, $conversationId, $source
        ) {
            $resource = ResourceInventory::withoutGlobalScopes()
                ->where('id', $resourceId)->where('bot_id', $bot->id)
                ->where('is_active', true)
                ->lockForUpdate()->first();
            if (!$resource) {
                return ['ok' => false, 'message' => 'resource not found'];
            }

            // Overlap check inside the row lock — any active row
            // that straddles the requested window blocks us.
            $overlap = Reservation::withoutGlobalScopes()
                ->where('resource_id', $resource->id)
                ->whereIn('status', [
                    Reservation::STATUS_REQUESTED,
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_CHECKED_IN,
                ])
                ->where('ends_at', '>', $starts)
                ->where('starts_at', '<', $ends)
                ->exists();
            if ($overlap) {
                return ['ok' => false, 'conflict' => true, 'message' => 'window overlaps an active reservation'];
            }

            try {
                $res = Reservation::create([
                    'tenant_id'       => $bot->tenant_id,
                    'bot_id'          => $bot->id,
                    'resource_id'     => $resource->id,
                    'conversation_id' => $conversationId,
                    'customer_name'   => mb_substr($customerName, 0, 180),
                    'customer_phone'  => $customerPhone ? mb_substr($customerPhone, 0, 40) : null,
                    'customer_email'  => $customerEmail ? mb_substr($customerEmail, 0, 180) : null,
                    'party_size'      => $partySize ?? $resource->capacity,
                    'notes'           => $notes,
                    'occasion'        => $occasion,
                    'starts_at'       => $starts,
                    'ends_at'         => $ends,
                    'status'          => Reservation::STATUS_REQUESTED,
                    'source'          => $source,
                ]);
            } catch (\Throwable $e) {
                Log::warning('ReserveResource insert failed', ['err' => $e->getMessage()]);
                return ['ok' => false, 'message' => 'could not persist reservation'];
            }

            return [
                'ok'              => true,
                'reservation_id'  => $res->id,
                'reservation'     => [
                    'resource'   => $resource->name,
                    'kind'       => $resource->kind,
                    'zone'       => $resource->zone,
                    'party_size' => $res->party_size,
                    'starts_at'  => $res->starts_at->toIso8601String(),
                    'ends_at'    => $res->ends_at->toIso8601String(),
                    'label'      => $res->starts_at->translatedFormat('l, j M Y, H:i') . ' → ' . $res->ends_at->translatedFormat('H:i'),
                ],
            ];
        });
    }
}
