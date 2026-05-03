@extends('layouts.dashboard')

@section('title', 'Analiză')
@section('breadcrumb')
<span class="text-ink font-medium">Analiză</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-ink">Analiză</h1>
        <a href="/dashboard/analiza/export?date_from={{ $dateFrom->toDateString() }}&date_to={{ $dateTo->toDateString() }}"
           class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2 text-sm font-medium text-inkSoft shadow-sm hover:bg-cream transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export CSV
        </a>
    </div>

    {{-- Period selector --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="inline-flex rounded-lg border border-line bg-white p-1 shadow-sm">
            <a href="/dashboard/analiza?period=today"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'today' ? 'bg-coral text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-cream' }}">
                Azi
            </a>
            <a href="/dashboard/analiza?period=week"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'week' ? 'bg-coral text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-cream' }}">
                Săptămâna
            </a>
            <a href="/dashboard/analiza?period=month"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'month' ? 'bg-coral text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-cream' }}">
                Luna
            </a>
            <button type="button" id="custom-period-btn"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'custom' ? 'bg-coral text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-cream' }}">
                Custom
            </button>
        </div>

        {{-- Custom date range --}}
        <form id="custom-period-form" method="GET" action="/dashboard/analiza"
              class="{{ $period === 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="date_from" value="{{ $period === 'custom' ? $dateFrom->toDateString() : now()->subDays(7)->toDateString() }}"
                   class="rounded-lg border border-line bg-white px-3 py-1.5 text-sm text-inkSoft shadow-sm focus:border-coral focus:ring-coral">
            <span class="text-sm text-muted">—</span>
            <input type="date" name="date_to" value="{{ $period === 'custom' ? $dateTo->toDateString() : now()->toDateString() }}"
                   class="rounded-lg border border-line bg-white px-3 py-1.5 text-sm text-inkSoft shadow-sm focus:border-coral focus:ring-coral">
            <button type="submit"
                    class="rounded-lg bg-coral px-3.5 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-coralh transition-colors">
                Aplică
            </button>
        </form>
    </div>

    {{-- Summary metric cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Total apeluri --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Total apeluri</p>
                    <p class="mt-1 text-3xl font-bold text-ink">{{ number_format($totalCalls) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-coralsoft text-coralh">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total minute --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Total minute</p>
                    <p class="mt-1 text-3xl font-bold text-ink">{{ number_format($totalMinutes, 1) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rata completare --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Rata completare</p>
                    <p class="mt-1 text-3xl font-bold text-ink">{{ $completionRate }}%</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Durată medie --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Durată medie</p>
                    <p class="mt-1 text-3xl font-bold text-ink">
                        @if($avgDuration >= 60)
                            {{ floor($avgDuration / 60) }}m {{ $avgDuration % 60 }}s
                        @else
                            {{ $avgDuration }}s
                        @endif
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-coralsoft text-coralh">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Sentiment summary card --}}
    <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-muted">Sentiment mediu clienți</p>
                @if($avgSentiment !== null)
                    @php
                        $sentLabel = $avgSentiment > 0.2 ? 'Pozitiv' : ($avgSentiment < -0.2 ? 'Negativ' : 'Neutru');
                        $sentEmoji = $avgSentiment > 0.2 ? '😊' : ($avgSentiment < -0.2 ? '😟' : '😐');
                        $sentColor = $avgSentiment > 0.2 ? 'text-emerald-600' : ($avgSentiment < -0.2 ? 'text-coral' : 'text-inkSoft');
                    @endphp
                    <p class="mt-1 text-3xl font-bold {{ $sentColor }}">{{ $sentEmoji }} {{ $sentLabel }}</p>
                    <p class="mt-0.5 text-xs text-muted">Scor mediu: {{ number_format($avgSentiment, 2) }}</p>
                @else
                    <p class="mt-1 text-3xl font-bold text-muted">—</p>
                    <p class="mt-0.5 text-xs text-muted">Nicio analiză disponibilă</p>
                @endif
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                </svg>
            </div>
        </div>
        @if($sentimentDistribution->isNotEmpty())
            <div class="mt-3 flex items-center gap-3 text-xs text-muted">
                @if($sentimentDistribution->get('positive'))
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span> {{ $sentimentDistribution->get('positive') }} pozitive</span>
                @endif
                @if($sentimentDistribution->get('neutral'))
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-slate-400"></span> {{ $sentimentDistribution->get('neutral') }} neutre</span>
                @endif
                @if($sentimentDistribution->get('negative'))
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> {{ $sentimentDistribution->get('negative') }} negative</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Charts section --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Volume apeluri - Bar chart --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-ink">Volume apeluri</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="callVolumeChart"></canvas>
            </div>
        </div>

        {{-- Durată medie per zi - Line chart --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-ink">Durată medie per zi</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="avgDurationChart"></canvas>
            </div>
        </div>

        {{-- Distribuție status - Doughnut chart --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-ink">Distribuție status</h3>
            <div class="mt-4 flex items-center justify-center" style="height: 260px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        {{-- Top agenți AI - Horizontal bar chart --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-ink">Top agenți AI</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="topBotsChart"></canvas>
            </div>
        </div>

        {{-- Sentiment distribution - Doughnut chart --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-ink">Distribuție sentiment</h3>
            <div class="mt-4 flex items-center justify-center" style="height: 260px;">
                <canvas id="sentimentChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Bots table --}}
    @if($topBots->isNotEmpty())
    <div class="rounded-xl border border-line bg-white shadow-sm">
        <div class="border-b border-line px-5 py-4">
            <h3 class="text-base font-semibold text-ink">Clasament agenți AI</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-cream/50">
                        <th class="px-5 py-3 font-medium text-muted w-16">#</th>
                        <th class="px-5 py-3 font-medium text-muted">Agent AI</th>
                        <th class="px-5 py-3 font-medium text-muted text-right">Apeluri</th>
                        <th class="px-5 py-3 font-medium text-muted text-right">% din total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($topBots as $index => $bot)
                    <tr class="hover:bg-cream/50 transition-colors">
                        <td class="whitespace-nowrap px-5 py-3 text-muted font-semibold">{{ $index + 1 }}</td>
                        <td class="whitespace-nowrap px-5 py-3 font-medium text-ink">{{ $bot->name }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-muted text-right">{{ number_format($bot->period_calls_count) }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-muted text-right">
                            {{ $totalCalls > 0 ? round(($bot->period_calls_count / $totalCalls) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Conversion funnel — visitor → conv → lead → callback → done --}}
    <div class="mt-6">
        @include('partials.funnel-widget')
    </div>

    {{-- Conversation heatmap — hour x day-of-week, încărcat lazy via JS --}}
    <div x-data="conversationHeatmap()" x-init="load()" class="card overflow-hidden mt-6">
        <div class="px-5 py-3 border-b border-line bg-cream/40 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                </div>
                <h3 class="display text-base font-semibold text-ink">Heatmap conversații (oră × zi)</h3>
                <span x-show="totalConv > 0" class="text-2xs text-muted mono" x-text="'· ' + totalConv + ' total în 30z'"></span>
            </div>
            <span class="text-2xs text-muted">click pe celulă pentru detaliu</span>
        </div>
        <div class="p-5">
            <template x-if="loading">
                <p class="text-sm text-muted text-center py-8">Se încarcă heatmap-ul…</p>
            </template>
            <template x-if="!loading && totalConv === 0">
                <p class="text-sm text-muted text-center py-8">Nicio conversație în ultimele 30 zile.</p>
            </template>
            <div x-show="!loading && totalConv > 0" class="overflow-x-auto">
                {{-- Grid: 7 rows (zile) × 25 cols (ora label + 24 ore) --}}
                <div class="inline-block min-w-full">
                    <div class="grid gap-1" style="grid-template-columns: 32px repeat(24, minmax(20px, 1fr));">
                        {{-- Header: hour labels --}}
                        <div></div>
                        <template x-for="h in 24" :key="'h' + h">
                            <div class="text-2xs text-muted text-center mono" x-text="(h - 1) % 4 === 0 ? (h - 1) : ''"></div>
                        </template>
                        {{-- 7 rows: Monday → Sunday --}}
                        <template x-for="(day, dIdx) in days" :key="'d' + dIdx">
                            <template x-for="(_, hIdx) in 25" :key="'cell-' + dIdx + '-' + hIdx">
                                <template x-if="hIdx === 0">
                                    <div class="text-2xs text-muted text-right pr-2 mono" x-text="day"></div>
                                </template>
                                <template x-if="hIdx > 0">
                                    <div :title="day + ' ' + (hIdx-1) + ':00 — ' + (matrix[dIdx]?.[hIdx-1] || 0) + ' conv'"
                                         :style="{ backgroundColor: cellColor(matrix[dIdx]?.[hIdx-1] || 0) }"
                                         :class="{ 'cursor-pointer': (matrix[dIdx]?.[hIdx-1] || 0) > 0 }"
                                         class="h-6 rounded-sm transition-all hover:scale-110 hover:ring-1 hover:ring-coral hover:z-10 relative">
                                        <span x-show="(matrix[dIdx]?.[hIdx-1] || 0) >= 5" class="text-2xs text-cream font-mono leading-none flex items-center justify-center h-full" x-text="matrix[dIdx]?.[hIdx-1] || ''"></span>
                                    </div>
                                </template>
                            </template>
                        </template>
                    </div>

                    {{-- Color legend --}}
                    <div class="flex items-center justify-end gap-1 mt-3 text-2xs text-muted">
                        <span>0</span>
                        <template x-for="step in 5" :key="'leg' + step">
                            <div class="w-4 h-4 rounded-sm" :style="{ backgroundColor: cellColor((step / 5) * maxCount) }"></div>
                        </template>
                        <span x-text="maxCount + '+'"></span>
                    </div>

                    <p class="text-2xs text-muted mt-2">
                        <strong>Pic activitate:</strong>
                        <span x-text="peakLabel"></span> ·
                        ultimele 30 zile.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function conversationHeatmap() {
    return {
        loading: true,
        matrix: [],         // 7 days × 24 hours, day 0 = Monday
        days: ['L','Ma','Mi','J','V','S','D'],
        totalConv: 0,
        maxCount: 0,
        peakLabel: '—',

        async load() {
            try {
                const r = await fetch('/dashboard/heatmap', { headers: { Accept: 'application/json' } });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const d = await r.json();
                this.matrix = d.matrix || [];
                this.totalConv = d.total || 0;
                this.maxCount = d.max || 0;
                this.peakLabel = d.peak_label || '—';
            } catch (e) {
                console.error('heatmap load failed', e);
            } finally {
                this.loading = false;
            }
        },

        cellColor(count) {
            if (!count || count === 0) return '#F5F1E8'; // bg-cream
            const ratio = Math.min(1, count / Math.max(1, this.maxCount));
            // Coral gradient: light to dark
            //   0   → #FEE2E2 (coralsoft)
            //   0.5 → #FCA5A5 (light coral)
            //   1   → #991B1B (coralh)
            if (ratio < 0.2) return '#FEE2E2';
            if (ratio < 0.4) return '#FCA5A5';
            if (ratio < 0.6) return '#F87171';
            if (ratio < 0.8) return '#DC2626';
            return '#991B1B';
        },
    };
}
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Custom period toggle
    var customBtn = document.getElementById('custom-period-btn');
    var customForm = document.getElementById('custom-period-form');
    if (customBtn && customForm) {
        customBtn.addEventListener('click', function () {
            customForm.classList.toggle('hidden');
            customForm.classList.toggle('flex');
        });
    }

    // Chart data from PHP
    var dailyCalls = @json($dailyCalls);
    var statusDistribution = @json($statusDistribution);
    var topBots = @json($topBots);

    var chartLabels = dailyCalls.map(function (d) { return d.date; });
    var chartCounts = dailyCalls.map(function (d) { return d.count; });
    var chartAvgSeconds = dailyCalls.map(function (d) {
        return d.count > 0 ? Math.round(d.total_seconds / d.count) : 0;
    });

    // Shared chart options
    var sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 12 },
                bodyFont: { size: 12 },
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    precision: 0,
                },
                border: { display: false },
            },
        },
    };

    // 1. Call Volume Bar Chart
    new Chart(document.getElementById('callVolumeChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Apeluri',
                data: chartCounts,
                backgroundColor: '#2563eb',
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 40,
            }],
        },
        options: Object.assign({}, sharedOptions, {
            plugins: Object.assign({}, sharedOptions.plugins, {
                tooltip: Object.assign({}, sharedOptions.plugins.tooltip, {
                    callbacks: {
                        label: function (ctx) { return ctx.parsed.y + ' apeluri'; }
                    }
                })
            })
        }),
    });

    // 2. Avg Duration Line Chart
    new Chart(document.getElementById('avgDurationChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Durată medie (s)',
                data: chartAvgSeconds,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true,
            }],
        },
        options: Object.assign({}, sharedOptions, {
            scales: Object.assign({}, sharedOptions.scales, {
                y: Object.assign({}, sharedOptions.scales.y, {
                    ticks: Object.assign({}, sharedOptions.scales.y.ticks, {
                        callback: function (value) {
                            if (value >= 60) {
                                return Math.floor(value / 60) + 'm ' + (value % 60) + 's';
                            }
                            return value + 's';
                        }
                    })
                })
            }),
            plugins: Object.assign({}, sharedOptions.plugins, {
                tooltip: Object.assign({}, sharedOptions.plugins.tooltip, {
                    callbacks: {
                        label: function (ctx) {
                            var val = ctx.parsed.y;
                            if (val >= 60) {
                                return Math.floor(val / 60) + 'm ' + (val % 60) + 's';
                            }
                            return val + 's';
                        }
                    }
                })
            })
        }),
    });

    // 3. Status Distribution Doughnut Chart
    var statusColorMap = {
        'completed': '#10b981',
        'failed': '#ef4444',
        'in_progress': '#3b82f6',
        'initiated': '#64748b',
        'busy': '#f97316',
        'no_answer': '#9ca3af',
    };
    var statusLabelMap = {
        'completed': 'Finalizat',
        'failed': 'Eșuat',
        'in_progress': 'În desfășurare',
        'initiated': 'Inițiat',
        'busy': 'Ocupat',
        'no_answer': 'Fără răspuns',
    };
    var statusKeys = Object.keys(statusDistribution);
    var statusValues = statusKeys.map(function (k) { return statusDistribution[k]; });
    var statusColors = statusKeys.map(function (k) { return statusColorMap[k] || '#94a3b8'; });
    var statusLabels = statusKeys.map(function (k) { return statusLabelMap[k] || k; });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusValues,
                backgroundColor: statusColors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 12 },
                        color: '#475569',
                    },
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 12 },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                            var pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                            return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            },
        },
    });

    // 4. Sentiment Distribution Doughnut Chart
    var sentimentDistribution = @json($sentimentDistribution);
    var sentimentColorMap = { 'positive': '#10b981', 'neutral': '#94a3b8', 'negative': '#ef4444' };
    var sentimentLabelMap = { 'positive': 'Pozitiv', 'neutral': 'Neutru', 'negative': 'Negativ' };
    var sentimentKeys = Object.keys(sentimentDistribution);
    var sentimentValues = sentimentKeys.map(function (k) { return sentimentDistribution[k]; });
    var sentimentColors = sentimentKeys.map(function (k) { return sentimentColorMap[k] || '#94a3b8'; });
    var sentimentLabels = sentimentKeys.map(function (k) { return sentimentLabelMap[k] || k; });

    if (sentimentValues.length > 0) {
        new Chart(document.getElementById('sentimentChart'), {
            type: 'doughnut',
            data: {
                labels: sentimentLabels,
                datasets: [{
                    data: sentimentValues,
                    backgroundColor: sentimentColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 12 },
                            color: '#475569',
                        },
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                },
            },
        });
    }

    // 5. Top Bots Horizontal Bar Chart
    var botNames = topBots.map(function (b) { return b.name; });
    var botCounts = topBots.map(function (b) { return b.period_calls_count; });

    new Chart(document.getElementById('topBotsChart'), {
        type: 'bar',
        data: {
            labels: botNames,
            datasets: [{
                label: 'Apeluri',
                data: botCounts,
                backgroundColor: [
                    '#2563eb', '#991b1b', '#0891b2', '#059669', '#d97706'
                ],
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 32,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 12 },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function (ctx) { return ctx.parsed.x + ' apeluri'; }
                    }
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 },
                    border: { display: false },
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#334155', font: { size: 12, weight: '500' } },
                    border: { display: false },
                },
            },
        },
    });
});
</script>
@endpush
