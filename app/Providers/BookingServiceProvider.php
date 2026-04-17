<?php

namespace App\Providers;

use App\Services\Booking\BookingToolDispatcher;
use App\Services\ToolRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Registers booking tool handlers on the ToolRegistry via container
 * extend(), so every resolution of ToolRegistry (non-singleton in
 * this app) gets booking handlers added idempotently.
 *
 * The registration is global on purpose: ChatCompletionService's
 * handleToolCalls path resolves its own ToolRegistry instance, and
 * the LLM might invoke a booking tool only after the request flow
 * has moved past the controller. By extending the binding, all
 * future resolutions — current request, future request, queued job
 * — find the booking tools registered and routable.
 *
 * The LLM never SEES the tools unless ChatbotApiController adds
 * them to the outbound tool list via $bot->engine()->chatTools(),
 * which is gated on engine_type === 'booking'. So non-booking bots
 * are unaffected even though the handlers are technically present
 * in the registry.
 */
class BookingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(ToolRegistry::class, function (ToolRegistry $registry, $app) {
            $dispatcher = $app->make(BookingToolDispatcher::class);

            $registry->register('check_availability', [
                'description' => 'Găsește sloturi libere pentru un serviciu.',
                'parameters'  => [
                    'service_type_id' => ['type' => 'integer', 'required' => true],
                    'preferred_from'  => ['type' => 'string'],
                    'days_ahead'      => ['type' => 'integer'],
                ],
                'handler' => fn (int $botId, array $params) => $dispatcher->checkAvailability($botId, $params),
            ]);

            $registry->register('book_appointment', [
                'description' => 'Rezervă un slot returnat de check_availability.',
                'parameters'  => [
                    'service_type_id' => ['type' => 'integer', 'required' => true],
                    'staff_member_id' => ['type' => 'integer', 'required' => true],
                    'starts_at'       => ['type' => 'string',  'required' => true],
                    'customer_name'   => ['type' => 'string',  'required' => true],
                    'customer_phone'  => ['type' => 'string'],
                    'customer_email'  => ['type' => 'string'],
                    'notes'           => ['type' => 'string'],
                ],
                'handler' => fn (int $botId, array $params) => $dispatcher->bookAppointment($botId, $params),
            ]);

            $registry->register('list_services', [
                'description' => 'Catalog servicii disponibile pentru acest bot.',
                'parameters'  => [],
                'handler' => fn (int $botId, array $params) => $dispatcher->listServices($botId, $params),
            ]);

            return $registry;
        });
    }
}
