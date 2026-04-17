<?php

namespace App\Actions\Booking;

use App\Models\Appointment;
use App\Models\Bot;
use App\Models\ServiceType;
use App\Models\StaffMember;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Compute free slots for a bot's booking engine. Pure, read-only —
 * returns an array of slot candidates. The LLM presents these to
 * the caller and asks which one to confirm.
 *
 * Isolation: the class accepts pre-resolved Bot + ServiceType, so it
 * never touches session / HTTP state. Callable from a queued job, a
 * controller, a test, or a tool-call handler without differences.
 */
class ComputeAvailability
{
    /**
     * @return array{
     *   service: array,
     *   staff: array,
     *   slots: array<int, array{
     *     staff_member_id: int,
     *     staff_name: string,
     *     starts_at: string,
     *     ends_at: string,
     *     label: string
     *   }>
     * }
     */
    public function handle(
        Bot $bot,
        ServiceType $service,
        ?string $preferredFromIso = null,
        ?int $staffMemberId = null,
        int $daysAhead = 7,
        int $maxSlots = 6,
    ): array {
        $from = $preferredFromIso
            ? CarbonImmutable::parse($preferredFromIso)
            : CarbonImmutable::now();
        // Never propose slots in the past, even when the LLM asks for one.
        if ($from->isPast()) {
            $from = CarbonImmutable::now();
        }
        $to = $from->copy()->addDays($daysAhead);

        $staffQuery = StaffMember::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('is_active', true);
        if ($staffMemberId) {
            $staffQuery->where('id', $staffMemberId);
        }
        $staffList = $staffQuery->get()->filter(fn (StaffMember $s) => $s->canHandle($service));
        if ($staffList->isEmpty()) {
            return [
                'service' => $this->serializeService($service),
                'staff'   => [],
                'slots'   => [],
            ];
        }

        $allSlots = [];
        foreach ($staffList as $staff) {
            $hoursByWeekday = WorkingHour::where('staff_member_id', $staff->id)
                ->get()
                ->groupBy('weekday');

            // Pull the booked window once per staff to minimise DB chatter.
            $booked = Appointment::withoutGlobalScopes()
                ->where('staff_member_id', $staff->id)
                ->whereIn('status', [
                    Appointment::STATUS_REQUESTED,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_REMINDER_SENT,
                ])
                ->where('ends_at', '>', $from)
                ->where('starts_at', '<', $to)
                ->get(['starts_at', 'ends_at'])
                ->map(fn ($a) => ['start' => Carbon::parse($a->starts_at), 'end' => Carbon::parse($a->ends_at)])
                ->values()
                ->all();

            foreach (CarbonPeriod::create($from->startOfDay(), '1 day', $to->startOfDay()) as $day) {
                $weekday = (int) $day->isoFormat('E'); // 1=Mon..7=Sun
                $blocks = $hoursByWeekday->get($weekday, collect());
                foreach ($blocks as $block) {
                    $slotStart = Carbon::parse($day->format('Y-m-d') . ' ' . $block->start_time);
                    $slotEnd   = Carbon::parse($day->format('Y-m-d') . ' ' . $block->end_time);

                    $cursor = $slotStart->copy();
                    while ($cursor->copy()->addMinutes($service->duration_minutes)->lte($slotEnd)) {
                        $candidateEnd = $cursor->copy()->addMinutes($service->duration_minutes);

                        if ($cursor->gte($from)
                            && !$this->overlapsAny($cursor, $candidateEnd, $booked)) {
                            $allSlots[] = [
                                'staff_member_id' => $staff->id,
                                'staff_name'      => $staff->name,
                                'starts_at'       => $cursor->toIso8601String(),
                                'ends_at'         => $candidateEnd->toIso8601String(),
                                'label'           => $cursor->translatedFormat('D, j M, H:i'),
                            ];
                            if (count($allSlots) >= $maxSlots * 3) {
                                break 3; // enough raw candidates to pick from
                            }
                        }
                        $cursor->addMinutes($service->duration_minutes + $service->buffer_minutes);
                    }
                }
            }
        }

        // Sort by time then de-dup by label so the LLM sees unique options.
        usort($allSlots, fn ($a, $b) => strcmp($a['starts_at'], $b['starts_at']));
        $slots = collect($allSlots)
            ->unique('starts_at')
            ->take($maxSlots)
            ->values()
            ->all();

        return [
            'service' => $this->serializeService($service),
            'staff'   => $staffList->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'slots'   => $slots,
        ];
    }

    /** @param array<int, array{start: Carbon, end: Carbon}> $booked */
    private function overlapsAny(Carbon $start, Carbon $end, array $booked): bool
    {
        foreach ($booked as $b) {
            if ($start->lt($b['end']) && $end->gt($b['start'])) return true;
        }
        return false;
    }

    private function serializeService(ServiceType $s): array
    {
        return [
            'id'               => $s->id,
            'name'             => $s->name,
            'duration_minutes' => $s->duration_minutes,
            'price'            => $s->price,
            'currency'         => $s->currency,
            'is_urgent'        => $s->is_urgent,
        ];
    }
}
