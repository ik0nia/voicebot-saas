<?php
if (!defined('ABSPATH')) exit;

class Sambla_Updater {
    private $plugin_slug = 'sambla-woocommerce/sambla-woocommerce.php';
    private $update_url;

    public function __construct() {
        $this->update_url = SAMBLA_API_BASE . '/api/v1/plugin/update-check';
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
    }

    public function check_update($transient) {
        if (empty($transient->checked)) return $transient;

        $response = wp_remote_get($this->update_url . '?slug=sambla-woocommerce&version=' . SAMBLA_VERSION, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) return $transient;

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['new_version']) && version_compare(SAMBLA_VERSION, $data['new_version'], '<')) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug' => 'sambla-woocommerce',
                'plugin' => $this->plugin_slug,
                'new_version' => $data['new_version'],
                'url' => $data['url'] ?? 'https://sambla.ro',
                'package' => $data['package'] ?? '',
                'icons' => $data['icons'] ?? [],
                'banners' => $data['banners'] ?? [],
                'tested' => $data['tested'] ?? '6.5',
                'requires_php' => $data['requires_php'] ?? '7.4',
            ];
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== 'sambla-woocommerce') {
            return $result;
        }

        $response = wp_remote_get($this->update_url . '?slug=sambla-woocommerce&version=' . SAMBLA_VERSION . '&info=full', [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) return $result;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data)) return $result;

        return (object) [
            'name' => $data['name'] ?? 'Sambla AI Chat for WooCommerce',
            'slug' => 'sambla-woocommerce',
            'version' => $data['new_version'] ?? SAMBLA_VERSION,
            'author' => '<a href="https://sambla.ro">Sambla</a>',
            'homepage' => 'https://sambla.ro',
            'requires' => '5.8',
            'tested' => $data['tested'] ?? '6.5',
            'requires_php' => '7.4',
            'download_link' => $data['package'] ?? '',
            'sections' => [
                'description' => $data['description'] ?? 'Chatbot AI inteligent pentru WooCommerce.',
                'changelog' => $data['changelog'] ?? '',
            ],
        ];
    }
}
