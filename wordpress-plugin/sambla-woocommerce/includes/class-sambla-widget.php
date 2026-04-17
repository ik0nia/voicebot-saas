<?php
if (!defined('ABSPATH')) exit;

class Sambla_Widget {
    public function __construct() {
        add_action('wp_footer', [$this, 'inject']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function inject() {
        if (is_admin() || !get_option('sambla_connected')) return;
        $channel_id = get_option('sambla_channel_id', '');
        if (empty($channel_id)) return;

        $c = get_option('sambla_widget_config', []);
        $attrs = 'data-channel-id="' . esc_attr($channel_id) . '"';
        $attrs .= ' data-color="' . esc_attr($c['color'] ?? '#991b1b') . '"';
        $attrs .= ' data-position="' . esc_attr($c['position'] ?? 'bottom-right') . '"';
        $attrs .= ' data-greeting="' . esc_attr($c['greeting'] ?? '') . '"';
        $attrs .= ' data-bot-name="' . esc_attr($c['bot_name'] ?? '') . '"';
        $attrs .= ' data-api-base="' . esc_attr(SAMBLA_API_BASE) . '"';
        if (!empty($c['icon_url'])) $attrs .= ' data-icon-url="' . esc_attr($c['icon_url']) . '"';

        // Inject contextual globals BEFORE the widget script loads so
        // the widget can pick them up on init. Three separate globals
        // kept small — widget reads what's present, ignores what's
        // missing. Output escaped via wp_json_encode (safe for <script>).
        $page_type = $this->detect_page_type();
        $product_context = $this->product_context();
        $cart_context = $this->cart_context();

        echo '<script>'
            . 'window.samblaPageType=' . wp_json_encode($page_type) . ';'
            . 'window.samblaProductContext=' . wp_json_encode($product_context) . ';'
            . 'window.samblaCartContext=' . wp_json_encode($cart_context) . ';'
            . '</script>';

        echo '<script src="' . esc_url(SAMBLA_API_BASE . '/widget/sambla-chat.js?v=' . SAMBLA_VERSION) . '" ' . $attrs . ' async defer></script>';
    }

    /**
     * Detect the current page_type for widget context. Uses WooCommerce
     * template tags where available; falls back to 'general'.
     */
    private function detect_page_type() {
        if (function_exists('is_product') && is_product())                          return 'product';
        if (function_exists('is_product_category') && is_product_category())        return 'category';
        if (function_exists('is_shop') && is_shop())                                return 'category';
        if (function_exists('is_cart') && is_cart())                                return 'cart';
        if (function_exists('is_checkout') && is_checkout())                        return 'cart';
        if (function_exists('is_front_page') && is_front_page())                    return 'home';
        return 'general';
    }

    /**
     * Lightweight product context for the chat payload. Only id,
     * name, price, categories — enough for the LLM to reference the
     * product without revealing unrelated DB internals.
     */
    private function product_context() {
        if (!function_exists('is_product') || !is_product()) return null;
        if (!function_exists('wc_get_product')) return null;
        $pid = get_queried_object_id();
        if (!$pid) return null;
        $product = wc_get_product($pid);
        if (!$product) return null;
        $cats = [];
        foreach (wp_get_post_terms($pid, 'product_cat') as $term) {
            $cats[] = $term->name;
        }
        return [
            'product_id' => (int) $pid,
            'name'       => (string) $product->get_name(),
            'price'      => (string) $product->get_price(),
            'currency'   => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : null,
            'categories' => array_slice($cats, 0, 5),
            'in_stock'   => method_exists($product, 'is_in_stock') ? (bool) $product->is_in_stock() : null,
            'permalink'  => get_permalink($pid),
        ];
    }

    /**
     * Cart summary — counts + total, plus a tiny list of items (id +
     * name + qty). Extended (G1) with free-shipping threshold data
     * so the bot can surface "îți mai lipsesc X lei pentru livrare
     * gratuită" conversion chips at the right moment.
     *
     * Threshold discovery order:
     *   1. explicit `sambla_free_shipping_threshold` WP option — lets
     *      the tenant set an exact value from the plugin settings
     *      without reading shipping-zone internals.
     *   2. scan registered free_shipping methods, take the smallest
     *      min_amount — works automatically for most stores.
     */
    private function cart_context() {
        if (!function_exists('WC') || !WC() || !WC()->cart) return null;
        $cart = WC()->cart;
        $items = [];
        foreach ($cart->get_cart() as $item) {
            $prod = $item['data'] ?? null;
            $items[] = [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'name'       => $prod && method_exists($prod, 'get_name') ? (string) $prod->get_name() : '',
                'qty'        => (int) ($item['quantity'] ?? 0),
            ];
            if (count($items) >= 8) break;
        }
        if (empty($items)) return null;

        $threshold = $this->free_shipping_threshold();
        $cartSubtotal = method_exists($cart, 'get_subtotal') ? (float) $cart->get_subtotal() : (float) $cart->get_total('raw');
        $missing = null;
        if ($threshold !== null && $threshold > 0 && $cartSubtotal < $threshold) {
            $missing = round($threshold - $cartSubtotal, 2);
        }

        return [
            'items_count' => (int) $cart->get_cart_contents_count(),
            'total'       => (string) $cart->get_cart_total(),
            'total_raw'   => round($cartSubtotal, 2),
            'currency'    => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : null,
            'items'       => $items,
            'shipping_threshold' => $threshold,
            'missing_amount_for_free_shipping' => $missing,
        ];
    }

    private function free_shipping_threshold() {
        $explicit = (float) get_option('sambla_free_shipping_threshold', 0);
        if ($explicit > 0) return round($explicit, 2);

        if (!function_exists('WC') || !WC() || !WC()->shipping()) return null;
        $lowest = null;
        try {
            $zones = class_exists('WC_Shipping_Zones') ? \WC_Shipping_Zones::get_zones() : [];
            foreach ($zones as $zone) {
                $methods = $zone['shipping_methods'] ?? [];
                foreach ($methods as $method) {
                    if (!is_object($method)) continue;
                    if (($method->id ?? null) !== 'free_shipping') continue;
                    if (!empty($method->enabled) && ($method->enabled !== 'yes' && $method->enabled !== true)) continue;
                    $min = (float) (method_exists($method, 'get_option') ? $method->get_option('min_amount', 0) : 0);
                    if ($min > 0 && ($lowest === null || $min < $lowest)) {
                        $lowest = $min;
                    }
                }
            }
        } catch (\Throwable $e) { return null; }
        return $lowest !== null ? round($lowest, 2) : null;
    }

    public function enqueue() {
        if (is_admin() || !get_option('sambla_connected')) return;
        wp_enqueue_script('sambla-cart', SAMBLA_PLUGIN_URL . 'assets/js/sambla-cart.js', [], SAMBLA_VERSION, true);
        wp_localize_script('sambla-cart', 'samblaCart', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sambla_cart'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
        ]);
    }
}
