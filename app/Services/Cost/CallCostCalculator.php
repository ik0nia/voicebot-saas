<?php

namespace App\Services\Cost;

use App\Models\PlatformSetting;

/**
 * Centralised per-call cost math.
 *
 * Token rates + per-minute rates live in PlatformSetting so the
 * operator can update them from Admin → Settings without a deploy
 * (OpenAI re-prices Realtime every few months). Defaults match the
 * gpt-4o-realtime-preview-2024-12-17 published prices verified
 * against the OpenAI Costs API on 2026-04-16.
 *
 * Usage:
 *   $cost = app(CallCostCalculator::class);
 *   $openaiCents = $cost->openaiFromUsage($usage);
 *   $twilioCents = $cost->twilioForCountry('US', $durationSeconds);
 */
class CallCostCalculator
{
    /**
     * Rate keys under PlatformSetting — cents per 1M tokens.
     * Falls back to the hard-coded defaults if unset.
     */
    private const OPENAI_RATES = [
        'text_input'         => ['key' => 'openai_rt_text_input_cents_per_1m',         'default' => 500],   // $5.00
        'text_input_cached'  => ['key' => 'openai_rt_text_input_cached_cents_per_1m',  'default' => 250],   // $2.50
        'text_output'        => ['key' => 'openai_rt_text_output_cents_per_1m',        'default' => 2000],  // $20.00
        'audio_input'        => ['key' => 'openai_rt_audio_input_cents_per_1m',        'default' => 4000],  // $40.00
        'audio_input_cached' => ['key' => 'openai_rt_audio_input_cached_cents_per_1m', 'default' => 250],   // $2.50
        'audio_output'       => ['key' => 'openai_rt_audio_output_cents_per_1m',       'default' => 8000],  // $80.00
    ];

    /**
     * Twilio per-minute voice pricing in cents. Source of truth is
     * the Twilio pricing page; we cache the commonly-used rates here.
     * Inbound only — outbound is charged to us per country of the
     * dialed number. Operator can override via PlatformSetting.
     */
    private const TWILIO_INBOUND_PER_MIN_CENTS = [
        'US' => 0.85,
        'RO' => 2.50,
        'GB' => 1.10,
        'DE' => 1.30,
        'FR' => 1.40,
        'ES' => 1.30,
        'IT' => 1.00,
        // Any unknown country falls back to 4c/min so the row
        // shows up in reports rather than silently $0.
        'DEFAULT' => 4.00,
    ];

    public function rate(string $key): float
    {
        $def = self::OPENAI_RATES[$key] ?? null;
        if (!$def) return 0.0;
        return (float) PlatformSetting::get($def['key'], $def['default']);
    }

    /**
     * Cents from an OpenAI Realtime `response.done.usage` payload.
     * Expects the shape OpenAI returns (input_tokens,
     * input_token_details, output_token_details, cached_tokens_details).
     */
    public function openaiFromUsage(array $usage): int
    {
        $audioIn  = (int) ($usage['input_token_details']['audio_tokens'] ?? 0);
        $audioInC = (int) ($usage['input_token_details']['cached_tokens_details']['audio_tokens'] ?? 0);
        $audioInFresh = max(0, $audioIn - $audioInC);
        $audioOut = (int) ($usage['output_token_details']['audio_tokens'] ?? 0);

        $textIn  = (int) ($usage['input_token_details']['text_tokens'] ?? 0);
        $textInC = (int) ($usage['input_token_details']['cached_tokens_details']['text_tokens'] ?? 0);
        $textInFresh = max(0, $textIn - $textInC);
        $textOut = (int) ($usage['output_token_details']['text_tokens'] ?? 0);

        $cents = 0.0;
        $cents += $audioInFresh * $this->rate('audio_input') / 1_000_000;
        $cents += $audioInC     * $this->rate('audio_input_cached') / 1_000_000;
        $cents += $audioOut     * $this->rate('audio_output') / 1_000_000;
        $cents += $textInFresh  * $this->rate('text_input') / 1_000_000;
        $cents += $textInC      * $this->rate('text_input_cached') / 1_000_000;
        $cents += $textOut      * $this->rate('text_output') / 1_000_000;

        return (int) round($cents);
    }

    /**
     * Twilio inbound voice cost in cents for a completed call.
     *
     * @param string|null $phoneNumber  Dialed number in E.164 form (+40..., +1...).
     * @param int $durationSeconds
     * @return int  Cents (rounded).
     */
    public function twilioForNumber(?string $phoneNumber, int $durationSeconds): int
    {
        if ($durationSeconds <= 0) return 0;
        $country = $this->countryFromE164($phoneNumber);
        $ratePerMin = $this->twilioRateForCountry($country);
        $minutes = $durationSeconds / 60;
        return (int) round($minutes * $ratePerMin);
    }

    private function twilioRateForCountry(string $countryCode): float
    {
        $override = PlatformSetting::get('twilio_inbound_cents_per_min_' . strtolower($countryCode));
        if ($override !== null && $override !== '') {
            return (float) $override;
        }
        return self::TWILIO_INBOUND_PER_MIN_CENTS[$countryCode]
            ?? self::TWILIO_INBOUND_PER_MIN_CENTS['DEFAULT'];
    }

    private function countryFromE164(?string $phone): string
    {
        if (!$phone) return 'DEFAULT';
        $prefixes = [
            '+40' => 'RO',
            '+1'  => 'US',
            '+44' => 'GB',
            '+49' => 'DE',
            '+33' => 'FR',
            '+34' => 'ES',
            '+39' => 'IT',
        ];
        foreach ($prefixes as $prefix => $iso) {
            if (str_starts_with($phone, $prefix)) return $iso;
        }
        return 'DEFAULT';
    }
}
