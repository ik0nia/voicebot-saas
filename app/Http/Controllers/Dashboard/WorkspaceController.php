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
            'trend' => $this->loadTrend($bot),
            'headline' => $this->buildHeadline($bot),
            'bnrRate' => app(\App\Services\Cost\BnrExchangeRate::class)->usdToRon(),
            'recentConversations' => $this->loadRecentConversations($bot),
            'kbStats' => $bot->knowledgeStats(),
            'channels' => $bot->channels()->orderBy('is_active', 'desc')->get(),
        ]);
    }

    /**
     * Last 7 days of key outcomes (older → newer) for sparkline
     * rendering. Zero-fills missing days so low-activity bots
     * still show a baseline.
     */
    private function loadTrend(\App\Models\Bot $bot): array
    {
        $start = today()->subDays(6);
        $end = today();
        $rate = app(\App\Services\Cost\BnrExchangeRate::class)->usdToRon();
        $byDate = \App\Models\DailyOutcomeRollup::where('tenant_id', $bot->tenant_id)
            ->where('bot_id', $bot->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $out = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $k = $d->toDateString();
            $r = $byDate->get($k);
            $revenueCents = $r ? (float) $r->revenue_booked_cents : 0;
            $out[] = [
                'date'          => $k,
                'bookings'      => $r ? (int) $r->bookings_requested : 0,
                'leads'         => $r ? (int) $r->leads_generated : 0,
                'orders'        => $r ? (int) $r->orders_influenced : 0,
                'revenue_ron'   => round(($revenueCents / 100) * $rate, 2),
                'conversations' => $r ? (int) $r->conversations_count : 0,
            ];
        }
        return $out;
    }

    /** Archetype-aware one-line headline for the Acum banner. */
    private function buildHeadline(\App\Models\Bot $bot): string
    {
        $today = $this->loadOutcomes($bot);
        $engine = $bot->engine_type;

        if ($engine === 'booking' || $engine === 'hybrid') {
            $count = $today['bookings_requested'] ?? 0;
            if ($count === 0) return 'Nicio programare azi încă — widget-ul e live și așteaptă.';
            return "{$count} programare" . ($count === 1 ? '' : 'i') . ' azi · ' .
                   ($today['bookings_confirmed'] ?? 0) . ' confirmate.';
        }
        if ($engine === 'hospitality') {
            $conv = $today['conversations_count'] ?? 0;
            return "{$conv} conversație" . ($conv === 1 ? '' : 'i') . ' azi — rezervările se salvează când clientul confirmă.';
        }
        if ($engine === 'ecommerce') {
            $o = $today['orders_influenced'] ?? 0;
            $rev = ($today['revenue_booked_cents'] ?? 0) / 100;
            if ($o === 0) return 'Conversații azi: ' . ($today['conversations_count'] ?? 0) . '. Ordine influențate: 0 — conectează WooCommerce dacă n-ai încă.';
            return "{$o} comandă" . ($o === 1 ? '' : 'i') . ' influențată' . ($o === 1 ? '' : 'e') . ' azi, ' . number_format($rev, 2) . ' RON atribuiți.';
        }
        if ($engine === 'lead') {
            $l = $today['leads_generated'] ?? 0;
            return "{$l} lead" . ($l === 1 ? '' : '-uri') . ' azi. Urmărește-le rapid în tab-ul Conversații.';
        }
        return 'Agentul este generic (fără nișă selectată). Rulează onboarding-ul vertical pentru flow-uri specializate.';
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
