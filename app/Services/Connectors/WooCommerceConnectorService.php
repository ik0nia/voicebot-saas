<?php

namespace App\Services\Connectors;

use App\Models\BotKnowledge;
use App\Models\KnowledgeConnector;
use App\Jobs\ProcessKnowledgeBatch;
use App\Services\Security\SsrfGuard;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Sync products from WooCommerce.
     *
     * ARCHITECTURE: This method ONLY creates/updates BotKnowledge records with status=pending.
     * It does NOT dispatch individual embedding jobs. Embedding processing is handled by:
     * - `knowledge:process` cron command (runs every minute)
     * - ProcessKnowledgeBatch job (controlled batching on 'knowledge' queue)
     *
     * This prevents queue explosion when syncing thousands of products.
     */
    public function sync(KnowledgeConnector $connector): int
    {
        SsrfGuard::validateUrl($connector->site_url);

        // SYNC LOCK: prevent concurrent syncs for same connector
        $lockKey = "wc_sync_lock_{$connector->id}";
        $lock = Cache::lock($lockKey, 1800); // 30 min max

        if (!$lock->get()) {
            Log::warning('WooCommerce sync: already in progress', ['connector_id' => $connector->id]);
            throw new \RuntimeException('Sincronizarea este deja în curs. Vă rugăm așteptați.');
        }

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
                    // Status is set to 'pending' — embedding will be processed by knowledge:process cron
                    BotKnowledge::updateOrCreate(
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

                    // NO dispatch here! Batched processing via knowledge:process cron.
                    $imported++;
                }

                $page++;
                $totalPages = (int) ($response->header('X-WP-TotalPages') ?? 1);
            } while ($page <= $totalPages);

            // Clean up products that no longer exist in WooCommerce
            if (!empty($syncedProductIds)) {
                $deleted = BotKnowledge::where('bot_id', $connector->bot_id)
                    ->where('source_type', 'connector')
                    ->where('source_id', $connector->id)
                    ->whereNotNull('metadata')
                    ->get()
                    ->filter(function ($k) use ($syncedProductIds) {
                        $wcId = $k->metadata['wc_product_id'] ?? null;
                        return $wcId && !in_array($wcId, $syncedProductIds);
                    });

                if ($deleted->count() > 0) {
                    BotKnowledge::whereIn('id', $deleted->pluck('id'))->delete();
                    Log::info('WooCommerce sync: removed stale products', [
                        'connector_id' => $connector->id,
                        'removed' => $deleted->count(),
                    ]);
                }
            }

            // Initialize sync progress for UI
            Cache::put("knowledge_sync_progress_{$connector->bot_id}", [
                'processed' => 0,
                'total' => $imported,
                'pending' => $imported,
                'ready' => 0,
                'started_at' => now()->toIso8601String(),
                'completed' => false,
            ], now()->addHours(2));

            $connector->update([
                'status' => 'connected',
                'last_synced_at' => now(),
                'sync_settings' => array_merge($connector->sync_settings ?? [], [
                    'last_sync_count' => $imported,
                    'total_products' => $imported,
                ]),
            ]);

            // Dispatch first batch immediately (rest handled by cron)
            ProcessKnowledgeBatch::dispatch($connector->bot_id, 50);

            Log::info('WooCommerce sync: completed, first batch dispatched', [
                'connector_id' => $connector->id,
                'bot_id' => $connector->bot_id,
                'imported' => $imported,
            ]);

        } catch (\Exception $e) {
            $connector->update(['status' => 'error']);
            throw $e;
        } finally {
            $lock->release();
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
