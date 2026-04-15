<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validate Romanian CUI (VAT ID) — format check + optional VIES lookup
 * (EU central registry, soap/rest endpoint). Valid CUI on invoice lets
 * the tenant recover VAT, so getting this right matters for B2B.
 */
class CuiValidator
{
    /**
     * Accepts "RO12345678", "12345678", "ro 1234567" — normalizes to
     * "RO12345678". Returns null if the input can't be parsed.
     */
    public function normalize(string $input): ?string
    {
        $clean = preg_replace('/\s+/', '', strtoupper($input));
        if (! preg_match('/^(?:RO)?(\d{2,10})$/', (string) $clean, $m)) {
            return null;
        }
        return 'RO' . $m[1];
    }

    /**
     * Format + checksum verification without hitting VIES. Fast, offline.
     */
    public function isFormatValid(string $input): bool
    {
        $normalized = $this->normalize($input);
        if (! $normalized) {
            return false;
        }
        $digits = substr($normalized, 2);
        if (strlen($digits) < 2 || strlen($digits) > 10) {
            return false;
        }

        // Romanian CUI checksum: weights [7,5,3,2,1,7,5,3,2], sum * 10 mod 11
        // last digit is the check digit; if 10 → 0.
        $weights = [7, 5, 3, 2, 1, 7, 5, 3, 2];
        $body = substr($digits, 0, -1);
        $check = (int) substr($digits, -1);

        // left-pad body to 9 for weight alignment
        $body = str_pad($body, 9, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $body[$i]) * $weights[$i];
        }
        $computed = ($sum * 10) % 11;
        if ($computed === 10) {
            $computed = 0;
        }
        return $computed === $check;
    }

    /**
     * Live VIES check (EU central registry). Returns:
     *   ['valid' => bool, 'name' => ?string, 'address' => ?string]
     * Cached 24h because VIES is rate-limited and flaky.
     */
    public function verifyLive(string $input): array
    {
        $normalized = $this->normalize($input);
        if (! $normalized) {
            return ['valid' => false, 'name' => null, 'address' => null];
        }

        return Cache::remember("vies_{$normalized}", now()->addDay(), function () use ($normalized) {
            $number = substr($normalized, 2);
            try {
                $resp = Http::timeout(8)->acceptJson()
                    ->get("https://ec.europa.eu/taxation_customs/vies/rest-api/ms/RO/vat/{$number}");
                if (! $resp->successful()) {
                    return ['valid' => false, 'name' => null, 'address' => null];
                }
                $data = $resp->json();
                return [
                    'valid' => (bool) ($data['isValid'] ?? false),
                    'name' => $data['name'] ?? null,
                    'address' => $data['address'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::warning('VIES lookup failed', ['cui' => $normalized, 'error' => $e->getMessage()]);
                return ['valid' => false, 'name' => null, 'address' => null];
            }
        });
    }
}
