<?php

namespace App\Engines;

/**
 * Mixed products + services (optica, beauty, service auto, cleaning).
 * Composes the Ecommerce + Booking feature sets.
 */
class HybridEngine extends BaseBotEngine
{
    public function type(): string { return 'hybrid'; }
    public function displayName(): string { return 'Produse + servicii'; }

    protected function defaultCapabilities(): array
    {
        return ['product_catalog', 'appointments', 'unified_cart'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['bookings_today', 'orders_influenced_today', 'avg_ticket'];
    }
}
