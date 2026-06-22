<?php

declare(strict_types=1);

namespace App\Services\Telephony;

use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;

/**
 * Twilio SMS outbound — notificări scurte la tenant (lead nou, apel pierdut)
 * sau la client (confirmare programare). Folosește numărul Twilio activ al
 * tenant-ului ca From. Tenant-ul fără număr propriu → sare cu warning.
 *
 * Limite: 160 chars per SMS (sub limita GSM-7); restul se trunchiază.
 * Pentru template-uri lungi recomandare folosesc email + push.
 */
class TwilioSmsService
{
    public function __construct(private TwilioService $twilio) {}

    /**
     * Trimite un SMS pe seama tenant-ului. Returnează SID-ul mesajului
     * la succes sau null la eșec (eșec nu blochează caller — best-effort).
     */
    public function send(Tenant $tenant, string $to, string $body): ?string
    {
        $from = $this->resolveFromNumber($tenant);
        if (!$from) {
            Log::info('TwilioSms: no active number for tenant — skip', [
                'tenant_id' => $tenant->id,
            ]);
            return null;
        }

        $body = trim($body);
        if ($body === '') return null;
        // Trunchiere defensivă la 160 chars (GSM-7 limit) ca să nu trimită
        // multi-segment SMS din greșeală.
        if (mb_strlen($body) > 160) {
            $body = mb_substr($body, 0, 157) . '…';
        }

        try {
            // Folosim master client — subaccount poate să nu existe pentru
            // toți tenants. Number-ul From restrânge oricum la cel propriu.
            $client = $this->twilio->masterClient();
            $msg = $client->messages->create($to, [
                'from' => $from,
                'body' => $body,
            ]);
            Log::info('TwilioSms sent', [
                'tenant_id' => $tenant->id,
                'sid' => $msg->sid,
                'to' => $to,
            ]);
            return (string) $msg->sid;
        } catch (\Throwable $e) {
            Log::warning('TwilioSms failed', [
                'tenant_id' => $tenant->id,
                'to' => $to,
                'err' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Primul număr activ al tenant-ului. Cache 5 min ca să nu hit DB la
     * fiecare SMS — schimbarea numerelor e rară.
     */
    private function resolveFromNumber(Tenant $tenant): ?string
    {
        $cacheKey = "twilio_sms_from:{$tenant->id}";
        $cached = \Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached !== '' ? $cached : null;
        }

        $number = PhoneNumber::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'twilio')
            ->where('status', PhoneNumber::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->value('number');

        \Cache::put($cacheKey, $number ?: '', 300);
        return $number ?: null;
    }
}
