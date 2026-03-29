<?php
if (!defined('ABSPATH')) exit;

/**
 * V2 AJAX handlers for Sambla chatbot WooCommerce integration.
 *
 * Handles:
 * - add_to_cart: Real WooCommerce cart session add (simple + variable)
 * - cart_capabilities: Exposes what the store supports
 * - product_check: Validates if a product can be added to cart
 *
 * Attribution: Stores bot session data into WooCommerce session meta
 * so it can be attached to the order on checkout.
 */
class Sambla_Ajax {

    public function __construct() {
        // Cart actions (logged-in and guest)
        add_action('wp_ajax_sambla_add_to_cart', [$this, 'add_to_cart']);
        add_action('wp_ajax_nopriv_sambla_add_to_cart', [$this, 'add_to_cart']);

        add_action('wp_ajax_sambla_add_multiple_to_cart', [$this, 'add_multiple']);
        add_action('wp_ajax_nopriv_sambla_add_multiple_to_cart', [$this, 'add_multiple']);

        // V2: Product capability check (can this product be added to cart?)
        add_action('wp_ajax_sambla_product_check', [$this, 'product_check']);
        add_action('wp_ajax_nopriv_sambla_product_check', [$this, 'product_check']);

        // V2: Store capabilities for widget
        add_action('wp_ajax_sambla_store_capabilities', [$this, 'store_capabilities']);
        add_action('wp_ajax_nopriv_sambla_store_capabilities', [$this, 'store_capabilities']);

        // V2: Track native WooCommerce add-to-cart (not from widget) if user has bot session
        add_action('woocommerce_add_to_cart', [$this, 'track_native_add_to_cart'], 10, 6);

        // V2: Attribution — attach session data to WooCommerce order on checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'attach_attribution_to_order'], 10, 3);
        add_action('woocommerce_thankyou', [$this, 'send_purchase_webhook'], 20, 1);
    }

    /**
     * Add a single product to cart.
     * Handles simple products directly, variable products with variation_id.
     * If variation is required but not provided, returns error with redirect URL.
     */
    public function add_to_cart() {
        $pid = absint($_POST['product_id'] ?? 0);
        $qty = max(1, absint($_POST['quantity'] ?? 1));
        $variation_id = absint($_POST['variation_id'] ?? 0);

        if (!$pid) {
            wp_send_json_error(['message' => 'Product ID invalid.', 'error' => 'invalid_product']);
        }

        $product = wc_get_product($pid);
        if (!$product) {
            wp_send_json_error(['message' => 'Produsul nu a fost găsit.', 'error' => 'not_found']);
        }

        // Check purchasability
        if (!$product->is_purchasable()) {
            wp_send_json_error(['message' => 'Produsul nu poate fi cumpărat.', 'error' => 'not_purchasable']);
        }

        // Check stock
        if (!$product->is_in_stock()) {
            wp_send_json_error(['message' => 'Produsul nu este în stoc.', 'error' => 'out_of_stock']);
        }

        // Handle product types
        if ($product->is_type('variable')) {
            if (!$variation_id) {
                // Variation required but not provided — redirect to product page
                wp_send_json_error([
                    'message' => 'Acest produs necesită selectarea opțiunilor.',
                    'error' => 'variation_required',
                    'redirect_url' => $product->get_permalink(),
                ]);
            }

            // Validate variation exists and is in stock
            $variation = wc_get_product($variation_id);
            if (!$variation || !$variation->is_in_stock()) {
                wp_send_json_error([
                    'message' => 'Varianta selectată nu este disponibilă.',
                    'error' => 'variation_unavailable',
                    'redirect_url' => $product->get_permalink(),
                ]);
            }

            $added = WC()->cart->add_to_cart($pid, $qty, $variation_id);
        } elseif ($product->is_type('simple')) {
            $added = WC()->cart->add_to_cart($pid, $qty);
        } elseif ($product->is_type('external')) {
            wp_send_json_error([
                'message' => 'Acest produs este disponibil pe alt site.',
                'error' => 'external_product',
                'redirect_url' => $product->get_product_url(),
            ]);
        } else {
            // Grouped, bundle, or other complex types — redirect to product page
            wp_send_json_error([
                'message' => 'Vizitați pagina produsului pentru a comanda.',
                'error' => 'complex_product',
                'redirect_url' => $product->get_permalink(),
            ]);
        }

        if (!empty($added)) {
            // Store attribution data in WC session
            $this->store_attribution_session($_POST);

            wp_send_json_success([
                'message' => $product->get_name() . ' a fost adăugat în coș!',
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_url' => wc_get_cart_url(),
                'product_name' => $product->get_name(),
            ]);
        }

        wp_send_json_error(['message' => 'Nu s-a putut adăuga produsul.', 'error' => 'add_failed']);
    }

    /**
     * Add multiple products to cart.
     */
    public function add_multiple() {
        $items = json_decode(stripslashes($_POST['items'] ?? '[]'), true);
        if (empty($items)) {
            wp_send_json_error(['message' => 'Nu sunt produse de adăugat.', 'error' => 'empty_items']);
        }

        $added = 0;
        $errors = [];

        foreach ($items as $item) {
            $pid = absint($item['product_id'] ?? $item['id'] ?? 0);
            $qty = max(1, absint($item['quantity'] ?? 1));
            if (!$pid) continue;

            $product = wc_get_product($pid);
            if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
                $errors[] = ['product_id' => $pid, 'error' => 'unavailable'];
                continue;
            }
            if (!$product->is_type('simple')) {
                $errors[] = ['product_id' => $pid, 'error' => 'not_simple', 'redirect_url' => $product->get_permalink()];
                continue;
            }

            if (WC()->cart->add_to_cart($pid, $qty)) {
                $added++;
            }
        }

        if ($added > 0) {
            $this->store_attribution_session($_POST);
        }

        wp_send_json_success([
            'message' => $added . ' produs(e) adăugat(e) în coș!',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_url' => wc_get_cart_url(),
            'added' => $added,
            'errors' => $errors,
        ]);
    }

    /**
     * V2: Check if a product can be added to cart from the widget.
     */
    public function product_check() {
        $pid = absint($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        if (!$pid) wp_send_json_error(['error' => 'invalid_product']);

        $product = wc_get_product($pid);
        if (!$product) wp_send_json_error(['error' => 'not_found']);

        $can_add = $product->is_purchasable() && $product->is_in_stock() && $product->is_type('simple');

        wp_send_json_success([
            'product_id' => $pid,
            'can_add' => $can_add,
            'requires_variation' => $product->is_type('variable'),
            'is_out_of_stock' => !$product->is_in_stock(),
            'is_external' => $product->is_type('external'),
            'product_type' => $product->get_type(),
            'permalink' => $product->get_permalink(),
        ]);
    }

    /**
     * V2: Return store capabilities for widget UI adaptation.
     */
    public function store_capabilities() {
        $connected = get_option('sambla_connected', false);
        $has_wc = class_exists('WooCommerce');

        wp_send_json_success([
            'woocommerce_connected' => $connected && $has_wc,
            'cart_enabled' => $has_wc,
            'checkout_url' => $has_wc ? wc_get_checkout_url() : null,
            'cart_url' => $has_wc ? wc_get_cart_url() : null,
            'currency' => $has_wc ? get_woocommerce_currency() : null,
            'currency_symbol' => $has_wc ? get_woocommerce_currency_symbol() : null,
        ]);
    }

    /**
     * V2: Track native WooCommerce add-to-cart if user has a bot session.
     * This catches the standard "Add to Cart" button on product pages,
     * NOT the widget ATC button (which goes through sambla_add_to_cart AJAX).
     */
    public function track_native_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        if (!WC()->session) return;

        $attribution = WC()->session->get('sambla_attribution');
        if (empty($attribution) || empty($attribution['session_id'])) return;

        // User has a bot session — this is a bot-influenced add to cart
        $api_key = get_option('sambla_api_key', '');
        $bot_id = $attribution['bot_id'] ?? get_option('sambla_bot_id', '');
        if (empty($api_key) || empty($bot_id)) return;

        $product = wc_get_product($product_id);

        $payload = [
            'events' => [[
                'event_name' => 'add_to_cart_success',
                'properties' => [
                    'product_id' => $product_id,
                    'product_name' => $product ? $product->get_name() : '',
                    'price' => $product ? $product->get_price() : 0,
                    'quantity' => $quantity,
                    'source' => 'native_woocommerce',
                ],
                'session_id' => $attribution['session_id'],
                'visitor_id' => $attribution['visitor_id'] ?? '',
                'conversation_id' => $attribution['conversation_id'] ?? null,
            ]],
        ];

        // Get channel_id
        $channel_id = get_option('sambla_channel_id', '');
        if (empty($channel_id)) return;

        wp_remote_post(SAMBLA_API_BASE . '/api/v1/chatbot/' . $channel_id . '/events', [
            'timeout' => 5,
            'blocking' => false, // non-blocking — don't slow down add to cart
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($payload),
        ]);
    }

    /**
     * Store bot session attribution data in WooCommerce session.
     * This data will be attached to the order on checkout.
     */
    private function store_attribution_session($post_data) {
        if (!WC()->session) return;

        $attribution = [
            'session_id' => sanitize_text_field($post_data['session_id'] ?? ''),
            'conversation_id' => absint($post_data['conversation_id'] ?? 0),
            'bot_id' => absint($post_data['bot_id'] ?? 0),
            'channel_id' => absint($post_data['channel_id'] ?? 0),
            'visitor_id' => sanitize_text_field($post_data['visitor_id'] ?? ''),
            'timestamp' => current_time('mysql'),
        ];

        // Only store if we have at least session_id
        if (!empty($attribution['session_id'])) {
            WC()->session->set('sambla_attribution', $attribution);
        }
    }

    /**
     * V2: Attach attribution data to order meta on checkout.
     */
    public function attach_attribution_to_order($order_id, $posted_data, $order) {
        if (!WC()->session) return;

        $attribution = WC()->session->get('sambla_attribution');
        if (empty($attribution)) return;

        $order->update_meta_data('_sambla_session_id', $attribution['session_id'] ?? '');
        $order->update_meta_data('_sambla_conversation_id', $attribution['conversation_id'] ?? '');
        $order->update_meta_data('_sambla_bot_id', $attribution['bot_id'] ?? '');
        $order->update_meta_data('_sambla_channel_id', $attribution['channel_id'] ?? '');
        $order->update_meta_data('_sambla_visitor_id', $attribution['visitor_id'] ?? '');
        $order->update_meta_data('_sambla_attribution_timestamp', $attribution['timestamp'] ?? '');
        $order->save();

        // Clear session attribution after attaching
        WC()->session->set('sambla_attribution', null);
    }

    /**
     * V2: Send purchase webhook to SaaS on order completion (thankyou page).
     */
    public function send_purchase_webhook($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Only send once
        if ($order->get_meta('_sambla_webhook_sent')) return;

        $session_id = $order->get_meta('_sambla_session_id');
        if (empty($session_id)) return; // No attribution data — not a chatbot-assisted order

        $bot_id = $order->get_meta('_sambla_bot_id');
        $api_key = get_option('sambla_api_key', '');
        if (empty($api_key) || empty($bot_id)) return;

        // Build order data
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'product_id' => $product ? $product->get_id() : 0,
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
            ];
        }

        $payload = [
            'wc_order_id' => (string) $order->get_id(),
            'order_number' => $order->get_order_number(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'status' => $order->get_status(),
            'items' => $items,
            'customer_email' => $order->get_billing_email(),
            'session_id' => $session_id,
            'conversation_id' => $order->get_meta('_sambla_conversation_id'),
            'bot_id' => $bot_id,
            'channel_id' => $order->get_meta('_sambla_channel_id'),
            'visitor_id' => $order->get_meta('_sambla_visitor_id'),
            'attribution_timestamp' => $order->get_meta('_sambla_attribution_timestamp'),
        ];

        // Sign payload with API key
        $signature = hash_hmac('sha256', wp_json_encode($payload), $api_key);

        $response = wp_remote_post(SAMBLA_API_BASE . '/api/v1/webhooks/woocommerce/' . $bot_id . '/purchase', [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Sambla-Signature' => $signature,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $order->update_meta_data('_sambla_webhook_sent', current_time('mysql'));
            $order->save();
        }
    }
}
