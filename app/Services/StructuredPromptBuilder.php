<?php

namespace App\Services;

use App\Models\Bot;

/**
 * Composes a bot's final system prompt from structured settings.
 *
 * Data source: $bot->settings (JSON). The structured config is seeded by
 * the 2026_04_17_170000 migration and edited by tenants via the dashboard.
 *
 * Not to be confused with \App\Services\PromptBuilder — that's the
 * existing fluent composer (knowledge + products + voice modifiers) used
 * by chat/voice runtime. This class is a narrow, stateless formatter for
 * the new structured-config feature and runs BEFORE the fluent composer:
 * its output becomes the `$base` the fluent builder wraps.
 *
 * Each build() call re-composes from scratch — stateless. Callers that
 * want caching wrap the result themselves (see ChatbotApiController
 * ::buildPromptForStream and generateAIResponse).
 *
 * Sanitisation: user-authored fields (faqs, dont_rules, business_info,
 * system_prompt escape hatch) get a minimal filter that strips obvious
 * prompt-injection attempts. This isn't a full guard — a determined
 * attacker with tenant-admin access can still bend the prompt — but it
 * catches the lowest-effort "Ignore previous instructions" paste.
 */
class StructuredPromptBuilder
{
    /** Hard cap on FAQ entries to keep the prompt bounded. */
    private const FAQ_LIMIT = 50;

    /**
     * Language code → display name shown inside the tone block.
     * Kept small: only the languages we actually support end-to-end.
     */
    private const LANGUAGE_NAMES = [
        'ro' => 'română',
        'en' => 'engleză',
        'de' => 'germană',
        'fr' => 'franceză',
        'es' => 'spaniolă',
        'it' => 'italiană',
    ];

    /**
     * Regex fragments matching the most common injection attempts.
     * Match is case-insensitive; a line hitting any pattern is dropped
     * entirely rather than partially rewritten (less surprising).
     */
    private const INJECTION_PATTERNS = [
        '/ignore\s+(all\s+)?(previous|prior|above)\s+(instructions|prompts?|messages?|rules?)/i',
        '/disregard\s+(all\s+)?(previous|prior|above)\s+(instructions|prompts?|messages?|rules?)/i',
        '/forget\s+(all\s+)?(previous|prior|above|everything)/i',
        '/you\s+are\s+now\s+(a\s+)?(different|new)/i',
        '/system\s*[:=]/i',
        '/={3,}\s*(system|instructions?|prompt)\s*={3,}/i',
        '/<\s*\/?\s*(system|user|assistant)\s*>/i',
    ];

    /**
     * Compose and return the final system prompt.
     */
    public function build(Bot $bot): string
    {
        $sections = $this->sections($bot);

        $parts = [];
        foreach ($sections as $section) {
            if ($section['enabled'] && $section['content'] !== '') {
                $parts[] = $section['content'];
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Structured view of each section — used by build() and by the
     * preview endpoint so the UI can show which blocks are active.
     *
     * @return array<int, array{name: string, enabled: bool, content: string}>
     */
    public function sections(Bot $bot): array
    {
        $settings = is_array($bot->settings) ? $bot->settings : [];
        $flagOn = (bool) ($settings['use_structured_prompt'] ?? false);

        return [
            [
                'name' => 'niche_addon',
                'enabled' => true,
                'content' => $this->nicheAddon($bot),
            ],
            [
                'name' => 'business_info',
                'enabled' => true,
                'content' => $this->businessInfoBlock($settings['business_info'] ?? []),
            ],
            [
                'name' => 'faqs',
                'enabled' => true,
                'content' => $this->faqBlock($settings['faqs'] ?? []),
            ],
            [
                'name' => 'dont_rules',
                'enabled' => true,
                'content' => $this->dontRulesBlock($settings['dont_rules'] ?? []),
            ],
            [
                'name' => 'tone_guide',
                'enabled' => true,
                'content' => $this->toneGuideBlock($settings['tone_guide'] ?? []),
            ],
            [
                'name' => 'escape_hatch',
                // The freeform system_prompt is only appended when the flag
                // is on — the off-path uses it directly, so including it
                // here too would double-print it.
                'enabled' => $flagOn,
                'content' => $this->escapeHatch($bot),
            ],
        ];
    }

    private function nicheAddon(Bot $bot): string
    {
        if (empty($bot->niche_slug)) {
            return '';
        }
        $addon = config('niches.' . $bot->niche_slug . '.prompt_addon');
        if (!is_string($addon) || trim($addon) === '') {
            return '';
        }
        return trim($addon);
    }

    /**
     * @param array<string, mixed> $info
     */
    private function businessInfoBlock(array $info): string
    {
        // Map of settings key → human label shown in the prompt.
        // Only labels for fields we render in the block; extras go last.
        $labels = [
            'address' => 'Adresă',
            'hours_text' => 'Program',
            'phone' => 'Telefon',
            'email' => 'Email',
            'website' => 'Website',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
        ];

        $lines = [];
        foreach ($labels as $key => $label) {
            $value = $info[$key] ?? '';
            if (is_string($value) && trim($value) !== '') {
                $lines[] = $label . ': ' . $this->sanitize(trim($value));
            }
        }

        // hours_schedule is an array — render as "Luni: 09-18" style lines.
        $schedule = $info['hours_schedule'] ?? [];
        if (is_array($schedule) && !empty($schedule)) {
            foreach ($schedule as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $day = isset($entry['day']) ? trim((string) $entry['day']) : '';
                $hours = isset($entry['hours']) ? trim((string) $entry['hours']) : '';
                if ($day !== '' && $hours !== '') {
                    $lines[] = $this->sanitize($day . ': ' . $hours);
                }
            }
        }

        $extras = $info['extras'] ?? '';
        if (is_string($extras) && trim($extras) !== '') {
            $lines[] = $this->sanitize(trim($extras));
        }

        if (empty($lines)) {
            return '';
        }

        return "=== INFORMAȚII BUSINESS ===\n" . implode("\n", $lines);
    }

    /**
     * @param array<int, mixed> $faqs
     */
    private function faqBlock(array $faqs): string
    {
        $rendered = [];
        foreach ($faqs as $faq) {
            if (count($rendered) >= self::FAQ_LIMIT) {
                break;
            }
            if (!is_array($faq)) {
                continue;
            }
            $q = isset($faq['question']) ? trim((string) $faq['question']) : '';
            $a = isset($faq['answer']) ? trim((string) $faq['answer']) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $rendered[] = 'Î: ' . $this->sanitize($q) . "\nR: " . $this->sanitize($a);
        }

        if (empty($rendered)) {
            return '';
        }

        return "=== ÎNTREBĂRI FRECVENTE ===\n" . implode("\n\n", $rendered);
    }

    /**
     * @param array<int, mixed> $rules
     */
    private function dontRulesBlock(array $rules): string
    {
        $bullets = [];
        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }
            $clean = trim($rule);
            if ($clean === '') {
                continue;
            }
            $bullets[] = '- ' . $this->sanitize($clean);
        }

        if (empty($bullets)) {
            return '';
        }

        return "=== REGULI STRICTE — NU FACE NICIODATĂ ===\n" . implode("\n", $bullets);
    }

    /**
     * @param array<string, mixed> $tone
     */
    private function toneGuideBlock(array $tone): string
    {
        $sentences = [];

        $length = $tone['length'] ?? null;
        if (is_string($length)) {
            $sentences[] = match ($length) {
                'short' => 'Răspunde scurt, 1-2 propoziții.',
                'medium' => 'Răspunde echilibrat, 2-4 propoziții.',
                'long' => 'Poți da răspunsuri detaliate când e util.',
                default => null,
            };
        }

        $register = $tone['register'] ?? null;
        if (is_string($register)) {
            $sentences[] = match ($register) {
                'tu' => 'Tutuiește clientul.',
                'dvs' => 'Folosește forma de politețe (dvs.).',
                'neutru' => 'Folosește un ton neutru, fără adresare directă.',
                default => null,
            };
        }

        if (array_key_exists('emoji_ok', $tone)) {
            $sentences[] = ! empty($tone['emoji_ok'])
                ? 'Poți folosi emoji cu moderație.'
                : 'Nu folosi emoji.';
        }

        $languages = $tone['languages'] ?? null;
        if (is_array($languages) && !empty($languages)) {
            $names = [];
            foreach ($languages as $code) {
                if (!is_string($code)) {
                    continue;
                }
                $names[] = self::LANGUAGE_NAMES[$code] ?? $code;
            }
            if (!empty($names)) {
                $sentences[] = 'Limbi acceptate: ' . implode(', ', $names) . '.';
            }
        }

        $sentences = array_values(array_filter($sentences, fn ($s) => is_string($s) && $s !== ''));

        if (empty($sentences)) {
            return '';
        }

        return "=== TON ȘI STIL ===\n" . implode("\n", $sentences);
    }

    private function escapeHatch(Bot $bot): string
    {
        $extra = $bot->system_prompt ?? '';
        if (!is_string($extra) || trim($extra) === '') {
            return '';
        }
        // The escape hatch is the tenant's own freeform prompt, so we
        // sanitise it too — even the legitimate owner might paste text
        // copied from a chat log that contains injection-looking lines.
        $clean = $this->sanitize(trim($extra));
        if ($clean === '') {
            return '';
        }
        return "=== INSTRUCȚIUNI SUPLIMENTARE ===\n" . $clean;
    }

    /**
     * Strip lines that match common prompt-injection patterns.
     * Kept minimal on purpose — aggressive filtering breaks legitimate
     * help-desk FAQs that use phrases like "system status".
     */
    private function sanitize(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $kept[] = $line;
                continue;
            }
            $matched = false;
            foreach (self::INJECTION_PATTERNS as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $kept[] = $line;
            }
        }

        return implode("\n", $kept);
    }
}
