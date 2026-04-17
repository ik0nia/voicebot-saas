<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Conversation;
use App\Models\DailyOutcomeRollup;
use Illuminate\Http\Request;

/**
 * Unified single-screen overview per bot. Additive — every legacy
 * page stays accessible; this surface only consolidates read-only
 * data. Edits still flow through the existing controllers via
 * "Deschide" links on each tab.
 */
class WorkspaceController extends Controller
{
    public function show(Request $request, Bot $bot)
    {
        // Tenant scope guard — don't let one tenant view another
        // tenant's bot via URL crafting even with the same route.
        abort_unless($bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(), 404);

        $tab = in_array(
            $request->get('tab'),
            ['acum', 'conversatii', 'agent', 'cunostinte', 'canale'],
            true
        ) ? $request->get('tab') : 'acum';

        return view('dashboard.workspace.show', [
            'bot' => $bot,
            'tab' => $tab,
            'engine' => $bot->engine(),
            'nichConfig' => $bot->niche_slug ? config('niches.' . $bot->niche_slug, []) : [],
            'outcomes' => $this->loadOutcomes($bot),
            'recentConversations' => $this->loadRecentConversations($bot),
            'kbStats' => $bot->knowledgeStats(),
            'channels' => $bot->channels()->orderBy('is_active', 'desc')->get(),
        ]);
    }

    /**
     * Today's outcome snapshot, prefers the rollup. Falls back to
     * live counts when the rollup hasn't run yet for today (e.g.
     * before 00:15 UTC scheduler fire).
     */
    private function loadOutcomes(Bot $bot): array
    {
        $rollup = DailyOutcomeRollup::where('tenant_id', $bot->tenant_id)
            ->where('bot_id', $bot->id)
            ->whereDate('date', today())
            ->first();

        if ($rollup) {
            return $rollup->only([
                'bookings_requested', 'bookings_confirmed',
                'leads_generated', 'callbacks_requested',
                'conversations_count', 'voice_calls_count',
                'orders_influenced', 'revenue_booked_cents',
            ]);
        }

        // Live fallback — cheap because tenant scope narrows fast.
        $todayStart = today();
        return [
            'bookings_requested' => \App\Models\Appointment::withoutGlobalScopes()
                ->where('bot_id', $bot->id)->whereDate('created_at', $todayStart)->count(),
            'bookings_confirmed' => \App\Models\Appointment::withoutGlobalScopes()
                ->where('bot_id', $bot->id)
                ->whereDate('created_at', $todayStart)
                ->whereIn('status', ['confirmed', 'reminder_sent', 'completed'])
                ->count(),
            'leads_generated' => \App\Models\Lead::withoutGlobalScopes()
                ->where('bot_id', $bot->id)->whereDate('created_at', $todayStart)->count(),
            'callbacks_requested' => \App\Models\CallbackRequest::withoutGlobalScopes()
                ->where('bot_id', $bot->id)->whereDate('created_at', $todayStart)->count(),
            'conversations_count' => Conversation::withoutGlobalScopes()
                ->where('bot_id', $bot->id)->whereDate('created_at', $todayStart)->count(),
            'voice_calls_count' => \App\Models\Call::withoutGlobalScopes()
                ->where('bot_id', $bot->id)->whereDate('created_at', $todayStart)->count(),
            'orders_influenced' => 0,
            'revenue_booked_cents' => 0,
        ];
    }

    private function loadRecentConversations(Bot $bot)
    {
        return Conversation::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->orderByDesc('last_activity_at')
            ->limit(10)
            ->get(['id', 'contact_name', 'contact_identifier', 'messages_count',
                   'status', 'last_activity_at', 'primary_intent', 'lead_score']);
    }
}
