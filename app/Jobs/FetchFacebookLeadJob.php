<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetch detaliile unui Facebook Lead Ad prin Graph API + creează Lead în DB.
 * Pornit din FacebookWebhookController.handleLeadgenChange. Async pentru
 * că Graph API e ocazional lent (1-3s per call).
 *
 * Folosește page_access_token-ul canalului FB activ pentru pageId.
 */
class FetchFacebookLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $leadgenId,
        public readonly string $pageId,
        public readonly string $formId,
    ) {}

    public function handle(): void
    {
        // Găsim canalul FB pentru page → tenant + access_token.
        $channel = Channel::withoutGlobalScopes()
            ->where('type', Channel::TYPE_FACEBOOK_MESSENGER)
            ->where('external_id', $this->pageId)
            ->where('is_active', true)
            ->first();
        if (!$channel) {
            Log::info('FetchFbLead: no channel for page', ['page_id' => $this->pageId]);
            return;
        }

        $token = $channel->config['page_access_token'] ?? null;
        if (!$token) {
            Log::warning('FetchFbLead: no page_access_token', ['channel_id' => $channel->id]);
            return;
        }

        $response = Http::timeout(8)
            ->get("https://graph.facebook.com/v21.0/{$this->leadgenId}", [
                'access_token' => $token,
                'fields' => 'id,created_time,field_data,form_id,ad_id,campaign_id',
            ]);

        if (!$response->successful()) {
            Log::warning('FetchFbLead: Graph fetch failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);
            return;
        }

        $data = $response->json();
        $contacts = $this->extractContact($data['field_data'] ?? []);

        Lead::create([
            'tenant_id' => $channel->tenant_id,
            'bot_id' => $channel->bot_id,
            'name' => $contacts['name'] ?? 'Lead Facebook Ads',
            'email' => $contacts['email'] ?? null,
            'phone' => $contacts['phone'] ?? null,
            'qualification_score' => 65, // pre-qualified de FB Lead Form
            'capture_source' => 'facebook_lead_ads',
            'capture_reason' => 'fb_leadgen_webhook',
            'gdpr_consent' => true, // FB Lead Forms include consent built-in
            'metadata' => [
                'leadgen_id' => $this->leadgenId,
                'form_id' => $this->formId,
                'page_id' => $this->pageId,
                'ad_id' => $data['ad_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'fb_created_time' => $data['created_time'] ?? null,
            ],
        ]);
        Log::info('FetchFbLead: created', ['leadgen_id' => $this->leadgenId]);
    }

    /**
     * @param array<int, array<string, mixed>> $fieldData
     * @return array{name?: string, email?: string, phone?: string}
     */
    private function extractContact(array $fieldData): array
    {
        $out = [];
        foreach ($fieldData as $field) {
            $name = mb_strtolower((string) ($field['name'] ?? ''));
            $values = $field['values'] ?? [];
            $value = is_array($values) ? trim((string) ($values[0] ?? '')) : '';
            if ($value === '') continue;

            if (str_contains($name, 'email')) $out['email'] = $value;
            elseif (str_contains($name, 'phone')) $out['phone'] = $value;
            elseif (str_contains($name, 'full_name') || str_contains($name, 'first_name') || str_contains($name, 'last_name') || $name === 'name') {
                $out['name'] = isset($out['name']) ? $out['name'] . ' ' . $value : $value;
            }
        }
        return $out;
    }
}
