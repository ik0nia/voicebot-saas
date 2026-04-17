<?php

namespace App\Services\Outcome;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Conversation;
use App\Models\DailyOutcomeRollup;
use App\Models\Lead;
use App\Models\PurchaseAttribution;
use App\Models\Tenant;
use App\Services\Cost\BnrExchangeRate;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Daily outcome aggregator — mirror of DailyCostAggregator but for
 * the "what did the bot earn" side. Idempotent (updateOrCreate) so
 * the same date can be reprocessed without duplication.
 *
 * Tables read:
 *   - appointments        (bookings_* metrics)
 *   - leads               (leads_generated, quotes_sent)
 *   - callback_requests   (callbacks_requested)
 *   - contact_messages    (contact_messages_received — SaaS leads)
 *   - conversations       (conversations_count, unique_visitors)
 *   - calls               (voice_calls_count)
 *
 * Tables intentionally NOT read (yet):
 *   - woo_orders / stripe / other ecommerce sources — requires
 *     per-tenant integration to tie orders back to a conversation.
 *     Metric columns stay at zero until that wiring lands.
 */
class DailyOutcomeAggregator
{
    public function __construct(private BnrExchangeRate $bnr) {}

    public function aggregate(CarbonInterface $date): int
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $rate = $this->bnr->usdToRon();

        $written = 0;

        foreach (Tenant::withoutGlobalScopes()->cursor() as $tenant) {
            // Per-bot + tenant-wide rows in one pass — aggregate
            // the per-bot rows into the tenant row as we go.
            $byBot = $this->perBotCounts($tenant->id, $start, $end);
            if (empty($byBot)) continue;

            $tenantTotals = array_fill_keys([
                'bookings_requested','bookings_confirmed','bookings_canceled','bookings_noshow',
                'leads_generated','quotes_sent','callbacks_requested','contact_messages_received',
                'orders_influenced','carts_abandoned','revenue_booked_cents',
                'conversations_count','voice_calls_count','unique_visitors',
            ], 0);

            foreach ($byBot as $botId => $m) {
                DailyOutcomeRollup::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'bot_id' => $botId ?: null, 'date' => $start->toDateString()],
                    array_merge($m, ['usd_ron_rate' => $rate])
                );
                $written++;

                foreach ($m as $k => $v) {
                    if (isset($tenantTotals[$k])) $tenantTotals[$k] += $v;
                }
            }

            // Tenant-wide row (bot_id NULL) — only write when the
            // tenant actually had activity today, to keep the table
            // lean for inactive tenants.
            if (array_sum($tenantTotals) > 0) {
                DailyOutcomeRollup::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'bot_id' => null, 'date' => $start->toDateString()],
                    array_merge($tenantTotals, ['usd_ron_rate' => $rate])
                );
                $written++;
            }
        }

        return $written;
    }

    /**
     * @return array<int, array<string,int|float>>   keyed by bot_id
     */
    private function perBotCounts(int $tenantId, $start, $end): array
    {
        $perBot = [];

        // Appointments — split by status.
        $apptAgg = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                bot_id,
                COUNT(*) AS bookings_requested,
                SUM(CASE WHEN status IN ('confirmed','reminder_sent','completed') THEN 1 ELSE 0 END) AS bookings_confirmed,
                SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) AS bookings_canceled,
                SUM(CASE WHEN status = 'noshow' THEN 1 ELSE 0 END) AS bookings_noshow
            ")
            ->groupBy('bot_id')
            ->get();
        foreach ($apptAgg as $row) {
            $perBot[$row->bot_id] = array_merge($perBot[$row->bot_id] ?? [], [
                'bookings_requested' => (int) $row->bookings_requested,
                'bookings_confirmed' => (int) $row->bookings_confirmed,
                'bookings_canceled'  => (int) $row->bookings_canceled,
                'bookings_noshow'    => (int) $row->bookings_noshow,
            ]);
        }

        // Leads.
        $leadAgg = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('bot_id, COUNT(*) AS leads_generated')
            ->groupBy('bot_id')
            ->get();
        foreach ($leadAgg as $row) {
            $perBot[$row->bot_id] = array_merge($perBot[$row->bot_id] ?? [], [
                'leads_generated' => (int) $row->leads_generated,
            ]);
        }

        // Callbacks.
        $cbAgg = CallbackRequest::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('bot_id, COUNT(*) AS callbacks_requested')
            ->groupBy('bot_id')
            ->get();
        foreach ($cbAgg as $row) {
            $perBot[$row->bot_id] = array_merge($perBot[$row->bot_id] ?? [], [
                'callbacks_requested' => (int) $row->callbacks_requested,
            ]);
        }

        // ContactMessage is a platform-wide SaaS inbox (leads for
        // Sambla itself), not a tenant-scoped artefact — no
        // tenant_id column in that table. Intentionally skipped
        // from per-tenant outcomes; surfaces in the admin SaaS
        // lead report instead.

        // Conversations + voice calls.
        $convAgg = Conversation::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('bot_id, COUNT(*) AS conversations_count, COUNT(DISTINCT visitor_id) AS unique_visitors')
            ->groupBy('bot_id')
            ->get();
        foreach ($convAgg as $row) {
            $perBot[$row->bot_id] = array_merge($perBot[$row->bot_id] ?? [], [
                'conversations_count' => (int) $row->conversations_count,
                'unique_visitors'     => (int) $row->unique_visitors,
            ]);
        }

        $callAgg = Call::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('bot_id, COUNT(*) AS voice_calls_count')
            ->groupBy('bot_id')
            ->get();
        foreach ($callAgg as $row) {
            $perBot[$row->bot_id] = array_merge($perBot[$row->bot_id] ?? [], [
                'voice_calls_count' => (int) $row->voice_calls_count,
            ]);
        }

        // Ecommerce — AttributionService already classifies WC orders
        // back to the conversation/bot that influenced them. We just
        // aggregate the existing rows; no webhook wiring change here.
        // Tenants without WooCommerce produce 0 rows → 0 orders, 0
        // revenue via COALESCE.
        $orderAgg = PurchaseAttribution::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('bot_id, COUNT(*) AS orders_influenced, COALESCE(SUM(order_total_cents), 0) AS revenue_booked_cents')
            ->groupBy('bot_id')
            ->get();
        foreach ($orderAgg as $row) {
            $perBot[$row->bot_id] = array_merge($perBot[$row->bot_id] ?? [], [
                'orders_influenced'    => (int) $row->orders_influenced,
                'revenue_booked_cents' => (float) $row->revenue_booked_cents,
            ]);
        }

        // Fill missing keys with zero so updateOrCreate writes a
        // consistent row shape regardless of which source fired.
        $zeroes = array_fill_keys([
            'bookings_requested','bookings_confirmed','bookings_canceled','bookings_noshow',
            'leads_generated','quotes_sent','callbacks_requested','contact_messages_received',
            'orders_influenced','carts_abandoned','revenue_booked_cents',
            'conversations_count','voice_calls_count','unique_visitors',
        ], 0);
        foreach ($perBot as $botId => $metrics) {
            $perBot[$botId] = array_merge($zeroes, $metrics);
        }

        return $perBot;
    }
}
