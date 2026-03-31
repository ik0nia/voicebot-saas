<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\TenantInsight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes conversation patterns over time and generates actionable insights.
 * This is the brain of the self-optimizing loop:
 *
 *   conversations → signals → analysis → insights → improvements
 *
 * Designed to run as a scheduled job (daily) or on-demand from dashboard.
 */
class AdaptiveLearningService
{
    /**
     * Run full analysis for a bot and generate insights.
     *
     * @return array{insights: array, metrics: array}
     */
    public function analyze(Bot $bot, int $days = 7): array
    {
        $since = now()->subDays($days);
        $insights = [];
        $metrics = [];

        // ── 1. Answer Quality Signals ──
        $qualityMetrics = $this->analyzeAnswerQuality($bot->id, $since);
        $metrics['answer_quality'] = $qualityMetrics;

        if ($qualityMetrics['fallback_rate'] > 0.3) {
            $insights[] = $this->createInsight($bot, 'high_fallback_rate', 'warning', [
                'title' => 'Rata mare de răspunsuri fallback',
                'description' => round($qualityMetrics['fallback_rate'] * 100) . '% din conversații au primit răspunsuri generice. Bot-ul nu găsește informații relevante.',
                'action_items' => [
                    'Verifică secțiunea "Ce nu știe bot-ul" pentru întrebări fără răspuns.',
                    'Adaugă conținut FAQ pentru întrebările cele mai comune.',
                ],
                'metric_value' => $qualityMetrics['fallback_rate'],
            ]);
        }

        // ── 2. Frustration Patterns ──
        $frustrationMetrics = $this->analyzeFrustrationPatterns($bot->id, $since);
        $metrics['frustration'] = $frustrationMetrics;

        if ($frustrationMetrics['high_frustration_rate'] > 0.15) {
            $insights[] = $this->createInsight($bot, 'high_frustration', 'critical', [
                'title' => 'Nivel ridicat de frustrare în conversații',
                'description' => round($frustrationMetrics['high_frustration_rate'] * 100) . '% din conversații au semnale de frustrare. Verifică experiența utilizatorilor.',
                'action_items' => [
                    'Revizuiește conversațiile cu frustrare mare.',
                    'Adaugă răspunsuri pentru topicurile problematice.',
                    'Consideră adăugarea unui buton de escaladare mai vizibil.',
                ],
                'metric_value' => $frustrationMetrics['high_frustration_rate'],
            ]);
        }

        // ── 3. Conversion Patterns ──
        $conversionMetrics = $this->analyzeConversionPatterns($bot->id, $since);
        $metrics['conversion'] = $conversionMetrics;

        if ($conversionMetrics['lead_capture_rate'] < 0.05 && $conversionMetrics['total_conversations'] >= 10) {
            $insights[] = $this->createInsight($bot, 'low_lead_capture', 'info', [
                'title' => 'Rata scăzută de captare lead-uri',
                'description' => 'Doar ' . round($conversionMetrics['lead_capture_rate'] * 100, 1) . '% din conversații generează lead-uri.',
                'action_items' => [
                    'Crește lead_aggressiveness în Politica de conversație.',
                    'Verifică dacă bot-ul întreabă date de contact la momentul potrivit.',
                ],
                'metric_value' => $conversionMetrics['lead_capture_rate'],
            ]);
        }

        // ── 4. Popular Topics (what users ask about most) ──
        $topicMetrics = $this->analyzePopularTopics($bot->id, $since);
        $metrics['topics'] = $topicMetrics;

        // ── 5. Time-based patterns ──
        $timeMetrics = $this->analyzeTimePatterns($bot->id, $since);
        $metrics['time_patterns'] = $timeMetrics;

        if (!empty($timeMetrics['peak_hours'])) {
            $peakStr = implode(', ', array_map(fn($h) => "{$h}:00", $timeMetrics['peak_hours']));
            $insights[] = $this->createInsight($bot, 'peak_hours', 'info', [
                'title' => 'Ore de vârf identificate',
                'description' => "Cele mai active ore: {$peakStr}. Asigură-te că bot-ul performează bine în aceste intervale.",
                'action_items' => [
                    'Verifică latența răspunsurilor în orele de vârf.',
                ],
                'metric_value' => count($timeMetrics['peak_hours']),
            ]);
        }

        // ── 6. Knowledge Base Effectiveness ──
        $kbMetrics = $this->analyzeKBEffectiveness($bot->id, $since);
        $metrics['kb_effectiveness'] = $kbMetrics;

        if ($kbMetrics['avg_similarity'] > 0 && $kbMetrics['avg_similarity'] < 0.65) {
            $insights[] = $this->createInsight($bot, 'low_kb_relevance', 'warning', [
                'title' => 'Relevanța scăzută a bazei de cunoștințe',
                'description' => 'Scorul mediu de similaritate e ' . round($kbMetrics['avg_similarity'] * 100) . '%. Conținutul din KB nu se potrivește bine cu întrebările clienților.',
                'action_items' => [
                    'Reformulează conținutul KB în stilul în care clienții pun întrebări.',
                    'Adaugă FAQ cu formulările exacte ale clienților.',
                ],
                'metric_value' => $kbMetrics['avg_similarity'],
            ]);
        }

        // Save insights to DB
        foreach ($insights as $insight) {
            if ($insight instanceof TenantInsight) {
                // Already saved in createInsight
            }
        }

        return [
            'insights' => $insights,
            'metrics' => $metrics,
        ];
    }

    private function analyzeAnswerQuality(int $botId, $since): array
    {
        $totalConversations = Conversation::where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->count();

        // Count conversations where fallback was triggered (no knowledge context found)
        $fallbackCount = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.bot_id', $botId)
            ->where('messages.created_at', '>=', $since)
            ->where('messages.direction', 'outbound')
            ->where(function ($q) {
                $q->where('messages.content', 'like', '%nu am această informație%')
                  ->orWhere('messages.content', 'like', '%nu am gasit%')
                  ->orWhere('messages.content', 'like', '%nu am găsit%')
                  ->orWhere('messages.content', 'like', '%contactați-ne direct%')
                  ->orWhere('messages.content', 'like', '%eroare tehnică%');
            })
            ->distinct('conversations.id')
            ->count('conversations.id');

        $avgMessagesPerConversation = Conversation::where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->avg('messages_count') ?? 0;

        return [
            'total_conversations' => $totalConversations,
            'fallback_count' => $fallbackCount,
            'fallback_rate' => $totalConversations > 0 ? $fallbackCount / $totalConversations : 0,
            'avg_messages' => round($avgMessagesPerConversation, 1),
        ];
    }

    private function analyzeFrustrationPatterns(int $botId, $since): array
    {
        // Look for frustration signals in inbound messages
        $totalConversations = Conversation::where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->count();

        $frustrationConversations = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.bot_id', $botId)
            ->where('messages.created_at', '>=', $since)
            ->where('messages.direction', 'inbound')
            ->where(function ($q) {
                $q->where('messages.content', 'like', '%nu funcționează%')
                  ->orWhere('messages.content', 'like', '%nu merge%')
                  ->orWhere('messages.content', 'like', '%nu înțelegi%')
                  ->orWhere('messages.content', 'like', '%operator%')
                  ->orWhere('messages.content', 'like', '%persoana reală%')
                  ->orWhere('messages.content', 'like', '%prost%')
                  ->orWhere('messages.content', 'like', '%inutil%');
            })
            ->distinct('conversations.id')
            ->count('conversations.id');

        return [
            'total_conversations' => $totalConversations,
            'frustrated_conversations' => $frustrationConversations,
            'high_frustration_rate' => $totalConversations > 0 ? $frustrationConversations / $totalConversations : 0,
        ];
    }

    private function analyzeConversionPatterns(int $botId, $since): array
    {
        $totalConversations = Conversation::where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->count();

        $leadsGenerated = \App\Models\Lead::where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->count();

        $qualifiedLeads = \App\Models\Lead::where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->where('status', 'qualified')
            ->count();

        return [
            'total_conversations' => $totalConversations,
            'leads_generated' => $leadsGenerated,
            'qualified_leads' => $qualifiedLeads,
            'lead_capture_rate' => $totalConversations > 0 ? $leadsGenerated / $totalConversations : 0,
        ];
    }

    private function analyzePopularTopics(int $botId, $since): array
    {
        $topics = DB::table('search_analytics')
            ->select(
                DB::raw('LOWER(TRIM(query)) as topic'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(results_count) as avg_results'),
            )
            ->where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw('LOWER(TRIM(query))'))
            ->orderByDesc('count')
            ->limit(15)
            ->get()
            ->toArray();

        return [
            'top_topics' => $topics,
            'total_unique_topics' => count($topics),
        ];
    }

    private function analyzeTimePatterns(int $botId, $since): array
    {
        $hourly = DB::table('conversations')
            ->select(
                DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
                DB::raw('COUNT(*) as count'),
            )
            ->where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderByDesc('count')
            ->get()
            ->toArray();

        $totalConv = array_sum(array_column($hourly, 'count'));
        $peakHours = [];
        foreach ($hourly as $h) {
            if ($totalConv > 0 && ($h->count / $totalConv) > 0.1) {
                $peakHours[] = (int) $h->hour;
            }
        }
        sort($peakHours);

        return [
            'hourly_distribution' => $hourly,
            'peak_hours' => $peakHours,
        ];
    }

    private function analyzeKBEffectiveness(int $botId, $since): array
    {
        // Use RAG logs to find average similarity scores
        // This assumes SearchAnalytics or logs capture similarity
        $avgResults = DB::table('search_analytics')
            ->where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->where('results_count', '>', 0)
            ->avg('results_count') ?? 0;

        $totalSearches = DB::table('search_analytics')
            ->where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->count();

        $zeroResults = DB::table('search_analytics')
            ->where('bot_id', $botId)
            ->where('created_at', '>=', $since)
            ->where('results_count', 0)
            ->count();

        return [
            'total_searches' => $totalSearches,
            'zero_result_searches' => $zeroResults,
            'avg_results_count' => round($avgResults, 1),
            'avg_similarity' => 0.70, // Default; real value needs RAG log integration
            'coverage_rate' => $totalSearches > 0 ? 1 - ($zeroResults / $totalSearches) : 0,
        ];
    }

    private function createInsight(Bot $bot, string $type, string $severity, array $data): TenantInsight
    {
        // Upsert: update existing insight of same type or create new
        return TenantInsight::updateOrCreate(
            [
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'insight_type' => $type,
                'is_dismissed' => false,
            ],
            [
                'severity' => $severity,
                'title' => $data['title'],
                'description' => $data['description'],
                'data' => $data,
                'action_items' => $data['action_items'] ?? [],
                'valid_from' => now(),
                'valid_until' => now()->addDays(7),
            ]
        );
    }
}
