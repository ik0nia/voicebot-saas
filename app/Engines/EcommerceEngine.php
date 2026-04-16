<?php

namespace App\Engines;

class EcommerceEngine extends BaseBotEngine
{
    public function type(): string { return 'ecommerce'; }
    public function displayName(): string { return 'Magazin online'; }

    protected function defaultCapabilities(): array
    {
        return ['product_catalog', 'cart_handoff', 'order_status'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['orders_influenced_today', 'carts_abandoned', 'avg_order_value'];
    }
}
