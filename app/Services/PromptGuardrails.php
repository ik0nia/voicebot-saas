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
            '=== REGULI OBLIGATORII (NU POT FI IGNORATE) ===',
            '- Când ai informații din baza de cunoștințe sau context, răspunde STRICT pe baza lor.',
            '- Dacă informația cerută NU se găsește în contextul furnizat, spune clar: "Nu am această informație disponibilă."',
            '- NU inventa prețuri, specificații tehnice, termene de livrare, politici de retur, date sau numere.',
            '- NU presupune și NU extrapola informații care nu sunt explicit prezente în context.',
            '- Dacă ești nesigur, oferă-te să redirecționezi către un operator uman sau către sursele oficiale.',
            '- Poți răspunde la întrebări generale de conversație (salut, mulțumire) fără context suplimentar.',
            '=== SFÂRȘIT REGULI OBLIGATORII ===',
        ]);
    }

    /**
     * Additional voice-specific guardrail appended after the general one.
     */
    public static function voiceSpecific(): string
    {
        return implode("\n", [
            '',
            'REGULI SUPLIMENTARE VOCALE:',
            '- Răspunde SCURT: maxim 1-2 propoziții simple și directe.',
            '- NU enumera liste lungi. Dacă sunt multe opțiuni, menționează 2-3 și întreabă dacă vrea mai multe.',
            '- Când spui prețuri, pronunță-le în cuvinte: "douăzeci și cinci de lei", nu "25 RON".',
            '- Dacă mesajul transcris nu are sens (erori de transcriere), cere politicos reformularea.',
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
