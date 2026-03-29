<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Maps general concepts/activities to product search queries.
 *
 * When a user asks "ce imi recomanzi pentru zugravit?", this service
 * breaks it into specific product sub-queries and aggregates results
 * from ProductSearchService.
 *
 * The concept mappings are general e-commerce categories, not tenant-specific.
 * They work as search hints — if a product doesn't exist in the catalog,
 * the sub-query simply returns 0 results.
 */
class RecommendationService
{
    /**
     * General concept → product type mappings.
     * Each concept maps to an array of product search queries.
     * These are general construction/home improvement terms, not catalog-specific.
     */
    private const CONCEPT_MAP = [
        // Activities
        'zugravit' => ['vopsea lavabila', 'amorsa', 'glet', 'trafalet', 'banda mascare'],
        'zugraveala' => ['vopsea lavabila', 'amorsa', 'glet', 'trafalet', 'banda mascare'],
        'zugravesc' => ['vopsea lavabila', 'amorsa', 'glet', 'trafalet', 'banda mascare'],
        'vopsesc' => ['vopsea lavabila', 'amorsa', 'trafalet', 'pensula'],
        'vopsit' => ['vopsea lavabila', 'amorsa', 'trafalet', 'pensula'],
        'renovez' => ['glet', 'vopsea lavabila', 'amorsa', 'adeziv gresie', 'silicon'],
        'renovare' => ['glet', 'vopsea lavabila', 'amorsa', 'adeziv gresie', 'silicon'],
        'placari' => ['adeziv gresie', 'faianta', 'gresie', 'chit rosturi', 'cruce distantier'],
        'placare' => ['adeziv gresie', 'faianta', 'gresie', 'chit rosturi', 'cruce distantier'],
        'montez' => ['adeziv montaj', 'surub', 'diblu', 'silicon'],
        'repar' => ['adeziv', 'silicon', 'spuma poliuretanica', 'chit'],
        'reparatii' => ['adeziv', 'silicon', 'spuma poliuretanica', 'chit'],
        'izol' => ['polistiren', 'vata minerala', 'spuma poliuretanica', 'adeziv polistiren'],
        'izolare' => ['polistiren', 'vata minerala', 'spuma poliuretanica', 'adeziv polistiren'],
        'izolatii' => ['polistiren', 'vata minerala', 'spuma poliuretanica', 'adeziv polistiren'],
        'termoizolatie' => ['polistiren', 'vata minerala', 'adeziv polistiren', 'plasa fibra sticla'],
        'tencuiesc' => ['tencuiala', 'amorsa', 'plasa fibra sticla', 'profil colt'],
        'tencuit' => ['tencuiala', 'amorsa', 'plasa fibra sticla', 'profil colt'],
        'gresie' => ['adeziv gresie', 'chit rosturi', 'cruce distantier', 'profil faianta'],
        'faianta' => ['adeziv faianta', 'chit rosturi', 'cruce distantier', 'profil faianta'],

        // Rooms / spaces
        'baie' => ['silicon sanitar', 'adeziv gresie', 'faianta', 'gresie', 'chit rosturi'],
        'bucatarie' => ['faianta', 'adeziv gresie', 'robinet', 'silicon sanitar'],
        'dormitor' => ['vopsea lavabila', 'parchet', 'plinta'],
        'living' => ['vopsea lavabila', 'parchet', 'plinta'],
        'exterior' => ['tencuiala exterior', 'vopsea exterior', 'polistiren', 'silicon exterior'],
        'fatada' => ['polistiren', 'adeziv polistiren', 'plasa fibra sticla', 'tencuiala decorativa'],
        'acoperis' => ['tigla', 'membrane', 'jgheab', 'burlane'],
        'subsol' => ['hidroizolatie', 'amorsa', 'mortar'],
        'garaj' => ['vopsea beton', 'mortar', 'rasina epoxidica'],
        'gradina' => ['pavaj', 'bordura', 'nisip', 'ciment'],
        'terasa' => ['gresie exterior', 'adeziv gresie', 'hidroizolatie'],

        // Systems
        'instalatii' => ['teava', 'robinet', 'cot', 'mufa', 'racord'],
        'instalatii sanitare' => ['teava ppr', 'robinet', 'racord', 'silicon sanitar'],
        'electric' => ['cablu', 'intrerupator', 'priza', 'tablou electric'],
        'incalzire' => ['calorifer', 'teava', 'robinet termostatic'],
    ];

    public function __construct(
        private ProductSearchService $productSearch,
    ) {}

    /**
     * Check if a concept has recommendations available.
     */
    public function hasConcept(string $concept): bool
    {
        $normalized = $this->normalizeConcept($concept);
        return $this->findConceptQueries($normalized) !== null;
    }

    /**
     * Get product recommendations for a concept.
     * Returns aggregated results from multiple sub-queries.
     *
     * @return array{products: array, concept: string, sub_queries: array, total: int}
     */
    public function recommend(int $botId, string $concept, int $limitPerQuery = 2): array
    {
        $normalized = $this->normalizeConcept($concept);
        $subQueries = $this->findConceptQueries($normalized);

        if ($subQueries === null) {
            return [
                'products' => [],
                'concept' => $concept,
                'sub_queries' => [],
                'total' => 0,
            ];
        }

        $allProducts = [];
        $seenIds = [];
        $usedQueries = [];

        foreach ($subQueries as $subQuery) {
            $results = $this->productSearch->search($botId, $subQuery, $limitPerQuery);

            $count = 0;
            foreach ($results as $product) {
                $id = $product->id ?? $product->wc_product_id ?? spl_object_id($product);
                if (isset($seenIds[$id])) continue;
                $seenIds[$id] = true;
                $allProducts[] = $product;
                $count++;
            }

            if ($count > 0) {
                $usedQueries[] = $subQuery;
            }
        }

        Log::debug('RecommendationService', [
            'bot_id' => $botId,
            'concept' => $concept,
            'normalized' => $normalized,
            'sub_queries' => $subQueries,
            'results_count' => count($allProducts),
        ]);

        return [
            'products' => $allProducts,
            'concept' => $normalized,
            'sub_queries' => $usedQueries,
            'total' => count($allProducts),
        ];
    }

    /**
     * Find sub-queries for a concept. Uses prefix matching for flexibility.
     */
    private function findConceptQueries(string $concept): ?array
    {
        // Exact match first
        if (isset(self::CONCEPT_MAP[$concept])) {
            return self::CONCEPT_MAP[$concept];
        }

        // Prefix match — "zugravesc" matches "zugrav*"
        foreach (self::CONCEPT_MAP as $key => $queries) {
            if (mb_strlen($key) >= 4 && str_starts_with($concept, mb_substr($key, 0, 4))) {
                return $queries;
            }
            if (mb_strlen($concept) >= 4 && str_starts_with($key, mb_substr($concept, 0, 4))) {
                return $queries;
            }
        }

        return null;
    }

    private function normalizeConcept(string $concept): string
    {
        $concept = mb_strtolower(trim($concept));
        $concept = str_replace(
            ['ă', 'â', 'î', 'ș', 'ț'],
            ['a', 'a', 'i', 's', 't'],
            $concept
        );
        // Remove trailing articles
        $concept = preg_replace('/\s+(la|in|din|cu|si)\s*$/u', '', $concept);
        return trim($concept);
    }
}
