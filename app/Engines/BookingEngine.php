<?php

namespace App\Engines;

use App\Models\Bot;

class BookingEngine extends BaseBotEngine
{
    public function type(): string { return 'booking'; }
    public function displayName(): string { return 'Programări'; }

    protected function defaultCapabilities(): array
    {
        return ['appointments', 'staff_schedule', 'sms_reminders'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['bookings_today', 'noshow_rate', 'urgent_cases'];
    }

    /**
     * OpenAI function-calling tool schemas the LLM is allowed to
     * invoke during a conversation with a booking bot. Keys chosen
     * to match the niches.chat_tools array so we can later restrict
     * per-niche without changing the engine.
     *
     * Returned in the raw OpenAI chat-completions format that
     * ChatCompletionService already understands, so the merge in
     * ChatbotApiController is a one-liner.
     */
    public function chatTools(Bot $bot): array
    {
        return [
            [
                'type' => 'function',
                'name' => 'check_availability',
                'description' => 'Găsește sloturi libere pentru un serviciu. Întotdeauna apeleaz-o înainte de book_appointment când clientul cere o programare.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_type_id' => ['type' => 'integer', 'description' => 'ID-ul serviciului (list_services înainte)'],
                        'preferred_from'  => ['type' => 'string', 'description' => 'ISO 8601, de ex "2026-04-18T09:00:00". Omis = de acum.'],
                        'days_ahead'      => ['type' => 'integer', 'description' => 'Câte zile în avans să caute. Default 7.'],
                    ],
                    'required' => ['service_type_id'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'book_appointment',
                'description' => 'Rezervă un slot returnat de check_availability. Confirmă cu clientul numele, telefonul și slotul exact înainte de apelare.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_type_id' => ['type' => 'integer'],
                        'staff_member_id' => ['type' => 'integer'],
                        'starts_at'       => ['type' => 'string', 'description' => 'ISO 8601 dintr-un slot disponibil'],
                        'customer_name'   => ['type' => 'string'],
                        'customer_phone'  => ['type' => 'string'],
                        'customer_email'  => ['type' => 'string'],
                        'notes'           => ['type' => 'string', 'description' => 'Observații scurte: motiv, simptome etc.'],
                    ],
                    'required' => ['service_type_id', 'staff_member_id', 'starts_at', 'customer_name'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'list_services',
                'description' => 'Returnează catalogul de servicii disponibile pentru acest bot.',
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
        ];
    }
}
