@extends('layouts.admin')

@section('title', 'Rapoarte')
@section('breadcrumb', 'Admin / Rapoarte')

@section('content')
<div class="space-y-8">

    {{-- ============================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ============================================================= --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Rapoarte</h1>
                <p class="text-sm text-slate-500 mt-0.5">Analiză detaliată a platformei &mdash; generat {{ $now->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-slate-800 rounded-lg hover:bg-slate-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Reîncarcă
        </a>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 1: SĂNĂTATE SERVICII EXTERNE --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Sănătate Servicii Externe</h2>
        </div>
        <div class="p-6">
            @if($serviceHealth['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $serviceHealth['error'] }}</div>
            @elseif(empty($serviceHealth['providers'] ?? []))
                <p class="text-sm text-slate-500">Nu există date de metrici API disponibile.</p>
            @else
                {{-- Uptime cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min(count($serviceHealth['providers']), 4) }} gap-4 mb-6">
                    @foreach($serviceHealth['providers'] as $provider)
                        @php
                            $uptime = $serviceHealth['uptime'][$provider] ?? ['uptime_pct' => 100, 'total' => 0, 'successes' => 0];
                            $trend = $serviceHealth['error_trends'][$provider] ?? ['current_rate' => 0, 'previous_rate' => 0, 'trend' => 'stable'];
                            $uptimePct = $uptime['uptime_pct'];
                            $uptimeColor = $uptimePct >= 99 ? 'green' : ($uptimePct >= 95 ? 'amber' : 'red');
                        @endphp
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">{{ ucfirst($provider) }}</h3>
                                <span class="w-3 h-3 rounded-full bg-{{ $uptimeColor }}-500"></span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900">{{ number_format($uptimePct, 1) }}%</p>
                            <p class="text-xs text-slate-500">Uptime 24h &bull; {{ number_format($uptime['total']) }} cereri</p>
                            <div class="mt-2 flex items-center gap-1 text-xs">
                                @if($trend['trend'] === 'up')
                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                    <span class="text-red-600">{{ $trend['current_rate'] }}% erori</span>
                                @elseif($trend['trend'] === 'down')
                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    <span class="text-green-600">{{ $trend['current_rate'] }}% erori (scădere)</span>
                                @else
                                    <span class="text-slate-500">{{ $trend['current_rate'] }}% erori (stabil)</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Hourly health timeline per provider --}}
                @foreach($serviceHealth['providers'] as $provider)
                    @php $timeline = $serviceHealth['timeline'][$provider] ?? []; @endphp
                    @if(count($timeline) > 0)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-slate-700 mb-2">{{ ucfirst($provider) }} — Timeline 48h</h4>
                            <div class="flex items-end gap-px overflow-x-auto pb-1">
                                @foreach($timeline as $slot)
                                    @php
                                        $rate = $slot['total'] > 0 ? ($slot['errors'] / $slot['total']) * 100 : 0;
                                        $cellColor = $rate == 0 ? 'bg-green-400' : ($rate < 5 ? 'bg-amber-400' : 'bg-red-500');
                                    @endphp
                                    <div class="group relative">
                                        <div class="w-3 h-6 {{ $cellColor }} rounded-sm cursor-pointer"></div>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10">
                                            {{ $slot['hour'] }}<br>
                                            {{ $slot['success'] }} ok / {{ $slot['errors'] }} erori<br>
                                            Latență: {{ $slot['avg_latency'] }}ms
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-[10px] text-slate-400">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-green-400"></span>OK</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-amber-400"></span>&lt;5% erori</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-red-500"></span>&ge;5% erori</span>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 2: ANALIZĂ COSTURI --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Analiză Costuri</h2>
        </div>
        <div class="p-6">
            @if($costAnalysis['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $costAnalysis['error'] }}</div>
            @else
                {{-- MoM comparison cards --}}
                @php $mom = $costAnalysis['mom'] ?? []; @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cost Luna Aceasta</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format(($mom['this_month']['total'] ?? 0) / 100, 2) }} <span class="text-sm font-normal text-slate-500">RON</span></p>
                        <div class="flex items-center gap-2 mt-1 text-xs">
                            <span class="text-slate-500">AI: {{ number_format(($mom['this_month']['ai'] ?? 0) / 100, 2) }}</span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-500">Voice: {{ number_format(($mom['this_month']['voice'] ?? 0) / 100, 2) }}</span>
                        </div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cost Luna Trecută</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format(($mom['last_month']['total'] ?? 0) / 100, 2) }} <span class="text-sm font-normal text-slate-500">RON</span></p>
                        <div class="flex items-center gap-2 mt-1 text-xs">
                            <span class="text-slate-500">AI: {{ number_format(($mom['last_month']['ai'] ?? 0) / 100, 2) }}</span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-500">Voice: {{ number_format(($mom['last_month']['voice'] ?? 0) / 100, 2) }}</span>
                        </div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Variație Lunară</p>
                        @if($mom['change_pct'] !== null)
                            <p class="text-2xl font-bold {{ $mom['change_pct'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $mom['change_pct'] > 0 ? '+' : '' }}{{ number_format($mom['change_pct'], 1) }}%
                            </p>
                            <p class="text-xs text-slate-500 mt-1">{{ $mom['change_pct'] > 0 ? 'Creștere față de luna trecută' : 'Scădere față de luna trecută' }}</p>
                        @else
                            <p class="text-2xl font-bold text-slate-400">N/A</p>
                            <p class="text-xs text-slate-500 mt-1">Nu există date luna trecută</p>
                        @endif
                    </div>
                </div>

                {{-- Daily cost chart (30 days) --}}
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-slate-700 mb-3">Cost zilnic — ultimele 30 zile (cenți)</h4>
                    <div class="flex items-end gap-px h-24">
                        @foreach($costAnalysis['daily_costs'] as $dc)
                            @php
                                $pct = $costAnalysis['max_daily_cost'] > 0 ? ($dc['total'] / $costAnalysis['max_daily_cost']) * 100 : 0;
                                $aiPct = $dc['total'] > 0 ? ($dc['ai_cost'] / $dc['total']) * $pct : 0;
                                $voicePct = $pct - $aiPct;
                            @endphp
                            <div class="flex-1 flex flex-col justify-end group relative cursor-pointer" style="height: 100%">
                                <div class="bg-blue-500 rounded-t-sm" style="height: {{ max($aiPct, 0) }}%"></div>
                                <div class="bg-purple-400" style="height: {{ max($voicePct, 0) }}%"></div>
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10">
                                    {{ $dc['day'] }}<br>
                                    AI: {{ number_format($dc['ai_cost'] / 100, 2) }} RON<br>
                                    Voice: {{ number_format($dc['voice_cost'] / 100, 2) }} RON<br>
                                    Total: {{ number_format($dc['total'] / 100, 2) }} RON
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-3 mt-2 text-[10px] text-slate-400">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-blue-500"></span>AI</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-purple-400"></span>Voice</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Top 10 tenants by cost --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Top 10 Tenanți — Cost luna aceasta</h4>
                        @if(count($costAnalysis['top_tenants'] ?? []) > 0)
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Tenant</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">AI</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Voice</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($costAnalysis['top_tenants'] as $t)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 font-medium text-slate-700">{{ Str::limit($t['tenant_name'], 25) }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600">{{ number_format($t['ai_cost'] / 100, 2) }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600">{{ number_format($t['voice_cost'] / 100, 2) }}</td>
                                                <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ number_format($t['total'] / 100, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Nu există date.</p>
                        @endif
                    </div>

                    {{-- Cost by AI model --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Cost per Model AI — luna aceasta</h4>
                        @if(count($costAnalysis['cost_by_model'] ?? []) > 0)
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Model</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Cereri</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Tokeni</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($costAnalysis['cost_by_model'] as $m)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 font-medium text-slate-700 text-xs">{{ $m['model'] ?? '—' }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600">{{ number_format($m['requests']) }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600 text-xs">{{ number_format(($m['input_tokens'] ?? 0) + ($m['output_tokens'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ number_format(($m['total_cost'] ?? 0) / 100, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Nu există date.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 3: TENDINȚE UTILIZARE --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Tendințe Utilizare</h2>
        </div>
        <div class="p-6">
            @if($usageTrends['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $usageTrends['error'] }}</div>
            @else
                {{-- Quick stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                        <p class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Tenanți Activi (7z)</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1">{{ $usageTrends['weekly_active_tenants'] ?? 0 }}</p>
                    </div>
                    @php
                        $totalConv30 = array_sum(array_column($usageTrends['daily_stats'] ?? [], 'conversations'));
                        $totalMsg30 = array_sum(array_column($usageTrends['daily_stats'] ?? [], 'messages'));
                        $totalLeads30 = array_sum(array_column($usageTrends['daily_stats'] ?? [], 'leads'));
                    @endphp
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                        <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider">Conversații (30z)</p>
                        <p class="text-2xl font-bold text-indigo-900 mt-1">{{ number_format($totalConv30) }}</p>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                        <p class="text-xs font-semibold text-purple-500 uppercase tracking-wider">Mesaje (30z)</p>
                        <p class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($totalMsg30) }}</p>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                        <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Lead-uri (30z)</p>
                        <p class="text-2xl font-bold text-emerald-900 mt-1">{{ number_format($totalLeads30) }}</p>
                    </div>
                </div>

                {{-- Conversations mini chart --}}
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-slate-700 mb-3">Conversații zilnice — ultimele 30 zile</h4>
                    <div class="flex items-end gap-px h-20">
                        @foreach($usageTrends['daily_stats'] as $ds)
                            @php $pct = $usageTrends['max_conversations'] > 0 ? ($ds['conversations'] / $usageTrends['max_conversations']) * 100 : 0; @endphp
                            <div class="flex-1 bg-indigo-500 rounded-t-sm group relative cursor-pointer" style="height: {{ max($pct, 1) }}%">
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10">
                                    {{ $ds['day'] }}: {{ $ds['conversations'] }} conversații
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Messages mini chart --}}
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-slate-700 mb-3">Mesaje zilnice — ultimele 30 zile</h4>
                    <div class="flex items-end gap-px h-20">
                        @foreach($usageTrends['daily_stats'] as $ds)
                            @php $pct = $usageTrends['max_messages'] > 0 ? ($ds['messages'] / $usageTrends['max_messages']) * 100 : 0; @endphp
                            <div class="flex-1 bg-purple-500 rounded-t-sm group relative cursor-pointer" style="height: {{ max($pct, 1) }}%">
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10">
                                    {{ $ds['day'] }}: {{ number_format($ds['messages']) }} mesaje
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- DAU (7 days) --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Tenanți Activi Zilnic (DAU) — 7 zile</h4>
                        @if(count($usageTrends['dau'] ?? []) > 0)
                            @php $maxDau = max(array_column($usageTrends['dau'], 'active_tenants') ?: [1]); @endphp
                            <div class="space-y-2">
                                @foreach($usageTrends['dau'] as $d)
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-slate-500 w-20 shrink-0">{{ $d['day'] }}</span>
                                        <div class="flex-1 bg-slate-100 rounded-full h-4 overflow-hidden">
                                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ $maxDau > 0 ? ($d['active_tenants'] / $maxDau) * 100 : 0 }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700 w-8 text-right">{{ $d['active_tenants'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Nu există date.</p>
                        @endif
                    </div>

                    {{-- Busiest hours --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Activitate pe Ore — ultimele 7 zile</h4>
                        <div class="flex items-end gap-px h-20">
                            @foreach($usageTrends['hourly_activity'] ?? [] as $hour => $count)
                                @php $pct = $usageTrends['max_hourly'] > 0 ? ($count / $usageTrends['max_hourly']) * 100 : 0; @endphp
                                <div class="flex-1 group relative cursor-pointer flex flex-col items-center justify-end" style="height: 100%">
                                    <div class="w-full bg-cyan-500 rounded-t-sm" style="height: {{ max($pct, 1) }}%"></div>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10">
                                        {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ number_format($count) }} conversații
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex justify-between mt-1 text-[9px] text-slate-400">
                            <span>00</span><span>06</span><span>12</span><span>18</span><span>23</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 4: ANALIZĂ ERORI --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Analiză Erori</h2>
        </div>
        <div class="p-6">
            @if($errorAnalysis['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errorAnalysis['error'] }}</div>
            @else
                {{-- Error rate trend chart --}}
                @if(count($errorAnalysis['error_rate_trend'] ?? []) > 0)
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Rată Erori pe Oră — ultimele 48h</h4>
                        <div class="flex items-end gap-px h-16">
                            @foreach($errorAnalysis['error_rate_trend'] as $ert)
                                @php
                                    $pct = $errorAnalysis['max_error_rate'] > 0 ? ($ert['rate'] / $errorAnalysis['max_error_rate']) * 100 : 0;
                                    $barColor = $ert['rate'] == 0 ? 'bg-green-400' : ($ert['rate'] < 5 ? 'bg-amber-400' : 'bg-red-500');
                                @endphp
                                <div class="flex-1 {{ $barColor }} rounded-t-sm group relative cursor-pointer" style="height: {{ max($pct, 1) }}%">
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10">
                                        {{ $ert['hour'] }}<br>
                                        {{ $ert['errors'] }}/{{ $ert['total'] }} ({{ $ert['rate'] }}%)
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Top errors --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Top Erori — ultimele 7 zile</h4>
                        @if(count($errorAnalysis['top_errors'] ?? []) > 0)
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Tip Eroare</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Provider</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Nr.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($errorAnalysis['top_errors'] as $err)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 text-xs font-mono text-red-700">{{ Str::limit($err['error_type'], 40) }}</td>
                                                <td class="px-3 py-2 text-xs text-slate-600">{{ $err['provider'] }}</td>
                                                <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ number_format($err['cnt']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">Nu au fost detectate erori in ultimele 7 zile.</div>
                        @endif
                    </div>

                    <div class="space-y-6">
                        {{-- Failed jobs --}}
                        <div>
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Job-uri Eșuate — ultimele 7 zile</h4>
                            @if(count($errorAnalysis['failed_jobs'] ?? []) > 0)
                                <div class="space-y-2">
                                    @php $maxFailed = max(array_column($errorAnalysis['failed_jobs'], 'count') ?: [1]); @endphp
                                    @foreach($errorAnalysis['failed_jobs'] as $fj)
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-mono text-slate-600 w-36 shrink-0 truncate" title="{{ $fj['full_class'] }}">{{ $fj['job_class'] }}</span>
                                            <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                                                <div class="h-full bg-red-500 rounded-full" style="width: {{ ($fj['count'] / $maxFailed) * 100 }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-slate-700 w-10 text-right">{{ $fj['count'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">Niciun job eșuat.</div>
                            @endif
                        </div>

                        {{-- Top error bots --}}
                        <div>
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Top 5 Boți cu Erori</h4>
                            @if(count($errorAnalysis['top_error_bots'] ?? []) > 0)
                                <div class="space-y-2">
                                    @php $maxBotErr = max(array_column($errorAnalysis['top_error_bots'], 'error_count') ?: [1]); @endphp
                                    @foreach($errorAnalysis['top_error_bots'] as $eb)
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-slate-600 w-36 shrink-0 truncate">{{ $eb['bot_name'] }}</span>
                                            <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                                                <div class="h-full bg-orange-500 rounded-full" style="width: {{ ($eb['error_count'] / $maxBotErr) * 100 }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-slate-700 w-10 text-right">{{ $eb['error_count'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-400">Niciun bot cu erori.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 5: HANDOFF & CALLBACK --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-fuchsia-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Transferuri & Solicitări Callback</h2>
        </div>
        <div class="p-6">
            @if($handoffCallback['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $handoffCallback['error'] }}</div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Handoff section --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wider">Handoff-uri (30 zile)</h4>

                        {{-- Stats row --}}
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="bg-violet-50 rounded-lg p-3 border border-violet-100 text-center">
                                <p class="text-xl font-bold text-violet-900">{{ $handoffCallback['handoff']['total'] ?? 0 }}</p>
                                <p class="text-[10px] text-violet-500 uppercase">Total</p>
                            </div>
                            <div class="bg-amber-50 rounded-lg p-3 border border-amber-100 text-center">
                                <p class="text-xl font-bold text-amber-900">{{ $handoffCallback['handoff']['by_status']['pending'] ?? 0 }}</p>
                                <p class="text-[10px] text-amber-500 uppercase">În așteptare</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 border border-green-100 text-center">
                                <p class="text-xl font-bold text-green-900">{{ $handoffCallback['handoff']['by_status']['resolved'] ?? 0 }}</p>
                                <p class="text-[10px] text-green-500 uppercase">Rezolvate</p>
                            </div>
                        </div>

                        @if($handoffCallback['handoff']['avg_resolution_minutes'])
                            <p class="text-xs text-slate-500 mb-4">Timp mediu rezolvare: <span class="font-semibold text-slate-700">{{ number_format($handoffCallback['handoff']['avg_resolution_minutes'], 0) }} min</span></p>
                        @endif

                        {{-- Status breakdown --}}
                        @if(count($handoffCallback['handoff']['by_status'] ?? []) > 0)
                            @php $handoffMax = max($handoffCallback['handoff']['by_status']) ?: 1; @endphp
                            <div class="space-y-1 mb-4">
                                @foreach($handoffCallback['handoff']['by_status'] as $status => $cnt)
                                    @php
                                        $statusColors = ['pending' => 'bg-amber-500', 'sent' => 'bg-blue-500', 'resolved' => 'bg-green-500', 'expired' => 'bg-slate-400'];
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-500 w-16 text-right">{{ $status }}</span>
                                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-full {{ $statusColors[$status] ?? 'bg-slate-400' }} rounded-full" style="width: {{ ($cnt / $handoffMax) * 100 }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-600 w-8">{{ $cnt }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Unresolved handoffs --}}
                        <details class="group">
                            <summary class="text-xs font-medium text-slate-600 cursor-pointer hover:text-slate-800">
                                Ultimele handoff-uri nerezolvate ({{ count($handoffCallback['unresolved_handoffs'] ?? []) }})
                                <svg class="inline w-3 h-3 ml-0.5 transform group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            @if(count($handoffCallback['unresolved_handoffs'] ?? []) > 0)
                                <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Tenant</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Bot</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Motiv</th>
                                                <th class="text-right px-2 py-1.5 font-semibold text-slate-500">Vârstă</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($handoffCallback['unresolved_handoffs'] as $uh)
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-2 py-1.5 text-slate-700">{{ Str::limit($uh['tenant_name'], 15) }}</td>
                                                    <td class="px-2 py-1.5 text-slate-600">{{ Str::limit($uh['bot_name'], 15) }}</td>
                                                    <td class="px-2 py-1.5 text-slate-600">{{ Str::limit($uh['trigger_reason'], 20) }}</td>
                                                    <td class="px-2 py-1.5 text-right {{ $uh['age_hours'] > 24 ? 'text-red-600 font-semibold' : 'text-slate-600' }}">{{ $uh['age_hours'] }}h</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="mt-2 text-xs text-green-600">Toate handoff-urile sunt rezolvate.</p>
                            @endif
                        </details>
                    </div>

                    {{-- Callback section --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wider">Callback-uri (30 zile)</h4>

                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="bg-fuchsia-50 rounded-lg p-3 border border-fuchsia-100 text-center">
                                <p class="text-xl font-bold text-fuchsia-900">{{ $handoffCallback['callback']['total'] ?? 0 }}</p>
                                <p class="text-[10px] text-fuchsia-500 uppercase">Total</p>
                            </div>
                            <div class="bg-amber-50 rounded-lg p-3 border border-amber-100 text-center">
                                <p class="text-xl font-bold text-amber-900">{{ $handoffCallback['callback']['by_status']['pending'] ?? 0 }}</p>
                                <p class="text-[10px] text-amber-500 uppercase">În așteptare</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 border border-green-100 text-center">
                                <p class="text-xl font-bold text-green-900">{{ $handoffCallback['callback']['by_status']['completed'] ?? 0 }}</p>
                                <p class="text-[10px] text-green-500 uppercase">Finalizate</p>
                            </div>
                        </div>

                        <div class="flex gap-4 text-xs text-slate-500 mb-4">
                            @if($handoffCallback['callback']['avg_confirm_minutes'])
                                <span>Confirmare medie: <span class="font-semibold text-slate-700">{{ number_format($handoffCallback['callback']['avg_confirm_minutes'], 0) }} min</span></span>
                            @endif
                            @if($handoffCallback['callback']['avg_complete_minutes'])
                                <span>Finalizare medie: <span class="font-semibold text-slate-700">{{ number_format($handoffCallback['callback']['avg_complete_minutes'], 0) }} min</span></span>
                            @endif
                        </div>

                        {{-- Status breakdown --}}
                        @if(count($handoffCallback['callback']['by_status'] ?? []) > 0)
                            @php $cbMax = max($handoffCallback['callback']['by_status']) ?: 1; @endphp
                            <div class="space-y-1 mb-4">
                                @foreach($handoffCallback['callback']['by_status'] as $status => $cnt)
                                    @php
                                        $cbColors = ['pending' => 'bg-amber-500', 'confirmed' => 'bg-blue-500', 'completed' => 'bg-green-500', 'cancelled' => 'bg-slate-400', 'no_answer' => 'bg-red-400'];
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-500 w-16 text-right">{{ $status }}</span>
                                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-full {{ $cbColors[$status] ?? 'bg-slate-400' }} rounded-full" style="width: {{ ($cnt / $cbMax) * 100 }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-600 w-8">{{ $cnt }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Pending callbacks --}}
                        <details class="group">
                            <summary class="text-xs font-medium text-slate-600 cursor-pointer hover:text-slate-800">
                                Callback-uri în așteptare ({{ count($handoffCallback['pending_callbacks'] ?? []) }})
                                <svg class="inline w-3 h-3 ml-0.5 transform group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            @if(count($handoffCallback['pending_callbacks'] ?? []) > 0)
                                <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Nume</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Telefon</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Data pref.</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Interval</th>
                                                <th class="text-right px-2 py-1.5 font-semibold text-slate-500">Creat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($handoffCallback['pending_callbacks'] as $pc)
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-2 py-1.5 text-slate-700">{{ Str::limit($pc['name'] ?? '—', 15) }}</td>
                                                    <td class="px-2 py-1.5 text-slate-600 font-mono">{{ $pc['phone'] ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 text-slate-600">{{ $pc['preferred_date'] ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 text-slate-600">{{ $pc['preferred_time_slot'] ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 text-right text-slate-500">{{ $pc['created_at'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="mt-2 text-xs text-green-600">Niciun callback în așteptare.</p>
                            @endif
                        </details>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 6: PROFITABILITATE PER TENANT --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Profitabilitate per Tenant</h2>
        </div>
        <div class="p-6">
            @if($profitability['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $profitability['error'] }}</div>
            @else
                {{-- Platform totals --}}
                @php $pf = $profitability['platform'] ?? []; @endphp
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                        <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Venituri Platformă</p>
                        <p class="text-xl font-bold text-emerald-900 mt-1">{{ number_format(($pf['revenue'] ?? 0) / 100, 2) }} <span class="text-sm font-normal">RON</span></p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                        <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Costuri Platformă</p>
                        <p class="text-xl font-bold text-red-900 mt-1">{{ number_format(($pf['cost'] ?? 0) / 100, 2) }} <span class="text-sm font-normal">RON</span></p>
                    </div>
                    <div class="bg-teal-50 rounded-xl p-4 border border-teal-100">
                        <p class="text-xs font-semibold text-teal-500 uppercase tracking-wider">Marjă Netă</p>
                        <p class="text-xl font-bold {{ ($pf['margin'] ?? 0) >= 0 ? 'text-teal-900' : 'text-red-900' }} mt-1">{{ number_format(($pf['margin'] ?? 0) / 100, 2) }} <span class="text-sm font-normal">RON</span></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Marjă %</p>
                        <p class="text-xl font-bold {{ ($pf['margin_pct'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-700' }} mt-1">{{ number_format($pf['margin_pct'] ?? 0, 1) }}%</p>
                    </div>
                </div>

                {{-- Tenant profitability table --}}
                @if(count($profitability['tenants'] ?? []) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">#</th>
                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Tenant</th>
                                    <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Venituri</th>
                                    <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Cost AI</th>
                                    <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Cost Voice</th>
                                    <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Cost Total</th>
                                    <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Marjă</th>
                                    <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($profitability['tenants'] as $idx => $pt)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-2 text-slate-400">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 font-medium text-slate-700">{{ Str::limit($pt['tenant_name'], 30) }}</td>
                                        <td class="px-3 py-2 text-right text-emerald-700 font-medium">{{ number_format($pt['revenue'] / 100, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-slate-600">{{ number_format($pt['ai_cost'] / 100, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-slate-600">{{ number_format($pt['voice_cost'] / 100, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-red-600">{{ number_format($pt['total_cost'] / 100, 2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold {{ $pt['margin'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($pt['margin'] / 100, 2) }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $pt['margin_pct'] >= 50 ? 'bg-emerald-100 text-emerald-800' : ($pt['margin_pct'] >= 0 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                                {{ number_format($pt['margin_pct'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-slate-400">Nu există date de profitabilitate luna aceasta.</p>
                @endif
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 7: KNOWLEDGE PIPELINE --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Knowledge Pipeline</h2>
        </div>
        <div class="p-6">
            @if($knowledgePipeline['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $knowledgePipeline['error'] }}</div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                    {{-- Agent runs --}}
                    <div class="bg-sky-50 rounded-xl p-4 border border-sky-100">
                        <p class="text-xs font-semibold text-sky-500 uppercase tracking-wider mb-2">Agent Runs</p>
                        <p class="text-2xl font-bold text-sky-900">{{ number_format($knowledgePipeline['agent_runs']['total'] ?? 0) }}</p>
                        <p class="text-xs text-sky-600 mt-1">Media: {{ number_format($knowledgePipeline['agent_runs']['avg_tokens'] ?? 0) }} tokeni</p>
                        @if(count($knowledgePipeline['agent_runs']['by_status'] ?? []) > 0)
                            <div class="mt-2 space-y-0.5">
                                @foreach($knowledgePipeline['agent_runs']['by_status'] as $st => $cnt)
                                    @php $stColor = match($st) { 'completed' => 'text-green-600', 'failed' => 'text-red-600', 'running' => 'text-blue-600', default => 'text-slate-500' }; @endphp
                                    <p class="text-[10px] {{ $stColor }}">{{ $st }}: {{ $cnt }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Website scans --}}
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                        <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-2">Website Scans</p>
                        <p class="text-2xl font-bold text-indigo-900">{{ number_format($knowledgePipeline['website_scans']['total'] ?? 0) }}</p>
                        <p class="text-xs text-indigo-600 mt-1">Media: {{ number_format($knowledgePipeline['website_scans']['avg_pages'] ?? 0, 1) }} pagini</p>
                        @if(count($knowledgePipeline['website_scans']['by_status'] ?? []) > 0)
                            <div class="mt-2 space-y-0.5">
                                @foreach($knowledgePipeline['website_scans']['by_status'] as $st => $cnt)
                                    @php $stColor = match($st) { 'completed' => 'text-green-600', 'failed' => 'text-red-600', 'scanning', 'pending' => 'text-blue-600', default => 'text-slate-500' }; @endphp
                                    <p class="text-[10px] {{ $stColor }}">{{ $st }}: {{ $cnt }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Connectors --}}
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                        <p class="text-xs font-semibold text-purple-500 uppercase tracking-wider mb-2">Conectori</p>
                        @php $connTotal = array_sum($knowledgePipeline['connectors']['by_type'] ?? []); @endphp
                        <p class="text-2xl font-bold text-purple-900">{{ $connTotal }}</p>
                        <p class="text-xs text-purple-600 mt-1">{{ $knowledgePipeline['connectors']['stale_count'] ?? 0 }} expirate (&gt;7z)</p>
                        @if(count($knowledgePipeline['connectors']['by_type'] ?? []) > 0)
                            <div class="mt-2 space-y-0.5">
                                @foreach($knowledgePipeline['connectors']['by_type'] as $type => $cnt)
                                    <p class="text-[10px] text-slate-600">{{ $type }}: {{ $cnt }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Knowledge items --}}
                    <div class="bg-teal-50 rounded-xl p-4 border border-teal-100">
                        <p class="text-xs font-semibold text-teal-500 uppercase tracking-wider mb-2">Knowledge Items</p>
                        @php $kbTotal = array_sum($knowledgePipeline['knowledge_items']['by_status'] ?? []); @endphp
                        <p class="text-2xl font-bold text-teal-900">{{ number_format($kbTotal) }}</p>
                        @if(count($knowledgePipeline['knowledge_items']['by_status'] ?? []) > 0)
                            <div class="mt-2 space-y-0.5">
                                @foreach($knowledgePipeline['knowledge_items']['by_status'] as $st => $cnt)
                                    @php $stColor = match($st) { 'ready' => 'text-green-600', 'failed' => 'text-red-600', 'pending', 'processing' => 'text-blue-600', default => 'text-slate-500' }; @endphp
                                    <p class="text-[10px] {{ $stColor }}">{{ $st }}: {{ number_format($cnt) }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Agent runs by slug --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Rulări per Agent</h4>
                        @if(count($knowledgePipeline['agent_runs']['by_slug'] ?? []) > 0)
                            @php $maxSlug = max(array_column($knowledgePipeline['agent_runs']['by_slug'], 'cnt') ?: [1]); @endphp
                            <div class="space-y-2">
                                @foreach($knowledgePipeline['agent_runs']['by_slug'] as $as)
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-slate-600 w-32 shrink-0 truncate font-mono">{{ $as['agent_slug'] }}</span>
                                        <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                                            <div class="h-full bg-sky-500 rounded-full" style="width: {{ ($as['cnt'] / $maxSlug) * 100 }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-500 w-20 text-right">{{ $as['cnt'] }} ({{ number_format($as['avg_tokens'] ?? 0) }}t)</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Nu există rulări.</p>
                        @endif
                    </div>

                    {{-- Recent KB failures --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Eșecuri Recente Knowledge Base</h4>
                        @if(count($knowledgePipeline['knowledge_items']['recent_failures'] ?? []) > 0)
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Titlu</th>
                                            <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Bot ID</th>
                                            <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Eroare</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($knowledgePipeline['knowledge_items']['recent_failures'] as $rf)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-2 py-1.5 text-slate-700">{{ Str::limit($rf['title'] ?? '—', 25) }}</td>
                                                <td class="px-2 py-1.5 text-slate-500">#{{ $rf['bot_id'] }}</td>
                                                <td class="px-2 py-1.5 text-red-600 font-mono">{{ Str::limit($rf['error_message'] ?? '—', 30) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">Niciun eșec recent.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- SECTION 8: WEBHOOK & INTEGRATION HEALTH --}}
    {{-- ============================================================= --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">Webhook & Integrări</h2>
        </div>
        <div class="p-6">
            @if($integrationHealth['error'])
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $integrationHealth['error'] }}</div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Connector health by type --}}
                    <div>
                        <h4 class="text-sm font-medium text-slate-700 mb-3">Stare Conectori per Tip</h4>
                        @if(count($integrationHealth['connector_health'] ?? []) > 0)
                            <div class="space-y-3">
                                @foreach($integrationHealth['connector_health'] as $type => $statuses)
                                    @php
                                        $total = array_sum($statuses);
                                        $connected = $statuses['connected'] ?? 0;
                                        $healthPct = $total > 0 ? round(($connected / $total) * 100) : 0;
                                    @endphp
                                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-slate-700">{{ ucfirst($type) }}</span>
                                            <span class="text-xs px-2 py-0.5 rounded font-medium {{ $healthPct >= 80 ? 'bg-green-100 text-green-800' : ($healthPct >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">{{ $healthPct }}% conectate</span>
                                        </div>
                                        <div class="flex gap-2 text-[10px]">
                                            @foreach($statuses as $st => $cnt)
                                                @php $dotColor = match($st) { 'connected' => 'bg-green-500', 'error' => 'bg-red-500', 'syncing' => 'bg-blue-500', default => 'bg-slate-400' }; @endphp
                                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>{{ $st }}: {{ $cnt }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Nu există conectori.</p>
                        @endif
                    </div>

                    <div class="space-y-6">
                        {{-- Stale connectors --}}
                        <div>
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Conectori Vechi (nesincronizați &gt;24h)</h4>
                            @if(count($integrationHealth['stale_connectors'] ?? []) > 0)
                                <div class="overflow-hidden rounded-lg border border-slate-200">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Tip</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Bot</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Status</th>
                                                <th class="text-left px-2 py-1.5 font-semibold text-slate-500">Ultima Sincr.</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($integrationHealth['stale_connectors'] as $sc)
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-2 py-1.5 text-slate-700 font-medium">{{ $sc['type'] }}</td>
                                                    <td class="px-2 py-1.5 text-slate-600">{{ Str::limit($sc['bot_name'], 20) }}</td>
                                                    <td class="px-2 py-1.5">
                                                        @php $scColor = match($sc['status']) { 'connected' => 'bg-green-100 text-green-700', 'error' => 'bg-red-100 text-red-700', default => 'bg-slate-100 text-slate-600' }; @endphp
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $scColor }}">{{ $sc['status'] }}</span>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-slate-500">
                                                        {{ $sc['last_synced_at'] }}
                                                        @if($sc['hours_since_sync'])
                                                            <span class="text-red-500">({{ $sc['hours_since_sync'] }}h)</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">Toți conectorii sunt sincronizați recent.</div>
                            @endif
                        </div>

                        {{-- Chat events --}}
                        <div>
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Evenimente Chat — ultimele 24h
                                <span class="ml-2 text-xs font-normal text-slate-400">(total: {{ number_format($integrationHealth['chat_event_total'] ?? 0) }})</span>
                            </h4>
                            @if(count($integrationHealth['chat_event_stats'] ?? []) > 0)
                                @php $maxEvt = max(array_column($integrationHealth['chat_event_stats'], 'cnt') ?: [1]); @endphp
                                <div class="space-y-1.5">
                                    @foreach($integrationHealth['chat_event_stats'] as $evt)
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] text-slate-600 w-32 shrink-0 truncate font-mono" title="{{ $evt['event_name'] }}">{{ $evt['event_name'] }}</span>
                                            <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                                <div class="h-full bg-pink-500 rounded-full" style="width: {{ ($evt['cnt'] / $maxEvt) * 100 }}%"></div>
                                            </div>
                                            <span class="text-[10px] font-semibold text-slate-600 w-10 text-right">{{ number_format($evt['cnt']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-400">Nu există evenimente în ultimele 24h.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center text-xs text-slate-400 pb-4">
        Raport generat la {{ $now->format('d/m/Y H:i:s') }} (UTC{{ $now->format('P') }})
    </div>

</div>
@endsection
