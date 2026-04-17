<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\ServiceType;
use App\Models\StaffMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * X1 — one-click booking confirmation. Widget renders an action chip
 * (returned by /booking-slots or by the LLM) that POSTs here with the
 * selected slot details + caller contact. We validate, create the
 * appointment via the existing Appointment model (same uniqueness
 * constraints the LLM tool-call path uses), and return a success
 * payload the widget renders as a confirmation card inline.
 *
 * Security:
 *  - Channel must be active.
 *  - Bot must run booking or hybrid engine.
 *  - ServiceType + StaffMember must belong to the bot's tenant.
 *  - starts_at + ends_at must parse to valid, future ISO 8601 times.
 *  - Slot must not overlap an existing appointment (DB unique +
 *    explicit overlap check for soft-deleted / different slots).
 *  - Rate limited via routes/api.php (5/min per IP).
 *
 * Fallback: on any validation/DB failure, returns JSON error; widget
 * falls back to the normal chat path without breaking.
 */
class ChatbotBookingConfirmController extends Controller
{
    public function store(Request $request, $channelId): JsonResponse
    {
        $validated = $request->validate([
            'service_type_id' => 'required|integer',
            'staff_member_id' => 'required|integer',
            'starts_at'       => 'required|string|max:40',
            'ends_at'         => 'required|string|max:40',
            'customer_name'   => 'required|string|max:120',
            'customer_phone'  => 'nullable|string|max:30',
            'customer_email'  => 'nullable|email|max:255',
            'conversation_id' => 'nullable|integer',
        ]);

        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();
        if (!$channel) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot || !$bot->is_active || !in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
            return response()->json(['error' => 'Agentul nu acceptă programări.'], 422);
        }

        $service = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('id', $validated['service_type_id'])
            ->first();
        $staff = StaffMember::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('id', $validated['staff_member_id'])
            ->first();
        if (!$service || !$staff) {
            return response()->json(['error' => 'Serviciu sau personal invalid.'], 422);
        }

        try {
            $starts = \Carbon\CarbonImmutable::parse($validated['starts_at']);
            $ends   = \Carbon\CarbonImmutable::parse($validated['ends_at']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Format dată invalid.'], 422);
        }

        if ($starts->isPast() || $ends->lte($starts)) {
            return response()->json(['error' => 'Slotul nu este valid sau e în trecut.'], 422);
        }

        // Overlap guard — even with the unique index on
        // (staff_member_id, starts_at, deleted_at) we want to return
        // a clean error before the insert raises.
        $overlap = Appointment::withoutGlobalScopes()
            ->where('staff_member_id', $staff->id)
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->exists();
        if ($overlap) {
            return response()->json([
                'error' => 'Slotul tocmai a fost rezervat. Alege alt interval.',
                'conflict' => true,
            ], 409);
        }

        try {
            $appointment = DB::transaction(function () use ($bot, $service, $staff, $starts, $ends, $validated) {
                return Appointment::create([
                    'tenant_id'       => $bot->tenant_id,
                    'bot_id'          => $bot->id,
                    'service_type_id' => $service->id,
                    'staff_member_id' => $staff->id,
                    'starts_at'       => $starts,
                    'ends_at'         => $ends,
                    'customer_name'   => $validated['customer_name'],
                    'customer_phone'  => $validated['customer_phone'] ?? null,
                    'customer_email'  => $validated['customer_email'] ?? null,
                    'status'          => 'confirmed',
                    'source'          => 'widget_action',
                    'conversation_id' => $validated['conversation_id'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('ChatbotBookingConfirm: create failed', [
                'channel_id' => $channel->id,
                'bot_id'     => $bot->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Nu am putut confirma programarea.'], 500);
        }

        return response()->json([
            'success'        => true,
            'appointment_id' => $appointment->id,
            'confirmation'   => [
                'service_name' => $service->name,
                'staff_name'   => $staff->name,
                'starts_at'    => $starts->toIso8601String(),
                'ends_at'      => $ends->toIso8601String(),
                'label'        => $starts->copy()->locale($bot->language ?: 'ro')->isoFormat('ddd, D MMM, HH:mm'),
            ],
        ]);
    }
}
