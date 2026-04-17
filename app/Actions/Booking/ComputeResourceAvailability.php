<?php

namespace App\Actions\Booking;

use App\Models\Appointment;
use App\Models\Bot;
use App\Models\BookableResource;
use App\Models\ResourceAvailabilityBlock;
use App\Models\ResourceAvailabilityRule;
use App\Models\ServiceType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Resource-based availability — the evolution of
 * ComputeAvailability that works against bookable_resources
 * instead of staff_members. The legacy action stays in place;
 * this one is used by bots that have been migrated to the
 * unified resource model OR by new bots coming in through
 * departments / doctor-specific flows.
 *
 * Honors resource_availability_blocks so future Google Calendar
 * sync can add "busy" rows and this action immediately respects
 * them — no code change needed when sync lands.
 */
class ComputeResourceAvailability
{
    /**
     * @return array{
     *   service: ?array,
     *   resources: array<int, array{id:int,name:string,kind:string,department_id:?int,location_id:?int}>,
     *   slots: array<int, array{
     *     resource_id:int, resource_name:string, department_id:?int, location_id:?int,
     *     starts_at:string, ends_at:string, label:string
     *   }>
     * }
     */
    public function handle(
        Bot $bot,
        ?ServiceType $service = null,
        ?int $resourceId = null,
        ?int $departmentId = null,
        ?int $locationId = null,
        ?string $preferredFromIso = null,
        int $daysAhead = 7,
        int $maxSlots = 6,
    ): array {
        $from = $preferredFromIso
            ? CarbonImmutable::parse($preferredFromIso)
            : CarbonImmutable::now();
        if ($from->isPast()) $from = CarbonImmutable::now();
        $to = $from->copy()->addDays($daysAhead);

        $query = BookableResource::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('is_active', true);
        if ($resourceId)    $query->where('id', $resourceId);
        if ($departmentId)  $query->where('department_id', $departmentId);
        if ($locationId)    $query->where('location_id', $locationId);
        if ($service) {
            $query->where(function ($q) use ($service) {
                // Resource handles this service OR is an "any" resource.
                $q->whereNull('service_type_ids')
                  ->orWhereJsonContains('service_type_ids', $service->id);
            });
        }
        $resources = $query->orderBy('sort_order')->get();
        if ($resources->isEmpty()) {
            return ['service' => $service ? $this->serializeService($service) : null, 'resources' => [], 'slots' => []];
        }

        $duration = $service?->duration_minutes ?? 30;
        $buffer = $service?->buffer_minutes ?? 0;

        $allSlots = [];
        foreach ($resources as $resource) {
            $rules = ResourceAvailabilityRule::where('bookable_resource_id', $resource->id)
                ->get()
                ->groupBy('weekday');
            if ($rules->isEmpty()) continue;

            $booked = Appointment::withoutGlobalScopes()
                ->where(function ($q) use ($resource) {
                    $q->where('bookable_resource_id', $resource->id)
                      ->when($resource->legacy_staff_member_id, function ($qq) use ($resource) {
                          $qq->orWhere('staff_member_id', $resource->legacy_staff_member_id);
                      });
                })
                ->whereIn('status', [
                    Appointment::STATUS_REQUESTED,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_REMINDER_SENT,
                ])
                ->where('ends_at', '>', $from)
                ->where('starts_at', '<', $to)
                ->get(['starts_at', 'ends_at'])
                ->map(fn ($a) => ['s' => Carbon::parse($a->starts_at), 'e' => Carbon::parse($a->ends_at)])
                ->all();

            $blocks = ResourceAvailabilityBlock::where('bookable_resource_id', $resource->id)
                ->where('ends_at', '>', $from)
                ->where('starts_at', '<', $to)
                ->get(['starts_at', 'ends_at'])
                ->map(fn ($b) => ['s' => Carbon::parse($b->starts_at), 'e' => Carbon::parse($b->ends_at)])
                ->all();
            $busy = array_merge($booked, $blocks);

            foreach (CarbonPeriod::create($from->startOfDay(), '1 day', $to->startOfDay()) as $day) {
                $weekday = (int) $day->isoFormat('E');
                $dayRules = $rules->get($weekday, collect())
                    ->filter(fn ($r) => (!$r->effective_from || $day->gte($r->effective_from))
                                    && (!$r->effective_to   || $day->lte($r->effective_to)));
                foreach ($dayRules as $rule) {
                    $slotStart = Carbon::parse($day->format('Y-m-d') . ' ' . $rule->start_time);
                    $slotEnd   = Carbon::parse($day->format('Y-m-d') . ' ' . $rule->end_time);

                    $cursor = $slotStart->copy();
                    while ($cursor->copy()->addMinutes($duration)->lte($slotEnd)) {
                        $candidateEnd = $cursor->copy()->addMinutes($duration);
                        if ($cursor->gte($from) && !$this->overlapsAny($cursor, $candidateEnd, $busy)) {
                            $allSlots[] = [
                                'resource_id'   => $resource->id,
                                'resource_name' => $resource->name,
                                'department_id' => $resource->department_id,
                                'location_id'   => $resource->location_id,
                                'starts_at'     => $cursor->toIso8601String(),
                                'ends_at'       => $candidateEnd->toIso8601String(),
                                'label'         => $cursor->translatedFormat('D, j M, H:i'),
                            ];
                            if (count($allSlots) >= $maxSlots * 3) break 3;
                        }
                        $cursor->addMinutes($duration + $buffer);
                    }
                }
            }
        }

        usort($allSlots, fn ($a, $b) => strcmp($a['starts_at'], $b['starts_at']));
        $slots = collect($allSlots)->unique(fn ($s) => $s['resource_id'] . '|' . $s['starts_at'])
            ->take($maxSlots)->values()->all();

        return [
            'service'   => $service ? $this->serializeService($service) : null,
            'resources' => $resources->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->name, 'kind' => $r->kind,
                'department_id' => $r->department_id, 'location_id' => $r->location_id,
            ])->values()->all(),
            'slots'     => $slots,
        ];
    }

    private function overlapsAny(Carbon $s, Carbon $e, array $busy): bool
    {
        foreach ($busy as $b) {
            if ($s->lt($b['e']) && $e->gt($b['s'])) return true;
        }
        return false;
    }

    private function serializeService(ServiceType $s): array
    {
        return [
            'id' => $s->id, 'name' => $s->name,
            'duration_minutes' => $s->duration_minutes,
            'price' => $s->price, 'currency' => $s->currency,
            'is_urgent' => $s->is_urgent,
        ];
    }
}
