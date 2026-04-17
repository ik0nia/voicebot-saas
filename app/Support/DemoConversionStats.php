<?php

namespace App\Support;

use App\Models\ChatEvent;
use App\Services\EventTaxonomy;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates the niche-demo funnel for the admin dashboard panel.
 *
 * Reads chat_events filtered to DEMO_VIEWED / DEMO_QUALIFIED and
 * groups by niche_slug (stored inside properties). Also counts how
 * many tenants registered with a `demo_qualified_from_niche` stamp
 * in settings — this is the actual signup conversion we care about.
 */
class DemoConversionStats
{
    /**
     * @return array{
     *   viewed:int, qualified:int, rate:float, messages:int,
     *   signups:int, by_niche: array<int, array{niche:string, viewed:int, qualified:int, signups:int, rate:float}>
     * }
     */
    public static function summary(int $days = 30): array
    {
        $since = now()->subDays($days);

        $events = ChatEvent::query()
            ->withoutGlobalScopes()
            ->whereIn('event_name', [
                EventTaxonomy::DEMO_VIEWED,
                EventTaxonomy::DEMO_QUALIFIED,
                EventTaxonomy::DEMO_MESSAGE_SENT,
            ])
            ->where('created_at', '>=', $since)
            // Pull the niche out of the JSONB properties blob.
            ->selectRaw("event_name, properties->>'niche_slug' as niche_slug, count(*) as n")
            ->groupBy('event_name', DB::raw("properties->>'niche_slug'"))
            ->get();

        $byNiche = [];
        foreach ($events as $row) {
            $slug = $row->niche_slug ?: '(unknown)';
            $byNiche[$slug] = $byNiche[$slug] ?? ['viewed' => 0, 'qualified' => 0, 'messages' => 0];
            if ($row->event_name === EventTaxonomy::DEMO_VIEWED) $byNiche[$slug]['viewed'] = (int) $row->n;
            if ($row->event_name === EventTaxonomy::DEMO_QUALIFIED) $byNiche[$slug]['qualified'] = (int) $row->n;
            if ($row->event_name === EventTaxonomy::DEMO_MESSAGE_SENT) $byNiche[$slug]['messages'] = (int) $row->n;
        }

        // Tenants that registered with a demo-attribution stamp.
        // Uses JSONB -> access so Postgres does the filtering. Cheap
        // — the tenants table is tiny compared to chat_events.
        $signupsByNicheRows = \App\Models\Tenant::query()
            ->withoutGlobalScopes()
            ->where('created_at', '>=', $since)
            ->whereNotNull(DB::raw("settings->>'demo_qualified_from_niche'"))
            ->selectRaw("settings->>'demo_qualified_from_niche' as niche_slug, count(*) as n")
            ->groupBy(DB::raw("settings->>'demo_qualified_from_niche'"))
            ->get();

        $signupsByNiche = [];
        foreach ($signupsByNicheRows as $row) {
            if (!$row->niche_slug) continue;
            $signupsByNiche[$row->niche_slug] = (int) $row->n;
            $byNiche[$row->niche_slug] = $byNiche[$row->niche_slug] ?? ['viewed' => 0, 'qualified' => 0, 'messages' => 0];
        }

        // Build ordered per-niche rows
        $perNiche = [];
        foreach ($byNiche as $slug => $data) {
            $viewed = $data['viewed'];
            $qualified = $data['qualified'];
            $rate = $viewed > 0 ? round(($qualified / $viewed) * 100, 1) : 0.0;
            $perNiche[] = [
                'niche'     => $slug,
                'viewed'    => $viewed,
                'qualified' => $qualified,
                'messages'  => $data['messages'],
                'signups'   => $signupsByNiche[$slug] ?? 0,
                'rate'      => $rate,
            ];
        }
        // Sort by viewed desc — "where is engagement" ordering.
        usort($perNiche, fn($a, $b) => $b['viewed'] <=> $a['viewed']);

        $totalViewed = array_sum(array_column($perNiche, 'viewed'));
        $totalQualified = array_sum(array_column($perNiche, 'qualified'));
        $totalMessages = array_sum(array_column($perNiche, 'messages'));
        $totalSignups = array_sum($signupsByNiche);

        return [
            'viewed'    => $totalViewed,
            'qualified' => $totalQualified,
            'messages'  => $totalMessages,
            'signups'   => $totalSignups,
            'rate'      => $totalViewed > 0 ? round(($totalQualified / $totalViewed) * 100, 1) : 0.0,
            'by_niche'  => $perNiche,
        ];
    }
}
