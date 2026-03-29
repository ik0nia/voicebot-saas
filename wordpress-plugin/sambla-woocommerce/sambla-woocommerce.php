<?php
/**
 * Plugin Name: Sambla AI Chat for WooCommerce
 * Plugin URI: https://sambla.ro
 * Description: Adaugă un chatbot AI inteligent pe magazinul tău WooCommerce. Chatbot-ul cunoaște produsele tale și poate recomanda clienților.
 * Version: 2.0.0
 * Author: Sambla
 * Author URI: https://sambla.ro
 * License: GPL v2 or later
 * Text Domain: sambla-woocommerce
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) exit;

define('SAMBLA_VERSION', '2.0.0');
define('SAMBLA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAMBLA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAMBLA_API_BASE', 'https://sambla.ro');

require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-api-client.php';
require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-admin.php';
require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-product-sync.php';
require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-widget.php';
require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-ajax.php';
require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-cron.php';
require_once SAMBLA_PLUGIN_DIR . 'includes/class-sambla-updater.php';

class Sambla_WooCommerce {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p><strong>Sambla AI Chat</strong> necesită WooCommerce pentru a funcționa.</p></div>';
            });
            return;
        }

        new Sambla_Admin();
        new Sambla_Product_Sync();
        new Sambla_Widget();
        new Sambla_Ajax();
        new Sambla_Cron();
        new Sambla_Updater();
    }

    public function activate() {
        add_option('sambla_api_key', '');
        add_option('sambla_connected', false);
        add_option('sambla_channel_id', '');
        add_option('sambla_bot_id', '');
        add_option('sambla_connector_id', '');
        add_option('sambla_widget_config', [
            'color' => '#991b1b',
            'icon_url' => '',
            'position' => 'bottom-right',
            'greeting' => 'Bună! Cu ce te pot ajuta?',
            'bot_name' => get_bloginfo('name') . ' - Asistent',
        ]);
        add_option('sambla_last_sync', '');

        if (!wp_next_scheduled('sambla_product_sync_cron')) {
            wp_schedule_event(time(), 'twicedaily', 'sambla_product_sync_cron');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('sambla_product_sync_cron');
    }
}

Sambla_WooCommerce::instance();
