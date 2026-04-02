<?php

namespace App\Services;

use App\Models\KnowledgeConnector;
use App\Services\Security\SsrfGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OrderLookupService
{
    private const DEFAULT_TIMEOUT = 10;
    private const CACHE_TTL_MINUTES = 10;
    private const MAX_DISPLAY_ORDERS = 3;

    /**
     * Courier tracking URL builders.
     */
    private const COURIER_URLS = [
        'fancourier' => 'https://www.fancourier.ro/awb-tracking/?awb=',
        'fan' => 'https://www.fancourier.ro/awb-tracking/?awb=',
        'cargus' => 'https://www.cargus.ro/tracking-online/?t=',
        'urgent' => 'https://www.cargus.ro/tracking-online/?t=',
        'dpd' => 'https://tracking.dpd.ro/tracking?reference=',
        'sameday' => 'https://sameday.ro/#/awb/',
        'gls' => 'https://gls-group.com/RO/ro/urmarire-colete?match=',
    ];

    /**
     * Look up order by number, email, or phone.
     *
     * @return array{found: bool, orders: array, message: string, verification_required?: bool}
     */
    public function lookup(int $botId, array $params): array
    {
        $connector = KnowledgeConnector::where('bot_id', $botId)
            ->whereIn('type', ['woocommerce', 'wordpress'])
            ->where('status', 'connected')
            ->first();

        if (!$connector || empty($connector->credentials)) {
            return ['found' => false, 'orders' => [], 'message' => trans('orders.connector_not_configured')];
        }

        $credentials = $connector->credentials;
        if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret'])) {
            return ['found' => false, 'orders' => [], 'message' => trans('orders.credentials_missing')];
        }

        $baseUrl = rtrim($connector->site_url, '/');

        try {
            SsrfGuard::validateUrl($baseUrl);
        } catch (\Exception $e) {
            return ['found' => false, 'orders' => [], 'message' => trans('orders.invalid_url')];
        }

        // Rate limiting per bot
        $rateLimitKey = 'order_lookup_' . $botId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return ['found' => false, 'orders' => [], 'message' => trans('orders.rate_limited')];
        }
        RateLimiter::hit($rateLimitKey, 60);

        $timeout = (int) config('sambla.woocommerce.timeout', self::DEFAULT_TIMEOUT);

        try {
            if (!empty($params['order_number'])) {
                return $this->lookupByNumber($baseUrl, $credentials, $params['order_number'], $timeout);
            }

            if (!empty($params['email'])) {
                return $this->lookupByEmail($baseUrl, $credentials, $params['email'], $timeout);
            }

            if (!empty($params['phone'])) {
                return $this->lookupByPhone($baseUrl, $credentials, $params['phone'], $timeout);
            }

            return ['found' => false, 'orders' => [], 'message' => trans('orders.need_identifier')];

        } catch (\Exception $e) {
            Log::error('OrderLookup failed', ['bot_id' => $botId, 'error' => $e->getMessage()]);
            return ['found' => false, 'orders' => [], 'message' => trans('orders.lookup_failed')];
        }
    }

    /**
     * Detect if a message is about orders and extract params.
     */
    public function detectOrderQuery(string $message): ?array
    {
        $msg = mb_strtolower(trim($message));

        $orderPatterns = [
            '/comand[aă]/u',
            '/unde.*comand/u',
            '/status.*comand/u',
            '/comand.*statu/u',
            '/livr[aă]r/u',
            '/colet/u',
            '/tracking/i',
            '/aw[b]?\s*\d/i',
            '/cand.*vine/u',
            '/cand.*ajunge/u',
            '/tracking\s*(number|nr)?/i',
            '/nr\.?\s*de\s*referint/u',
            '/numar.*comand/u',
            '/awb/i',
            '/expedit/u',
            '/status.*livr/u',
            // Extended patterns
            '/cand.*primesc/u',
            '/unde.*e.*comanda/u',
            '/verific.*comand/u',
        ];

        $isOrderQuery = false;
        foreach ($orderPatterns as $pattern) {
            if (preg_match($pattern, $msg)) {
                $isOrderQuery = true;
                break;
            }
        }

        if (!$isOrderQuery) return null;

        return $this->extractIdentifiers($message);
    }

    /**
     * Extract order params from message without requiring order keywords.
     */
    public function extractOrderParams(string $message): ?array
    {
        $params = $this->extractIdentifiers($message);
        return !empty($params) ? $params : null;
    }

    /**
     * Extract order number, email, and phone from text.
     * Supports E.164 international phone format.
     */
    private function extractIdentifiers(string $message): array
    {
        $params = [];

        // Order number — strictly digits only to prevent URL injection
        if (preg_match('/#?\b(\d{3,8})\b/', $message, $m)) {
            $params['order_number'] = preg_replace('/\D/', '', $m[1]);
        }

        // Email — use filter_var for proper validation instead of loose regex
        if (preg_match('/[\w.+-]+@[\w.-]+\.\w{2,}/', $message, $m)) {
            $candidate = $m[0];
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $params['email'] = $candidate;
            }
        }

        // Phone - E.164 international format support
        if (preg_match('/\+?\d{1,4}[\s.-]?\(?\d{1,4}\)?[\s.-]?\d{2,4}[\s.-]?\d{2,4}[\s.-]?\d{0,4}/', $message, $m)) {
            $phone = preg_replace('/[\s.\-\(\)]/', '', $m[0]);
            if (strlen($phone) >= 8 && strlen($phone) <= 15) {
                $params['phone'] = $phone;
            }
        }

        return $params;
    }

    private function lookupByNumber(string $baseUrl, array $credentials, string $orderNumber, int $timeout): array
    {
        $cacheKey = 'order_num_' . md5($baseUrl . $orderNumber);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($baseUrl, $credentials, $orderNumber, $timeout) {
            $response = Http::timeout($timeout)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get($baseUrl . '/wp-json/wc/v3/orders/' . urlencode($orderNumber));

            if ($response->status() === 404) {
                return ['found' => false, 'orders' => [], 'message' => trans('orders.order_not_found', ['number' => $orderNumber])];
            }

            if (!$response->successful()) {
                Log::warning('OrderLookup: WooCommerce API error (byNumber)', ['status' => $response->status(), 'order' => $orderNumber]);
                if ($response->status() === 401) {
                    return ['found' => false, 'orders' => [], 'message' => trans('orders.credentials_invalid')];
                }
                if ($response->status() === 429) {
                    return ['found' => false, 'orders' => [], 'message' => trans('orders.rate_limited')];
                }
                return ['found' => false, 'orders' => [], 'message' => trans('orders.verify_failed')];
            }

            $order = $response->json();
            return ['found' => true, 'orders' => [$this->formatOrder($order)], 'message' => ''];
        });
    }

    private function lookupByEmail(string $baseUrl, array $credentials, string $email, int $timeout): array
    {
        $cacheKey = 'order_email_' . md5($baseUrl . $email);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($baseUrl, $credentials, $email, $timeout) {
            $response = Http::timeout($timeout)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get($baseUrl . '/wp-json/wc/v3/orders', [
                    'search' => $email,
                    'per_page' => 10,
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            if (!$response->successful()) {
                Log::warning('OrderLookup: WooCommerce API error (byEmail)', ['status' => $response->status()]);
                if ($response->status() === 401) {
                    return ['found' => false, 'orders' => [], 'message' => trans('orders.credentials_invalid')];
                }
                if ($response->status() === 429) {
                    return ['found' => false, 'orders' => [], 'message' => trans('orders.rate_limited')];
                }
                return ['found' => false, 'orders' => [], 'message' => trans('orders.verify_failed')];
            }

            $orders = $response->json();
            if (empty($orders)) {
                return ['found' => false, 'orders' => [], 'message' => trans('orders.orders_not_found_email', ['email' => $email])];
            }

            return $this->paginateResults($orders);
        });
    }

    private function lookupByPhone(string $baseUrl, array $credentials, string $phone, int $timeout): array
    {
        $cacheKey = 'order_phone_' . md5($baseUrl . $phone);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($baseUrl, $credentials, $phone, $timeout) {
            // Use WooCommerce search param for scalable lookup
            $response = Http::timeout($timeout)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get($baseUrl . '/wp-json/wc/v3/orders', [
                    'search' => $phone,
                    'per_page' => 10,
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            if (!$response->successful()) {
                Log::warning('OrderLookup: WooCommerce API error (byPhone)', ['status' => $response->status()]);
                if ($response->status() === 401) {
                    return ['found' => false, 'orders' => [], 'message' => trans('orders.credentials_invalid')];
                }
                if ($response->status() === 429) {
                    return ['found' => false, 'orders' => [], 'message' => trans('orders.rate_limited')];
                }
                return ['found' => false, 'orders' => [], 'message' => trans('orders.verify_failed')];
            }

            $orders = $response->json();
            if (empty($orders)) {
                return ['found' => false, 'orders' => [], 'message' => trans('orders.orders_not_found_phone')];
            }

            return $this->paginateResults($orders);
        });
    }

    /**
     * Paginate results: show first N, inform about total.
     */
    private function paginateResults(array $orders): array
    {
        $total = count($orders);
        $shown = min($total, self::MAX_DISPLAY_ORDERS);
        $displayOrders = array_map([$this, 'formatOrder'], array_slice($orders, 0, $shown));

        $message = '';
        if ($total > $shown) {
            $message = trans('orders.too_many_results', ['count' => $total, 'shown' => $shown]);
        }

        return [
            'found' => true,
            'orders' => $displayOrders,
            'message' => $message,
            'total_count' => $total,
        ];
    }

    private function formatOrder(array $order): array
    {
        $statusRaw = $order['status'] ?? 'unknown';
        $status = trans("orders.status_{$statusRaw}");
        // If translation key doesn't exist, use raw status
        if ($status === "orders.status_{$statusRaw}") {
            $status = ucfirst($statusRaw);
        }

        $items = collect($order['line_items'] ?? [])->map(fn($item) => [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'total' => $item['total'],
        ])->toArray();

        $trackingMeta = $order['meta_data'] ? collect($order['meta_data'])->firstWhere('key', '_tracking_number') : null;
        $trackingNumber = $trackingMeta['value'] ?? null;
        $trackingUrl = $trackingNumber ? $this->buildTrackingUrl($trackingNumber, $order['shipping_lines'] ?? []) : null;

        return [
            'number' => $order['number'] ?? $order['id'],
            'status' => $status,
            'status_raw' => $statusRaw,
            'date' => isset($order['date_created']) ? date('d.m.Y H:i', strtotime($order['date_created'])) : '',
            'total' => ($order['total'] ?? '0') . ' ' . ($order['currency'] ?? 'RON'),
            // payment_method and customer_name redacted — not needed by voice/chat bot
            // and exposing them to someone who guesses an order number is a data leak
            'shipping_method' => collect($order['shipping_lines'] ?? [])->pluck('method_title')->implode(', ') ?: '-',
            'tracking' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'items' => $items,
        ];
    }

    /**
     * Build tracking URL based on detected courier.
     */
    private function buildTrackingUrl(string $trackingNumber, array $shippingLines): ?string
    {
        $shippingMethod = mb_strtolower(collect($shippingLines)->pluck('method_title')->implode(' '));

        foreach (self::COURIER_URLS as $keyword => $baseUrl) {
            if (str_contains($shippingMethod, $keyword)) {
                return $baseUrl . urlencode($trackingNumber);
            }
        }

        return null;
    }
}
