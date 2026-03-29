<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Bot;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount(['users', 'bots', 'calls', 'conversations'])
            ->withSum('calls', 'cost_cents')
            ->latest()
            ->paginate(20);

        // Calculate chat costs per tenant
        $tenantIds = $tenants->pluck('id');
        $chatCosts = \App\Models\Message::query()
            ->selectRaw('c.tenant_id, SUM(messages.cost_cents) as chat_cost')
            ->join('conversations as c', 'messages.conversation_id', '=', 'c.id')
            ->whereIn('c.tenant_id', $tenantIds)
            ->where('messages.cost_cents', '>', 0)
            ->groupBy('c.tenant_id')
            ->pluck('chat_cost', 'tenant_id');

        $tenants->each(function ($tenant) use ($chatCosts) {
            $tenant->chat_cost_cents = $chatCosts[$tenant->id] ?? 0;
            $tenant->total_cost_cents = ($tenant->calls_sum_cost_cents ?? 0) + $tenant->chat_cost_cents;
        });

        return view('admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->loadCount(['users', 'bots', 'calls', 'conversations']);
        $tenant->loadSum('calls', 'cost_cents');

        // Chat cost for this tenant
        $tenant->chat_cost_cents = \App\Models\Message::query()
            ->join('conversations as c', 'messages.conversation_id', '=', 'c.id')
            ->where('c.tenant_id', $tenant->id)
            ->sum('messages.cost_cents');
        $tenant->total_cost_cents = ($tenant->calls_sum_cost_cents ?? 0) + $tenant->chat_cost_cents;

        $bots = Bot::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->withCount(['calls', 'conversations'])
            ->withSum('calls', 'cost_cents')
            ->get();

        $recentCalls = Call::withoutGlobalScopes()->where('tenant_id', $tenant->id)->with('bot')->latest()->take(10)->get();
        $users = User::where('tenant_id', $tenant->id)->get();
        $conversations = Conversation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->withSum('messages as real_cost_cents', 'cost_cents')
            ->latest()->take(10)->get();

        $usage = app(\App\Services\PlanLimitService::class)->getUsageSummary($tenant);
        $planLimits = \App\Models\PlanLimit::findBySlug($tenant->plan_slug ?? 'free');
        $allPlans = \App\Models\PlanLimit::where('is_active', true)->orderBy('sort_order')->get();
        $leads = \App\Models\Lead::where('tenant_id', $tenant->id)->count();
        $opportunities = Conversation::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('is_opportunity', true)->count();
        $revenue = \App\Models\PurchaseAttribution::where('tenant_id', $tenant->id)->sum('order_total_cents');

        return view('admin.tenants.show', compact(
            'tenant', 'bots', 'recentCalls', 'users', 'conversations',
            'usage', 'planLimits', 'allPlans', 'leads', 'opportunities', 'revenue'
        ));
    }

    /**
     * Update tenant plan override.
     */
    public function override(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50',
            'value' => 'required',
        ]);

        $overrides = $tenant->plan_overrides ?? [];
        $overrides[$validated['key']] = $validated['value'];
        $tenant->update(['plan_overrides' => $overrides]);

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("tenant_{$tenant->id}_plan");

        return back()->with('success', "Override '{$validated['key']}' setat la '{$validated['value']}'.");
    }

    /**
     * Remove a specific override.
     */
    public function removeOverride(Request $request, Tenant $tenant, string $key)
    {
        $overrides = $tenant->plan_overrides ?? [];
        unset($overrides[$key]);
        $tenant->update(['plan_overrides' => empty($overrides) ? null : $overrides]);
        \Illuminate\Support\Facades\Cache::forget("tenant_{$tenant->id}_plan");

        return back()->with('success', "Override '{$key}' eliminat.");
    }

    /**
     * Change tenant plan.
     */
    public function changePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate(['plan_slug' => 'required|string|exists:plan_limits,slug']);
        $tenant->update(['plan_slug' => $validated['plan_slug']]);
        \Illuminate\Support\Facades\Cache::forget("tenant_{$tenant->id}_plan");

        return back()->with('success', "Plan schimbat la '{$validated['plan_slug']}'.");
    }
}
