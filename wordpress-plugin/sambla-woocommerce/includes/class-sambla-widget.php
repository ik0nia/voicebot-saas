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

        echo '<script src="' . esc_url(SAMBLA_API_BASE . '/widget/sambla-chat.js?v=' . SAMBLA_VERSION) . '" ' . $attrs . ' async defer></script>';
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
