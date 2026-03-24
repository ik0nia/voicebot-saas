<?php
if (!defined('ABSPATH')) exit;

class Sambla_Product_Sync {
    public function __construct() {
        // WooCommerce product hooks (auto-push on change)
        add_action('woocommerce_new_product', [$this, 'sync_single_product']);
        add_action('woocommerce_update_product', [$this, 'sync_single_product']);
        add_action('woocommerce_delete_product', [$this, 'delete_product']);
        add_action('woocommerce_trash_product', [$this, 'delete_product']);

        // WordPress page/post hooks (auto-push on change)
        add_action('save_post_page', [$this, 'sync_single_page'], 10, 2);
        add_action('save_post_post', [$this, 'sync_single_page'], 10, 2);
        add_action('wp_trash_post', [$this, 'delete_page']);
    }

    // ── Auto-push: single product ──

    public function sync_single_product($product_id) {
        if (!get_option('sambla_connected')) return;
        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') return;
        (new Sambla_Api_Client())->sync_products([$this->format_product($product)], home_url());
    }

    public function delete_product($product_id) {
        if (!get_option('sambla_connected')) return;
        (new Sambla_Api_Client())->sync_products([], home_url(), [$product_id]);
    }

    // ── Auto-push: single page/post ──

    public function sync_single_page($post_id, $post) {
        if (!get_option('sambla_connected')) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        if ($post->post_status !== 'publish') return;

        $content = wp_strip_all_tags($post->post_content);
        if (strlen($content) < 50) return;

        (new Sambla_Api_Client())->sync_pages([[
            'id' => $post_id,
            'title' => $post->post_title,
            'content' => $content,
            'url' => get_permalink($post_id),
            'type' => $post->post_type,
        ]], home_url());
    }

    public function delete_page($post_id) {
        if (!get_option('sambla_connected')) return;
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, ['page', 'post'])) return;
        (new Sambla_Api_Client())->sync_pages([], home_url(), [$post_id]);
    }

    // ── Full sync (manual, one-time) ──

    public function full_sync() {
        if (!get_option('sambla_connected')) return ['error' => 'Nu ești conectat la Sambla.'];

        $client = new Sambla_Api_Client();
        $synced_products = 0;
        $synced_pages = 0;

        // Sync WooCommerce products (if WooCommerce is active)
        if (class_exists('WooCommerce')) {
            $page = 1;
            do {
                $products = wc_get_products([
                    'status' => 'publish',
                    'limit' => 50,
                    'page' => $page,
                    'type' => ['simple', 'variable'],
                ]);
                if (empty($products)) break;

                $formatted = array_map([$this, 'format_product'], $products);
                $r = $client->sync_products($formatted, home_url());
                if (isset($r['synced'])) $synced_products += $r['synced'];

                $page++;
            } while (count($products) === 50);
        }

        // Sync pages and posts
        $post_types = ['page', 'post'];
        foreach ($post_types as $type) {
            $page = 1;
            do {
                $posts = get_posts([
                    'post_type' => $type,
                    'post_status' => 'publish',
                    'posts_per_page' => 50,
                    'paged' => $page,
                ]);
                if (empty($posts)) break;

                $formatted = [];
                foreach ($posts as $post) {
                    $content = wp_strip_all_tags($post->post_content);
                    if (strlen($content) < 50) continue;
                    $formatted[] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'content' => $content,
                        'url' => get_permalink($post->ID),
                        'type' => $type,
                    ];
                }

                if (!empty($formatted)) {
                    $r = $client->sync_pages($formatted, home_url());
                    if (isset($r['synced'])) $synced_pages += $r['synced'];
                }

                $page++;
            } while (count($posts) === 50);
        }

        update_option('sambla_last_sync', current_time('mysql'));
        return [
            'synced' => $synced_products + $synced_pages,
            'products' => $synced_products,
            'pages' => $synced_pages,
        ];
    }

    private function format_product($product) {
        $image_id = $product->get_image_id();
        $cats = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);

        $attrs = [];
        foreach ($product->get_attributes() as $attr) {
            if (is_object($attr)) {
                $name = wc_attribute_label($attr->get_name());
                $values = $attr->is_taxonomy()
                    ? wp_get_post_terms($product->get_id(), $attr->get_name(), ['fields' => 'names'])
                    : $attr->get_options();
                $attrs[$name] = is_array($values) ? $values : [$values];
            }
        }

        return [
            'wc_product_id' => $product->get_id(),
            'name' => $product->get_name(),
            'short_description' => wp_strip_all_tags($product->get_short_description()),
            'description' => wp_strip_all_tags($product->get_description()),
            'price' => (float) $product->get_price(),
            'regular_price' => (float) $product->get_regular_price(),
            'sale_price' => $product->get_sale_price() ? (float) $product->get_sale_price() : null,
            'currency' => get_woocommerce_currency(),
            'sku' => $product->get_sku(),
            'stock_status' => $product->get_stock_status(),
            'image_url' => $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '',
            'categories' => is_array($cats) ? $cats : [],
            'attributes' => $attrs,
            'permalink' => $product->get_permalink(),
        ];
    }
}
