<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Operator console — pagină PWA-friendly pentru operatori care preiau
 * conversații live de la bot.
 *
 * Endpoints:
 *   GET  /dashboard/operator                — UI principal (lista + detail)
 *   GET  /dashboard/operator/feed           — JSON cu conversații active
 *                                             (polled la 5s; viitor: Reverb)
 *   POST /dashboard/operator/{conv}/take    — operator preia conv
 *   POST /dashboard/operator/{conv}/release — handback la bot
 *   POST /dashboard/operator/{conv}/reply   — trimite mesaj ca operator
 *   POST /dashboard/operator/push/subscribe — înregistrează endpoint Web Push
 *   POST /dashboard/operator/push/test      — trimite o notificare de test
 */
class OperatorConsoleController extends Controller
{
    public function show()
    {
        return view('dashboard.operator.show', [
            'vapidPublicKey' => config('services.vapid.public'),
            'currentUser' => auth()->user(),
        ]);
    }

    /**
     * JSON feed pentru operator — conversațiile active din ultimele 24h
     * + ce așteaptă răspuns + ce sunt asignate operatorului curent.
     */
    public function feed(Request $request): JsonResponse
    {
        $tenant = auth()->user()->tenant;
        $userId = auth()->id();

        if (!$tenant) {
            return response()->json(['conversations' => [], 'no_tenant' => true]);
        }

        $cutoff = now()->subDay();

        $rows = Conversation::with(['bot:id,name', 'channel:id,type'])
            ->where('last_activity_at', '>=', $cutoff)
            ->orderByDesc('last_activity_at')
            ->limit(50)
            ->get(['id', 'bot_id', 'channel_id', 'contact_name', 'contact_identifier',
                   'messages_count', 'lead_score', 'primary_intent', 'status',
                   'last_activity_at', 'assignee_user_id', 'metadata']);

        $payload = $rows->map(function (Conversation $c) use ($userId) {
            $isMine = (int) $c->assignee_user_id === (int) $userId;
            $isUnassigned = empty($c->assignee_user_id);
            $needsHuman = (bool) ($c->metadata['needs_human'] ?? false);
            $autoTags = $c->metadata['auto_tags'] ?? null;

            return [
                'id' => $c->id,
                'bot_name' => $c->bot?->name ?? '—',
                'channel_type' => $c->channel?->type ?? 'unknown',
                'contact' => $c->contact_name ?: ($c->contact_identifier ?: 'Vizitator anonim'),
                'messages_count' => $c->messages_count ?? 0,
                'lead_score' => $c->lead_score,
                'intent' => $c->primary_intent,
                'status' => $c->status,
                'last_activity' => $c->last_activity_at?->toIso8601String(),
                'last_activity_relative' => $c->last_activity_at?->diffForHumans(),
                'is_mine' => $isMine,
                'is_unassigned' => $isUnassigned,
                'needs_human' => $needsHuman,
                'urgency' => $autoTags['urgency'] ?? null,
                'sentiment' => $autoTags['sentiment'] ?? null,
            ];
        })->all();

        return response()->json([
            'conversations' => $payload,
            'now' => now()->toIso8601String(),
            'counters' => [
                'mine' => collect($payload)->where('is_mine', true)->count(),
                'unassigned' => collect($payload)->where('is_unassigned', true)->count(),
                'needs_human' => collect($payload)->where('needs_human', true)->count(),
            ],
        ]);
    }

    /**
     * Mesajele unei conversații pentru panoul detail.
     */
    public function messages(Conversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->limit(100)
            ->get(['id', 'direction', 'content', 'created_at', 'sent_at', 'metadata'])
            ->map(function ($m) {
                $meta = $m->metadata ?? [];
                $direction = $m->direction ?? 'inbound';
                $senderType = $meta['sender_type'] ?? null;

                // Whitelist what we expose to the UI: anything operators
                // need to see exactly the surface the visitor saw, but
                // nothing AI-debug-only (intents/pipelines stay internal).
                return [
                    'id' => $m->id,
                    'role' => $direction === 'inbound' ? 'user' : ($senderType === 'operator' ? 'operator' : 'bot'),
                    'content' => $m->content,
                    'at' => $m->created_at->toIso8601String(),
                    'at_relative' => $m->created_at->diffForHumans(),
                    'products' => $meta['products'] ?? null,
                    'quick_replies' => $meta['quick_replies'] ?? null,
                    'page_context' => $meta['page_context'] ?? null,
                    'operator_name' => $meta['operator_name'] ?? null,
                ];
            });

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'contact' => $conversation->contact_name ?: ($conversation->contact_identifier ?: 'Vizitator'),
                'bot_name' => $conversation->bot?->name,
                'is_mine' => (int) $conversation->assignee_user_id === (int) auth()->id(),
                'assignee_user_id' => $conversation->assignee_user_id,
                'status' => $conversation->status,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Operator preia conversația de la bot.
     */
    public function take(Conversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $conversation->update([
            'assignee_user_id' => auth()->id(),
            'assigned_at' => now(),
        ]);
        $this->recordAccess($conversation);

        return response()->json(['ok' => true, 'assignee_user_id' => auth()->id()]);
    }

    /**
     * Operator trimite back conversația la bot.
     */
    public function release(Conversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $conversation->update([
            'assignee_user_id' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Operator trimite un mesaj în conversație.
     * Folosește pipeline-ul existent de send (channel-specific) prin direct
     * insert + canal de outbound dispatch.
     */
    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'content' => 'required|string|max:4000',
        ]);

        // Insert direct ca outbound (operator) — nu trecem prin LLM
        $msg = $conversation->messages()->create([
            'direction' => 'outbound',
            'content' => $validated['content'],
            'content_type' => 'text',
            'metadata' => [
                'sender_type' => 'operator',
                'operator_user_id' => auth()->id(),
                'operator_name' => auth()->user()->name,
            ],
            'sent_at' => now(),
        ]);

        $conversation->update([
            'messages_count' => $conversation->messages_count + 1,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => $msg->id,
                'role' => 'bot',
                'content' => $msg->content,
                'at' => $msg->created_at->toIso8601String(),
                'is_operator' => true,
            ],
        ]);
    }

    /**
     * Search istoric mesaje peste toate conversațiile tenantului. ILIKE pe
     * content cu paginare. Util pentru operator care caută context vechi.
     */
    public function searchMessages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:200',
        ]);
        $needle = '%' . $validated['q'] . '%';

        $rows = \App\Models\Message::query()
            ->whereHas('conversation', fn($q) => $q
                ->where('tenant_id', auth()->user()->tenant_id))
            ->where('content', 'ilike', $needle)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'conversation_id', 'direction', 'content', 'created_at'])
            ->map(fn($m) => [
                'id' => $m->id,
                'conversation_id' => $m->conversation_id,
                'direction' => $m->direction,
                'snippet' => mb_substr((string) $m->content, 0, 220),
                'created_at' => optional($m->created_at)->toIso8601String(),
            ]);

        return response()->json(['results' => $rows]);
    }

    /**
     * Înregistrează acces operator pe conversație în metadata.access_log.
     * Lightweight audit fără tabel nou. Util pentru investigații GDPR ulterioare.
     * Cap 100 entries (FIFO) ca să nu balonăm metadata.
     */
    private function recordAccess(Conversation $conv): void
    {
        try {
            $meta = $conv->metadata ?? [];
            $log = is_array($meta['access_log'] ?? null) ? $meta['access_log'] : [];
            $log[] = [
                'user_id' => auth()->id(),
                'at' => now()->toIso8601String(),
                'ip' => request()->ip(),
            ];
            if (count($log) > 100) {
                $log = array_slice($log, -100);
            }
            $meta['access_log'] = $log;
            $conv->update(['metadata' => $meta]);
        } catch (\Throwable $e) {
            \Log::debug('recordAccess failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Închide manual o conversație (operator decide că s-a terminat).
     * Setează status='closed', închide needs_human, clearează bot assignee.
     */
    public function closeConversation(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $meta = $conversation->metadata ?? [];
        $meta['closed_by_operator_id'] = auth()->id();
        $meta['closed_at'] = now()->toIso8601String();
        unset($meta['needs_human']);

        $conversation->update([
            'status' => 'closed',
            'metadata' => $meta,
            'ended_at' => now(),
        ]);

        return response()->json(['ok' => true, 'status' => 'closed']);
    }

    /**
     * Reassign conversație la alt operator din echipa tenantului.
     * Util pentru transfer intern fără a pune conversația înapoi în coadă.
     */
    public function reassign(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate([
            'assignee_user_id' => 'required|integer|exists:users,id',
        ]);
        // Verifică același tenant — security guard.
        $target = \App\Models\User::find($validated['assignee_user_id']);
        if (!$target || $target->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'cross-tenant reassign refuzat'], 403);
        }
        $conversation->update([
            'assignee_user_id' => $target->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => auth()->id(),
        ]);
        return response()->json(['ok' => true, 'assignee' => ['id' => $target->id, 'name' => $target->name]]);
    }

    /**
     * Operator marchează mesajele citite pană la `up_to_message_id`.
     * Update messages.read_at + broadcast pentru widget.
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate(['up_to_message_id' => 'required|integer']);
        $upTo = $validated['up_to_message_id'];

        \App\Models\Message::where('conversation_id', $conversation->id)
            ->where('id', '<=', $upTo)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        try {
            \App\Events\MessageRead::dispatch($conversation, $upTo, 'operator');
        } catch (\Throwable $e) {
            \Log::debug('read broadcast failed', ['error' => $e->getMessage()]);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Broadcast typing indicator de la operator → widget vizitator.
     * Idempotent — debouncing happens client-side.
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate(['is_typing' => 'required|boolean']);
        try {
            \App\Events\TypingIndicator::dispatch($conversation, 'operator', $validated['is_typing']);
        } catch (\Throwable $e) {
            \Log::debug('typing broadcast failed', ['error' => $e->getMessage()]);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Returnează canned responses (snippet-uri) pentru tenantul curent.
     * UI-ul operator console le folosește ca dropdown rapid de inserare.
     */
    public function cannedResponses(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;
        if (!$tenant) {
            return response()->json(['responses' => []]);
        }
        return response()->json(['responses' => $tenant->cannedResponses()]);
    }

    /**
     * Adaugă o notă internă pe conversation (vizibilă doar pentru echipă).
     * Stocată în `metadata.operator_notes` ca array de {by, at, body}.
     * Max 50 note per conversație ca să nu balonăm metadata.
     */
    public function addNote(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $meta = $conversation->metadata ?? [];
        $notes = is_array($meta['operator_notes'] ?? null) ? $meta['operator_notes'] : [];
        if (count($notes) >= 50) {
            return response()->json(['error' => 'Limita de 50 note atinsă'], 422);
        }
        $notes[] = [
            'by' => auth()->user()->name ?: auth()->user()->email,
            'user_id' => auth()->id(),
            'at' => now()->toIso8601String(),
            'body' => trim($validated['body']),
        ];
        $meta['operator_notes'] = $notes;
        $conversation->update(['metadata' => $meta]);

        return response()->json([
            'ok' => true,
            'notes' => $notes,
        ]);
    }

    /**
     * Setează tag-uri pe conversație (lead/support/complaint/billing/etc.).
     * Stocate în `metadata.tags[]`. Tag-urile sunt free-form (lowercase).
     */
    public function setTags(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate([
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:32',
        ]);

        $tags = array_values(array_unique(array_filter(array_map(
            fn($t) => mb_strtolower(trim((string) $t), 'UTF-8'),
            $validated['tags'] ?? []
        ))));

        $meta = $conversation->metadata ?? [];
        $meta['tags'] = $tags;
        $conversation->update(['metadata' => $meta]);

        return response()->json(['ok' => true, 'tags' => $tags]);
    }

    // ─── Push subscription endpoints ─────────────────────────────────

    public function pushSubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:1000',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'label' => 'nullable|string|max:100',
        ]);

        $hash = hash('sha256', $validated['endpoint']);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => auth()->id(),
                'endpoint' => $validated['endpoint'],
                'p256dh' => $validated['keys']['p256dh'],
                'auth' => $validated['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'label' => $validated['label'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function pushTest(PushNotificationService $svc): JsonResponse
    {
        $sent = $svc->sendToUser(auth()->id(), [
            'title' => 'Sambla — test notificare',
            'body' => 'Notificările push funcționează ✓',
            'url' => '/dashboard/operator',
            'tag' => 'test',
            'icon' => '/images/logo-icon.png',
        ]);

        return response()->json(['ok' => true, 'sent_to_devices' => $sent]);
    }
}
