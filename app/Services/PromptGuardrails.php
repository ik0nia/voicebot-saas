<?php

namespace App\Services;

/**
 * Centralized guardrails injected into all AI prompts (chat, voice, channels).
 *
 * These rules are appended AFTER the user-configured system_prompt and knowledge context,
 * ensuring they cannot be overridden by tenant configuration.
 */
class PromptGuardrails
{
    /**
     * Core anti-hallucination guardrail for all channels.
     * Injected at the END of the system prompt so it takes precedence.
     */
    public static function antiHallucination(): string
    {
        return implode("\n", [
            '',
            '=== REGULI OBLIGATORII ===',
            '- Răspunde STRICT pe baza informațiilor din context. NU inventa date, prețuri, termene sau specificații.',
            '- Dacă nu ai informația cerută, spune natural: "Nu am detalii despre asta momentan" și oferă o alternativă (contact, reformulare).',
            '- NU presupune și NU extrapola.',
            '- Dacă ești nesigur, spune-o direct și oferă opțiunea de a contacta echipa.',
            '- Întrebări conversaționale (salut, mulțumesc, despre tine) — răspunde natural fără context suplimentar.',
            '- CÂND contextul conține "[NU s-au găsit produse]" sau "[NU am găsit produse]" — NU spune că ai găsit produse. Recunoaște că nu ai și sugerează reformularea.',
            '=== SFÂRȘIT REGULI ===',
        ]);
    }

    /**
     * Additional voice-specific guardrail appended after the general one.
     */
    public static function voiceSpecific(): string
    {
        return implode("\n", [
            '',
            'REGULI VOCALE:',
            '- Maxim 2 propoziții scurte, naturale.',
            '- NU enumera liste. Menționează cel mai relevant și întreabă dacă vrea mai mult.',
            '- Prețuri în cuvinte: "douăzeci și cinci de lei", nu "25 RON".',
            '- Dacă mesajul transcris pare incorect, cere reformularea politicos.',
            '- Termină cu o întrebare scurtă care menține conversația.',
        ]);
    }

    /**
     * Append guardrails to any system prompt.
     * Use this everywhere instead of manually adding rules.
     */
    public static function apply(string $systemPrompt, bool $isVoice = false): string
    {
        $prompt = $systemPrompt . self::antiHallucination();

        if ($isVoice) {
            $prompt .= self::voiceSpecific();
        }

        return $prompt;
    }
}
