<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Conversation;
use App\Models\Lead;
use App\Services\WebhookDispatcher;
use Illuminate\Database\Eloquent\Model;

/**
 * Single observer pentru toate evenimentele care trebuie să iasă pe
 * webhook-uri tenant. Înregistrat în AppServiceProvider.
 *
 * Mapping:
 *   Lead created            → "lead.created"
 *   CallbackRequest created → "callback.requested"
 *   Appointment created     → "appointment.created"
 *   Call updated (status=completed) → "call.ended"  (only on transition to completed)
 *   Conversation updated (status=closed) → "conversation.completed"
 *
 * Toate observer-urile dispatch-uiesc DeliverWebhook pe queue, nu sincron.
 */
class WebhookEventObserver
{
    public function __construct(private WebhookDispatcher $dispatcher) {}

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Lead => $this->dispatch('lead.created', $model, [
                'lead_id' => $model->id,
                'name' => $model->name,
                'email' => $model->email,
                'phone' => $model->phone,
                'source' => $model->source,
                'pipeline_stage' => $model->pipeline_stage,
                'bot_id' => $model->bot_id,
                'created_at' => $model->created_at?->toIso8601String(),
            ]),
            $model instanceof CallbackRequest => $this->dispatch('callback.requested', $model, [
                'callback_id' => $model->id,
                'name' => $model->name,
                'phone' => $model->phone,
                'preferred_window' => $model->preferred_window,
                'bot_id' => $model->bot_id,
                'created_at' => $model->created_at?->toIso8601String(),
            ]),
            $model instanceof Appointment => $this->dispatch('appointment.created', $model, [
                'appointment_id' => $model->id,
                'name' => $model->name,
                'phone' => $model->phone,
                'service' => $model->service,
                'starts_at' => $model->starts_at?->toIso8601String(),
                'bot_id' => $model->bot_id,
                'status' => $model->status,
            ]),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        // Call.ended firing — DOAR pe tranziție la status=completed/ended
        if ($model instanceof Call) {
            $original = $model->getOriginal('status');
            $new = $model->status;
            $isEndedNow = in_array($new, ['completed', 'ended', 'finished'], true)
                && !in_array($original, ['completed', 'ended', 'finished'], true);
            if ($isEndedNow) {
                $this->dispatch('call.ended', $model, [
                    'call_id' => $model->id,
                    'duration_seconds' => $model->duration_seconds,
                    'status' => $model->status,
                    'caller_number' => $model->caller_number,
                    'direction' => $model->direction,
                    'bot_id' => $model->bot_id,
                    'started_at' => $model->started_at?->toIso8601String(),
                    'ended_at' => $model->ended_at?->toIso8601String(),
                ]);
            }
        }

        // Conversation.completed — pe tranziție la status=closed
        if ($model instanceof Conversation) {
            $original = $model->getOriginal('status');
            if ($model->status === 'closed' && $original !== 'closed') {
                $this->dispatch('conversation.completed', $model, [
                    'conversation_id' => $model->id,
                    'contact_name' => $model->contact_name,
                    'contact_identifier' => $model->contact_identifier,
                    'messages_count' => $model->messages_count,
                    'lead_score' => $model->lead_score,
                    'primary_intent' => $model->primary_intent,
                    'channel_id' => $model->channel_id,
                    'bot_id' => $model->bot_id,
                ]);
            }
        }
    }

    private function dispatch(string $event, Model $model, array $payload): void
    {
        $tenantId = $model->tenant_id ?? null;
        if (!$tenantId) {
            return;
        }
        $this->dispatcher->dispatch($event, (int) $tenantId, $payload);
    }
}
