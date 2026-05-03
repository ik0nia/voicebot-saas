<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\WebhookEndpoint;

/**
 * Front-door pentru dispatch outbound webhooks.
 *
 * Caller-ul trimite un eveniment (ex. lead.created cu Lead $lead) — noi
 * rezolvăm tenant_id, găsim endpoint-urile active subscribed la eveniment
 * și dispatch-uim DeliverWebhook job pentru fiecare.
 *
 * Apelat din Observers / event listeners — niciodată sincron în request
 * (toate trec prin queue).
 */
class WebhookDispatcher
{
    /**
     * Dispatch un eveniment la toate endpoint-urile relevante.
     *
     * @param  string  $event       Event name canonic, ex. "lead.created"
     * @param  int     $tenantId    Tenant căruia îi aparține eventul
     * @param  array   $payload     Date despre subiect (will be JSON-encoded as `data` în payload)
     */
    public function dispatch(string $event, int $tenantId, array $payload): void
    {
        // Folosim withoutGlobalScopes ca să găsim endpoint-urile chiar
        // dacă caller-ul rulează fără sesiune auth (queue worker, observer).
        $endpoints = WebhookEndpoint::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($e) => in_array($event, $e->events ?? [], true));

        foreach ($endpoints as $endpoint) {
            DeliverWebhook::dispatch($endpoint->id, $event, $payload);
        }
    }
}
