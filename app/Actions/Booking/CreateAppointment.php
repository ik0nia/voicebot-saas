<?php

namespace App\Actions\Booking;

use App\Models\Appointment;
use App\Models\Bot;
use App\Models\ServiceType;
use App\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Persist an appointment. Relies on the unique index on
 * (staff_member_id, starts_at, deleted_at) in the appointments
 * table — two concurrent tool-calls racing for the same slot
 * both try to insert; one wins, the other hits a duplicate-key
 * error that we catch and return as a structured conflict so
 * the LLM can offer the next slot gracefully.
 */
class CreateAppointment
{
    /**
     * @return array{ok: bool, appointment_id?: int, conflict?: bool, message?: string, appointment?: array}
     */
    public function handle(
        Bot $bot,
        int $serviceTypeId,
        int $staffMemberId,
        string $startsAtIso,
        string $customerName,
        ?string $customerPhone = null,
        ?string $customerEmail = null,
        ?string $notes = null,
        ?int $conversationId = null,
        string $source = 'chat',
    ): array {
        // Defence in depth: never persist an appointment for a bot
        // that isn't actually on the booking engine. The controller
        // already gates this, but the action is called from jobs too.
        if ($bot->engine_type !== 'booking') {
            return ['ok' => false, 'message' => 'bot not on booking engine'];
        }

        $service = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->where('id', $serviceTypeId)
            ->where('is_active', true)->first();
        $staff = StaffMember::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->where('id', $staffMemberId)
            ->where('is_active', true)->first();

        if (!$service) return ['ok' => false, 'message' => 'service not found'];
        if (!$staff)   return ['ok' => false, 'message' => 'staff not found'];
        if (!$staff->canHandle($service)) {
            return ['ok' => false, 'message' => 'staff does not handle this service'];
        }

        $startsAt = Carbon::parse($startsAtIso);
        if ($startsAt->isPast()) {
            return ['ok' => false, 'message' => 'cannot book a past slot'];
        }
        $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

        try {
            $appt = Appointment::create([
                'tenant_id'        => $bot->tenant_id,
                'bot_id'           => $bot->id,
                'service_type_id'  => $service->id,
                'staff_member_id'  => $staff->id,
                'conversation_id'  => $conversationId,
                'customer_name'    => mb_substr($customerName, 0, 180),
                'customer_phone'   => $customerPhone ? mb_substr($customerPhone, 0, 40) : null,
                'customer_email'   => $customerEmail ? mb_substr($customerEmail, 0, 180) : null,
                'notes'            => $notes,
                'starts_at'        => $startsAt,
                'ends_at'          => $endsAt,
                'status'           => Appointment::STATUS_REQUESTED,
                'source'           => $source,
            ]);
        } catch (QueryException $e) {
            // 23505 = unique_violation (Postgres). That's the concurrent-
            // booking race we're explicitly guarding against.
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'duplicate key')) {
                Log::info('CreateAppointment: slot taken, returning conflict', [
                    'bot_id' => $bot->id, 'starts_at' => $startsAtIso,
                ]);
                return ['ok' => false, 'conflict' => true, 'message' => 'slot already taken'];
            }
            throw $e;
        }

        return [
            'ok'             => true,
            'appointment_id' => $appt->id,
            'appointment'    => [
                'service'      => $service->name,
                'staff'        => $staff->name,
                'starts_at'    => $appt->starts_at->toIso8601String(),
                'ends_at'      => $appt->ends_at->toIso8601String(),
                'label'        => $appt->starts_at->translatedFormat('l, j M Y, H:i'),
                'duration_min' => $service->duration_minutes,
                'price'        => $service->price,
                'currency'     => $service->currency,
            ],
        ];
    }
}
