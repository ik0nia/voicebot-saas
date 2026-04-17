<?php

namespace App\Services\Booking;

use App\Actions\Booking\ComputeAvailability;
use App\Actions\Booking\CreateAppointment;
use App\Models\Bot;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Log;

/**
 * Executes booking tool-calls invoked by the LLM. Registered onto
 * the request-scoped ToolRegistry in BookingServiceProvider via
 * container extend(). Actions stay pure — this is the thin HTTP-
 * dialect glue.
 *
 * Every handler double-checks engine_type === 'booking' before
 * touching the DB. Defence in depth: even if somehow a tool call
 * lands on a non-booking bot, we refuse loudly rather than silently
 * creating rows in the wrong scope.
 */
class BookingToolDispatcher
{
    public function __construct(
        private ComputeAvailability $compute,
        private CreateAppointment   $create,
    ) {}

    public function checkAvailability(int $botId, array $params): array
    {
        $bot = $this->resolveBookingBot($botId);
        if (!$bot) return ['error' => 'bot not on booking engine'];

        $serviceId = (int) ($params['service_type_id'] ?? 0);
        if (!$serviceId) return ['error' => 'service_type_id required'];

        $service = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->where('id', $serviceId)->first();
        if (!$service) return ['error' => 'service not found'];

        return $this->compute->handle(
            $bot,
            $service,
            $params['preferred_from'] ?? null,
            $params['staff_member_id'] ?? null,
            max(1, min(30, (int) ($params['days_ahead'] ?? 7))),
            max(1, min(10, (int) ($params['max_slots'] ?? 6))),
        );
    }

    public function bookAppointment(int $botId, array $params): array
    {
        $bot = $this->resolveBookingBot($botId);
        if (!$bot) return ['error' => 'bot not on booking engine'];

        foreach (['service_type_id', 'staff_member_id', 'starts_at', 'customer_name'] as $req) {
            if (empty($params[$req])) {
                return ['error' => "{$req} required"];
            }
        }

        return $this->create->handle(
            $bot,
            (int) $params['service_type_id'],
            (int) $params['staff_member_id'],
            (string) $params['starts_at'],
            (string) $params['customer_name'],
            $params['customer_phone'] ?? null,
            $params['customer_email'] ?? null,
            $params['notes'] ?? null,
            $params['conversation_id'] ?? null,
            $params['source'] ?? 'chat',
        );
    }

    public function listServices(int $botId, array $params): array
    {
        $bot = $this->resolveBookingBot($botId);
        if (!$bot) return ['error' => 'bot not on booking engine'];

        $services = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'price', 'currency', 'is_urgent']);

        return [
            'services' => $services->map(fn ($s) => [
                'id'               => $s->id,
                'name'             => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'price'            => $s->price,
                'currency'         => $s->currency,
                'is_urgent'        => (bool) $s->is_urgent,
            ])->all(),
        ];
    }

    /**
     * Only return the bot object when it is actually configured for
     * booking — otherwise callers get null and refuse the operation.
     */
    private function resolveBookingBot(int $botId): ?Bot
    {
        $bot = Bot::withoutGlobalScopes()->find($botId);
        if (!$bot) {
            Log::warning('BookingToolDispatcher: bot not found', ['bot_id' => $botId]);
            return null;
        }
        if ($bot->engine_type !== 'booking') {
            Log::warning('BookingToolDispatcher: non-booking bot attempted tool-call', [
                'bot_id' => $botId, 'engine' => $bot->engine_type,
            ]);
            return null;
        }
        return $bot;
    }
}
