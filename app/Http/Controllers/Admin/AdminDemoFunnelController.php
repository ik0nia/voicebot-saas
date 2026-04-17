<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DemoConversionStats;
use Illuminate\Http\Request;

/**
 * Per-niche breakdown of the public demo funnel.
 *
 * /admin/demo — linked from the "Demo pe landing pages" card on
 * /admin/costuri. Mostly a fuller view of the same data so the team
 * can see which niches are producing signups, and which are getting
 * traffic but no engagement.
 */
class AdminDemoFunnelController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(1, min(365, $days));

        $stats = DemoConversionStats::summary($days);

        return view('admin.demo.index', [
            'days'  => $days,
            'stats' => $stats,
        ]);
    }
}
