@extends('layouts.admin')
@section('title', 'Control Center')
@section('breadcrumb')<span class="text-white font-medium">Control Center</span>@endsection
@section('content')
<div class="space-y-8 max-w-[1400px]">

    {{-- ── Period selector ── --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Control Center</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $periodLabel }}</p>
        </div>
        <div class="flex gap-1.5 bg-white rounded-xl border border-slate-200 p-1">
            @foreach(['today' => 'Azi', '7days' => '7 zile', '30days' => '30 zile', 'this_month' => 'Lună', 'all' => 'Tot'] as $val => $label)
                <a href="?period={{ $val }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $period === $val ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 1: HEADER METRICS — cele mai importante cifre --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

        {{-- Revenue — HERO CARD --}}
        <div class="col-span-2 md:col-span-1 bg-emerald-50 rounded-2xl p-6 border border-emerald-200">
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Revenue Atribuit</p>
            <p class="text-3xl font-extrabold text-emerald-700 mt-2">{{ number_format($commerce['revenue_total_cents'] / 100, 0) }} <span class="text-lg font-semibold">RON</span></p>
            <p class="text-xs text-emerald-600/70 mt-1">{{ $commerce['purchases'] }} cumpărături</p>
        </div>

        {{-- Conversații --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Conversații</p>
            <p class="text-2xl font-bold text-slate-900 mt-1.5">{{ number_format($platform['conversations']) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ number_format($platform['messages']) }} mesaje</p>
        </div>

        {{-- Leads --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Leads</p>
            <p class="text-2xl font-bold text-violet-600 mt-1.5">{{ $leadStats['leads'] }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $leadStats['leads_converted'] }} convertite</p>
        </div>

        {{-- Oportunități --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Oportunități</p>
            <p class="text-2xl font-bold text-amber-600 mt-1.5">{{ $leadStats['opportunities'] }}</p>
            <p class="text-xs text-slate-400 mt-0.5">fără date contact</p>
        </div>

        {{-- Tenanți --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Tenanți</p>
            <p class="text-2xl font-bold text-slate-900 mt-1.5">{{ $platform['total_tenants'] }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $platform['active_tenants'] }} activi · {{ $platform['new_tenants_month'] }} noi</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 2: COMMERCE + LEADS (2 coloane) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Commerce breakdown --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-sm font-semibold text-slate-900">Commerce & Revenue</h2>
                <span class="text-xs text-emerald-600 font-medium bg-emerald-50 px-2 py-0.5 rounded-full">{{ $commerce['purchases'] }} conversii</span>
            </div>
            <div class="grid grid-cols-3 gap-4 mb-5">
                <div class="text-center p-3 bg-slate-50 rounded-xl">
                    <p class="text-lg font-bold text-slate-900">{{ number_format($commerce['product_impressions']) }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">Afișări</p>
                </div>
                <div class="text-center p-3 bg-slate-50 rounded-xl">
                    <p class="text-lg font-bold text-slate-900">{{ number_format($commerce['product_clicks']) }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">Click-uri</p>
                </div>
                <div class="text-center p-3 bg-emerald-50 rounded-xl">
                    <p class="text-lg font-bold text-emerald-700">{{ number_format($commerce['add_to_cart']) }}</p>
                    <p class="text-[10px] text-emerald-600 mt-0.5">Add to Cart</p>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-medium text-slate-500 mb-2">Atribuire revenue</p>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-500"></div><span class="text-xs text-slate-600">Directe din bot</span></div>
                        <span class="text-xs font-semibold text-slate-900">{{ number_format($commerce['revenue_strict_cents'] / 100, 0) }} RON</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-amber-400"></div><span class="text-xs text-slate-600">Influențate</span></div>
                        <span class="text-xs font-semibold text-slate-900">{{ number_format($commerce['revenue_probable_cents'] / 100, 0) }} RON</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-blue-400"></div><span class="text-xs text-slate-600">Asistate</span></div>
                        <span class="text-xs font-semibold text-slate-900">{{ number_format($commerce['revenue_assisted_cents'] / 100, 0) }} RON</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Leads & Opportunities --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-sm font-semibold text-slate-900">Leads & Oportunități</h2>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div class="p-4 bg-violet-50 rounded-xl border border-violet-100">
                    <p class="text-2xl font-bold text-violet-700">{{ $leadStats['leads'] }}</p>
                    <p class="text-xs text-violet-600 mt-1">Leads cu contact</p>
                    <p class="text-[10px] text-violet-500 mt-0.5">{{ $leadStats['leads_qualified'] }} calificate · {{ $leadStats['leads_converted'] }} convertite</p>
                </div>
                <div class="p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <p class="text-2xl font-bold text-amber-700">{{ $leadStats['opportunities'] }}</p>
                    <p class="text-xs text-amber-600 mt-1">Oportunități</p>
                    <p class="text-[10px] text-amber-500 mt-0.5">interes fără contact</p>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-medium text-slate-500 mb-3">Rate de conversie</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-slate-500">Opp → Lead</span>
                            <span class="text-xs font-bold text-slate-900">{{ $leadStats['opp_to_lead_rate'] }}%</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-violet-500 rounded-full" style="width: {{ min($leadStats['opp_to_lead_rate'], 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-slate-500">Lead → Client</span>
                            <span class="text-xs font-bold text-slate-900">{{ $leadStats['lead_to_client_rate'] }}%</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-emerald-500 rounded-full" style="width: {{ min($leadStats['lead_to_client_rate'], 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 3: VOICE + COST (2 coloane, mai subtile) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Voice</h2>
            <div class="grid grid-cols-4 gap-3">
                @foreach([
                    ['label' => 'Apeluri', 'value' => number_format($voice['calls']), 'color' => 'text-slate-900'],
                    ['label' => 'Minute', 'value' => number_format($voice['minutes']), 'color' => 'text-sky-600'],
                    ['label' => 'Cost', 'value' => number_format($voice['cost_cents'] / 100, 2) . '€', 'color' => 'text-red-500'],
                    ['label' => 'Tenanți', 'value' => $voice['active_tenants'], 'color' => 'text-slate-900'],
                ] as $v)
                <div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">{{ $v['label'] }}</p>
                    <p class="text-lg font-bold {{ $v['color'] }} mt-0.5">{{ $v['value'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Costuri AI</h2>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Chat AI</p>
                    <p class="text-lg font-bold text-slate-900 mt-0.5">{{ number_format($costs['ai_cost_cents'] / 100, 4) }}€</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ number_format($costs['ai_cost_cents'], 4) }}¢</p>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Voice</p>
                    <p class="text-lg font-bold text-slate-900 mt-0.5">{{ number_format($costs['voice_cost_cents'] / 100, 4) }}€</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ number_format($costs['voice_cost_cents'], 4) }}¢</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3 -m-1">
                    <p class="text-[10px] text-red-500 uppercase tracking-wider font-semibold">Total</p>
                    <p class="text-lg font-extrabold text-red-600 mt-0.5">{{ number_format($costs['total_cost_cents'] / 100, 4) }}€</p>
                    <p class="text-[10px] text-red-400 mt-0.5">{{ number_format($costs['total_cost_cents'], 4) }}¢</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 4: FUNNEL (full width, vizual clar) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900 mb-5">Funnel Conversie</h2>
        @php
            $fMax = max($funnel['conversations'], 1);
            $funnelSteps = [
                ['label' => 'Conversații', 'value' => $funnel['conversations'], 'bg' => 'bg-slate-200', 'fill' => 'bg-slate-500'],
                ['label' => 'Produse afișate', 'value' => $funnel['products_shown'], 'bg' => 'bg-blue-100', 'fill' => 'bg-blue-500'],
                ['label' => 'Click-uri', 'value' => $funnel['product_clicks'], 'bg' => 'bg-blue-100', 'fill' => 'bg-blue-600'],
                ['label' => 'Add to Cart', 'value' => $funnel['add_to_cart'], 'bg' => 'bg-emerald-100', 'fill' => 'bg-emerald-500'],
                ['label' => 'Cumpărături', 'value' => $funnel['purchases'], 'bg' => 'bg-emerald-100', 'fill' => 'bg-emerald-600'],
            ];
        @endphp
        <div class="space-y-3">
            @foreach($funnelSteps as $i => $step)
            <div class="flex items-center gap-4">
                <div class="w-32 text-right">
                    <span class="text-xs font-medium text-slate-600">{{ $step['label'] }}</span>
                </div>
                <div class="flex-1 {{ $step['bg'] }} rounded-full h-8 overflow-hidden relative">
                    <div class="{{ $step['fill'] }} h-full rounded-full transition-all duration-500 flex items-center" style="width: {{ max(($step['value'] / $fMax) * 100, 3) }}%">
                    </div>
                </div>
                <div class="w-24 flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-900">{{ number_format($step['value']) }}</span>
                    <span class="text-[10px] text-slate-400">{{ round(($step['value'] / $fMax) * 100, 1) }}%</span>
                </div>
                @if($i > 0)
                <div class="w-16 text-right">
                    @php $prev = $funnelSteps[$i-1]['value']; $dropoff = $prev > 0 ? round((1 - $step['value'] / $prev) * 100) : 0; @endphp
                    <span class="text-[10px] font-medium {{ $dropoff > 80 ? 'text-red-500' : ($dropoff > 50 ? 'text-amber-500' : 'text-slate-400') }}">-{{ $dropoff }}%</span>
                </div>
                @else
                <div class="w-16"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 5: ALERTS (doar dacă există) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if($alerts->isNotEmpty())
    <div class="bg-red-50 rounded-2xl p-5 border border-red-200">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
            <h2 class="text-sm font-semibold text-red-800">{{ $alerts->count() }} tenanți cu probleme</h2>
        </div>
        <div class="space-y-2">
            @foreach($alerts->take(8) as $alert)
            <div class="flex items-center justify-between bg-white rounded-xl px-4 py-2.5 border border-red-100">
                <div class="flex items-center gap-3">
                    <span>{{ $alert['status'] === 'critical' ? '🔴' : '🟡' }}</span>
                    <a href="{{ route('admin.tenants.show', $alert['id']) }}" class="text-sm font-medium text-slate-900 hover:text-blue-600">{{ $alert['name'] }}</a>
                    <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">{{ $alert['plan'] }}</span>
                </div>
                <div class="flex gap-1 flex-wrap justify-end">
                    @foreach($alert['warnings'] as $w)
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full {{ str_contains($w, 'Limită') ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $w }}</span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 6: TENANT HEALTH --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Tenant Health</h2>
            <span class="text-xs text-slate-400">{{ $tenantHealth->count() }} tenanți</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider"></th>
                        <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Tenant</th>
                        <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Plan</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Boți</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Conv.</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Woo</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Produse</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">KB</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Voice</th>
                        <th class="text-center px-3 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Billing</th>
                        <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Probleme</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($tenantHealth as $th)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-2.5 text-center">{{ $th['status'] === 'healthy' ? '🟢' : ($th['status'] === 'critical' ? '🔴' : '🟡') }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('admin.tenants.show', $th['id']) }}" class="text-sm font-medium text-slate-900 hover:text-blue-600">{{ $th['name'] }}</a>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $th['plan'] }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-center text-sm text-slate-600">{{ $th['bots_count'] }}</td>
                        <td class="px-3 py-2.5 text-center text-sm text-slate-600">{{ $th['conversations_count'] }}</td>
                        <td class="px-3 py-2.5 text-center">
                            @if($th['woo_connected'])<span class="text-emerald-500">✓</span>@else<span class="text-slate-300">—</span>@endif
                        </td>
                        <td class="px-3 py-2.5 text-center text-sm {{ $th['products'] > 0 ? 'text-slate-600' : 'text-slate-300' }}">{{ number_format($th['products']) }}</td>
                        <td class="px-3 py-2.5 text-center text-sm {{ $th['knowledge'] >= 3 ? 'text-slate-600' : 'text-red-400' }}">{{ $th['knowledge'] }}</td>
                        <td class="px-3 py-2.5 text-center">
                            @if($th['has_voice'])<span class="text-emerald-500">✓</span>@else<span class="text-slate-300">—</span>@endif
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            @if($th['billing_complete'])<span class="text-emerald-500">✓</span>@else<span class="text-red-400">✗</span>@endif
                        </td>
                        <td class="px-4 py-2.5">
                            @foreach(array_slice($th['warnings'], 0, 2) as $w)
                            <span class="text-[10px] {{ str_contains($w, 'Limită') ? 'text-red-500' : 'text-amber-500' }}">{{ $w }}</span>@if(!$loop->last)<span class="text-slate-300"> · </span>@endif
                            @endforeach
                        </td>
                        <td class="px-3 py-2.5">
                            <a href="{{ route('admin.tenants.show', $th['id']) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">→</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 7: TOP TENANTS + FAILED SEARCHES --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Top by Revenue --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Top Revenue</h2>
            @forelse($topTenantsByRevenue as $i => $tt)
            <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                <div class="flex items-center gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <a href="{{ route('admin.tenants.show', $tt['tenant_id']) }}" class="text-sm text-slate-700 hover:text-blue-600">{{ $tt['tenant'] }}</a>
                </div>
                <span class="text-sm font-semibold text-emerald-600">{{ number_format($tt['revenue_cents'] / 100, 0) }} RON</span>
            </div>
            @empty
            <p class="text-xs text-slate-400 py-4">Nicio atribuire încă.</p>
            @endforelse
        </div>

        {{-- Top by Cost --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Top Cost AI</h2>
            @forelse($topTenantsByCost as $i => $tt)
            <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                <div class="flex items-center gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-red-100 text-red-600 text-[10px] font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <a href="{{ route('admin.tenants.show', $tt['tenant_id']) }}" class="text-sm text-slate-700 hover:text-blue-600">{{ $tt['tenant'] }}</a>
                </div>
                <span class="text-sm font-semibold text-red-500">{{ number_format($tt['cost_cents'] / 100, 4) }}€</span>
            </div>
            @empty
            <p class="text-xs text-slate-400 py-4">Niciun cost.</p>
            @endforelse
        </div>

        {{-- Failed searches --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Căutări fără rezultat</h2>
            @forelse($failedSearches as $fs)
            <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                <span class="text-sm text-slate-700 truncate max-w-[180px]">"{{ $fs->query }}"</span>
                <span class="text-xs text-slate-400 font-medium bg-slate-100 px-2 py-0.5 rounded-full">{{ $fs->cnt }}×</span>
            </div>
            @empty
            <p class="text-xs text-slate-400 py-4">Nicio căutare eșuată.</p>
            @endforelse
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECȚIUNEA 8: TREND 7 ZILE --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Trend — Ultimele 7 Zile</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left py-2.5 px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Ziua</th>
                        <th class="text-right py-2.5 px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Conversații</th>
                        <th class="text-right py-2.5 px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Mesaje</th>
                        <th class="text-right py-2.5 px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Leads</th>
                        <th class="text-right py-2.5 px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($trend as $day)
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-2.5 px-3 text-sm text-slate-600">{{ $day['date'] }}</td>
                        <td class="py-2.5 px-3 text-right text-sm font-medium text-slate-900">{{ number_format($day['conversations']) }}</td>
                        <td class="py-2.5 px-3 text-right text-sm text-slate-600">{{ number_format($day['messages']) }}</td>
                        <td class="py-2.5 px-3 text-right text-sm font-medium text-violet-600">{{ $day['leads'] }}</td>
                        <td class="py-2.5 px-3 text-right text-sm font-medium text-emerald-600">{{ number_format($day['revenue_cents'] / 100, 0) }} RON</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Platform footer stats --}}
    <div class="flex items-center justify-center gap-6 text-[10px] text-slate-400 py-2">
        <span>{{ $platform['active_bots'] }} boți activi</span>
        <span>·</span>
        <span>{{ $platform['total_users'] }} utilizatori</span>
        <span>·</span>
        <span>{{ $platform['sessions'] }} sesiuni perioadă</span>
    </div>

</div>
@endsection
