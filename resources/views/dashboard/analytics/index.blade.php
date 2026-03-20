@extends('layouts.dashboard')

@section('title', 'Analiză')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Analiză</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-900">Analiză</h1>
        <a href="/dashboard/analiza/export?date_from={{ $dateFrom->toDateString() }}&date_to={{ $dateTo->toDateString() }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export CSV
        </a>
    </div>

    {{-- Period selector --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
            <a href="/dashboard/analiza?period=today"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'today' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                Azi
            </a>
            <a href="/dashboard/analiza?period=week"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'week' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                Săptămâna
            </a>
            <a href="/dashboard/analiza?period=month"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'month' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                Luna
            </a>
            <button type="button" id="custom-period-btn"
               class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors {{ $period === 'custom' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                Custom
            </button>
        </div>

        {{-- Custom date range --}}
        <form id="custom-period-form" method="GET" action="/dashboard/analiza"
              class="{{ $period === 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="date_from" value="{{ $period === 'custom' ? $dateFrom->toDateString() : now()->subDays(7)->toDateString() }}"
                   class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <span class="text-sm text-slate-400">—</span>
            <input type="date" name="date_to" value="{{ $period === 'custom' ? $dateTo->toDateString() : now()->toDateString() }}"
                   class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <button type="submit"
                    class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition-colors">
                Aplică
            </button>
        </form>
    </div>

    {{-- Summary metric cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Total apeluri --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total apeluri</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($totalCalls) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total minute --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total minute</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($totalMinutes, 1) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rata completare --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Rata completare</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $completionRate }}%</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Durată medie --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Durată medie</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">
                        @if($avgDuration >= 60)
                            {{ floor($avgDuration / 60) }}m {{ $avgDuration % 60 }}s
                        @else
                            {{ $avgDuration }}s
                        @endif
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts section --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Volume apeluri - Bar chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Volume apeluri</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="callVolumeChart"></canvas>
            </div>
        </div>

        {{-- Durată medie per zi - Line chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Durată medie per zi</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="avgDurationChart"></canvas>
            </div>
        </div>

        {{-- Distribuție status - Doughnut chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Distribuție status</h3>
            <div class="mt-4 flex items-center justify-center" style="height: 260px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        {{-- Top boți - Horizontal bar chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Top boți</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="topBotsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Bots table --}}
    @if($topBots->isNotEmpty())
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Clasament boți</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-5 py-3 font-medium text-slate-500 w-16">#</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Bot</th>
                        <th class="px-5 py-3 font-medium text-slate-500 text-right">Apeluri</th>
                        <th class="px-5 py-3 font-medium text-slate-500 text-right">% din total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($topBots as $index => $bot)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="whitespace-nowrap px-5 py-3 text-slate-400 font-semibold">{{ $index + 1 }}</td>
                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $bot->name }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600 text-right">{{ number_format($bot->period_calls_count) }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600 text-right">
                            {{ $totalCalls > 0 ? round(($bot->calls_count / $totalCalls) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
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

    // 4. Top Bots Horizontal Bar Chart
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
                    '#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706'
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
