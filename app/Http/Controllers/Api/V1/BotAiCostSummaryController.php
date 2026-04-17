<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\Cost\SetupAiCostReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-facing view of setup-AI spend per bot. The UI surfaces it
 * as a small "AI cost" badge next to the bot name in the agent
 * builder so users understand what their "generate with AI" clicks
 * cost the platform before we flip enforcement on.
 *
 * Mirrors to a session route at /dashboard/agenti/{bot}/ai-cost-summary
 * so the Blade dashboard UI (built separately) can call the same
 * logic with CSRF instead of Sanctum.
 */
class BotAiCostSummaryController extends Controller
{
    public function __construct(private SetupAiCostReport $report) {}

    public function show(Request $request, Bot $bot): JsonResponse
    {
        // Route-model binding resolves the bot without tenant scope
        // awareness; enforce tenant ownership ourselves. Any mismatch
        // is 403 — do NOT leak "bot exists" vs "bot forbidden".
        $user = $request->user();
        if (!$user || $bot->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $summary = $this->report->botSummary($bot->id, $bot->tenant_id);

        return response()->json($summary);
    }
}
