<?php
if (!defined('ABSPATH')) exit;

class Sambla_Api_Client {
    private $api_key;
    private $base_url;

    public function __construct($api_key = null) {
        $this->api_key = $api_key ?: get_option('sambla_api_key', '');
        $this->base_url = SAMBLA_API_BASE;
    }

    public function connect($site_url, $site_name = '') {
        $data = [
            'site_url' => $site_url,
            'site_name' => $site_name ?: get_bloginfo('name'),
        ];

        // Send WooCommerce REST API credentials if available
        $wc_key = get_option('sambla_wc_consumer_key', '');
        $wc_secret = get_option('sambla_wc_consumer_secret', '');
        if ($wc_key && $wc_secret) {
            $data['wc_consumer_key'] = $wc_key;
            $data['wc_consumer_secret'] = $wc_secret;
        }

        return $this->post('/api/v1/integrations/connect', $data);
    }

    public function disconnect() {
        return $this->post('/api/v1/integrations/disconnect', [
            'connector_id' => get_option('sambla_connector_id', ''),
        ]);
    }

    public function sync_products($products, $site_url, $deleted_ids = []) {
        return $this->post('/api/v1/integrations/sync-products', [
            'products' => $products,
            'site_url' => $site_url,
            'deleted_ids' => $deleted_ids,
        ]);
    }

    public function sync_pages($pages, $site_url, $deleted_ids = []) {
        return $this->post('/api/v1/integrations/sync-pages', [
            'pages' => $pages,
            'site_url' => $site_url,
            'deleted_ids' => $deleted_ids,
        ]);
    }

    public function update_widget_config($config) {
        return $this->put('/api/v1/integrations/widget-config', array_merge(
            ['channel_id' => get_option('sambla_channel_id', '')],
            $config
        ));
    }

    public function get_status() {
        return $this->get('/api/v1/integrations/status');
    }

    private function post($endpoint, $data = []) { return $this->request('POST', $endpoint, $data); }
    private function put($endpoint, $data = []) { return $this->request('PUT', $endpoint, $data); }
    private function get($endpoint) { return $this->request('GET', $endpoint); }

    private function request($method, $endpoint, $data = null) {
        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if ($data && in_array($method, ['POST', 'PUT'])) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($this->base_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 400) {
            return ['error' => $body['error'] ?? $body['message'] ?? 'Request failed', 'code' => $code];
        }

        return $body;
    }
}
