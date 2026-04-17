<?php

namespace App\Engines;

use App\Models\Bot;

/**
 * Reservations over a finite resource pool (mese, camere, săli).
 * Distinct from Booking (staff-based) because capacity is managed
 * at resource level, not staff availability.
 *
 * Iteration 8: schema landed; runtime deferred. chatTools()
 * returns [] deliberately — the actions (check_room_availability,
 * reserve_table/room, create_payment_link) ship together with
 * the HospitalityToolDispatcher in a future iteration. Keeping
 * the LLM tool list empty until then stops it hallucinating calls
 * to endpoints that don't exist yet.
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

    /**
     * Placeholder — intentionally empty until the Hospitality
     * runtime dispatcher ships. Niche preview still shows the
     * planned tool names via config/niches.php so operators see
     * what's coming without the LLM invoking undefined endpoints.
     */
    public function chatTools(Bot $bot): array
    {
        return [];
    }
}
