<?php

namespace App\Providers;

use App\Services\Hospitality\HospitalityToolDispatcher;
use App\Services\ToolRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Extends ToolRegistry container binding so hospitality tool
 * handlers are registered on every resolution idempotently.
 * Mirrors BookingServiceProvider to stay uniform.
 */
class HospitalityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(ToolRegistry::class, function (ToolRegistry $registry, $app) {
            $d = $app->make(HospitalityToolDispatcher::class);

            $registry->register('check_table_availability', [
                'description' => 'Verifică mese libere la un restaurant pentru un interval de timp.',
                'parameters'  => [
                    'starts_at'  => ['type' => 'string', 'required' => true],
                    'ends_at'    => ['type' => 'string', 'required' => true],
                    'party_size' => ['type' => 'integer'],
                    'zone'       => ['type' => 'string'],
                ],
                'handler' => fn (int $botId, array $params) => $d->checkTableAvailability($botId, $params),
            ]);

            $registry->register('reserve_table', [
                'description' => 'Rezervă o masă identificată prin resource_id.',
                'parameters'  => [
                    'resource_id'    => ['type' => 'integer', 'required' => true],
                    'starts_at'      => ['type' => 'string',  'required' => true],
                    'ends_at'        => ['type' => 'string',  'required' => true],
                    'customer_name'  => ['type' => 'string',  'required' => true],
                    'customer_phone' => ['type' => 'string'],
                    'party_size'     => ['type' => 'integer'],
                    'occasion'       => ['type' => 'string'],
                ],
                'handler' => fn (int $botId, array $params) => $d->reserveTable($botId, $params),
            ]);

            $registry->register('check_room_availability', [
                'description' => 'Verifică camere libere la un hotel/pensiune pentru un interval de date.',
                'parameters'  => [
                    'starts_at'  => ['type' => 'string', 'required' => true],
                    'ends_at'    => ['type' => 'string', 'required' => true],
                    'party_size' => ['type' => 'integer'],
                ],
                'handler' => fn (int $botId, array $params) => $d->checkRoomAvailability($botId, $params),
            ]);

            $registry->register('reserve_room', [
                'description' => 'Rezervă o cameră identificată prin resource_id.',
                'parameters'  => [
                    'resource_id'    => ['type' => 'integer', 'required' => true],
                    'starts_at'      => ['type' => 'string',  'required' => true],
                    'ends_at'        => ['type' => 'string',  'required' => true],
                    'customer_name'  => ['type' => 'string',  'required' => true],
                    'customer_phone' => ['type' => 'string'],
                    'customer_email' => ['type' => 'string'],
                    'party_size'     => ['type' => 'integer'],
                ],
                'handler' => fn (int $botId, array $params) => $d->reserveRoom($botId, $params),
            ]);

            return $registry;
        });
    }
}
