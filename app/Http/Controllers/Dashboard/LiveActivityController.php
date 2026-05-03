<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

/**
 * Live activity snapshot — endpoint JSON polled de widget-ul „Live"
 * de pe /dashboard la fiecare 5s.
 *
 * NU folosește websocket (deși Reverb e în stack); polling 5s e simplu,
 * cache-able și suficient pentru un dashboard. Răspunsul include un
 * timestamp ISO ca să detectăm clock skew client-side.
 */
class LiveActivityController extends Controller
{
    public function snapshot(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'no_tenant' => true,
                'now' => now()->toIso8601String(),
            ]);
        }

        // Active conversations = activitate în ultimele 5 min
        $activeConversations = Conversation::where('last_activity_at', '>=', now()->subMinutes(5))
            ->count();

        // Mesaje în ultima oră — sumate pe conversații cu activitate recentă
        $messagesLastHour = Conversation::where('last_activity_at', '>=', now()->subHour())
            ->sum('messages_count');

        // Apeluri „în derulare" — status active sau started în ultimele 30 min fără ended_at
        $callsInProgress = Call::whereIn('status', ['initiated', 'ringing', 'in-progress', 'active'])
            ->orWhere(function ($q) {
                $q->where('created_at', '>=', now()->subMinutes(30))
                  ->whereNull('ended_at');
            })
            ->count();

        // Lead-uri azi
        $leadsToday = Lead::whereDate('created_at', today())->count();

        // Programări azi
        $callbacksToday = CallbackRequest::whereDate('created_at', today())->count();

        // Latest event — newest entry across leads, callbacks, calls, conversations
        $latest = $this->latestEvent();

        return response()->json([
            'active_conversations' => $activeConversations,
            'messages_last_hour'   => (int) $messagesLastHour,
            'calls_in_progress'    => $callsInProgress,
            'leads_today'          => $leadsToday,
            'callbacks_today'      => $callbacksToday,
            'latest'               => $latest,
            'now'                  => now()->toIso8601String(),
        ]);
    }

    /**
     * Returns the most recent „event" across: leads, callbacks, calls,
     * conversations. Used to power the live ticker line.
     */
    private function latestEvent(): ?array
    {
        $candidates = [];

        $lead = Lead::latest('id')->first(['id', 'name', 'email', 'phone', 'pipeline_stage', 'created_at']);
        if ($lead) {
            $candidates[] = [
                'type' => 'lead',
                'icon' => '🎯',
                'text' => 'Lead nou: ' . ($lead->name ?: $lead->email ?: $lead->phone ?: ('#' . $lead->id)),
                'at'   => $lead->created_at?->toIso8601String(),
                'sort' => $lead->created_at?->timestamp ?? 0,
            ];
        }

        $cb = CallbackRequest::latest('id')->first(['id', 'name', 'phone', 'created_at']);
        if ($cb) {
            $candidates[] = [
                'type' => 'callback',
                'icon' => '📞',
                'text' => 'Cerere callback: ' . ($cb->name ?: $cb->phone ?: ('#' . $cb->id)),
                'at'   => $cb->created_at?->toIso8601String(),
                'sort' => $cb->created_at?->timestamp ?? 0,
            ];
        }

        $call = Call::latest('id')->first(['id', 'caller_number', 'duration_seconds', 'status', 'ended_at', 'created_at']);
        if ($call) {
            $action = $call->ended_at ? 'încheiat' : 'început';
            $candidates[] = [
                'type' => 'call',
                'icon' => '📲',
                'text' => "Apel {$action}: " . ($call->caller_number ?: ('#' . $call->id))
                    . ($call->duration_seconds ? " · {$call->duration_seconds}s" : ''),
                'at'   => ($call->ended_at ?: $call->created_at)?->toIso8601String(),
                'sort' => ($call->ended_at ?: $call->created_at)?->timestamp ?? 0,
            ];
        }

        $conv = Conversation::orderByDesc('last_activity_at')
            ->first(['id', 'contact_name', 'contact_identifier', 'messages_count', 'last_activity_at']);
        if ($conv) {
            $candidates[] = [
                'type' => 'conversation',
                'icon' => '💬',
                'text' => 'Conversație activă: ' . ($conv->contact_name ?: $conv->contact_identifier ?: ('#' . $conv->id))
                    . " · {$conv->messages_count} mesaje",
                'at'   => $conv->last_activity_at?->toIso8601String(),
                'sort' => $conv->last_activity_at?->timestamp ?? 0,
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['sort'] <=> $a['sort']);
        unset($candidates[0]['sort']);
        return $candidates[0];
    }
}
