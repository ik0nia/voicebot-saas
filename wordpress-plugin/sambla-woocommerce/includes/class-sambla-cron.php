<?php
if (!defined('ABSPATH')) exit;

class Sambla_Cron {
    public function __construct() {
        add_action('sambla_product_sync_cron', [$this, 'run']);
    }

    public function run() {
        (new Sambla_Product_Sync())->full_sync();
    }
}
