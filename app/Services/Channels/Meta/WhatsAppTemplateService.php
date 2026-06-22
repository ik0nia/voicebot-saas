<?php

declare(strict_types=1);

namespace App\Services\Channels\Meta;

use App\Models\Channel;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Submit/sync/delete WhatsApp message templates against the Meta Graph API.
 *
 * Endpoints:
 *  POST /{waba_id}/message_templates  — submit new
 *  GET  /{waba_id}/message_templates  — list (used by sync())
 *  DELETE /{waba_id}/message_templates?name=… — delete by name (all langs)
 *
 * Status transitions are driven by the message_template_status_update
 * webhook field — handled by WhatsAppTemplateWebhookHandler. This service
 * is the side that initiates submission; webhook is the side that hears
 * back from Meta.
 */
class WhatsAppTemplateService
{
    // Bump 2026-06-22 audit: aliniat la v21.0 (v18 e aproape EOL).
    private const META_GRAPH = 'https://graph.facebook.com/v21.0';

    /**
     * Submit a DRAFT template to Meta. On success the template moves to
     * PENDING and gains a meta_template_id; final APPROVED/REJECTED state
     * arrives later via webhook (typically within minutes for UTILITY,
     * up to 24h for MARKETING during high-volume periods).
     *
     * @return array{success: bool, meta_template_id: ?string, error: ?string}
     */
    public function submit(WhatsAppTemplate $template): array
    {
        if (!$template->isDraft()) {
            return [
                'success' => false,
                'meta_template_id' => null,
                'error' => "Template {$template->id} is not in DRAFT status (current: {$template->status})",
            ];
        }

        $channel = $template->channel;
        if (!$channel || $channel->type !== Channel::TYPE_WHATSAPP) {
            return [
                'success' => false,
                'meta_template_id' => null,
                'error' => 'Template channel must be WhatsApp',
            ];
        }

        $wabaId = $channel->getCredential('waba_id');
        $token = $channel->getCredential('access_token');
        if (!$wabaId || !$token) {
            return [
                'success' => false,
                'meta_template_id' => null,
                'error' => 'Channel missing waba_id or access_token',
            ];
        }

        $payload = [
            'name' => $template->name,
            'category' => $template->category,
            'language' => $template->language,
            'components' => $template->components,
        ];

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->post(self::META_GRAPH . "/{$wabaId}/message_templates", $payload);

            if ($response->successful()) {
                $metaId = $response->json('id');
                $metaStatus = $response->json('status', WhatsAppTemplate::STATUS_PENDING);

                $template->update([
                    'meta_template_id' => $metaId,
                    'status' => $metaStatus,
                    'submitted_at' => now(),
                    'rejection_reason' => null,
                ]);

                return ['success' => true, 'meta_template_id' => $metaId, 'error' => null];
            }

            $code = $response->json('error.code');
            $msg = $response->json('error.message') ?? 'Meta API error';
            $sanitized = is_string($msg)
                ? preg_replace('/EAA[A-Za-z0-9_\-]{20,}/', 'EAA[REDACTED]', $msg)
                : 'Meta API error';
            $errorString = $code ? "[{$code}] {$sanitized}" : $sanitized;

            Log::warning('WhatsAppTemplateService: submit failed', [
                'template_id' => $template->id,
                'channel_id' => $channel->id,
                'meta_error_code' => $code,
                'meta_error_message' => mb_substr($sanitized, 0, 200),
            ]);

            return ['success' => false, 'meta_template_id' => null, 'error' => $errorString];
        } catch (\Throwable $e) {
            Log::error('WhatsAppTemplateService: submit request failed', [
                'template_id' => $template->id,
                'exception' => $e::class,
            ]);
            return ['success' => false, 'meta_template_id' => null, 'error' => $e::class];
        }
    }

    /**
     * Pull current Meta-side status for ALL templates on this channel and
     * reconcile local rows. Useful as a recovery path if a webhook was
     * missed (Meta does not retry failed message_template_status_update
     * delivery). Idempotent.
     */
    public function syncFromMeta(Channel $channel): array
    {
        $wabaId = $channel->getCredential('waba_id');
        $token = $channel->getCredential('access_token');
        if (!$wabaId || !$token) {
            return ['success' => false, 'synced' => 0, 'error' => 'Channel missing waba_id or access_token'];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->get(self::META_GRAPH . "/{$wabaId}/message_templates", [
                    'fields' => 'id,name,language,status,category,rejected_reason,components',
                    'limit' => 200,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'synced' => 0, 'error' => 'Meta returned HTTP ' . $response->status()];
            }

            $synced = 0;
            foreach ($response->json('data', []) as $remote) {
                $template = WhatsAppTemplate::query()
                    ->where('channel_id', $channel->id)
                    ->where('name', $remote['name'])
                    ->where('language', $remote['language'])
                    ->first();

                if (!$template) {
                    // Meta-side template we don't know about (created in
                    // Business Manager directly) — adopt it.
                    $template = WhatsAppTemplate::create([
                        'channel_id' => $channel->id,
                        'name' => $remote['name'],
                        'language' => $remote['language'],
                        'category' => $remote['category'],
                        'status' => $remote['status'],
                        'meta_template_id' => $remote['id'],
                        'components' => $remote['components'] ?? [],
                        'rejection_reason' => $remote['rejected_reason'] ?? null,
                        'submitted_at' => now(),
                        'approved_at' => $remote['status'] === WhatsAppTemplate::STATUS_APPROVED ? now() : null,
                    ]);
                    $synced++;
                    continue;
                }

                $patch = [
                    'meta_template_id' => $remote['id'],
                    'status' => $remote['status'],
                    'rejection_reason' => $remote['rejected_reason'] ?? null,
                ];
                if ($remote['status'] === WhatsAppTemplate::STATUS_APPROVED && !$template->approved_at) {
                    $patch['approved_at'] = now();
                }
                $template->update($patch);
                $synced++;
            }

            return ['success' => true, 'synced' => $synced, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('WhatsAppTemplateService: sync request failed', [
                'channel_id' => $channel->id,
                'exception' => $e::class,
            ]);
            return ['success' => false, 'synced' => 0, 'error' => $e::class];
        }
    }

    /**
     * Apply a status-update webhook event from Meta.
     *
     * @param array{message_template_id?: string, message_template_name?: string, message_template_language?: string, event?: string, reason?: string} $event
     */
    public function applyStatusUpdate(array $event): void
    {
        $metaId = $event['message_template_id'] ?? null;
        $name = $event['message_template_name'] ?? null;
        $language = $event['message_template_language'] ?? null;
        $eventType = $event['event'] ?? null; // APPROVED / REJECTED / PAUSED / DISABLED / etc.

        if (!$eventType) {
            return;
        }

        $query = WhatsAppTemplate::query();
        if ($metaId) {
            $query->where('meta_template_id', $metaId);
        } elseif ($name && $language) {
            $query->where('name', $name)->where('language', $language);
        } else {
            Log::warning('WhatsAppTemplateService: status update lacks template identifiers', [
                'event' => $event,
            ]);
            return;
        }

        $template = $query->first();
        if (!$template) {
            Log::info('WhatsAppTemplateService: status update for unknown template', [
                'meta_template_id' => $metaId,
                'name' => $name,
                'language' => $language,
            ]);
            return;
        }

        $patch = ['status' => $eventType];
        if ($eventType === WhatsAppTemplate::STATUS_REJECTED) {
            $patch['rejection_reason'] = $event['reason'] ?? null;
        }
        if ($eventType === WhatsAppTemplate::STATUS_APPROVED && !$template->approved_at) {
            $patch['approved_at'] = now();
            $patch['rejection_reason'] = null;
        }
        $template->update($patch);
    }
}
