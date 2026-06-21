<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Conversation;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public API: vizitatorul cere operator uman.
 * Apelat de widget când:
 *   - User dă click pe „👤 Vorbește cu un operator"
 *   - Sau scrie ceva de tip „vreau să vorbesc cu cineva real" (detect server-side)
 *
 * Flag-ează conversation.metadata['needs_human'] = true → apare în feed-ul
 * operator console + trimite Web Push la toți operatorii tenantului.
 *
 * Public route — verificare prin channel_id (nu cere auth tenant).
 */
class EscalationController extends Controller
{
    public function request(Request $request, int $channel, PushNotificationService $push): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
            'reason' => 'nullable|string|max:200',
        ]);

        // Throttle 5 escalări / minut / channel (anti-abuse)
        $key = "escalation:{$channel}:{$validated['session_id']}";
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['error' => 'Solicitare deja înregistrată'], 429);
        }
        RateLimiter::hit($key, 60);

        $ch = Channel::withoutGlobalScopes()->find($channel);
        if (!$ch || !$ch->is_active) {
            return response()->json(['error' => 'Canal indisponibil'], 404);
        }

        // Caută conversația activă pentru această sesiune
        $conv = Conversation::withoutGlobalScopes()
            ->where('channel_id', $channel)
            ->where(function ($q) use ($validated) {
                $q->where('external_conversation_id', $validated['session_id'])
                  ->orWhere('contact_identifier', $validated['session_id']);
            })
            ->orderByDesc('id')
            ->first();

        if (!$conv) {
            return response()->json(['error' => 'Conversație negăsită'], 404);
        }

        $metadata = $conv->metadata ?? [];

        // Dedup: dacă conv a fost deja escaladată în ultimele 24h și un
        // operator încă nu a preluat-o, doar acknowledge scurt fără push
        // duplicat. Altfel vizitatorul vede „Am chemat un coleg" de fiecare
        // dată când apasă butonul / revine în chat.
        $alreadyEscalatedRecently = false;
        if (!empty($metadata['needs_human']) && !empty($metadata['escalated_at'])) {
            try {
                $prev = \Carbon\Carbon::parse($metadata['escalated_at']);
                $alreadyEscalatedRecently = $prev->diffInHours(now()) < 24
                    && empty($conv->assignee_user_id);
            } catch (\Throwable $e) {
                $alreadyEscalatedRecently = false;
            }
        }

        if ($alreadyEscalatedRecently) {
            $bot = $conv->bot ?? null;
            $msg = $bot ? $bot->handoffMessages()['reminded']
                : 'Un coleg a fost deja notificat și revine cu informații cât mai curând.';
            \App\Models\Message::create([
                'conversation_id' => $conv->id,
                'direction' => 'outbound',
                'content' => $msg,
                'content_type' => 'text',
                'metadata' => ['sender_type' => 'system', 'system_event' => 'escalation_reminded'],
                'sent_at' => now(),
            ]);
            $conv->increment('messages_count');
            \Log::info('Escalation dedup (widget): reminder only', [
                'conversation_id' => $conv->id,
                'previously_escalated_at' => $metadata['escalated_at'] ?? null,
            ]);
            return response()->json(['ok' => true, 'deduped' => true]);
        }

        // Set flag + reason. Stop the bot immediately — the next inbound
        // message must reach the operator queue without bot interleaving.
        // Clearing assignee_bot_id is the canonical signal that the bot
        // is "off duty" for this conversation; the chat pipeline checks
        // needs_human + assignee_user_id and short-circuits AI generation.
        $metadata['needs_human'] = true;
        $metadata['escalated_at'] = now()->toIso8601String();
        $metadata['escalation_reason'] = $validated['reason'] ?? 'visitor_request';
        // Reset SLA flags so cron poate re-evalua escalarea nouă.
        unset($metadata['sla_warned'], $metadata['sla_fallback_sent']);
        $conv->metadata = $metadata;
        $conv->assignee_bot_id = null;
        $conv->save();

        // Insert a system message so the visitor immediately sees that
        // their request was received — silence after pressing "talk to
        // human" feels broken, even when the push went out 200ms ago.
        $botModel = $conv->bot ?? null;
        $ackMsg = $botModel ? $botModel->handoffMessages()['escalated']
            : 'Am chemat un coleg, ajunge în câteva momente.';
        \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'direction' => 'outbound',
            'content' => $ackMsg,
            'content_type' => 'text',
            'metadata' => ['sender_type' => 'system', 'system_event' => 'escalation_acknowledged'],
            'sent_at' => now(),
        ]);
        $conv->increment('messages_count');

        // Push la toți operatorii tenantului (excludem nimeni — toată echipa află)
        $sentTo = $push->sendToTenantUsers((int) $conv->tenant_id, [
            'title' => '🆘 Vizitator cere operator',
            'body' => ($conv->contact_name ?: 'Vizitator anonim') . ' din chat ' . ($ch->name ?: 'web') . ' vrea să vorbească cu un om.',
            'url' => '/dashboard/operator?focus=' . $conv->id,
            'tag' => 'escalation-' . $conv->id,
            'icon' => '/images/logo-icon.png',
            'requireInteraction' => true,
        ]);

        // Email-ul către operatori e dezactivat — push notification e
        // suficient. Userul nu vrea inbox spam la fiecare răspuns / fiecare
        // escalation. Se poate reactiva când avem un toggle per-tenant
        // pentru preferințe notificare.
        $emailedCount = 0;

        \Log::info('Conversation escalated to human', [
            'conversation_id' => $conv->id,
            'tenant_id' => $conv->tenant_id,
            'reason' => $metadata['escalation_reason'],
            'pushed_to_devices' => $sentTo,
            'emailed_operators' => $emailedCount,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Echipa a fost notificată. Cineva va prelua conversația în câteva momente.',
            'devices_notified' => $sentTo,
        ]);
    }
}
