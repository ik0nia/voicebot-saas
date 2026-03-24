<?php
if (!defined('ABSPATH')) exit;

class Sambla_Ajax {
    public function __construct() {
        add_action('wp_ajax_sambla_add_to_cart', [$this, 'add_to_cart']);
        add_action('wp_ajax_nopriv_sambla_add_to_cart', [$this, 'add_to_cart']);
        add_action('wp_ajax_sambla_add_multiple_to_cart', [$this, 'add_multiple']);
        add_action('wp_ajax_nopriv_sambla_add_multiple_to_cart', [$this, 'add_multiple']);
    }

    public function add_to_cart() {
        $pid = absint($_POST['product_id'] ?? 0);
        $qty = max(1, absint($_POST['quantity'] ?? 1));
        if (!$pid) wp_send_json_error(['message' => 'Product ID invalid.']);

        $product = wc_get_product($pid);
        if (!$product || !$product->is_purchasable()) wp_send_json_error(['message' => 'Produsul nu poate fi adăugat.']);

        if (WC()->cart->add_to_cart($pid, $qty)) {
            wp_send_json_success([
                'message' => $product->get_name() . ' a fost adăugat în coș!',
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_url' => wc_get_cart_url(),
            ]);
        }
        wp_send_json_error(['message' => 'Nu s-a putut adăuga produsul.']);
    }

    public function add_multiple() {
        $items = json_decode(stripslashes($_POST['items'] ?? '[]'), true);
        if (empty($items)) wp_send_json_error(['message' => 'Nu sunt produse de adăugat.']);

        $added = 0;
        foreach ($items as $item) {
            $pid = absint($item['product_id'] ?? 0);
            $qty = max(1, absint($item['quantity'] ?? 1));
            if (!$pid) continue;
            $product = wc_get_product($pid);
            if ($product && $product->is_purchasable() && WC()->cart->add_to_cart($pid, $qty)) $added++;
        }

        wp_send_json_success([
            'message' => $added . ' produs(e) adăugat(e) în coș!',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_url' => wc_get_cart_url(),
            'added' => $added,
        ]);
    }
}
