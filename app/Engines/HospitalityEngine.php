<?php

namespace App\Engines;

/**
 * Reservations over a finite resource pool (mese, camere, săli).
 * Distinct from Booking (staff-based) because capacity is managed
 * at resource level, not staff availability.
 */
class HospitalityEngine extends BaseBotEngine
{
    public function type(): string { return 'hospitality'; }
    public function displayName(): string { return 'Rezervări'; }

    protected function defaultCapabilities(): array
    {
        return ['reservations', 'resource_inventory', 'prepayment'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['reservations_today', 'occupancy_rate'];
    }
}
