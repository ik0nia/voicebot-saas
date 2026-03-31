<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\ConversationPolicy;
use App\Models\WooCommerceProduct;
use App\Models\SearchAnalytics;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;

class BotHealthScoreService
{
    /**
     * Calculate comprehensive health score for a bot.
     * Cached for 1 hour per bot.
     *
     * @return array{score: int, components: array, suggestions: array, grade: string}
     */
    public function calculate(Bot $bot): array
    {
        return Cache::remember("bot_health_{$bot->id}", 3600, function () use ($bot) {
            $components = [];
            $suggestions = [];

            // 1. Knowledge Base Completeness (0-25 points)
            $kbScore = $this->scoreKnowledgeBase($bot, $suggestions);
            $components['knowledge_base'] = ['score' => $kbScore, 'max' => 25, 'label' => 'Baza de cunoștințe'];

            // 2. System Prompt Quality (0-20 points)
            $promptScore = $this->scorePrompt($bot, $suggestions);
            $components['prompt'] = ['score' => $promptScore, 'max' => 20, 'label' => 'Prompt sistem'];

            // 3. Configuration Completeness (0-15 points)
            $configScore = $this->scoreConfiguration($bot, $suggestions);
            $components['configuration'] = ['score' => $configScore, 'max' => 15, 'label' => 'Configurare'];

            // 4. Recent Performance (0-25 points)
            $perfScore = $this->scorePerformance($bot, $suggestions);
            $components['performance'] = ['score' => $perfScore, 'max' => 25, 'label' => 'Performanță recentă'];

            // 5. Channel Readiness (0-15 points)
            $channelScore = $this->scoreChannels($bot, $suggestions);
            $components['channels'] = ['score' => $channelScore, 'max' => 15, 'label' => 'Canale active'];

            $total = array_sum(array_column($components, 'score'));
            $grade = match(true) {
                $total >= 90 => 'A+',
                $total >= 80 => 'A',
                $total >= 70 => 'B',
                $total >= 60 => 'C',
                $total >= 40 => 'D',
                default => 'F',
            };

            // Sort suggestions by impact (high first)
            usort($suggestions, fn($a, $b) => ($b['impact_score'] ?? 0) <=> ($a['impact_score'] ?? 0));

            return [
                'score' => $total,
                'grade' => $grade,
                'components' => $components,
                'suggestions' => array_slice($suggestions, 0, 5), // Top 5 suggestions
            ];
        });
    }

    private function scoreKnowledgeBase(Bot $bot, array &$suggestions): int
    {
        $score = 0;
        $chunks = BotKnowledge::where('bot_id', $bot->id)->where('status', 'ready');
        $totalChunks = $chunks->count();
        $totalDocs = $chunks->distinct('title')->count('title');

        // Has any knowledge at all?
        if ($totalChunks === 0) {
            $suggestions[] = [
                'type' => 'critical',
                'icon' => '📚',
                'message' => 'Adaugă conținut în baza de cunoștințe — fără el, bot-ul nu poate răspunde la întrebări specifice.',
                'action' => 'add_knowledge',
                'impact_score' => 100,
            ];
            return 0;
        }

        // Basic content exists (+8)
        $score += min(8, $totalDocs * 2);

        // Has FAQ-type content (+5)
        $hasFaq = BotKnowledge::where('bot_id', $bot->id)
            ->where('status', 'ready')
            ->where('source_type', 'faq')
            ->exists();
        if ($hasFaq) {
            $score += 5;
        } else {
            $suggestions[] = [
                'type' => 'important',
                'icon' => '❓',
                'message' => 'Adaugă conținut FAQ — întrebările frecvente primesc cele mai bune răspunsuri.',
                'action' => 'add_faq',
                'impact_score' => 70,
            ];
        }

        // Has enough volume (+5)
        if ($totalChunks >= 20) {
            $score += 5;
        } elseif ($totalChunks >= 10) {
            $score += 3;
        } else {
            $suggestions[] = [
                'type' => 'improvement',
                'icon' => '📄',
                'message' => "Ai doar {$totalChunks} chunks de conținut. Adaugă mai multe documente pentru răspunsuri mai complete.",
                'action' => 'add_more_content',
                'impact_score' => 50,
            ];
        }

        // Has diverse content types (+4)
        $sourceTypes = BotKnowledge::where('bot_id', $bot->id)
            ->where('status', 'ready')
            ->distinct()
            ->pluck('source_type')
            ->filter()
            ->count();
        if ($sourceTypes >= 3) {
            $score += 4;
        } elseif ($sourceTypes >= 2) {
            $score += 2;
        }

        // Has embeddings on all chunks (+3)
        $withEmbeddings = BotKnowledge::where('bot_id', $bot->id)
            ->where('status', 'ready')
            ->whereNotNull('embedding')
            ->count();
        if ($withEmbeddings === $totalChunks) {
            $score += 3;
        } elseif ($withEmbeddings > 0) {
            $score += 1;
        }

        return min(25, $score);
    }

    private function scorePrompt(Bot $bot, array &$suggestions): int
    {
        $score = 0;
        $prompt = $bot->system_prompt ?? '';
        $wordCount = str_word_count($prompt);

        // Has a custom prompt at all?
        if ($wordCount < 10) {
            $suggestions[] = [
                'type' => 'critical',
                'icon' => '✍️',
                'message' => 'Scrie un prompt de sistem detaliat — spune-i bot-ului cine este, ce face, și cum să răspundă.',
                'action' => 'edit_prompt',
                'impact_score' => 90,
            ];
            return 0;
        }

        // Prompt length quality
        if ($wordCount >= 100) {
            $score += 8;
        } elseif ($wordCount >= 50) {
            $score += 5;
        } else {
            $score += 3;
            $suggestions[] = [
                'type' => 'improvement',
                'icon' => '✍️',
                'message' => 'Prompt-ul tău are doar ' . $wordCount . ' cuvinte. Prompt-urile mai detaliate (100+ cuvinte) produc răspunsuri mai bune.',
                'action' => 'edit_prompt',
                'impact_score' => 40,
            ];
        }

        // Contains business identity signals (+4)
        $identityPatterns = ['magazin', 'firma', 'compani', 'echipa', 'servicii', 'produse', 'brand', 'store', 'company', 'shop'];
        $hasIdentity = false;
        foreach ($identityPatterns as $pattern) {
            if (stripos($prompt, $pattern) !== false) {
                $hasIdentity = true;
                break;
            }
        }
        $score += $hasIdentity ? 4 : 0;

        // Contains behavioral instructions (+4)
        $behaviorPatterns = ['răspunde', 'tonul', 'stil', 'scurt', 'detaliat', 'politicos', 'profesionist', 'respond', 'tone', 'style'];
        $hasBehavior = false;
        foreach ($behaviorPatterns as $pattern) {
            if (stripos($prompt, $pattern) !== false) {
                $hasBehavior = true;
                break;
            }
        }
        $score += $hasBehavior ? 4 : 0;
        if (!$hasBehavior) {
            $suggestions[] = [
                'type' => 'improvement',
                'icon' => '🎯',
                'message' => 'Adaugă instrucțiuni de comportament în prompt: cum să răspundă, ce ton să folosească, ce să evite.',
                'action' => 'edit_prompt',
                'impact_score' => 35,
            ];
        }

        // Contains domain-specific terms (+4)
        if ($wordCount >= 30) {
            $score += 4; // Assume custom content = domain specific
        }

        return min(20, $score);
    }

    private function scoreConfiguration(Bot $bot, array &$suggestions): int
    {
        $score = 0;

        // Has greeting message (+3)
        if (!empty($bot->greeting_message)) {
            $score += 3;
        } else {
            $suggestions[] = [
                'type' => 'improvement',
                'icon' => '👋',
                'message' => 'Adaugă un mesaj de întâmpinare personalizat.',
                'action' => 'edit_greeting',
                'impact_score' => 30,
            ];
        }

        // Has conversation policy (+5)
        $hasPolicy = ConversationPolicy::where('bot_id', $bot->id)->where('is_active', true)->exists();
        if ($hasPolicy) {
            $score += 5;
        } else {
            $suggestions[] = [
                'type' => 'improvement',
                'icon' => '⚙️',
                'message' => 'Configurează politica de conversație: ton, stil de răspuns, reguli de business.',
                'action' => 'configure_policy',
                'impact_score' => 45,
            ];
        }

        // Has language set (+2)
        if (!empty($bot->language)) {
            $score += 2;
        }

        // Has custom voice (for voice bots) (+3)
        if (!empty($bot->voice) && $bot->voice !== 'alloy') {
            $score += 3;
        } elseif ($bot->calls_count > 0) {
            $score += 1;
        }

        // Has products connected (for ecommerce) (+2)
        $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
        if ($hasProducts) {
            $score += 2;
        }

        return min(15, $score);
    }

    private function scorePerformance(Bot $bot, array &$suggestions): int
    {
        $score = 0;

        // Recent conversations (last 7 days)
        $recentConversations = Conversation::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($recentConversations === 0) {
            $suggestions[] = [
                'type' => 'info',
                'icon' => '📊',
                'message' => 'Bot-ul nu a avut conversații în ultima săptămână. Testează-l sau distribuie linkul de demo.',
                'action' => 'test_bot',
                'impact_score' => 20,
            ];
            return 5; // Base score for existing bot
        }

        // Has conversations (+5)
        $score += min(5, $recentConversations);

        // Check failed searches ratio
        $failedSearches = SearchAnalytics::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('results_count', 0)
            ->count();
        $totalSearches = SearchAnalytics::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($totalSearches > 0) {
            $failRate = $failedSearches / $totalSearches;
            if ($failRate < 0.1) {
                $score += 10; // Excellent
            } elseif ($failRate < 0.2) {
                $score += 7;
            } elseif ($failRate < 0.4) {
                $score += 4;
                $suggestions[] = [
                    'type' => 'important',
                    'icon' => '🔍',
                    'message' => round($failRate * 100) . '% din căutări nu au găsit rezultate. Verifică ce întreabă clienții și adaugă conținutul lipsă.',
                    'action' => 'view_failed_searches',
                    'impact_score' => 60,
                ];
            } else {
                $score += 1;
                $suggestions[] = [
                    'type' => 'critical',
                    'icon' => '🚨',
                    'message' => round($failRate * 100) . '% din căutări eșuează! Bot-ul nu poate răspunde la majoritatea întrebărilor.',
                    'action' => 'view_failed_searches',
                    'impact_score' => 85,
                ];
            }
        } else {
            $score += 5; // No search data = neutral
        }

        // Conversation depth (avg messages per conversation)
        $avgMessages = Conversation::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->avg('messages_count') ?? 0;
        if ($avgMessages >= 4) {
            $score += 5; // Good engagement
        } elseif ($avgMessages >= 2) {
            $score += 3;
        }

        // Has leads generated (+5)
        $recentLeads = \App\Models\Lead::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        if ($recentLeads > 0) {
            $score += 5;
        }

        return min(25, $score);
    }

    private function scoreChannels(Bot $bot, array &$suggestions): int
    {
        $score = 0;
        $channels = $bot->channels()->where('is_active', true)->get();

        if ($channels->isEmpty()) {
            $suggestions[] = [
                'type' => 'critical',
                'icon' => '📡',
                'message' => 'Activează cel puțin un canal (web chatbot, WhatsApp, voce) pentru ca bot-ul să fie accesibil clienților.',
                'action' => 'add_channel',
                'impact_score' => 80,
            ];
            return 0;
        }

        // At least one channel active (+5)
        $score += 5;

        // Multiple channels (+5)
        if ($channels->count() >= 2) {
            $score += 5;
        } elseif ($channels->count() === 1) {
            $score += 2;
        }

        // Has voice channel (+3 bonus)
        if ($channels->contains('type', 'voice')) {
            $score += 3;
        }

        // Has web chatbot (+2)
        if ($channels->contains('type', 'web_chatbot')) {
            $score += 2;
        }

        return min(15, $score);
    }

    /**
     * Invalidate the health score cache for a bot.
     */
    public function invalidate(int $botId): void
    {
        Cache::forget("bot_health_{$botId}");
    }
}
