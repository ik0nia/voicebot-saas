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

        return view('admin.tenants.show', compact('tenant', 'bots', 'recentCalls', 'users', 'conversations'));
    }
}
