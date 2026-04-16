<?php

namespace App\Services\Cost;

use App\Models\Call;
use App\Models\DailyCostRollup;
use App\Models\Message;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Builds daily per-tenant + per-bot cost roll-ups from raw calls +
 * messages data. Idempotent — running twice for the same date just
 * overwrites; the Admin "re-aggregate date X" button is safe.
 *
 * Roll-up granularity: per (tenant_id, bot_id, date). A null bot_id
 * row per (tenant_id, date) is the tenant-wide total for the day.
 */
class DailyCostAggregator
{
    public function __construct(
        private BnrExchangeRate $bnr,
    ) {}

    public function aggregate(CarbonInterface $date): int
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $ratePerUsd = $this->bnr->usdToRon();

        // Wipe this day's rows first so dropped activity (e.g. a call
        // that was soft-deleted) doesn't leave stale per-bot rows
        // behind. updateOrCreate only adds/updates; it never removes.
        DailyCostRollup::whereDate('date', $start->toDateString())->delete();

        $written = 0;

        foreach (Tenant::withoutGlobalScopes()->cursor() as $tenant) {
            // Voice calls per bot within the day — grouped to avoid
            // N+1 on bot lookups.
            $voiceByBot = Call::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('
                    bot_id,
                    COUNT(*) as calls_count,
                    COALESCE(SUM(GREATEST(duration_seconds, 0)), 0) as seconds,
                    COALESCE(SUM(openai_cost_cents), 0) as openai_cents,
                    COALESCE(SUM(twilio_cost_cents), 0) as twilio_cents,
                    COALESCE(SUM(embedding_cost_cents), 0) as embedding_cents,
                    COALESCE(SUM(cost_cents), 0) as total_cents
                ')
                ->groupBy('bot_id')
                ->get()
                ->keyBy(fn ($r) => $r->bot_id ?? 0);

            // Chat conversations per bot. Message cost attributes to
            // the bot that owns the conversation.
            $chatByBot = DB::table('messages')
                ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                ->where('conversations.tenant_id', $tenant->id)
                ->whereBetween('messages.created_at', [$start, $end])
                ->selectRaw('
                    conversations.bot_id as bot_id,
                    COUNT(DISTINCT conversations.id) as conv_count,
                    COUNT(messages.id) as msg_count,
                    COALESCE(SUM(messages.cost_cents), 0) as cost_cents
                ')
                ->groupBy('conversations.bot_id')
                ->get()
                ->keyBy(fn ($r) => $r->bot_id ?? 0);

            // Union of bot ids across voice + chat for this tenant/day.
            $botIds = collect($voiceByBot->keys())
                ->merge($chatByBot->keys())
                ->unique();

            // Per-bot rows + accumulate tenant-wide totals as we go.
            $tenantVoice = ['count' => 0, 'seconds' => 0, 'openai' => 0, 'twilio' => 0, 'emb' => 0, 'total' => 0];
            $tenantChat  = ['conv' => 0, 'msg' => 0, 'cost' => 0];

            foreach ($botIds as $botId) {
                $v = $voiceByBot->get($botId);
                $c = $chatByBot->get($botId);

                $voiceTotal = $v ? (float) $v->total_cents : 0.0;
                $chatCost   = $c ? (float) $c->cost_cents : 0.0;
                $total      = $voiceTotal + $chatCost;

                if ($total == 0.0 && ($v->calls_count ?? 0) === 0 && ($c->conv_count ?? 0) === 0) {
                    continue; // nothing happened for this bot that day
                }

                DailyCostRollup::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'bot_id' => $botId ?: null,
                        'date' => $start->toDateString(),
                    ],
                    [
                        'voice_calls_count' => (int) ($v->calls_count ?? 0),
                        'voice_seconds' => (int) ($v->seconds ?? 0),
                        'voice_openai_cents' => (float) ($v->openai_cents ?? 0),
                        'voice_twilio_cents' => (float) ($v->twilio_cents ?? 0),
                        'voice_embedding_cents' => (float) ($v->embedding_cents ?? 0),
                        'voice_total_cents' => $voiceTotal,
                        'chat_conversations_count' => (int) ($c->conv_count ?? 0),
                        'chat_messages_count' => (int) ($c->msg_count ?? 0),
                        'chat_cost_cents' => $chatCost,
                        'total_cents' => $total,
                        'usd_ron_rate' => $ratePerUsd,
                    ]
                );
                $written++;

                $tenantVoice['count']   += (int) ($v->calls_count ?? 0);
                $tenantVoice['seconds'] += (int) ($v->seconds ?? 0);
                $tenantVoice['openai']  += (float) ($v->openai_cents ?? 0);
                $tenantVoice['twilio']  += (float) ($v->twilio_cents ?? 0);
                $tenantVoice['emb']     += (float) ($v->embedding_cents ?? 0);
                $tenantVoice['total']   += $voiceTotal;
                $tenantChat['conv']     += (int) ($c->conv_count ?? 0);
                $tenantChat['msg']      += (int) ($c->msg_count ?? 0);
                $tenantChat['cost']     += $chatCost;
            }

            // Tenant-wide row (bot_id NULL) — skip tenants with no
            // activity this day to keep the table lean.
            if ($tenantVoice['total'] || $tenantChat['cost'] || $tenantVoice['count'] || $tenantChat['conv']) {
                DailyCostRollup::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'bot_id' => null, 'date' => $start->toDateString()],
                    [
                        'voice_calls_count' => $tenantVoice['count'],
                        'voice_seconds' => $tenantVoice['seconds'],
                        'voice_openai_cents' => $tenantVoice['openai'],
                        'voice_twilio_cents' => $tenantVoice['twilio'],
                        'voice_embedding_cents' => $tenantVoice['emb'],
                        'voice_total_cents' => $tenantVoice['total'],
                        'chat_conversations_count' => $tenantChat['conv'],
                        'chat_messages_count' => $tenantChat['msg'],
                        'chat_cost_cents' => $tenantChat['cost'],
                        'total_cents' => $tenantVoice['total'] + $tenantChat['cost'],
                        'usd_ron_rate' => $ratePerUsd,
                    ]
                );
                $written++;
            }
        }

        return $written;
    }
}
