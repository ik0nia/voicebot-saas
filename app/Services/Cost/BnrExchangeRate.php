<?php

namespace App\Services\Cost;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BNR (Banca Națională a României) reference exchange rates. Pulled
 * from the official XML feed at https://www.bnr.ro/nbrfxrates.xml
 * once a day — BNR publishes around 13:00 EET, so a 24h cache covers
 * the same published day for every request that comes in.
 *
 * We only use USD → RON here (OpenAI + Twilio charge in USD, tenant
 * billing is in RON). If we ever need EUR / GBP, the same cache key
 * extends — the parser already returns every currency BNR lists.
 */
class BnrExchangeRate
{
    private const FEED_URL = 'https://www.bnr.ro/nbrfxrates.xml';
    private const CACHE_KEY = 'bnr_rates';
    private const CACHE_TTL_SECONDS = 86400; // 24h

    // Fallback used if the feed is unreachable. Updated 2026-04 from
    // https://cursbnr.ro/curs-valutar — overridable via PlatformSetting
    // `bnr_fallback_usd_ron` so the operator can tune it without a
    // deploy when the fallback is too stale.
    private const FALLBACK_USD_RON = 4.55;

    public function usdToRon(): float
    {
        $rates = $this->allRates();
        return (float) ($rates['USD'] ?? $this->fallbackRate());
    }

    public function convert(float $amountUsd): float
    {
        return round($amountUsd * $this->usdToRon(), 4);
    }

    /**
     * Force a refresh — useful for ops scripts or a manual "refresh
     * now" button in admin if we add one.
     */
    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);
        return $this->allRates();
    }

    private function allRates(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            try {
                $response = Http::timeout(10)->get(self::FEED_URL);
                if (!$response->successful()) {
                    Log::warning('BnrExchangeRate: feed non-2xx', ['status' => $response->status()]);
                    return [];
                }
                return $this->parseXml($response->body());
            } catch (\Throwable $e) {
                Log::warning('BnrExchangeRate: feed fetch failed', ['err' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Parse BNR's XML into ['USD' => 4.5123, 'EUR' => 4.9756, …].
     * BNR uses the `multiplier` attribute for some currencies
     * (JPY published per 100 yen etc.); we normalise to "1 unit of
     * that currency = X RON".
     */
    private function parseXml(string $xml): array
    {
        $out = [];
        try {
            $doc = simplexml_load_string($xml);
            if (!$doc) return [];
            $cube = $doc->Body->Cube ?? null;
            if (!$cube) return [];
            foreach ($cube->Rate as $rate) {
                $currency = (string) $rate['currency'];
                $multiplier = (float) ($rate['multiplier'] ?? 1);
                $value = (float) $rate;
                if ($currency === '' || $value <= 0) continue;
                $out[$currency] = $multiplier > 1 ? $value / $multiplier : $value;
            }
        } catch (\Throwable $e) {
            Log::warning('BnrExchangeRate: XML parse failed', ['err' => $e->getMessage()]);
        }
        return $out;
    }

    private function fallbackRate(): float
    {
        return (float) \App\Models\PlatformSetting::get('bnr_fallback_usd_ron', self::FALLBACK_USD_RON);
    }
}
