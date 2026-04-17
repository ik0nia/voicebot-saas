<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatEvent;
use App\Services\EventTaxonomy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Per-chip conversion analytics. Two datasets:
 *
 *  - chip_shown  — emitted server-side when a quick_replies SSE event
 *                  is sent. One row per render, includes the label
 *                  array + user_state + page_type.
 *  - quick_reply_clicked — emitted by the widget JS on tap. Has the
 *                  clicked label + page_type.
 *
 * CTR = clicks / shows. Computed per label so operators can see
 * which chip copies convert and which get ignored.
 *
 * Deliberately lightweight — no Chart.js, no flashy UI. A sortable
 * table and three KPI cards is all enterprise reporting needs at
 * this stage.
 */
class AdminChipAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) ($request->get('days', 30));
        $days = max(1, min(90, $days));
        $since = Carbon::now()->subDays($days);

        $driver = DB::connection()->getDriverName();
        $labelExpr = $driver === 'pgsql'
            ? "properties->>'label'"
            : "json_extract(properties, '$.label')";
        $pageTypeExpr = $driver === 'pgsql'
            ? "properties->>'page_type'"
            : "json_extract(properties, '$.page_type')";
        $userStateExpr = $driver === 'pgsql'
            ? "properties->>'user_state'"
            : "json_extract(properties, '$.user_state')";

        // Clicks — per-label.
        try {
            $clicks = ChatEvent::query()
                ->withoutGlobalScopes()
                ->where('event_name', EventTaxonomy::CHIP_CLICKED)
                ->where('created_at', '>=', $since)
                ->selectRaw("{$labelExpr} as label, {$pageTypeExpr} as page_type, count(*) as n")
                ->groupBy(DB::raw($labelExpr), DB::raw($pageTypeExpr))
                ->get();
        } catch (\Throwable $e) {
            $clicks = collect();
        }

        // Shows — labels is an array, so we unnest / scan rows and
        // count per-label manually (portable across drivers without
        // jsonb unnest trickery).
        try {
            $shows = ChatEvent::query()
                ->withoutGlobalScopes()
                ->where('event_name', EventTaxonomy::CHIP_SHOWN)
                ->where('created_at', '>=', $since)
                ->select('properties')
                ->get();
        } catch (\Throwable $e) {
            $shows = collect();
        }

        $showsByLabel = [];
        foreach ($shows as $row) {
            $props = is_array($row->properties) ? $row->properties : (array) json_decode((string) $row->properties, true);
            $labels = $props['labels'] ?? [];
            $pt = (string) ($props['page_type'] ?? '');
            foreach ((array) $labels as $lbl) {
                $key = (string) $lbl . '|' . $pt;
                $showsByLabel[$key] = ($showsByLabel[$key] ?? 0) + 1;
            }
        }

        // Join clicks + shows per label|page_type.
        $rows = [];
        foreach ($clicks as $row) {
            $lbl = (string) ($row->label ?? '');
            $pt  = (string) ($row->page_type ?? '');
            if ($lbl === '') continue;
            $key = $lbl . '|' . $pt;
            $shown = $showsByLabel[$key] ?? 0;
            $clicked = (int) $row->n;
            $rows[$key] = [
                'label'     => $lbl,
                'page_type' => $pt ?: '(any)',
                'shown'     => $shown,
                'clicked'   => $clicked,
                'ctr'       => $shown > 0 ? round(($clicked / $shown) * 100, 1) : null,
            ];
        }
        // Include shown-but-never-clicked labels.
        foreach ($showsByLabel as $key => $n) {
            if (isset($rows[$key])) continue;
            [$lbl, $pt] = explode('|', $key, 2);
            $rows[$key] = [
                'label'     => $lbl,
                'page_type' => $pt ?: '(any)',
                'shown'     => $n,
                'clicked'   => 0,
                'ctr'       => $n > 0 ? 0.0 : null,
            ];
        }
        $rows = array_values($rows);
        usort($rows, fn($a, $b) => ($b['clicked'] <=> $a['clicked']) ?: ($b['shown'] <=> $a['shown']));

        // User-state distribution (from chip_shown).
        $stateCounts = [];
        try {
            $stateRows = ChatEvent::query()
                ->withoutGlobalScopes()
                ->where('event_name', EventTaxonomy::CHIP_SHOWN)
                ->where('created_at', '>=', $since)
                ->selectRaw("{$userStateExpr} as state, count(*) as n")
                ->groupBy(DB::raw($userStateExpr))
                ->get();
            foreach ($stateRows as $r) {
                $stateCounts[(string) ($r->state ?? 'unknown')] = (int) $r->n;
            }
        } catch (\Throwable $e) { }

        $totalShown = array_sum(array_column($rows, 'shown'));
        $totalClicked = array_sum(array_column($rows, 'clicked'));
        $overallCtr = $totalShown > 0 ? round(($totalClicked / $totalShown) * 100, 1) : 0.0;

        return view('admin.chip-analytics.index', [
            'days'         => $days,
            'since'        => $since,
            'rows'         => $rows,
            'stateCounts'  => $stateCounts,
            'totalShown'   => $totalShown,
            'totalClicked' => $totalClicked,
            'overallCtr'   => $overallCtr,
        ]);
    }
}
