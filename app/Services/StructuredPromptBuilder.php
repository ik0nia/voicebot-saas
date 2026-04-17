<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Log;

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
     * Rough upper bound on composed prompt size before we log a warning.
     * 20000 chars ≈ 5000 tokens; legitimate bots should sit well under
     * this. Outlier bots (50 FAQs + 30 rules + long freeform extras) may
     * trip it — we warn, we don't truncate.
     */
    private const PROMPT_SIZE_WARN_THRESHOLD = 20000;

    /**
     * Compose and return the final system prompt.
     *
     * @param string|null $userState X2 — optional state hint from
     *    UserStateResolver ('browsing'|'comparing'|'high_intent'|'stuck'|'price_sensitive').
     *    When present, prepends a short steering block so the LLM
     *    adapts tone + verbosity without the chips having to do all
     *    the work. Safe no-op when null.
     */
    public function build(Bot $bot, ?string $userState = null): string
    {
        $sections = $this->sections($bot);

        $parts = [];
        foreach ($sections as $section) {
            if ($section['enabled'] && $section['content'] !== '') {
                $parts[] = $section['content'];
            }
        }

        $output = implode("\n\n", $parts);

        if ($userState && $userState !== 'browsing') {
            $steer = $this->stateSteeringBlock($userState);
            if ($steer !== '') {
                $output = $steer . "\n\n" . $output;
            }
        }

        // Alert on outlier bots. No truncation — the runtime callers
        // downstream may still accept large prompts; the warning is
        // informational so we notice when a tenant drifts into
        // "50 FAQs + 30 rules" territory.
        $size = strlen($output);
        if ($size > self::PROMPT_SIZE_WARN_THRESHOLD) {
            Log::warning('Prompt size exceeds 20K chars for bot #' . $bot->id . ': ' . $size . ' chars');
        }

        return $output;
    }

    /**
     * X2: state-aware steering. Short, prescriptive; sits at the very
     * top of the prompt because recency bias and primacy both want
     * this: the LLM should absorb the user's mood before it even
     * looks at niche defaults.
     */
    private function stateSteeringBlock(string $state): string
    {
        return match ($state) {
            'high_intent' => "=== STARE CLIENT: HIGH INTENT ===\nClientul e gata să acționeze. Răspunsuri SCURTE (max 2-3 propoziții), orientate pe pași concreți. Confirmă acțiunea, nu pune întrebări de discovery.",
            'stuck' => "=== STARE CLIENT: STUCK / CONFUZ ===\nClientul e blocat. Răspunsuri SIMPLE, un singur pas la un moment dat. Evită jargon. Propune TU o direcție în loc să ceri mai multe detalii.",
            'price_sensitive' => "=== STARE CLIENT: PRICE SENSITIVE ===\nClientul caută cea mai bună valoare. Prioritizează opțiuni ieftine de calitate, menționează promoții active. Evită premium dacă nu e explicit cerut.",
            'comparing' => "=== STARE CLIENT: COMPARĂ OPȚIUNI ===\nClientul cântărește alternative. Oferă COMPARAȚIE structurată (listă cu 2-3 bullets pe opțiune, avantaje/dezavantaje). Recomandă clar câștigătorul la final.",
            default => '',
        };
    }

    /**
     * Resolve the tone used for prompt composition for this bot.
     *
     * Precedence (first non-empty wins):
     *   1. bot.settings.tone_guide     — tenant customised tone
     *   2. niche default_tone          — niche template default
     *   3. hardcoded fallback          — safe neutral
     *
     * Kept deliberately narrow: we only fall through to the next level
     * when the current one is missing/empty — a bot with a partially
     * filled tone_guide (e.g. only `length` set) still wins over the
     * niche default, because tenants who edit any tone field expect
     * their choice to stick.
     *
     * @return array{length?: string, register?: string, emoji_ok?: bool, languages?: array<int, string>}
     */
    public function effectiveTone(Bot $bot): array
    {
        $settings = is_array($bot->settings) ? $bot->settings : [];
        $botTone = $settings['tone_guide'] ?? null;
        if (is_array($botTone) && !empty($botTone)) {
            return $botTone;
        }

        if (!empty($bot->niche_slug)) {
            $nicheTone = config('niches.' . $bot->niche_slug . '.default_tone');
            if (is_array($nicheTone) && !empty($nicheTone)) {
                return $nicheTone;
            }
        }

        // Hardcoded safe fallback — matches the tone seeded by Iteration D
        // when a niche has no default_tone of its own.
        return [
            'length' => 'medium',
            'register' => 'tu',
            'emoji_ok' => false,
            'languages' => ['ro'],
        ];
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
                // Resolved via effectiveTone() so the section falls back
                // to the niche default when the bot's own tone_guide is
                // empty. See the toneGuideBlock() doc comment for the
                // precedence rules.
                'content' => $this->toneGuideBlock($this->effectiveTone($bot)),
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
     * Render the TON ȘI STIL block.
     *
     * Tone guide is emitted AFTER the niche addon. This means a tenant's
     * explicit tone_guide settings override niche defaults via LLM
     * recency bias. Behavior is intentional — tenants customizing tone
     * win over niche templates. If you need niche tone to win, set the
     * bot's tone_guide to the niche's default_tone values.
     *
     * Callers should pass the array returned by effectiveTone() — that
     * helper already applies the bot → niche → fallback precedence, so
     * this method only has to format whichever tone won.
     *
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
