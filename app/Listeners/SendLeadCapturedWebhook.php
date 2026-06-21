<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadCaptured;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * POST la webhook-ul configurat per tenant când un lead nou e capturat —
 * permite integrare CRM extern (Pipedrive, HubSpot, custom) fără cod custom.
 *
 * URL configurabil prin `tenant.settings.webhooks.lead_captured_url`.
 * Secret HMAC opțional: `tenant.settings.webhooks.lead_captured_secret`.
 */
class SendLeadCapturedWebhook implements ShouldQueue
{
    public function handle(LeadCaptured $event): void
    {
        $lead = $event->lead;
        $tenant = $lead->tenant;
        if (!$tenant) {
            return;
        }

        $url = $tenant->settings['webhooks']['lead_captured_url'] ?? null;
        if (!is_string($url) || trim($url) === '') {
            return;
        }
        if (!preg_match('#^https?://#i', $url)) {
            Log::warning('SendLeadCapturedWebhook: invalid URL scheme', ['tenant_id' => $tenant->id, 'url' => $url]);
            return;
        }

        $secret = $tenant->settings['webhooks']['lead_captured_secret'] ?? null;

        $payload = [
            'event' => 'lead.captured',
            'timestamp' => now()->toIso8601String(),
            'tenant_id' => $tenant->id,
            'source' => $event->source,
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'qualification_score' => $lead->qualification_score,
                'capture_source' => $lead->capture_source,
                'created_at' => optional($lead->created_at)->toIso8601String(),
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $headers = ['Content-Type' => 'application/json'];
        if (is_string($secret) && trim($secret) !== '') {
            $headers['X-Sambla-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $secret);
        }

        try {
            Http::timeout(8)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::warning('SendLeadCapturedWebhook: failed', [
                'tenant_id' => $tenant->id,
                'lead_id' => $lead->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
