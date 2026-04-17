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
     * OpenAI function-calling manifest for hospitality bots.
     * Mirrors the BookingEngine shape — runtime actions live in
     * app/Actions/Hospitality + app/Services/Hospitality, wired
     * onto ToolRegistry by HospitalityServiceProvider. Returned
     * only when the merging call-site in ChatbotApiController
     * detects engine_type === 'hospitality'.
     */
    public function chatTools(Bot $bot): array
    {
        return [
            [
                'type' => 'function',
                'name' => 'check_table_availability',
                'description' => 'Verifică ce mese sunt libere într-un interval orar, opțional filtrate pe zonă (terasă / salon) sau număr persoane.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'starts_at'  => ['type' => 'string', 'description' => 'ISO 8601, ex 2026-04-18T19:30:00'],
                        'ends_at'    => ['type' => 'string', 'description' => 'ISO 8601, ex 2026-04-18T21:30:00'],
                        'party_size' => ['type' => 'integer', 'description' => 'Număr persoane (minim capacitate masă)'],
                        'zone'       => ['type' => 'string',  'description' => 'ex "terasa", "salon_1"'],
                    ],
                    'required' => ['starts_at', 'ends_at'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'reserve_table',
                'description' => 'Rezervă o masă returnată de check_table_availability. Confirmă cu clientul mesa + numele înainte.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'resource_id'    => ['type' => 'integer'],
                        'starts_at'      => ['type' => 'string'],
                        'ends_at'        => ['type' => 'string'],
                        'customer_name'  => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'party_size'     => ['type' => 'integer'],
                        'occasion'       => ['type' => 'string', 'description' => 'aniversare / business / etc.'],
                    ],
                    'required' => ['resource_id','starts_at','ends_at','customer_name'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'check_room_availability',
                'description' => 'Verifică ce camere sunt libere pentru un interval de date (check-in → check-out).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'starts_at'  => ['type' => 'string', 'description' => 'Data check-in, ISO 8601'],
                        'ends_at'    => ['type' => 'string', 'description' => 'Data check-out, ISO 8601'],
                        'party_size' => ['type' => 'integer'],
                    ],
                    'required' => ['starts_at', 'ends_at'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'reserve_room',
                'description' => 'Rezervă o cameră returnată de check_room_availability.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'resource_id'    => ['type' => 'integer'],
                        'starts_at'      => ['type' => 'string'],
                        'ends_at'        => ['type' => 'string'],
                        'customer_name'  => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'customer_email' => ['type' => 'string'],
                        'party_size'     => ['type' => 'integer'],
                    ],
                    'required' => ['resource_id','starts_at','ends_at','customer_name'],
                ],
            ],
        ];
    }
}
