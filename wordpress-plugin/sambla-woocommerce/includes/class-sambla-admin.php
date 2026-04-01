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
        add_action('wp_ajax_sambla_save_page_mapping', [$this, 'ajax_save_page_mapping']);
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

        // Fetch usage and conversations from Sambla API (cached 5 min)
        $usage = [];
        $recent_conversations = [];
        $bot_name = '';
        $plan_name = '';

        if ($connected) {
            $status = $this->get_cached_status();
            if ($status) {
                $usage = $status['usage'] ?? [];
                $recent_conversations = $status['recent_conversations'] ?? [];
                $bot_name = $status['bot_name'] ?? '';
                $plan_name = $status['plan'] ?? 'starter';
            }
        }

        // Get WordPress pages for page mapping
        $wp_pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
        $page_mapping = get_option('sambla_page_mapping', []);

        include SAMBLA_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Get status from Sambla API with 5-minute cache.
     */
    private function get_cached_status() {
        $cached = get_transient('sambla_dashboard_status');
        if ($cached !== false) return $cached;

        $client = new Sambla_Api_Client();
        $status = $client->get_status();

        if (isset($status['error'])) return null;

        set_transient('sambla_dashboard_status', $status, 300); // 5 min cache
        return $status;
    }

    public function ajax_connect() {
        check_ajax_referer('sambla_admin', 'nonce');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        if (empty($api_key)) wp_send_json_error(['message' => 'API Key este obligatoriu.']);

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
        delete_transient('sambla_dashboard_status');
        wp_send_json_success(['message' => 'Conectat cu succes!']);
    }

    public function ajax_disconnect() {
        check_ajax_referer('sambla_admin', 'nonce');
        (new Sambla_Api_Client())->disconnect();
        update_option('sambla_connected', false);
        update_option('sambla_channel_id', '');
        update_option('sambla_bot_id', '');
        update_option('sambla_connector_id', '');
        delete_transient('sambla_dashboard_status');
        wp_send_json_success(['message' => 'Deconectat.']);
    }

    public function ajax_sync_now() {
        check_ajax_referer('sambla_admin', 'nonce');

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
        delete_transient('sambla_dashboard_status');
        wp_send_json_success(['message' => $msg, 'data' => $result]);
    }

    public function ajax_save_settings() {
        check_ajax_referer('sambla_admin', 'nonce');
        $config = [
            'color' => sanitize_hex_color($_POST['color'] ?? '#991b1b'),
            'position' => sanitize_text_field($_POST['position'] ?? 'bottom-right'),
            'bot_name' => sanitize_text_field($_POST['bot_name'] ?? ''),
        ];
        // Greeting is managed from Sambla dashboard, not from WP plugin
        $existing = get_option('sambla_widget_config', []);
        $config = array_merge($existing, $config);
        update_option('sambla_widget_config', $config);
        (new Sambla_Api_Client())->update_widget_config($config);
        wp_send_json_success(['message' => 'Setări salvate!']);
    }

    /**
     * Save page mapping — standard business pages linked to Sambla knowledge base.
     */
    public function ajax_save_page_mapping() {
        check_ajax_referer('sambla_admin', 'nonce');

        $mapping = [];
        $page_types = ['contact', 'terms', 'delivery', 'returns', 'privacy', 'cookies', 'about', 'faq'];

        foreach ($page_types as $type) {
            $page_id = intval($_POST["page_{$type}"] ?? 0);
            if ($page_id > 0) {
                $mapping[$type] = $page_id;
            }
        }

        update_option('sambla_page_mapping', $mapping);

        // Sync mapped pages content to Sambla as knowledge
        $synced = 0;
        $client = new Sambla_Api_Client();
        $pages_data = [];

        foreach ($mapping as $type => $page_id) {
            $page = get_post($page_id);
            if (!$page) continue;

            $content = strip_tags(apply_filters('the_content', $page->post_content));
            if (strlen($content) < 20) continue;

            $pages_data[] = [
                'title' => $page->post_title,
                'content' => $content,
                'type' => $type,
                'url' => get_permalink($page_id),
            ];
            $synced++;
        }

        if (!empty($pages_data)) {
            $client->sync_pages($pages_data, home_url());
        }

        wp_send_json_success(['message' => "{$synced} pagini standard sincronizate cu baza de cunoștințe."]);
    }
}
