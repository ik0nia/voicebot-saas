<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

/**
 * Conversation replay — randa mesajele unei conversații cu timing real
 * (sau accelerate 4×) ca într-un video. Folosit pentru:
 *   - QA prin care un manager vede cum a curgut interacțiunea
 *   - debugging: ai pierdut clientul la ce pas?
 *   - demo: arată unui prospect cum funcționează agentul tău
 *
 * URL: /dashboard/transcrieri/conversatie/{conversation}/replay
 */
class ConversationReplayController extends Controller
{
    public function show(Conversation $conversation)
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $conversation->load('bot');
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'direction', 'content', 'created_at']);

        // Calculează timing relativ — primul mesaj la t=0, restul ca delta în ms
        $start = $messages->first()?->created_at;
        $timeline = [];
        if ($start) {
            foreach ($messages as $msg) {
                $deltaMs = $msg->created_at->diffInMilliseconds($start);
                // direction: inbound (user) sau outbound (bot)
                $role = ($msg->direction ?? 'inbound') === 'inbound' ? 'user' : 'bot';
                $timeline[] = [
                    'id'      => $msg->id,
                    'role'    => $role,
                    'content' => $msg->content,
                    'delta_ms' => $deltaMs,
                    'at'      => $msg->created_at->toIso8601String(),
                ];
            }
        }

        return view('dashboard.conversation-replay.show', [
            'conversation' => $conversation,
            'timeline' => $timeline,
            'totalDurationSec' => $start && $messages->last()
                ? $start->diffInSeconds($messages->last()->created_at)
                : 0,
        ]);
    }
}
