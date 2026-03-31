<?php

namespace App\Services;

/**
 * Filters Whisper hallucinations — phantom transcripts generated from silence,
 * background noise, or low-quality audio input.
 *
 * Ported from frontend isWhisperHallucination() to ensure server-side filtering.
 */
class WhisperHallucinationFilter
{
    /**
     * Check if a transcript is likely a Whisper hallucination.
     */
    public static function isHallucination(?string $text): bool
    {
        if (!$text) return true;

        $t = trim($text);
        if (mb_strlen($t) < 3) return true;

        // Normalize diacritics for easier matching
        $lower = mb_strtolower($t);
        $lower = str_replace(
            ['ă', 'â', 'î', 'ì', 'ș', 'ş', 'ț', 'ţ'],
            ['a', 'a', 'i', 'i', 's', 's', 't', 't'],
            $lower
        );

        // Pattern-based detection
        $patterns = [
            // YouTube / TV / podcast sign-offs
            '/multumesc\s+(pentru|de)\s+(vizionare|urmarire|atentie|tot|ca)/u',
            '/va\s+multumesc/u',
            '/dati\s+(un\s+)?like/u',
            '/lasati\s+un\s+comentariu/u',
            '/distribuiti/u',
            '/abonati[\s-]?va/u',
            '/va\s+abonati/u',
            '/abonati\s+la\s+(canal|pagina)/u',
            '/uitati\s+sa\s+va\s+abonati/u',
            '/material\s+video/u',
            '/retea\s+social|retele\s+sociale/u',
            '/subscribe/i',
            '/like\s+(si|and)\s+subscribe/i',
            '/thank(s|\s+you)\s+(for\s+)?watch/i',

            // Subtitles / credits
            '/subtitr(are|at|ari)/u',
            '/traducere\s+(si\s+)?subtitr/u',
            '/transcriere\s+realiz/u',
            '/subtitles\s+by/i',
            '/amara\.org/i',
            '/realizat\s+de/u',
            '/produs\s+de/u',
            '/regizat\s+de/u',
            '/sustinut\s+de/u',
            '/sponsorizat\s+de/u',
            '/un\s+proiect\s+(al|de)/u',
            '/in\s+parteneriat\s+cu/u',
            '/copyright|©/i',
            '/www\.|http/i',

            // TV / show transitions
            '/vizionare\s+(placuta|frumoasa)/u',
            '/auditie\s+placuta/u',
            '/ne\s+vedem\s+(in|la|data|saptamana|episod|curand)/u',
            '/urmatorul\s+(episod|video|material|clip|capitol)/u',
            '/urmatoarea\s+(reteta|editie|emisiune|parte)/u',
            '/pe\s+(curand|saptamana\s+viitoare|data\s+viitoare)/u',
            '/va\s+asteptam/u',
            '/ramaneti\s+(pe|cu|alaturi)/u',
            '/stati\s+(pe|cu)\s+noi/u',
            '/reveniti/u',

            // Greetings / sign-offs (not real customer questions)
            '/la\s+revedere/u',
            '/noapte\s+buna/u',
            '/somn\s+usor/u',
            '/pofta\s+buna/u',
            '/bon\s+appetit/i',
            '/la\s+multi\s+ani/u',
            '/craciun\s+fericit/u',
            '/paste\s+fericit/u',
            '/sarbator/u',
            '/an\s+nou\s+fericit/u',

            // Sound effects in brackets or parentheses
            '/\[(muzica|aplauze|ras|rasete|suspine)\]/u',
            '/\((muzica|aplauze|ras|rasete|suspine)\)/u',
            '/^\s*(muzica|aplauze|rasete|suspine)\s*$/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $lower)) return true;
        }

        // Only punctuation/whitespace/ellipsis
        if (preg_match('/^[\s.,!?…\-–—:;\'"„"]+$/', $t)) return true;

        // Very short without vowels
        if (mb_strlen($t) < 8 && !preg_match('/[aeiouăîâșț]/iu', $t)) return true;

        // Single filler sounds
        if (preg_match('/^(uh+|um+|hm+|mhm+|ah+|oh+|eh+)\s*[.!?]*$/i', $t)) return true;

        // Repeated same word 3+ times
        $words = preg_split('/\s+/', $t);
        if (count($words) >= 3 && count(array_unique(array_map('mb_strtolower', $words))) === 1) return true;

        // Short sign-offs (1-3 words)
        if (count($words) <= 3 && preg_match('/(bafta|succes|mersi|pa|ciao|bye|adio|salut)/u', $lower)) return true;

        // Note: We intentionally do NOT filter short 1-2 word fragments on the backend.
        // In voice calls, customers often say single product names ("polistiren", "șuruburi")
        // which are valid queries. The frontend demo has stricter filtering for UI purposes.

        return false;
    }
}
