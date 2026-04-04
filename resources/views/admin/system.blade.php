@extends('layouts.admin')

@section('title', 'System Health')
@section('breadcrumb', 'System Health Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">System Health</h1>
            <p class="text-sm text-slate-500 mt-1">Monitorizare infrastructura si performanta platforma</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.system.clearCaches') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Clear Caches
                </button>
            </form>
            <a href="{{ route('admin.system.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-slate-800 rounded-lg hover:bg-slate-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </a>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- 1. INFRASTRUCTURE STATUS --}}
    {{-- ============================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        {{-- Redis --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Redis</h3>
                @if(($infra['redis']['status'] ?? '') === 'ok')
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                @else
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                @endif
            </div>
            @if(($infra['redis']['status'] ?? '') === 'ok')
                <p class="text-2xl font-bold text-slate-900">{{ $infra['redis']['latency_ms'] }} ms</p>
                <p class="text-xs text-slate-500 mt-1">Latency ping</p>
            @else
                <p class="text-sm text-red-600">{{ $infra['redis']['message'] ?? 'Connection failed' }}</p>
            @endif
        </div>

        {{-- PostgreSQL --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">PostgreSQL</h3>
                @if(($infra['database']['status'] ?? '') === 'ok')
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                @else
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                @endif
            </div>
            @if(($infra['database']['status'] ?? '') === 'ok')
                <p class="text-2xl font-bold text-slate-900">{{ $infra['database']['latency_ms'] }} ms</p>
                <p class="text-xs text-slate-500 mt-1">Size: {{ $infra['database']['size'] ?? 'N/A' }} | Connections: {{ $infra['database']['connections'] ?? 'N/A' }}</p>
            @else
                <p class="text-sm text-red-600">{{ $infra['database']['message'] ?? 'Connection failed' }}</p>
            @endif
        </div>

        {{-- Disk --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Disk</h3>
                @if(($infra['disk']['usage_pct'] ?? 0) < 80)
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                @elseif(($infra['disk']['usage_pct'] ?? 0) < 90)
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                @else
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                @endif
            </div>
            <p class="text-2xl font-bold text-slate-900">{{ $infra['disk']['usage_pct'] ?? 0 }}%</p>
            <p class="text-xs text-slate-500 mt-1">{{ $infra['disk']['free'] ?? 'N/A' }} free / {{ $infra['disk']['total'] ?? 'N/A' }} total</p>
            <div class="mt-2 w-full bg-slate-100 rounded-full h-2">
                <div class="h-2 rounded-full {{ ($infra['disk']['usage_pct'] ?? 0) >= 90 ? 'bg-red-500' : (($infra['disk']['usage_pct'] ?? 0) >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $infra['disk']['usage_pct'] ?? 0 }}%"></div>
            </div>
        </div>

        {{-- Memory --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Memory</h3>
                @if(($infra['memory']['usage_pct'] ?? 0) < 80)
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                @elseif(($infra['memory']['usage_pct'] ?? 0) < 90)
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                @else
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                @endif
            </div>
            <p class="text-2xl font-bold text-slate-900">{{ $infra['memory']['usage_pct'] ?? 0 }}%</p>
            <p class="text-xs text-slate-500 mt-1">{{ $infra['memory']['available_gb'] ?? 'N/A' }} GB free / {{ $infra['memory']['total_gb'] ?? 'N/A' }} GB total</p>
            <div class="mt-2 w-full bg-slate-100 rounded-full h-2">
                <div class="h-2 rounded-full {{ ($infra['memory']['usage_pct'] ?? 0) >= 90 ? 'bg-red-500' : (($infra['memory']['usage_pct'] ?? 0) >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $infra['memory']['usage_pct'] ?? 0 }}%"></div>
            </div>
        </div>
    </div>

    {{-- PHP Info row --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">PHP Runtime</h3>
        <div class="flex flex-wrap gap-6 text-sm">
            <div><span class="text-slate-500">Version:</span> <span class="font-medium text-slate-900">{{ $infra['php']['version'] ?? 'N/A' }}</span></div>
            <div><span class="text-slate-500">Memory Limit:</span> <span class="font-medium text-slate-900">{{ $infra['php']['memory_limit'] ?? 'N/A' }}</span></div>
            <div><span class="text-slate-500">Max Execution:</span> <span class="font-medium text-slate-900">{{ $infra['php']['max_execution_time'] ?? 'N/A' }}s</span></div>
            <div>
                <span class="text-slate-500">OPcache:</span>
                @if(!empty($infra['php']['opcache']['opcache_enabled']))
                    <span class="font-medium text-green-600">Enabled</span>
                @else
                    <span class="font-medium text-red-600">Disabled</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- 2. PLATFORM METRICS --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Platform Metrics</h2>
        @if(!empty($metrics['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $metrics['error'] }}</div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                @php
                    $metricCards = [
                        ['label' => 'Conversatii azi', 'value' => $metrics['conversations_today'] ?? 0, 'sub' => ($metrics['conversations_7d'] ?? 0) . ' (7 zile)'],
                        ['label' => 'Mesaje azi', 'value' => $metrics['messages_today'] ?? 0, 'sub' => ''],
                        ['label' => 'Leads azi', 'value' => $metrics['leads_today'] ?? 0, 'sub' => ($metrics['leads_7d'] ?? 0) . ' (7 zile)'],
                        ['label' => 'Apeluri azi', 'value' => $metrics['calls_today'] ?? 0, 'sub' => ''],
                        ['label' => 'Boti activi', 'value' => $metrics['active_bots'] ?? 0, 'sub' => ''],
                        ['label' => 'Tenanti', 'value' => $metrics['total_tenants'] ?? 0, 'sub' => ''],
                        ['label' => 'Utilizatori', 'value' => $metrics['total_users'] ?? 0, 'sub' => ''],
                        ['label' => 'Revenue azi', 'value' => number_format($metrics['revenue_today'] ?? 0, 2) . ' RON', 'sub' => number_format($metrics['revenue_7d'] ?? 0, 2) . ' RON (7 zile)'],
                    ];
                @endphp
                @foreach($metricCards as $card)
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $card['label'] }}</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ $card['value'] }}</p>
                        @if($card['sub'])
                            <p class="text-xs text-slate-400 mt-0.5">{{ $card['sub'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 3. AI API HEALTH --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">AI API Health (24h)</h2>
        @if(!empty($aiHealth['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $aiHealth['error'] }}</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Error Rate</p>
                    <p class="text-2xl font-bold {{ ($aiHealth['error_rate'] ?? 0) > 5 ? 'text-red-600' : (($aiHealth['error_rate'] ?? 0) > 1 ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ number_format($aiHealth['error_rate'] ?? 0, 1) }}%
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg Latency</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $aiHealth['avg_latency'] ?? 0 }} ms</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost Today</p>
                    <p class="text-2xl font-bold text-slate-900">${{ number_format($aiHealth['total_cost_today'] ?? 0, 2) }}</p>
                </div>
            </div>

            @if(!empty($aiHealth['by_provider']) && count($aiHealth['by_provider']))
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Provider</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Model</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Requests</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Avg Latency</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Cost</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Tokens (In/Out)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($aiHealth['by_provider'] as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 font-medium text-slate-900">{{ $row->provider }}</td>
                                    <td class="px-4 py-2.5 text-slate-700">{{ $row->model }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $row->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $row->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-slate-700">{{ number_format($row->cnt) }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-700">{{ round($row->avg_latency) }} ms</td>
                                    <td class="px-4 py-2.5 text-right text-slate-700">${{ number_format(($row->total_cost ?? 0) / 100, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-700">{{ number_format($row->total_input ?? 0) }} / {{ number_format($row->total_output ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 4. QUEUE HEALTH --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Queue Health</h2>
        @if(!empty($queues['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $queues['error'] }}</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Failed Jobs (Total)</p>
                            <p class="text-2xl font-bold {{ ($queues['failed_jobs'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $queues['failed_jobs'] ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Last 24h</p>
                            <p class="text-2xl font-bold {{ ($queues['failed_recent'] ?? 0) > 0 ? 'text-yellow-600' : 'text-green-600' }}">{{ $queues['failed_recent'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-2">
                    <form method="POST" action="{{ route('admin.system.retryAll') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Retry All</button>
                    </form>
                    <form method="POST" action="{{ route('admin.system.clearFailed') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700" onclick="return confirm('Sigur stergi toate job-urile esuate?')">Clear All Failed</button>
                    </form>
                    <form method="POST" action="{{ route('admin.system.reprocessKb') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">Reprocess Failed KB</button>
                    </form>
                </div>
            </div>

            @if(!empty($queues['failed_types']) && count($queues['failed_types']))
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                        <h4 class="text-sm font-semibold text-slate-700">Failed Job Types (7 zile)</h4>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Job Type</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-slate-600">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($queues['failed_types'] as $ft)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 text-slate-700">{{ $ft->job_type ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-slate-900">{{ $ft->cnt }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 4b. SOCIAL MEDIA COSTS --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Social Media AI Costs</h2>
        @if(!empty($socialCosts['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $socialCosts['error'] }}</div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <p class="text-2xl font-bold text-purple-700">${{ number_format($socialCosts['total_today'] ?? 0, 4) }}</p>
                    <p class="text-xs text-purple-600 mt-1">Cost social azi</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <p class="text-2xl font-bold text-purple-700">${{ number_format($socialCosts['total_7d'] ?? 0, 4) }}</p>
                    <p class="text-xs text-purple-600 mt-1">Cost social 7 zile</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <p class="text-2xl font-bold text-blue-700">{{ $socialCosts['posts_published'] ?? 0 }}</p>
                    <p class="text-xs text-blue-600 mt-1">Publicate (7 zile)</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                    <p class="text-2xl font-bold text-amber-700">{{ $socialCosts['posts_scheduled'] ?? 0 }}</p>
                    <p class="text-xs text-amber-600 mt-1">Programate</p>
                </div>
            </div>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase">Tip</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-slate-500 uppercase">Apeluri 7d</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-slate-500 uppercase">Cost azi</th>
                        <th class="text-right py-2 px-3 text-xs font-semibold text-slate-500 uppercase">Cost 7d</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-3 font-medium">📝 Text (GPT-4o-mini)</td>
                        <td class="py-2 px-3 text-right">{{ $socialCosts['text_calls_7d'] ?? 0 }}</td>
                        <td class="py-2 px-3 text-right">${{ number_format($socialCosts['text_today'] ?? 0, 4) }}</td>
                        <td class="py-2 px-3 text-right">${{ number_format($socialCosts['text_7d'] ?? 0, 4) }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-3 font-medium">🖼️ Imagini (Gemini 3.1 Flash)</td>
                        <td class="py-2 px-3 text-right">{{ $socialCosts['image_calls_7d'] ?? 0 }}</td>
                        <td class="py-2 px-3 text-right">${{ number_format($socialCosts['image_today'] ?? 0, 4) }}</td>
                        <td class="py-2 px-3 text-right">${{ number_format($socialCosts['image_7d'] ?? 0, 4) }}</td>
                    </tr>
                </tbody>
            </table>
            @if(($aiHealth['chat_cost_today'] ?? null) !== null)
                <p class="text-xs text-slate-500 mt-3">💬 Cost chat AI azi (fără social): ${{ number_format($aiHealth['chat_cost_today'], 4) }}</p>
            @endif
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 5. KNOWLEDGE BASE HEALTH --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Knowledge Base Health</h2>
        @if(!empty($kbHealth['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $kbHealth['error'] }}</div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Chunks</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($kbHealth['total_chunks'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ready</p>
                    <p class="text-xl font-bold text-green-600 mt-1">{{ number_format($kbHealth['ready'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending</p>
                    <p class="text-xl font-bold text-yellow-600 mt-1">{{ number_format($kbHealth['pending'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Failed</p>
                    <p class="text-xl font-bold text-red-600 mt-1">{{ number_format($kbHealth['failed'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">No Embedding</p>
                    <p class="text-xl font-bold {{ ($kbHealth['without_embedding'] ?? 0) > 0 ? 'text-yellow-600' : 'text-slate-900' }} mt-1">{{ number_format($kbHealth['without_embedding'] ?? 0) }}</p>
                </div>
            </div>

            @if(!empty($kbHealth['failed_details']) && count($kbHealth['failed_details']))
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                        <h4 class="text-sm font-semibold text-slate-700">Failed Documents per Bot</h4>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Bot ID</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-slate-600">Failed Count</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Last Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($kbHealth['failed_details'] as $fd)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 text-slate-700">
                                        <a href="{{ route('admin.bots.show', $fd->bot_id) }}" class="text-blue-600 hover:underline">{{ $fd->bot_id }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-right font-medium text-red-600">{{ $fd->cnt }}</td>
                                    <td class="px-4 py-2 text-slate-500 text-xs max-w-md truncate">{{ \Illuminate\Support\Str::limit($fd->last_error, 120) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 6. SEARCH QUALITY --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Search Quality (7 zile)</h2>
        @if(!empty($searchQuality['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $searchQuality['error'] }}</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Searches</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($searchQuality['total'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Failed (0 results)</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($searchQuality['failed'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Fail Rate</p>
                    <p class="text-2xl font-bold {{ ($searchQuality['fail_rate'] ?? 0) > 20 ? 'text-red-600' : (($searchQuality['fail_rate'] ?? 0) > 10 ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $searchQuality['fail_rate'] ?? 0 }}%
                    </p>
                </div>
            </div>

            @if(!empty($searchQuality['top_failed']) && count($searchQuality['top_failed']))
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                        <h4 class="text-sm font-semibold text-slate-700">Top Failed Queries</h4>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Query</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-slate-600">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($searchQuality['top_failed'] as $sq)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 text-slate-700">{{ \Illuminate\Support\Str::limit($sq->query, 100) }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-slate-900">{{ $sq->cnt }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 7. CONVERSATION RATINGS --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Conversation Ratings (7 zile)</h2>
        @if(!empty($ratings['error']))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $ratings['error'] }}</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Average Rating</p>
                            <p class="text-3xl font-bold text-slate-900">{{ $ratings['avg'] ?? 0 }}</p>
                            <p class="text-xs text-slate-400">din {{ $ratings['total'] ?? 0 }} voturi</p>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Distribution</p>
                            @for($i = 5; $i >= 1; $i--)
                                @php
                                    $count = $ratings['distribution'][$i] ?? 0;
                                    $total = $ratings['total'] ?? 0;
                                    $pct = $total > 0 ? round($count / $total * 100) : 0;
                                @endphp
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs text-slate-500 w-4 text-right">{{ $i }}</span>
                                    <div class="flex-1 bg-slate-100 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $i >= 4 ? 'bg-green-500' : ($i === 3 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-400 w-8">{{ $count }}</span>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                        <h4 class="text-sm font-semibold text-slate-700">Recent Low Ratings (1-2)</h4>
                    </div>
                    @if(!empty($ratings['recent_low']) && count($ratings['recent_low']))
                        <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
                            @foreach($ratings['recent_low'] as $lr)
                                <div class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold">{{ $lr->rating }}</span>
                                        <span class="text-xs text-slate-500">{{ $lr->bot->name ?? 'Bot #' . $lr->bot_id }}</span>
                                        <span class="text-xs text-slate-400 ml-auto">{{ \Carbon\Carbon::parse($lr->created_at)->diffForHumans() }}</span>
                                    </div>
                                    @if($lr->feedback)
                                        <p class="text-xs text-slate-600 mt-1 pl-8">{{ \Illuminate\Support\Str::limit($lr->feedback, 150) }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-4 py-6 text-center text-sm text-slate-400">Niciun rating scazut in ultimele 7 zile.</div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 8. A/B EXPERIMENTS --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">A/B Experiments</h2>
        @if(!empty($experiments) && count($experiments))
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Experiment</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Bot</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Assignments</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($experiments as $exp)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-medium text-slate-900">{{ $exp->name ?? 'Experiment #' . $exp->id }}</td>
                                <td class="px-4 py-2.5 text-slate-700">{{ $exp->bot->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($exp->status ?? '') === 'running' ? 'bg-green-100 text-green-700' : (($exp->status ?? '') === 'completed' ? 'bg-slate-100 text-slate-600' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ $exp->status ?? 'unknown' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right text-slate-700">{{ number_format($exp->assignments_count ?? 0) }}</td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $exp->created_at ? $exp->created_at->diffForHumans() : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 px-4 py-6 text-center text-sm text-slate-400">Niciun experiment gasit.</div>
        @endif
    </div>

    {{-- ============================================================= --}}
    {{-- 9. ERROR LOG --}}
    {{-- ============================================================= --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Error Log (Failed Jobs - Last 20)</h2>
        @if(!empty($errors) && count($errors))
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Job</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Queue</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Error</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Failed At</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($errors as $err)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-slate-500 font-mono text-xs">{{ $err['id'] }}</td>
                                <td class="px-4 py-2.5 font-medium text-slate-900 text-xs">{{ $err['job'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $err['queue'] }}</td>
                                <td class="px-4 py-2.5 text-red-600 text-xs max-w-xs truncate" title="{{ $err['error'] }}">{{ \Illuminate\Support\Str::limit($err['error'], 100) }}</td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($err['failed_at'])->diffForHumans() }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <form method="POST" action="{{ route('admin.system.retryJob', $err['id']) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100">Retry</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 px-4 py-6 text-center text-sm text-slate-400">Niciun job esuat. Totul functioneaza corect.</div>
        @endif
    </div>

</div>
@endsection
