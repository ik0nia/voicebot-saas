<?php

namespace App\Services\Widget;

use App\Models\Conversation;

/**
 * Lightweight heuristic inference of a caller's state across the
 * current conversation. Returns one of:
 *
 *   browsing         — default; no strong signal either way
 *   comparing        — user is weighing options (X vs Y, diferență, mai bun)
 *   high_intent      — explicit buying/booking verbs (vreau, comand, rezerv)
 *   stuck            — asking lots of questions without deciding
 *   price_sensitive  — budget signals (preț, reducere, ieftin, promoție)
 *
 * Intentionally heuristic — no LLM call, no DB round-trip beyond
 * reading conversation.metadata + the messages already loaded.
 * Wrong answers are cheap; the UI uses state only to pick chip copy.
 *
 * Graceful by design — returns 'browsing' when signals are absent
 * or ambiguous. Downstream callers should treat it as a soft hint.
 */
class UserStateResolver
{
    public const BROWSING = 'browsing';
    public const COMPARING = 'comparing';
    public const HIGH_INTENT = 'high_intent';
    public const STUCK = 'stuck';
    public const PRICE_SENSITIVE = 'price_sensitive';

    /**
     * Heuristic lexicons. Diacritics folded at comparison time so
     * "preț" and "pret" both match.
     */
    private const LEX_INTENT = [
        'vreau',
        'comand', 'cumpăr', 'cumpar',
        'rezerv', 'programez',
        'adaug in cos', 'adauga in cos',
        'finalizez', 'plătesc', 'platesc',
    ];
    private const LEX_COMPARE = [
        ' vs ',
        'diferenț', 'diferent',
        'mai bun', 'mai rau',
        'comparat', 'compară', 'compara',
        'alternativ',
        'care e mai',
    ];
    private const LEX_PRICE = [
        'ieftin',
        'reducere',
        'promoți', 'promoti',
        'buget',
        'preț', 'pret',
        'scump',
        'cod promo',
    ];
    private const LEX_URGENCY = [
        'acum', 'astăzi', 'astazi', 'urgent', 'asap', 'imediat',
    ];

    /**
     * @param Conversation|null $conversation  May be null for the very
     *                                        first turn of a session.
     * @param string $currentUserMessage       The message the user just sent.
     * @param array  $pageContext              Optional — quick_reply clicks
     *                                        recorded in metadata help too.
     * @return array{state:string, signals:array<int,string>, confidence:string}
     */
    public function resolve(?Conversation $conversation, string $currentUserMessage, array $pageContext = []): array
    {
        $signals = [];

        // Collect the last ~3 user messages (+ current) for pattern
        // matching. More than that adds noise without signal.
        $userMessages = [];
        if ($conversation) {
            $recent = $conversation->messages()
                ->where('direction', 'inbound')
                ->latest('id')
                ->limit(3)
                ->pluck('content')
                ->toArray();
            $userMessages = array_reverse($recent);
        }
        $userMessages[] = $currentUserMessage;

        $folded = $this->foldDiacritics(mb_strtolower(implode(" \n ", array_filter($userMessages))));
        $userCount = count($userMessages);

        $hasIntent    = $this->matchAny($folded, self::LEX_INTENT);
        $hasCompare   = $this->matchAny($folded, self::LEX_COMPARE);
        $hasPrice     = $this->matchAny($folded, self::LEX_PRICE);
        $hasUrgency   = $this->matchAny($folded, self::LEX_URGENCY);
        $hasQuestionMark = substr_count($folded, '?') >= 3;
        $hasManyShortMsgs = $userCount >= 3 && array_sum(array_map('mb_strlen', $userMessages)) / $userCount < 40;

        // Priority order matters — check the most decisive signals
        // first so "vreau să cumpăr cel mai ieftin" lands as
        // high_intent, not price_sensitive.

        if ($hasIntent || $hasUrgency) {
            $signals[] = 'intent_verb';
            if ($hasUrgency) $signals[] = 'urgency';
            return ['state' => self::HIGH_INTENT, 'signals' => $signals, 'confidence' => 'medium'];
        }

        if ($hasCompare) {
            $signals[] = 'compare_phrase';
            return ['state' => self::COMPARING, 'signals' => $signals, 'confidence' => 'medium'];
        }

        if ($hasPrice) {
            $signals[] = 'price_phrase';
            return ['state' => self::PRICE_SENSITIVE, 'signals' => $signals, 'confidence' => 'medium'];
        }

        if ($hasQuestionMark || $hasManyShortMsgs) {
            $signals[] = $hasQuestionMark ? 'many_questions' : 'many_short_msgs';
            return ['state' => self::STUCK, 'signals' => $signals, 'confidence' => 'low'];
        }

        return ['state' => self::BROWSING, 'signals' => $signals, 'confidence' => 'low'];
    }

    private function matchAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if ($n !== '' && str_contains($haystack, $this->foldDiacritics($n))) return true;
        }
        return false;
    }

    private function foldDiacritics(string $s): string
    {
        return strtr($s, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
            'Ă' => 'a', 'Â' => 'a', 'Î' => 'i', 'Ș' => 's', 'Ş' => 's', 'Ț' => 't', 'Ţ' => 't',
        ]);
    }
}
