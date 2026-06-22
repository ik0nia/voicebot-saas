<?php

declare(strict_types=1);

namespace App\Services\Google;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Places (New) API — autocomplete adrese pentru widget callback +
 * onboarding tenant. Proxy server-side ca să nu expunem cheia API în
 * JavaScript.
 *
 * Cache 1 zi pe query+country — autocomplete result-urile pentru aceeași
 * fragmentă sunt cuasi-statice.
 *
 * Endpoint folosit: https://places.googleapis.com/v1/places:autocomplete
 * (Places API New v1, lansat 2024). Format mai simplu și mai ieftin decât
 * legacy Places.
 */
class GooglePlacesService
{
    private const API_URL = 'https://places.googleapis.com/v1/places:autocomplete';
    private const CACHE_TTL = 86400; // 1 zi

    /**
     * Autocomplete adresă. Returnează maxim 5 sugestii ranked.
     *
     * @return array<int, array{place_id: string, primary: string, secondary: string, full: string}>
     */
    public function autocomplete(string $input, string $country = 'ro'): array
    {
        $input = trim($input);
        if (mb_strlen($input) < 3) return [];

        $apiKey = config('services.google.places_api_key');
        if (!$apiKey) {
            Log::debug('GooglePlaces: no API key configured');
            return [];
        }

        $cacheKey = 'places_ac:' . md5($input . '|' . $country);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(4)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $apiKey,
                ])
                ->post(self::API_URL, [
                    'input' => $input,
                    'includedRegionCodes' => [$country],
                    'languageCode' => 'ro',
                ]);

            if (!$response->successful()) {
                Log::warning('GooglePlaces autocomplete failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);
                return [];
            }

            $suggestions = $response->json('suggestions') ?? [];
            $results = [];
            foreach ($suggestions as $s) {
                $pred = $s['placePrediction'] ?? null;
                if (!$pred) continue;
                $results[] = [
                    'place_id' => (string) ($pred['placeId'] ?? ''),
                    'primary' => (string) ($pred['structuredFormat']['mainText']['text'] ?? ''),
                    'secondary' => (string) ($pred['structuredFormat']['secondaryText']['text'] ?? ''),
                    'full' => (string) ($pred['text']['text'] ?? ''),
                ];
                if (count($results) >= 5) break;
            }

            Cache::put($cacheKey, $results, self::CACHE_TTL);
            return $results;
        } catch (\Throwable $e) {
            Log::warning('GooglePlaces failed', ['err' => $e->getMessage()]);
            return [];
        }
    }
}
