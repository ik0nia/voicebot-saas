<?php

namespace App\Services\Hospitality;

use App\Actions\Hospitality\CheckResourceAvailability;
use App\Actions\Hospitality\ReserveResource;
use App\Models\Bot;
use Illuminate\Support\Facades\Log;

/**
 * Executes hospitality tool-calls invoked by the LLM. Mirror of
 * BookingToolDispatcher — same defence-in-depth engine check on
 * every call so a non-hospitality bot can't exercise these by
 * accident.
 */
class HospitalityToolDispatcher
{
    public function __construct(
        private CheckResourceAvailability $check,
        private ReserveResource $reserve,
    ) {}

    public function checkTableAvailability(int $botId, array $params): array
    {
        return $this->checkKind($botId, 'table', $params);
    }

    public function checkRoomAvailability(int $botId, array $params): array
    {
        return $this->checkKind($botId, 'room', $params);
    }

    public function reserveTable(int $botId, array $params): array
    {
        return $this->reserveKind($botId, 'table', $params);
    }

    public function reserveRoom(int $botId, array $params): array
    {
        return $this->reserveKind($botId, 'room', $params);
    }

    private function checkKind(int $botId, string $kind, array $params): array
    {
        $bot = $this->resolveHospitalityBot($botId);
        if (!$bot) return ['error' => 'bot not on hospitality engine'];
        foreach (['starts_at', 'ends_at'] as $req) {
            if (empty($params[$req])) return ['error' => "{$req} required"];
        }
        return $this->check->handle(
            $bot, $kind,
            (string) $params['starts_at'],
            (string) $params['ends_at'],
            isset($params['party_size']) ? (int) $params['party_size'] : null,
            $params['zone'] ?? null,
        );
    }

    private function reserveKind(int $botId, string $kind, array $params): array
    {
        $bot = $this->resolveHospitalityBot($botId);
        if (!$bot) return ['error' => 'bot not on hospitality engine'];
        foreach (['resource_id', 'starts_at', 'ends_at', 'customer_name'] as $req) {
            if (empty($params[$req])) return ['error' => "{$req} required"];
        }
        return $this->reserve->handle(
            $bot,
            (int) $params['resource_id'],
            (string) $params['starts_at'],
            (string) $params['ends_at'],
            (string) $params['customer_name'],
            $params['customer_phone'] ?? null,
            $params['customer_email'] ?? null,
            isset($params['party_size']) ? (int) $params['party_size'] : null,
            $params['notes'] ?? null,
            $params['occasion'] ?? null,
            $params['conversation_id'] ?? null,
            $params['source'] ?? 'chat',
        );
    }

    private function resolveHospitalityBot(int $botId): ?Bot
    {
        $bot = Bot::withoutGlobalScopes()->find($botId);
        if (!$bot) return null;
        if ($bot->engine_type !== 'hospitality') {
            Log::warning('HospitalityToolDispatcher: non-hospitality bot attempted tool-call', [
                'bot_id' => $botId, 'engine' => $bot->engine_type,
            ]);
            return null;
        }
        return $bot;
    }
}
