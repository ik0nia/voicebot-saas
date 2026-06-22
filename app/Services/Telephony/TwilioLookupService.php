<?php

declare(strict_types=1);

namespace App\Services\Telephony;

use App\Services\TwilioService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio Lookup v2 — validează numere de telefon înainte să intre în
 * pipeline-ul de lead. Catch: numere fictive, neformatate corect, prefixe
 * inexistente. Opțional carrier + line_type pentru anti-fraud.
 *
 * Cache 7 zile pe (E164 normalizat) — rezultatul se schimbă rar.
 *
 * Cost: gratis pentru basic validation (validare format + existenta),
 * ~$0.005 / lookup pentru line_type_intelligence. Folosim doar basic
 * by default; line type doar dacă e cerut explicit.
 */
class TwilioLookupService
{
    private const API_BASE = 'https://lookups.twilio.com/v2/PhoneNumbers';
    private const CACHE_TTL = 604800; // 7 zile

    public function __construct(private TwilioService $twilio) {}

    /**
     * Validează un număr. Returnează:
     * - valid: true dacă Twilio confirmă format + existență
     * - e164: forma normalizată +40xxxxxxxxx (sau null dacă invalid)
     * - country: ISO 2-letter (RO, GB, DE...)
     * - line_type: 'mobile' / 'landline' / 'voip' / etc — DOAR dacă requestType='full'
     * - carrier: numele operatorului — DOAR dacă requestType='full'
     *
     * @return array{valid: bool, e164: ?string, country: ?string, line_type: ?string, carrier: ?string, raw?: array}
     */
    public function validate(string $phone, string $defaultCountry = 'RO', string $requestType = 'basic'): array
    {
        $cleaned = $this->cleanPhone($phone, $defaultCountry);
        if (!$cleaned) {
            return ['valid' => false, 'e164' => null, 'country' => null, 'line_type' => null, 'carrier' => null];
        }

        $cacheKey = "twilio_lookup_v2:{$cleaned}:{$requestType}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $sid = config('services.twilio.account_sid');
            $token = config('services.twilio.auth_token');
            if (!$sid || !$token) {
                Log::warning('TwilioLookup: missing credentials');
                return ['valid' => false, 'e164' => null, 'country' => null, 'line_type' => null, 'carrier' => null];
            }

            $fields = $requestType === 'full' ? ['line_type_intelligence'] : [];
            $params = !empty($fields) ? ['Fields' => implode(',', $fields)] : [];

            $response = Http::withBasicAuth($sid, $token)
                ->timeout(5)
                ->get(self::API_BASE . '/' . rawurlencode($cleaned), $params);

            if (!$response->successful()) {
                // 404 = număr invalid; orice altceva = best-effort fallback la cleaned.
                $result = $response->status() === 404
                    ? ['valid' => false, 'e164' => null, 'country' => null, 'line_type' => null, 'carrier' => null]
                    : ['valid' => true, 'e164' => $cleaned, 'country' => null, 'line_type' => null, 'carrier' => null];
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                return $result;
            }

            $data = $response->json();
            $lineInfo = $data['line_type_intelligence'] ?? [];

            $result = [
                'valid' => (bool) ($data['valid'] ?? true),
                'e164' => $data['phone_number'] ?? $cleaned,
                'country' => $data['country_code'] ?? null,
                'line_type' => $lineInfo['type'] ?? null,
                'carrier' => $lineInfo['carrier_name'] ?? null,
            ];
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        } catch (\Throwable $e) {
            Log::warning('TwilioLookup failed', ['phone' => $cleaned, 'err' => $e->getMessage()]);
            // Fail-open — nu blocăm fluxul dacă Twilio e down.
            return ['valid' => true, 'e164' => $cleaned, 'country' => null, 'line_type' => null, 'carrier' => null];
        }
    }

    /**
     * Normalizare la format E.164 ca să match cu schema Twilio.
     */
    private function cleanPhone(string $phone, string $defaultCountry): ?string
    {
        $phone = trim($phone);
        if ($phone === '') return null;
        // Strip everything except + and digits.
        $stripped = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if ($stripped === '') return null;

        if (str_starts_with($stripped, '+')) {
            return $stripped;
        }

        // Numerele RO încep cu 07xxxxxxxx; adăugăm +40 și scoatem 0.
        if ($defaultCountry === 'RO') {
            if (str_starts_with($stripped, '40')) return '+' . $stripped;
            if (str_starts_with($stripped, '0')) return '+40' . substr($stripped, 1);
            if (strlen($stripped) === 9) return '+40' . $stripped;
        }
        // Fallback generic.
        return '+' . $stripped;
    }
}
