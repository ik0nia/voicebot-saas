@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('breadcrumb')<span class="text-slate-900 font-medium">Dashboard Admin</span>@endsection
@section('content')
<div class="space-y-6">
    {{-- Period selector --}}
    <div class="flex flex-wrap items-center gap-2">
        @php
            $periods = [
                'today' => 'Azi',
                'yesterday' => 'Ieri',
                '7days' => '7 zile',
                '30days' => '30 zile',
                'this_month' => 'Luna curentă',
                'last_month' => 'Luna trecută',
                'this_year' => 'Anul curent',
                'all' => 'Tot',
            ];
        @endphp
        @foreach($periods as $key => $label)
            <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $period === $key ? 'bg-red-800 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach

        {{-- Custom range --}}
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2 ml-2">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="date_from" value="{{ $period === 'custom' ? $dateFrom->toDateString() : '' }}"
                   class="rounded-lg border border-slate-200 text-sm py-1.5 px-2 text-slate-700" placeholder="De la">
            <input type="date" name="date_to" value="{{ $period === 'custom' ? $dateTo->toDateString() : '' }}"
                   class="rounded-lg border border-slate-200 text-sm py-1.5 px-2 text-slate-700" placeholder="Până la">
            <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $period === 'custom' ? 'bg-red-800 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                Aplică
            </button>
        </form>
    </div>

    <p class="text-sm text-slate-500">Perioada: <span class="font-medium text-slate-700">{{ $periodLabel }}</span> ({{ $dateFrom->format('d.m.Y') }} — {{ $dateTo->format('d.m.Y') }})</p>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Tenanti</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalTenants }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Boti activi</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $activeBots }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Conversatii</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $periodConversations }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalConversations }} total</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Apeluri</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $periodCalls }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalCalls }} total | {{ $periodMinutes }} min</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Mesaje</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $periodMessages }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalMessages }} total</p>
        </div>
    </div>

    {{-- Cost overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">Cost apeluri</p>
            <p class="mt-1 text-2xl font-bold text-amber-900">{{ number_format($periodCallCostCents / 100, 2) }} $</p>
            <p class="text-xs text-amber-600 mt-1">Total: {{ number_format($totalCallCostCents / 100, 2) }} $</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-700">Cost AI chat</p>
            <p class="mt-1 text-2xl font-bold text-blue-900">{{ number_format($periodChatCostCents / 100, 4) }} $</p>
            <p class="text-xs text-blue-600 mt-1">Total: {{ number_format($totalChatCostCents / 100, 4) }} $</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-700">Cost total</p>
            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ number_format(($periodCallCostCents + $periodChatCostCents) / 100, 2) }} $</p>
            <p class="text-xs text-emerald-600 mt-1">Total: {{ number_format(($totalCallCostCents + $totalChatCostCents) / 100, 2) }} $</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Cost mediu/conversatie</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $periodConversations > 0 ? number_format($periodChatCostCents / $periodConversations / 100, 4) : '0' }} $</p>
            <p class="text-xs text-slate-400 mt-1">Din {{ $periodConversations }} conversatii</p>
        </div>
    </div>

    {{-- Costs per tenant --}}
    @if($tenantCosts->count())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-semibold text-slate-900">Costuri per tenant</h3>
            <span class="text-sm font-medium text-slate-500">Total: {{ number_format($tenantCosts->sum('total_cost_cents') / 100, 2) }} $</span>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-100 text-left">
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Tenant</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Apeluri</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Conversatii</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost apeluri</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost AI chat</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost total</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($tenantCosts as $tenant)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-red-800 hover:underline">{{ $tenant->name }}</a></td>
                    <td class="px-5 py-3 text-slate-600">{{ $tenant->calls_count }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $tenant->conversations_count }}</td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($tenant->calls_sum_cost_cents ?? 0) / 100, 2) }} $</td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format($tenant->chat_cost_cents / 100, 4) }} $</td>
                    <td class="px-5 py-3 font-semibold text-slate-900">{{ number_format($tenant->total_cost_cents / 100, 2) }} $</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Costs per bot --}}
    @if($botCosts->count())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-semibold text-slate-900">Costuri per bot</h3>
            <span class="text-sm font-medium text-slate-500">Total: {{ number_format($botCosts->sum('total_cost_cents') / 100, 2) }} $</span>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-100 text-left">
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Bot</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Tenant</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Apeluri</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Minute</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Conversatii</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost apeluri</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost AI chat</th>
                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost total</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($botCosts as $bot)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><a href="{{ route('admin.bots.show', $bot) }}" class="font-medium text-red-800 hover:underline">{{ $bot->name }}</a></td>
                    <td class="px-5 py-3 text-slate-500">{{ $bot->tenant?->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $bot->calls_count }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ number_format(($bot->calls_sum_duration_seconds ?? 0) / 60, 1) }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $bot->conversations_count }}</td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($bot->calls_sum_cost_cents ?? 0) / 100, 2) }} $</td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format($bot->chat_cost_cents / 100, 4) }} $</td>
                    <td class="px-5 py-3 font-semibold text-slate-900">{{ number_format($bot->total_cost_cents / 100, 2) }} $</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Recent activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-semibold text-slate-900">Apeluri recente</h3></div>
            <table class="w-full text-sm"><tbody class="divide-y divide-slate-100">
                @forelse($recentCalls as $call)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><a href="{{ route('admin.calls.show', $call) }}" class="text-red-800 hover:underline">#{{ $call->id }}</a></td>
                    <td class="px-5 py-3 text-slate-500">{{ $call->tenant?->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-700">{{ $call->bot?->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($call->cost_cents ?? 0) / 100, 2) }} $</td>
                    <td class="px-5 py-3 text-slate-400 text-xs">{{ $call->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Niciun apel.</td></tr>
                @endforelse
            </tbody></table>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-semibold text-slate-900">Conversatii recente</h3></div>
            <table class="w-full text-sm"><tbody class="divide-y divide-slate-100">
                @forelse($recentConversations as $conv)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><a href="{{ route('admin.conversations.show', $conv) }}" class="text-red-800 hover:underline">#{{ $conv->id }}</a></td>
                    <td class="px-5 py-3 text-slate-500">{{ $conv->tenant?->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-700">{{ $conv->bot?->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $conv->messages_count }} msg</td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ ($conv->real_cost_cents ?? 0) > 0 ? number_format($conv->real_cost_cents / 100, 4) . ' $' : '-' }}</td>
                    <td class="px-5 py-3 text-slate-400 text-xs">{{ $conv->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Nicio conversatie.</td></tr>
                @endforelse
            </tbody></table>
        </div>
    </div>
</div>
@endsection
