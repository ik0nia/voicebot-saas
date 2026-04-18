<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan tenants:conversion-report --days=7 [--tenant=slug]`
 *
 * Prints the headline chat-to-lead funnel per tenant for the last N
 * days. Not a dashboard — one table the operator can diff day over
 * day until a real dashboard ships. Zero LLM calls, zero external
 * network — pure DB aggregates against conversations, messages,
 * leads, and chat_events.
 *
 * Columns:
 *   - conversations: all conversations that had at least one user msg
 *   - msg_avg: avg outbound+inbound messages per conversation
 *   - chip_shown / chip_clicked: chip_shown + quick_reply_clicked
 *     event counts (proxy for widget engagement)
 *   - leads_auto: leads captured via auto-extract from chat
 *   - leads_prechat: leads from the prechat form
 *   - cv_rate: leads_auto + leads_prechat / conversations (%)
 *   - cost_ron: conversation.cost_cents aggregated, converted from
 *     USD cents (/100) to RON using 4.50 rate — consistent with
 *     the admin reports
 *
 * The deliberate missing piece is lead-to-revenue — needs the
 * pipeline_stage=won rollup which sits in a separate Linear ticket.
 */
final class TenantConversionReport extends Command
{
    protected $signature = 'tenants:conversion-report
        {--days=7 : Look-back window in days}
        {--tenant= : Filter to a single tenant slug (default: all tenants with any conversation in window)}';

    protected $description = 'Per-tenant chat funnel: conversations → chips → leads → cost.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $since = now()->subDays($days);
        $tenantSlug = $this->option('tenant');

        $tenants = Tenant::query()
            ->when($tenantSlug, fn ($q) => $q->where('slug', $tenantSlug))
            ->when(!$tenantSlug, fn ($q) => $q->whereHas(
                'conversations',
                fn ($qq) => $qq->where('created_at', '>=', $since),
            ))
            ->orderBy('name')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants with conversations in the last ' . $days . ' days.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($tenants as $tenant) {
            $rows[] = $this->rowFor($tenant, $since);
        }

        $this->line("<info>Conversion report — last {$days} days</info> (since {$since->format('Y-m-d H:i')})");
        $this->line('');
        $this->table(
            ['Tenant', 'Convs', 'Msg avg', 'Chips shown', 'Chips clicked', 'Leads (auto)', 'Leads (prechat)', 'CV %', 'Cost RON'],
            $rows,
        );

        return self::SUCCESS;
    }

    /**
     * @return list<int|string>
     */
    private function rowFor(Tenant $tenant, Carbon $since): array
    {
        $convQuery = Conversation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $since);

        $conversations = (clone $convQuery)->count();
        $msgAvg = (float) (clone $convQuery)->avg('messages_count');
        $costCents = (float) (clone $convQuery)->sum('cost_cents');

        $chipShown = ChatEvent::where('tenant_id', $tenant->id)
            ->where('event_name', 'chip_shown')
            ->where('occurred_at', '>=', $since)
            ->count();
        $chipClicked = ChatEvent::where('tenant_id', $tenant->id)
            ->where('event_name', 'quick_reply_clicked')
            ->where('occurred_at', '>=', $since)
            ->count();

        $leadsByReason = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('capture_reason, count(*) as cnt')
            ->groupBy('capture_reason')
            ->pluck('cnt', 'capture_reason');

        $leadsAuto = (int) ($leadsByReason['auto_extracted'] ?? 0)
            + (int) ($leadsByReason['bot_asked_contact'] ?? 0)
            + (int) ($leadsByReason['high_lead_score'] ?? 0)
            + (int) ($leadsByReason['contact_info_provided'] ?? 0);
        $leadsPrechat = (int) ($leadsByReason['prechat_form'] ?? 0);
        $leadsTotal = $leadsAuto + $leadsPrechat;

        $cvRate = $conversations > 0
            ? round(($leadsTotal / $conversations) * 100, 1)
            : 0.0;

        // cost_cents is a USD-cent decimal; divide by 100 for USD,
        // times RON rate. Match the admin reports so numbers line up.
        $costRon = round(($costCents / 100) * 4.50, 2);

        return [
            $tenant->slug ?: ('#' . $tenant->id),
            $conversations,
            round($msgAvg, 1),
            $chipShown,
            $chipClicked,
            $leadsAuto,
            $leadsPrechat,
            $cvRate . '%',
            number_format($costRon, 2, '.', '') . ' RON',
        ];
    }
}
