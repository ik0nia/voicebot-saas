<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\ComputeAvailability;
use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, non-LLM endpoint the widget calls to render clickable slot
 * buttons on booking-context pages. Delegates to the same
 * ComputeAvailability action the LLM tool-dispatcher uses, so the
 * widget always sees the same slots the bot would propose.
 *
 * Why it exists:
 *   When a caller is on a booking page the widget can show 3-4
 *   suggested slots before the user even types. That converts faster
 *   than a chat round-trip where the LLM has to ask for the service
 *   and then call the tool itself.
 *
 * Safety:
 *   - Channel resolved + must be active.
 *   - Bot must run a booking-family engine (booking, hybrid).
 *   - Requested service must belong to the bot's tenant (checked via
 *     bot_id FK — ServiceType has bot_id, so tenant is implicit).
 *   - Read-only; no writes. Same rate limit group as /products (30/min
 *     per IP) because the shape and cost is comparable.
 */
class ChatbotBookingSlotsController extends Controller
{
    public function index(Request $request, $channelId, ComputeAvailability $compute): JsonResponse
    {
        $validated = $request->validate([
            'service_type_id' => 'required|integer',
            'preferred_from'  => 'nullable|string|max:40',
            'staff_member_id' => 'nullable|integer',
            'days_ahead'      => 'nullable|integer|min:1|max:30',
            'max_slots'       => 'nullable|integer|min:1|max:10',
            'urgent_only'     => 'nullable|boolean',
            'time_window'     => 'nullable|string|in:morning,afternoon,evening',
        ]);

        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();
        if (!$channel) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot || !in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
            return response()->json(['error' => 'Agentul nu oferă programări.'], 422);
        }

        $service = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('id', $validated['service_type_id'])
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->first();
        if (!$service) {
            return response()->json(['error' => 'Serviciu invalid.'], 404);
        }

        $urgent = (bool) ($validated['urgent_only'] ?? false);
        $maxSlots = $urgent ? 3 : (int) ($validated['max_slots'] ?? 4);

        try {
            $result = $compute->handle(
                bot: $bot,
                service: $service,
                preferredFromIso: $validated['preferred_from'] ?? null,
                staffMemberId: $validated['staff_member_id'] ?? null,
                daysAhead: (int) ($validated['days_ahead'] ?? 7),
                maxSlots: max(1, min(10, $maxSlots + 4)), // overfetch so time_window filter has material
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ChatbotBookingSlots: compute failed', [
                'channel_id' => $channel->id,
                'bot_id'     => $bot->id,
                'service_id' => $service->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Nu am putut calcula sloturile.'], 500);
        }

        $slots = $result['slots'] ?? [];
        $window = $validated['time_window'] ?? null;
        if ($window && !empty($slots)) {
            $slots = array_values(array_filter($slots, function ($s) use ($window) {
                try {
                    $hour = (int) \Carbon\CarbonImmutable::parse($s['starts_at'])->format('H');
                } catch (\Throwable $e) {
                    return true; // Keep anything we can't parse — fail open.
                }
                return match ($window) {
                    'morning'   => $hour >= 6  && $hour < 12,
                    'afternoon' => $hour >= 12 && $hour < 17,
                    'evening'   => $hour >= 17 && $hour < 23,
                    default     => true,
                };
            }));
        }

        $slots = array_slice($slots, 0, $maxSlots);

        return response()->json([
            'service' => $result['service'] ?? null,
            'staff'   => $result['staff']   ?? [],
            'slots'   => $slots,
            'mode'    => $urgent ? 'urgent' : 'normal',
            'window'  => $window,
        ]);
    }
}
