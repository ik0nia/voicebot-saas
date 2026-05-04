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

        // Set flag + reason. Stop the bot immediately — the next inbound
        // message must reach the operator queue without bot interleaving.
        // Clearing assignee_bot_id is the canonical signal that the bot
        // is "off duty" for this conversation; the chat pipeline checks
        // needs_human + assignee_user_id and short-circuits AI generation.
        $metadata = $conv->metadata ?? [];
        $metadata['needs_human'] = true;
        $metadata['escalated_at'] = now()->toIso8601String();
        $metadata['escalation_reason'] = $validated['reason'] ?? 'visitor_request';
        $conv->metadata = $metadata;
        $conv->assignee_bot_id = null;
        $conv->save();

        // Insert a system message so the visitor immediately sees that
        // their request was received — silence after pressing "talk to
        // human" feels broken, even when the push went out 200ms ago.
        \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'direction' => 'outbound',
            'content' => 'Am chemat un coleg, ajunge în câteva momente.',
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

        // Email fallback: operatori cu tab-ul închis pică pe mail. Queued
        // (Notification implements ShouldQueue) ca să nu blocăm răspunsul
        // HTTP cu un SMTP roundtrip.
        $emailedCount = 0;
        try {
            $operators = \App\Models\User::where('tenant_id', $conv->tenant_id)->get();
            foreach ($operators as $op) {
                $op->notify(new \App\Notifications\OperatorEscalationNotification(
                    $conv,
                    $metadata['escalation_reason'],
                ));
                $emailedCount++;
            }
        } catch (\Throwable $e) {
            \Log::warning('Escalation email queue failed', [
                'conversation_id' => $conv->id,
                'error' => $e->getMessage(),
            ]);
        }

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
