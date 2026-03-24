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
    private const STATUS_MAP = [
        'pending' => 'În așteptare plată',
        'processing' => 'Se procesează',
        'on-hold' => 'În așteptare',
        'completed' => 'Finalizată',
        'cancelled' => 'Anulată',
        'refunded' => 'Rambursată',
        'failed' => 'Eșuată',
        'trash' => 'Ștearsă',
    ];

    /**
     * Look up order by number, email, or phone.
     *
     * @return array{found: bool, orders: array, message: string}
     */
    public function lookup(int $botId, array $params): array
    {
        $connector = KnowledgeConnector::withoutGlobalScopes()
            ->where('bot_id', $botId)
            ->whereIn('type', ['woocommerce', 'wordpress'])
            ->where('status', 'connected')
            ->first();

        if (!$connector || empty($connector->credentials)) {
            return ['found' => false, 'orders' => [], 'message' => 'Magazinul nu are conectorul configurat pentru verificarea comenzilor.'];
        }

        $credentials = $connector->credentials;
        if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret'])) {
            return ['found' => false, 'orders' => [], 'message' => 'Credențialele WooCommerce nu sunt configurate.'];
        }

        $baseUrl = rtrim($connector->site_url, '/');

        try {
            SsrfGuard::validateUrl($baseUrl);
        } catch (\Exception $e) {
            return ['found' => false, 'orders' => [], 'message' => 'URL invalid.'];
        }

        $rateLimitKey = 'order_lookup_' . $botId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return ['found' => false, 'orders' => [], 'message' => 'Prea multe verificări de comenzi. Încercați din nou într-un minut.'];
        }
        RateLimiter::hit($rateLimitKey, 60);

        try {
            // Search by order number
            if (!empty($params['order_number'])) {
                return $this->lookupByNumber($baseUrl, $credentials, $params['order_number']);
            }

            // Search by email
            if (!empty($params['email'])) {
                return $this->lookupByEmail($baseUrl, $credentials, $params['email']);
            }

            // Search by phone
            if (!empty($params['phone'])) {
                return $this->lookupByPhone($baseUrl, $credentials, $params['phone']);
            }

            return ['found' => false, 'orders' => [], 'message' => 'Aveți nevoie de numărul comenzii, emailul sau telefonul pentru a verifica comanda.'];

        } catch (\Exception $e) {
            Log::error('OrderLookup failed', ['bot_id' => $botId, 'error' => $e->getMessage()]);
            return ['found' => false, 'orders' => [], 'message' => 'Nu am putut verifica comanda. Încercați din nou sau contactați magazinul.'];
        }
    }

    /**
     * Detect if a message is about orders and extract params.
     *
     * @return array|null  Returns params if order query detected, null otherwise.
     */
    public function detectOrderQuery(string $message): ?array
    {
        $msg = mb_strtolower(trim($message));

        // Check if it's about orders
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
        ];

        $isOrderQuery = false;
        foreach ($orderPatterns as $pattern) {
            if (preg_match($pattern, $msg)) {
                $isOrderQuery = true;
                break;
            }
        }

        if (!$isOrderQuery) return null;

        $params = [];

        // Extract order number (#1234, comanda 1234, nr 1234)
        if (preg_match('/#?\b(\d{3,8})\b/', $message, $m)) {
            $params['order_number'] = $m[1];
        }

        // Extract email
        if (preg_match('/[\w.+-]+@[\w.-]+\.\w{2,}/', $message, $m)) {
            $params['email'] = $m[0];
        }

        // Extract phone (Romanian formats)
        if (preg_match('/(?:\+?\d{1,3}[\s.-]?)?\(?\d{2,4}\)?[\s.-]?\d{3,4}[\s.-]?\d{3,4}/', $message, $m)) {
            $params['phone'] = preg_replace('/[\s.-]/', '', $m[0]);
        }

        return $params;
    }

    /**
     * Extract order params (number, email, phone) from a message without requiring order keywords.
     * Used for follow-up messages where the bot already asked for details.
     *
     * @return array|null  Returns params if any identifiers found, null otherwise.
     */
    public function extractOrderParams(string $message): ?array
    {
        $params = [];

        // Extract order number
        if (preg_match('/#?\b(\d{3,8})\b/', $message, $m)) {
            $params['order_number'] = $m[1];
        }

        // Extract email
        if (preg_match('/[\w.+-]+@[\w.-]+\.\w{2,}/', $message, $m)) {
            $params['email'] = $m[0];
        }

        // Extract phone (Romanian formats)
        if (preg_match('/(?:\+?\d{1,3}[\s.-]?)?\(?\d{2,4}\)?[\s.-]?\d{3,4}[\s.-]?\d{3,4}/', $message, $m)) {
            $params['phone'] = preg_replace('/[\s.-]/', '', $m[0]);
        }

        return !empty($params) ? $params : null;
    }

    private function lookupByNumber(string $baseUrl, array $credentials, string $orderNumber): array
    {
        return Cache::remember('order_lookup_num_' . $orderNumber, now()->addMinutes(10), function () use ($baseUrl, $credentials, $orderNumber) {
            $response = Http::timeout(10)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get($baseUrl . '/wp-json/wc/v3/orders/' . $orderNumber);

            if ($response->status() === 404) {
                return ['found' => false, 'orders' => [], 'message' => "Nu am găsit comanda #{$orderNumber}."];
            }

            if (!$response->successful()) {
                return ['found' => false, 'orders' => [], 'message' => 'Nu am putut verifica comanda.'];
            }

            $order = $response->json();
            return ['found' => true, 'orders' => [$this->formatOrder($order)], 'message' => ''];
        });
    }

    private function lookupByEmail(string $baseUrl, array $credentials, string $email): array
    {
        return Cache::remember('order_lookup_email_' . md5($email), now()->addMinutes(10), function () use ($baseUrl, $credentials, $email) {
            $response = Http::timeout(10)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get($baseUrl . '/wp-json/wc/v3/orders', [
                    'search' => $email,
                    'per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            if (!$response->successful()) {
                return ['found' => false, 'orders' => [], 'message' => 'Nu am putut verifica comenzile.'];
            }

            $orders = $response->json();
            if (empty($orders)) {
                return ['found' => false, 'orders' => [], 'message' => "Nu am găsit comenzi pentru emailul {$email}."];
            }

            return [
                'found' => true,
                'orders' => array_map([$this, 'formatOrder'], array_slice($orders, 0, 3)),
                'message' => '',
            ];
        });
    }

    private function lookupByPhone(string $baseUrl, array $credentials, string $phone): array
    {
        return Cache::remember('order_lookup_phone_' . md5($phone), now()->addMinutes(10), function () use ($baseUrl, $credentials, $phone) {
            $response = Http::timeout(10)
                ->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
                ->get($baseUrl . '/wp-json/wc/v3/orders', [
                    'search' => $phone,
                    'per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            if (!$response->successful()) {
                return ['found' => false, 'orders' => [], 'message' => 'Nu am putut verifica comenzile.'];
            }

            $orders = $response->json();
            if (empty($orders)) {
                return ['found' => false, 'orders' => [], 'message' => 'Nu am găsit comenzi pentru acest număr de telefon.'];
            }

            return ['found' => true, 'orders' => array_map([$this, 'formatOrder'], $orders), 'message' => ''];
        });
    }

    private function formatOrder(array $order): array
    {
        $status = self::STATUS_MAP[$order['status'] ?? ''] ?? ($order['status'] ?? 'Necunoscut');

        $items = collect($order['line_items'] ?? [])->map(fn($item) => [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'total' => $item['total'],
        ])->toArray();

        $trackingNumber = $order['meta_data'] ? collect($order['meta_data'])->firstWhere('key', '_tracking_number')['value'] ?? null : null;
        $trackingUrl = null;
        if ($trackingNumber) {
            $shippingMethod = mb_strtolower(collect($order['shipping_lines'] ?? [])->pluck('method_title')->implode(' '));
            if (str_contains($shippingMethod, 'fan') || str_contains($shippingMethod, 'fancourier')) {
                $trackingUrl = 'https://www.fancourier.ro/awb-tracking/?awb=' . urlencode($trackingNumber);
            } elseif (str_contains($shippingMethod, 'cargus') || str_contains($shippingMethod, 'urgent')) {
                $trackingUrl = 'https://www.cargus.ro/tracking-online/?t=' . urlencode($trackingNumber);
            } elseif (str_contains($shippingMethod, 'dpd')) {
                $trackingUrl = 'https://tracking.dpd.ro/tracking?reference=' . urlencode($trackingNumber);
            } elseif (str_contains($shippingMethod, 'sameday')) {
                $trackingUrl = 'https://sameday.ro/#/awb/' . urlencode($trackingNumber);
            } elseif (str_contains($shippingMethod, 'gls')) {
                $trackingUrl = 'https://gls-group.com/RO/ro/urmarire-colete?match=' . urlencode($trackingNumber);
            }
        }

        return [
            'number' => $order['number'] ?? $order['id'],
            'status' => $status,
            'status_raw' => $order['status'] ?? '',
            'date' => isset($order['date_created']) ? date('d.m.Y H:i', strtotime($order['date_created'])) : '',
            'total' => ($order['total'] ?? '0') . ' ' . ($order['currency'] ?? 'RON'),
            'payment_method' => $order['payment_method_title'] ?? '',
            'shipping_method' => collect($order['shipping_lines'] ?? [])->pluck('method_title')->implode(', ') ?: '-',
            'tracking' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'items' => $items,
            'customer_name' => trim(($order['billing']['first_name'] ?? '') . ' ' . ($order['billing']['last_name'] ?? '')),
        ];
    }
}
