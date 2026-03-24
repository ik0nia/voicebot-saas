<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessKnowledgeDocument;
use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\Channel;
use App\Models\KnowledgeConnector;
use App\Models\Site;
use App\Models\WooCommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegrationApiController extends Controller
{
    /**
     * Connect a WooCommerce site to Sambla.
     */
    public function connect(Request $request): JsonResponse
    {
        $request->validate([
            'site_url' => 'required|url|max:500',
            'site_name' => 'nullable|string|max:255',
            'bot_id' => 'nullable|integer|exists:bots,id',
        ]);

        $tenant = $request->user()->tenant;
        $siteUrl = rtrim($request->input('site_url'), '/');
        $domain = parse_url($siteUrl, PHP_URL_HOST);

        // Find or create site
        $site = Site::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'domain' => $domain],
            [
                'name' => $request->input('site_name', $domain),
                'status' => 'active',
                'verified_at' => now(),
            ]
        );

        // Find existing bot or create one
        if ($request->input('bot_id')) {
            $bot = Bot::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($request->input('bot_id'));
        } else {
            $bot = Bot::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('site_id', $site->id)
                ->first();

            if (!$bot) {
                $bot = Bot::create([
                    'tenant_id' => $tenant->id,
                    'site_id' => $site->id,
                    'name' => $request->input('site_name', $domain) . ' - Chatbot',
                    'slug' => Str::slug($domain) . '-' . Str::random(4),
                    'system_prompt' => "Ești asistentul virtual al magazinului online. Ajuți clienții să găsească produse, răspunzi la întrebări despre produse, prețuri și disponibilitate. Fii prietenos și util.",
                    'voice' => 'alloy',
                    'language' => 'ro',
                    'is_active' => true,
                ]);
            }
        }

        // Find or create web_chatbot channel
        $channel = Channel::withoutGlobalScopes()->firstOrCreate(
            ['bot_id' => $bot->id, 'type' => Channel::TYPE_WEB_CHATBOT],
            [
                'config' => [
                    'color' => '#991b1b',
                    'greeting' => 'Bună! Cu ce te pot ajuta?',
                    'position' => 'bottom-right',
                ],
                'is_active' => true,
                'status' => 'connected',
            ]
        );

        // Find or create WooCommerce connector
        $connector = KnowledgeConnector::withoutGlobalScopes()->firstOrCreate(
            ['bot_id' => $bot->id, 'type' => 'woocommerce'],
            [
                'site_url' => $siteUrl,
                'status' => 'connected',
            ]
        );

        $updateData = [
            'status' => 'connected',
            'site_url' => $siteUrl,
        ];

        // Save WooCommerce REST API credentials if provided
        $wcKey = $request->input('wc_consumer_key');
        $wcSecret = $request->input('wc_consumer_secret');
        if ($wcKey && $wcSecret) {
            $updateData['credentials'] = [
                'consumer_key' => $wcKey,
                'consumer_secret' => $wcSecret,
            ];
        }

        $connector->update($updateData);

        return response()->json([
            'success' => true,
            'channel_id' => $channel->id,
            'bot_id' => $bot->id,
            'connector_id' => $connector->id,
            'widget_config' => [
                'color' => $channel->config['color'] ?? '#991b1b',
                'greeting' => $channel->config['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
                'position' => $channel->config['position'] ?? 'bottom-right',
                'icon_url' => $channel->config['icon_url'] ?? null,
                'bot_name' => $bot->name,
            ],
        ]);
    }

    /**
     * Disconnect integration.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $request->validate([
            'connector_id' => 'nullable|integer',
        ]);

        $tenant = $request->user()->tenant;

        $query = KnowledgeConnector::withoutGlobalScopes()
            ->whereHas('bot', fn($q) => $q->where('tenant_id', $tenant->id))
            ->where('type', 'woocommerce');

        if ($request->input('connector_id')) {
            $query->where('id', $request->input('connector_id'));
        }

        $connector = $query->first();

        if ($connector) {
            $connector->update(['status' => 'disconnected']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Sync products from WooCommerce.
     */
    public function syncProducts(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array|max:100',
            'products.*.wc_product_id' => 'required|integer',
            'products.*.name' => 'required|string|max:500',
            'products.*.short_description' => 'nullable|string|max:5000',
            'products.*.description' => 'nullable|string|max:10000',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.regular_price' => 'nullable|numeric|min:0',
            'products.*.sale_price' => 'nullable|numeric|min:0',
            'products.*.currency' => 'nullable|string|max:10',
            'products.*.sku' => 'nullable|string|max:255',
            'products.*.stock_status' => 'nullable|string|in:instock,outofstock,onbackorder',
            'products.*.image_url' => 'nullable|url|max:2000',
            'products.*.categories' => 'nullable|array',
            'products.*.attributes' => 'nullable|array',
            'products.*.permalink' => 'required|url|max:2000',
            'site_url' => 'required|url|max:500',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'integer',
        ]);

        $tenant = $request->user()->tenant;
        $siteUrl = rtrim($request->input('site_url'), '/');

        // Find connector
        $connector = KnowledgeConnector::withoutGlobalScopes()
            ->whereHas('bot', fn($q) => $q->where('tenant_id', $tenant->id))
            ->where('type', 'woocommerce')
            ->where('status', 'connected')
            ->first();

        if (!$connector) {
            return response()->json(['error' => 'No active WooCommerce connector found. Call /connect first.'], 404);
        }

        $bot = $connector->bot;
        $synced = 0;
        $deleted = 0;

        // Sync products
        foreach ($request->input('products', []) as $productData) {
            $product = WooCommerceProduct::updateOrCreate(
                ['bot_id' => $bot->id, 'wc_product_id' => $productData['wc_product_id']],
                [
                    'name' => $productData['name'],
                    'short_description' => $productData['short_description'] ?? null,
                    'price' => $productData['price'],
                    'regular_price' => $productData['regular_price'] ?? null,
                    'sale_price' => $productData['sale_price'] ?? null,
                    'currency' => $productData['currency'] ?? 'RON',
                    'sku' => $productData['sku'] ?? null,
                    'stock_status' => $productData['stock_status'] ?? 'instock',
                    'image_url' => $productData['image_url'] ?? null,
                    'permalink' => $productData['permalink'],
                    'categories' => $productData['categories'] ?? null,
                    'attributes' => $productData['attributes'] ?? null,
                    'site_url' => $siteUrl,
                ]
            );

            // Create/update knowledge base entry for embeddings
            $knowledgeText = $product->toKnowledgeText();
            $knowledge = BotKnowledge::updateOrCreate(
                [
                    'bot_id' => $bot->id,
                    'source_type' => 'connector',
                    'source_id' => $connector->id,
                    'title' => 'Produs: ' . $product->name,
                ],
                [
                    'type' => 'text',
                    'content' => $knowledgeText,
                    'status' => 'pending',
                    'metadata' => [
                        'wc_product_id' => $product->wc_product_id,
                        'connector_type' => 'woocommerce',
                    ],
                ]
            );

            $product->update(['knowledge_id' => $knowledge->id]);

            // Dispatch embedding job
            ProcessKnowledgeDocument::dispatch($knowledge);
            $synced++;
        }

        // Delete removed products
        foreach ($request->input('deleted_ids', []) as $wcId) {
            $product = WooCommerceProduct::where('bot_id', $bot->id)
                ->where('wc_product_id', $wcId)
                ->first();

            if ($product) {
                if ($product->knowledge_id) {
                    BotKnowledge::where('id', $product->knowledge_id)->delete();
                }
                $product->delete();
                $deleted++;
            }
        }

        $connector->update([
            'last_synced_at' => now(),
            'sync_settings' => array_merge($connector->sync_settings ?? [], [
                'last_sync_count' => $synced,
                'total_products' => WooCommerceProduct::where('bot_id', $bot->id)->count(),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'synced' => $synced,
            'deleted' => $deleted,
            'total_products' => WooCommerceProduct::where('bot_id', $bot->id)->count(),
        ]);
    }

    /**
     * Sync pages/posts from WordPress.
     */
    public function syncPages(Request $request): JsonResponse
    {
        $request->validate([
            'pages' => 'required|array|max:100',
            'pages.*.id' => 'required|integer',
            'pages.*.title' => 'required|string|max:500',
            'pages.*.content' => 'required|string|max:50000',
            'pages.*.url' => 'required|url|max:2000',
            'pages.*.type' => 'nullable|string|in:page,post',
            'site_url' => 'required|url|max:500',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'integer',
        ]);

        $tenant = $request->user()->tenant;

        $connector = KnowledgeConnector::withoutGlobalScopes()
            ->whereHas('bot', fn($q) => $q->where('tenant_id', $tenant->id))
            ->whereIn('type', ['woocommerce', 'wordpress'])
            ->where('status', 'connected')
            ->first();

        if (!$connector) {
            return response()->json(['error' => 'No active connector found. Call /connect first.'], 404);
        }

        $bot = $connector->bot;
        $synced = 0;
        $deleted = 0;

        foreach ($request->input('pages', []) as $pageData) {
            $type = $pageData['type'] ?? 'page';
            $prefix = $type === 'post' ? 'Articol' : 'Pagină';

            $knowledge = BotKnowledge::updateOrCreate(
                [
                    'bot_id' => $bot->id,
                    'source_type' => 'connector',
                    'source_id' => $connector->id,
                    'title' => "{$prefix}: " . $pageData['title'],
                ],
                [
                    'type' => 'text',
                    'content' => $pageData['content'],
                    'status' => 'pending',
                    'metadata' => [
                        'wp_page_id' => $pageData['id'],
                        'wp_url' => $pageData['url'],
                        'wp_type' => $type,
                        'connector_type' => $connector->type,
                    ],
                ]
            );

            ProcessKnowledgeDocument::dispatch($knowledge);
            $synced++;
        }

        foreach ($request->input('deleted_ids', []) as $wpId) {
            $count = BotKnowledge::where('bot_id', $bot->id)
                ->where('source_type', 'connector')
                ->where('source_id', $connector->id)
                ->whereJsonContains('metadata->wp_page_id', $wpId)
                ->delete();
            $deleted += $count;
        }

        return response()->json([
            'success' => true,
            'synced' => $synced,
            'deleted' => $deleted,
        ]);
    }

    /**
     * Update widget configuration.
     */
    public function widgetConfig(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => 'required|integer',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'icon_url' => 'nullable|url|max:2000',
            'position' => 'nullable|string|in:bottom-right,bottom-left',
            'greeting' => 'nullable|string|max:500',
            'bot_name' => 'nullable|string|max:255',
        ]);

        $tenant = $request->user()->tenant;

        $channel = Channel::withoutGlobalScopes()
            ->whereHas('bot', fn($q) => $q->where('tenant_id', $tenant->id))
            ->findOrFail($request->input('channel_id'));

        $config = $channel->config ?? [];

        if ($request->has('color')) $config['color'] = $request->input('color');
        if ($request->has('icon_url')) $config['icon_url'] = $request->input('icon_url');
        if ($request->has('position')) $config['position'] = $request->input('position');
        if ($request->has('greeting')) $config['greeting'] = $request->input('greeting');

        $channel->update(['config' => $config]);

        if ($request->filled('bot_name')) {
            $channel->bot->update(['name' => $request->input('bot_name')]);
        }

        return response()->json(['success' => true, 'config' => $config]);
    }

    /**
     * Check integration status.
     */
    public function status(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $connector = KnowledgeConnector::withoutGlobalScopes()
            ->whereHas('bot', fn($q) => $q->where('tenant_id', $tenant->id))
            ->where('type', 'woocommerce')
            ->first();

        if (!$connector) {
            return response()->json([
                'connected' => false,
                'message' => 'No WooCommerce integration found.',
            ]);
        }

        $bot = $connector->bot;
        $channel = Channel::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('type', Channel::TYPE_WEB_CHATBOT)
            ->first();

        return response()->json([
            'connected' => $connector->status === 'connected',
            'connector_id' => $connector->id,
            'bot_id' => $bot->id,
            'bot_name' => $bot->name,
            'channel_id' => $channel?->id,
            'channel_active' => $channel?->is_active ?? false,
            'last_synced_at' => $connector->last_synced_at,
            'total_products' => WooCommerceProduct::where('bot_id', $bot->id)->count(),
            'widget_config' => $channel ? [
                'color' => $channel->config['color'] ?? '#991b1b',
                'greeting' => $channel->config['greeting'] ?? '',
                'position' => $channel->config['position'] ?? 'bottom-right',
                'icon_url' => $channel->config['icon_url'] ?? null,
                'bot_name' => $bot->name,
            ] : null,
        ]);
    }

    /**
     * Look up order details from WooCommerce.
     */
    public function orderLookup(Request $request): JsonResponse
    {
        $request->validate([
            'order_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $tenant = $request->user()->tenant;

        $connector = KnowledgeConnector::withoutGlobalScopes()
            ->whereHas('bot', fn($q) => $q->where('tenant_id', $tenant->id))
            ->whereIn('type', ['woocommerce', 'wordpress'])
            ->where('status', 'connected')
            ->first();

        if (!$connector) {
            return response()->json(['error' => 'No active connector found.'], 404);
        }

        $orderService = app(\App\Services\OrderLookupService::class);
        $result = $orderService->lookup($connector->bot_id, $request->only(['order_number', 'email', 'phone']));

        return response()->json($result);
    }
}
