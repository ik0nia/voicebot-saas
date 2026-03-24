<?php

namespace App\Services\Connectors;

use App\Models\BotKnowledge;
use App\Models\KnowledgeConnector;
use App\Jobs\ProcessKnowledgeDocument;
use App\Services\Security\SsrfGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WooCommerceConnectorService
{
    public function testConnection(KnowledgeConnector $connector): bool
    {
        try {
            SsrfGuard::validateUrl($connector->site_url);

            $credentials = $connector->credentials;
            if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret'])) {
                return false;
            }

            $response = Http::timeout(10)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get(rtrim($connector->site_url, '/') . '/wp-json/wc/v3/products?per_page=1');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sync(KnowledgeConnector $connector): int
    {
        SsrfGuard::validateUrl($connector->site_url);

        $connector->update(['status' => 'syncing']);
        $imported = 0;

        try {
            $credentials = $connector->credentials;
            $baseUrl = rtrim($connector->site_url, '/');
            $page = 1;
            $perPage = 100; // WooCommerce API max

            // Track synced product IDs to clean up stale entries after
            $syncedProductIds = [];

            do {
                $response = Http::timeout(30)
                    ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                    ->get($baseUrl . '/wp-json/wc/v3/products', [
                        'per_page' => $perPage,
                        'page' => $page,
                        'status' => 'publish',
                    ]);

                if (!$response->successful()) break;

                $products = $response->json();
                if (empty($products)) break;

                foreach ($products as $product) {
                    $content = $this->formatProductContent($product);
                    if (strlen($content) < 50) continue;

                    $wcProductId = $product['id'];
                    $syncedProductIds[] = $wcProductId;

                    // Idempotent: update existing or create new
                    $knowledge = BotKnowledge::updateOrCreate(
                        [
                            'bot_id' => $connector->bot_id,
                            'source_type' => 'connector',
                            'source_id' => $connector->id,
                            'title' => '[WooCommerce] ' . ($product['name'] ?? 'Produs'),
                        ],
                        [
                            'type' => 'text',
                            'content' => $content,
                            'status' => 'pending',
                            'metadata' => [
                                'connector_type' => 'woocommerce',
                                'wc_product_id' => $wcProductId,
                                'wc_sku' => $product['sku'] ?? '',
                                'wc_url' => $product['permalink'] ?? '',
                            ],
                        ]
                    );

                    ProcessKnowledgeDocument::dispatch($knowledge);
                    $imported++;
                }

                $page++;
                $totalPages = (int) ($response->header('X-WP-TotalPages') ?? 1);
            } while ($page <= $totalPages);

            // Clean up products that no longer exist in WooCommerce
            if (!empty($syncedProductIds)) {
                $stale = BotKnowledge::where('bot_id', $connector->bot_id)
                    ->where('source_type', 'connector')
                    ->where('source_id', $connector->id)
                    ->get()
                    ->filter(function ($k) use ($syncedProductIds) {
                        $wcId = $k->metadata['wc_product_id'] ?? null;
                        return $wcId && !in_array($wcId, $syncedProductIds);
                    });

                if ($stale->count() > 0) {
                    BotKnowledge::whereIn('id', $stale->pluck('id'))->delete();
                    Log::info('WooCommerce sync: removed stale products', [
                        'connector_id' => $connector->id,
                        'removed' => $stale->count(),
                    ]);
                }
            }

            $connector->update([
                'status' => 'connected',
                'last_synced_at' => now(),
                'sync_settings' => array_merge($connector->sync_settings ?? [], [
                    'last_sync_count' => $imported,
                    'total_products' => BotKnowledge::where('bot_id', $connector->bot_id)
                        ->where('source_type', 'connector')
                        ->where('source_id', $connector->id)
                        ->where('status', 'ready')
                        ->count(),
                ]),
            ]);

        } catch (\Exception $e) {
            $connector->update(['status' => 'error']);
            throw $e;
        }

        return $imported;
    }

    private function formatProductContent(array $product): string
    {
        $parts = [];

        $parts[] = 'Produs: ' . ($product['name'] ?? '');

        if (!empty($product['short_description'])) {
            $parts[] = 'Descriere scurtă: ' . strip_tags($product['short_description']);
        }
        if (!empty($product['description'])) {
            $desc = strip_tags($product['description']);
            // Truncate very long descriptions to keep each product as 1 embedding chunk
            if (strlen($desc) > 1500) {
                $desc = mb_substr($desc, 0, 1500) . '...';
            }
            $parts[] = 'Descriere: ' . $desc;
        }
        if (!empty($product['price'])) {
            $currency = $product['currency'] ?? '';
            $parts[] = 'Preț: ' . $product['price'] . ' ' . $currency;

            if (!empty($product['regular_price']) && !empty($product['sale_price'])) {
                $parts[] = 'Preț normal: ' . $product['regular_price'] . ' ' . $currency;
                $parts[] = 'Preț redus: ' . $product['sale_price'] . ' ' . $currency;
            }
        }
        if (!empty($product['sku'])) {
            $parts[] = 'SKU: ' . $product['sku'];
        }
        if (!empty($product['categories'])) {
            $cats = array_map(fn($c) => $c['name'] ?? '', $product['categories']);
            $parts[] = 'Categorii: ' . implode(', ', array_filter($cats));
        }
        if (!empty($product['attributes'])) {
            foreach ($product['attributes'] as $attr) {
                $options = implode(', ', $attr['options'] ?? []);
                if ($options) {
                    $parts[] = ($attr['name'] ?? 'Atribut') . ': ' . $options;
                }
            }
        }
        if (isset($product['stock_status'])) {
            $parts[] = 'Stoc: ' . ($product['stock_status'] === 'instock' ? 'În stoc' : 'Indisponibil');
        }
        if (!empty($product['permalink'])) {
            $parts[] = 'Link: ' . $product['permalink'];
        }

        return implode("\n", $parts);
    }
}
