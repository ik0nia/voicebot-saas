<?php
if (!defined('ABSPATH')) exit;

class Sambla_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_sambla_connect', [$this, 'ajax_connect']);
        add_action('wp_ajax_sambla_disconnect', [$this, 'ajax_disconnect']);
        add_action('wp_ajax_sambla_sync_now', [$this, 'ajax_sync_now']);
        add_action('wp_ajax_sambla_save_settings', [$this, 'ajax_save_settings']);
    }

    public function add_menu() {
        add_menu_page(
            'Sambla AI Chat',
            'Sambla AI',
            'manage_options',
            'sambla-settings',
            [$this, 'render_settings_page'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>'),
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_sambla-settings') return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();
        wp_enqueue_style('sambla-admin', SAMBLA_PLUGIN_URL . 'admin/css/admin-style.css', [], SAMBLA_VERSION);
        wp_enqueue_script('sambla-admin', SAMBLA_PLUGIN_URL . 'admin/js/admin-script.js', ['jquery', 'wp-color-picker'], SAMBLA_VERSION, true);
        wp_localize_script('sambla-admin', 'samblaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sambla_admin'),
        ]);
    }

    public function render_settings_page() {
        $connected = get_option('sambla_connected', false);
        $config = get_option('sambla_widget_config', []);
        $last_sync = get_option('sambla_last_sync', '');
        $api_key = get_option('sambla_api_key', '');
        include SAMBLA_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function ajax_connect() {
        check_ajax_referer('sambla_admin', 'nonce');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        if (empty($api_key)) wp_send_json_error(['message' => 'API Key este obligatoriu.']);

        // Save WooCommerce credentials if provided
        $wc_key = sanitize_text_field($_POST['wc_key'] ?? '');
        $wc_secret = sanitize_text_field($_POST['wc_secret'] ?? '');
        if ($wc_key) update_option('sambla_wc_consumer_key', $wc_key);
        if ($wc_secret) update_option('sambla_wc_consumer_secret', $wc_secret);

        update_option('sambla_api_key', $api_key);
        $result = (new Sambla_Api_Client($api_key))->connect(home_url(), get_bloginfo('name'));

        if (isset($result['error'])) {
            update_option('sambla_connected', false);
            wp_send_json_error(['message' => $result['error']]);
        }

        update_option('sambla_connected', true);
        update_option('sambla_channel_id', $result['channel_id'] ?? '');
        update_option('sambla_bot_id', $result['bot_id'] ?? '');
        update_option('sambla_connector_id', $result['connector_id'] ?? '');
        if (isset($result['widget_config'])) {
            update_option('sambla_widget_config', array_merge(get_option('sambla_widget_config', []), $result['widget_config']));
        }
        wp_send_json_success(['message' => 'Conectat cu succes!']);
    }

    public function ajax_disconnect() {
        check_ajax_referer('sambla_admin', 'nonce');
        (new Sambla_Api_Client())->disconnect();
        update_option('sambla_connected', false);
        update_option('sambla_channel_id', '');
        update_option('sambla_bot_id', '');
        update_option('sambla_connector_id', '');
        wp_send_json_success(['message' => 'Deconectat.']);
    }

    public function ajax_sync_now() {
        check_ajax_referer('sambla_admin', 'nonce');

        // Cooldown: 5 minutes between full syncs
        $last = get_option('sambla_last_sync', '');
        if ($last && (time() - strtotime($last)) < 300) {
            $remaining = 300 - (time() - strtotime($last));
            $min = ceil($remaining / 60);
            wp_send_json_error(['message' => "Sincronizarea a fost făcută recent. Încearcă din nou în {$min} minut" . ($min > 1 ? 'e' : '') . '.' ]);
            return;
        }

        $result = (new Sambla_Product_Sync())->full_sync();
        if (isset($result['error'])) wp_send_json_error(['message' => $result['error']]);
        $products = $result['products'] ?? 0;
        $pages = $result['pages'] ?? 0;
        $msg = "{$products} produse și {$pages} pagini sincronizate.";
        wp_send_json_success(['message' => $msg, 'data' => $result]);
    }

    public function ajax_save_settings() {
        check_ajax_referer('sambla_admin', 'nonce');
        $config = [
            'color' => sanitize_hex_color($_POST['color'] ?? '#991b1b'),
            'icon_url' => esc_url_raw($_POST['icon_url'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? 'bottom-right'),
            'greeting' => sanitize_textarea_field($_POST['greeting'] ?? ''),
            'bot_name' => sanitize_text_field($_POST['bot_name'] ?? ''),
        ];
        update_option('sambla_widget_config', $config);
        (new Sambla_Api_Client())->update_widget_config($config);
        wp_send_json_success(['message' => 'Setări salvate!']);
    }
}
