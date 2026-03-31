<?php

namespace App\Services;

use App\Models\SearchAnalytics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Identifies knowledge gaps — queries that the bot couldn't answer well.
 * Powers the "What Your Bot Doesn't Know" dashboard.
 */
class KnowledgeGapService
{
    /**
     * Get top unanswered queries for a bot.
     * Cached for 30 minutes.
     *
     * @return array{gaps: array, total_failed: int, total_searches: int, fail_rate: float}
     */
    public function analyze(int $botId, int $days = 7, int $limit = 20): array
    {
        $cacheKey = "knowledge_gaps_{$botId}_{$days}";

        return Cache::remember($cacheKey, 1800, function () use ($botId, $days, $limit) {
            $since = now()->subDays($days);

            // Total search stats
            $totalSearches = SearchAnalytics::where('bot_id', $botId)
                ->where('created_at', '>=', $since)
                ->count();

            $totalFailed = SearchAnalytics::where('bot_id', $botId)
                ->where('created_at', '>=', $since)
                ->where('results_count', 0)
                ->count();

            // Top failed queries grouped by normalized query
            $gaps = DB::table('search_analytics')
                ->select(
                    DB::raw('LOWER(TRIM(query)) as normalized_query'),
                    DB::raw('COUNT(*) as occurrences'),
                    DB::raw('MAX(created_at) as last_asked'),
                )
                ->where('bot_id', $botId)
                ->where('created_at', '>=', $since)
                ->where('results_count', 0)
                ->groupBy(DB::raw('LOWER(TRIM(query))'))
                ->orderByDesc('occurrences')
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    return [
                        'query' => $row->normalized_query,
                        'occurrences' => $row->occurrences,
                        'last_asked' => $row->last_asked,
                        'category' => $this->categorizeGap($row->normalized_query),
                    ];
                })
                ->toArray();

            // Categorize gaps for actionable suggestions
            $categories = [];
            foreach ($gaps as $gap) {
                $cat = $gap['category'];
                $categories[$cat] = ($categories[$cat] ?? 0) + $gap['occurrences'];
            }
            arsort($categories);

            return [
                'gaps' => $gaps,
                'categories' => $categories,
                'total_failed' => $totalFailed,
                'total_searches' => $totalSearches,
                'fail_rate' => $totalSearches > 0 ? round($totalFailed / $totalSearches * 100, 1) : 0,
                'suggestions' => $this->generateSuggestions($categories, $gaps),
            ];
        });
    }

    /**
     * Categorize a failed query into content categories.
     */
    private function categorizeGap(string $query): string
    {
        $q = mb_strtolower($query);

        $categoryPatterns = [
            'pricing' => ['pret', 'cost', 'tarif', 'cat costa', 'pris', 'price'],
            'shipping' => ['livr', 'transport', 'expedi', 'curier', 'colet', 'shipping'],
            'returns' => ['retur', 'schimb', 'inapoi', 'return'],
            'payment' => ['plat', 'card', 'rate', 'factur', 'payment'],
            'availability' => ['stoc', 'disponibil', 'cand vine', 'stock'],
            'product_info' => ['dimensiun', 'marime', 'culoare', 'material', 'greutate', 'specif'],
            'contact' => ['contact', 'telefon', 'email', 'adresa', 'program', 'orar'],
            'warranty' => ['garanti', 'warranty', 'service'],
            'promotions' => ['reducere', 'oferta', 'promo', 'discount', 'cupon'],
            'process' => ['cum', 'etape', 'procedur', 'how'],
        ];

        foreach ($categoryPatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_strpos($q, $pattern) !== false) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    /**
     * Generate actionable suggestions based on gap categories.
     */
    private function generateSuggestions(array $categories, array $gaps): array
    {
        $suggestions = [];

        $suggestionMap = [
            'pricing' => [
                'icon' => '💰',
                'title' => 'Adaugă informații de preț',
                'description' => 'Clienții întreabă despre prețuri dar bot-ul nu găsește răspunsuri. Adaugă o pagină de prețuri sau un document cu tarifele.',
                'template' => 'pricing',
            ],
            'shipping' => [
                'icon' => '🚚',
                'title' => 'Adaugă politica de livrare',
                'description' => 'Întrebări despre livrare fără răspuns. Adaugă: metode de livrare, costuri, termene, zone de acoperire.',
                'template' => 'shipping',
            ],
            'returns' => [
                'icon' => '🔄',
                'title' => 'Adaugă politica de retur',
                'description' => 'Clienții întreabă despre retururi. Adaugă: condiții, termen, proces, excepții.',
                'template' => 'returns',
            ],
            'payment' => [
                'icon' => '💳',
                'title' => 'Adaugă metode de plată',
                'description' => 'Întrebări despre plată fără răspuns. Adaugă: metode acceptate, rate, facturare.',
                'template' => 'payment',
            ],
            'availability' => [
                'icon' => '📦',
                'title' => 'Adaugă informații de disponibilitate',
                'description' => 'Clienții întreabă despre stocuri. Consideră sincronizarea produselor via WooCommerce connector.',
                'template' => null,
            ],
            'contact' => [
                'icon' => '📞',
                'title' => 'Adaugă informații de contact',
                'description' => 'Clienții caută date de contact. Adaugă: telefon, email, adresă, program de lucru.',
                'template' => 'contact',
            ],
            'warranty' => [
                'icon' => '🛡️',
                'title' => 'Adaugă informații garanție',
                'description' => 'Întrebări despre garanție fără răspuns. Adaugă: termeni, acoperire, proces de service.',
                'template' => 'warranty',
            ],
            'promotions' => [
                'icon' => '🏷️',
                'title' => 'Adaugă promoții curente',
                'description' => 'Clienții caută reduceri sau oferte. Adaugă promoțiile active și codurile de cupon.',
                'template' => 'promotions',
            ],
        ];

        foreach ($categories as $category => $count) {
            if (isset($suggestionMap[$category]) && $count >= 2) {
                $suggestion = $suggestionMap[$category];
                $suggestion['occurrences'] = $count;
                $suggestion['sample_queries'] = array_slice(
                    array_column(
                        array_filter($gaps, fn($g) => $g['category'] === $category),
                        'query'
                    ),
                    0,
                    3
                );
                $suggestions[] = $suggestion;
            }
        }

        return $suggestions;
    }

    /**
     * Invalidate gap analysis cache.
     */
    public function invalidate(int $botId): void
    {
        for ($days = 1; $days <= 30; $days++) {
            Cache::forget("knowledge_gaps_{$botId}_{$days}");
        }
    }
}
