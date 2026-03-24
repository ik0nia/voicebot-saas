<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\WooCommerceProduct;

class ProductContextService
{
    public function __construct(
        private readonly ProductSearchService $productSearchService,
        private readonly OrderLookupService $orderLookupService,
    ) {}

    /**
     * Build product context for AI prompt based on user message.
     *
     * @return array{context: string, products: array, order_result: array|null}
     */
    public function buildContext(Bot $bot, string $userMessage): array
    {
        $context = '';
        $products = [];
        $orderResult = null;

        // Check for order queries
        $orderParams = $this->orderLookupService->detectOrderQuery($userMessage);
        if ($orderParams) {
            $orderResult = $this->orderLookupService->lookup($bot->id, $orderParams);
            if ($orderResult['found']) {
                $context .= $this->formatOrderContext($orderResult);
            }
        }

        // Product search
        $searchResults = $this->productSearchService->search($bot->id, $userMessage, 5);
        if (!empty($searchResults)) {
            $products = array_map([$this->productSearchService, 'toCardArray'], $searchResults);
            $context .= $this->formatProductContext($searchResults);
        }

        return [
            'context' => $context,
            'products' => $products,
            'order_result' => $orderResult,
        ];
    }

    private function formatOrderContext(array $orderResult): string
    {
        $ctx = "\n\nINFORMAȚII COMANDĂ:\n";
        foreach ($orderResult['orders'] as $order) {
            $ctx .= "Comanda #{$order['number']}: Status: {$order['status']}, Total: {$order['total']}, Data: {$order['date']}\n";
            if ($order['tracking']) {
                $ctx .= "Tracking: {$order['tracking']}\n";
                if ($order['tracking_url']) {
                    $ctx .= "Link tracking: {$order['tracking_url']}\n";
                }
            }
        }
        return $ctx;
    }

    private function formatProductContext(array $results): string
    {
        $ctx = "\n\nPRODUSE GĂSITE:\n";
        foreach (array_slice($results, 0, 5) as $p) {
            $ctx .= "- {$p->name}: {$p->price} {$p->currency}";
            if ($p->sale_price && $p->sale_price !== $p->price) {
                $ctx .= " (reducere de la {$p->regular_price})";
            }
            $ctx .= "\n";
        }
        return $ctx;
    }
}
