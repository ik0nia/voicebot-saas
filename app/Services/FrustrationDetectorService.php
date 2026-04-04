<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\SearchAnalytics;
use Illuminate\Support\Facades\Cache;

/**
 * Detects user frustration signals from conversation patterns.
 * Used to adapt tone, offer escalation, and prevent bad experiences.
 */
class FrustrationDetectorService
{
    /**
     * Analyze frustration level for a conversation.
     *
     * Results are cached for 2 minutes per conversation — frustration level
     * doesn't change drastically between consecutive messages.
     *
     * @param Conversation $conversation
     * @param string $currentMessage
     * @param \Illuminate\Support\Collection|null $preloadedMessages Pre-loaded recent messages to avoid redundant queries
     * @return array{level: string, score: int, signals: array, recommendation: string}
     */
    public function analyze(Conversation $conversation, string $currentMessage, ?\Illuminate\Support\Collection $preloadedMessages = null): array
    {
        $cacheKey = "frustration_{$conversation->id}";

        // Check cache — frustration doesn't change drastically between messages
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $signals = [];
        $score = 0;

        // Signal 1: Repeated questions (user asks same thing differently)
        if ($preloadedMessages !== null) {
            // Use pre-loaded messages — filter to inbound only, take 6
            $recentMessages = $preloadedMessages
                ->where('direction', 'inbound')
                ->take(6)
                ->pluck('content')
                ->toArray();
        } else {
            $recentMessages = $conversation->messages()
                ->where('direction', 'inbound')
                ->orderByDesc('id')
                ->take(6)
                ->pluck('content')
                ->toArray();
        }

        if (count($recentMessages) >= 2) {
            $similarity = $this->checkRepetition($recentMessages);
            if ($similarity > 0.6) {
                $score += 25;
                $signals[] = 'repeated_question';
            }
        }

        // Signal 2: Frustration keywords in current message
        $msg = mb_strtolower($currentMessage);
        $frustrationWords = [
            'nu funcționează', 'nu merge', 'nu înțelegi', 'nu ma ajuți',
            'nu ajută', 'inutil', 'prost', 'enervant', 'ridicol',
            'de ce nu', 'iar', 'din nou', 'tot nu', 'încă nu',
            'altcineva', 'operator', 'om real', 'persoana reală',
        ];
        foreach ($frustrationWords as $word) {
            if (mb_strpos($msg, $word) !== false) {
                $score += 20;
                $signals[] = 'frustration_language';
                break;
            }
        }

        // Signal 3: Punctuation intensity (!!!, ???, CAPS)
        if (preg_match('/[!?]{3,}/', $currentMessage)) {
            $score += 15;
            $signals[] = 'intense_punctuation';
        }
        $upperRatio = $this->uppercaseRatio($currentMessage);
        if ($upperRatio > 0.5 && mb_strlen($currentMessage) > 10) {
            $score += 15;
            $signals[] = 'caps_lock';
        }

        // Signal 4: Failed searches in this conversation
        $failedSearches = SearchAnalytics::where('bot_id', $conversation->bot_id)
            ->where('created_at', '>=', $conversation->started_at ?? now()->subHour())
            ->where('results_count', 0)
            ->count();
        if ($failedSearches >= 3) {
            $score += 20;
            $signals[] = 'multiple_failed_searches';
        } elseif ($failedSearches >= 1) {
            $score += 10;
            $signals[] = 'failed_search';
        }

        // Signal 5: Short dismissive messages after long conversation
        $msgCount = $conversation->messages_count ?? 0;
        if ($msgCount >= 6 && mb_strlen(trim($currentMessage)) <= 5) {
            $shortWords = ['nu', 'prost', 'gata', 'las', 'pa'];
            if (in_array(mb_strtolower(trim($currentMessage)), $shortWords)) {
                $score += 20;
                $signals[] = 'dismissive_short_message';
            }
        }

        // Signal 6: Conversation length without resolution
        if ($msgCount >= 10) {
            $score += 10;
            $signals[] = 'long_unresolved';
        }

        $score = min(100, $score);

        $level = match(true) {
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };

        $recommendation = match($level) {
            'high' => 'escalate', // Offer human handoff immediately
            'medium' => 'empathize', // Acknowledge difficulty, adapt tone
            'low' => 'continue', // Normal conversation
        };

        $result = [
            'level' => $level,
            'score' => $score,
            'signals' => $signals,
            'recommendation' => $recommendation,
        ];

        // Cache for 2 minutes — avoids recomputation on rapid follow-up messages
        Cache::put($cacheKey, $result, now()->addMinutes(2));

        return $result;
    }

    /**
     * Get prompt modifier based on frustration level.
     */
    public function getPromptModifier(string $level): string
    {
        return match($level) {
            'high' => implode("\n", [
                '',
                'ATENȚIE — CLIENTUL ESTE FRUSTRAT:',
                '- Recunoaște frustrarea: "Înțeleg că situația este frustranta și îmi cer scuze."',
                '- NU repeta informațiile deja date.',
                '- Oferă IMEDIAT opțiunea de a vorbi cu un operator uman.',
                '- Fii extrem de empatic și concis.',
                '- Dacă nu poți rezolva, spune direct: "Vă conectez cu un coleg care vă poate ajuta mai bine."',
            ]),
            'medium' => implode("\n", [
                '',
                'NOTĂ — Clientul pare puțin frustrat:',
                '- Fii mai empatic decât de obicei.',
                '- Verifică dacă ai răspuns corect la întrebarea anterioară.',
                '- Oferă opțiuni alternative dacă prima soluție nu a funcționat.',
            ]),
            default => '',
        };
    }

    private function checkRepetition(array $messages): float
    {
        if (count($messages) < 2) return 0;

        $current = mb_strtolower($messages[0] ?? '');
        $currentWords = array_filter(preg_split('/\s+/', $current), fn($w) => mb_strlen($w) > 2);

        $maxSimilarity = 0;
        for ($i = 1; $i < count($messages); $i++) {
            $prev = mb_strtolower($messages[$i] ?? '');
            $prevWords = array_filter(preg_split('/\s+/', $prev), fn($w) => mb_strlen($w) > 2);

            if (empty($currentWords) || empty($prevWords)) continue;

            $intersection = count(array_intersect($currentWords, $prevWords));
            $smaller = min(count($currentWords), count($prevWords));
            $similarity = $smaller > 0 ? $intersection / $smaller : 0;
            $maxSimilarity = max($maxSimilarity, $similarity);
        }

        return $maxSimilarity;
    }

    private function uppercaseRatio(string $text): float
    {
        $letters = preg_replace('/[^a-zA-ZăîâșțĂÎÂȘȚ]/u', '', $text);
        if (empty($letters)) return 0;

        $upper = preg_replace('/[^A-ZĂÎÂȘȚ]/u', '', $letters);
        return mb_strlen($upper) / mb_strlen($letters);
    }
}
