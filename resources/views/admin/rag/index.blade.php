@extends('layouts.admin')

@section('title', 'RAG analytics')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">RAG analytics</span>
@endsection

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">RAG analytics</h1>
            <p class="mt-2 text-sm text-muted">Calitatea răspunsurilor knowledge base. Datele se logează automat la fiecare search.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-2xs uppercase tracking-wider text-muted font-semibold">Interval</label>
            <select name="days" onchange="this.form.submit()"
                    class="rounded-lg border border-line bg-white px-3 py-1.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                <option value="1" {{ $days === 1 ? 'selected' : '' }}>1 zi</option>
                <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 zile</option>
                <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 zile</option>
                <option value="90" {{ $days === 90 ? 'selected' : '' }}>90 zile</option>
            </select>
        </form>
    </div>

    {{-- KPI tiles --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @php
            $kpis = [
                ['label' => 'Search-uri totale', 'value' => number_format($totalSearches), 'sub' => 'în interval', 'tint' => 'sky'],
                ['label' => 'Zero results', 'value' => $zeroResultsRate . '%', 'sub' => 'din total', 'tint' => $zeroResultsRate > 20 ? 'coral' : ($zeroResultsRate > 10 ? 'amber' : 'mint')],
                ['label' => 'Score mediu top match', 'value' => $avgTopScore, 'sub' => '0–1 cosine', 'tint' => $avgTopScore < 0.5 ? 'coral' : 'mint'],
                ['label' => 'Reranking activat', 'value' => $rerankRate . '%', 'sub' => 'din search-uri', 'tint' => 'lilac'],
                ['label' => 'Fallback activat', 'value' => $fallbackRate . '%', 'sub' => 'din search-uri', 'tint' => $fallbackRate > 10 ? 'amber' : 'mint'],
            ];
            $tints = [
                'sky'   => ['bg' => 'bg-[#DCEBFA]', 'text' => 'text-[#1E40AF]'],
                'mint'  => ['bg' => 'bg-[#D7EFE0]', 'text' => 'text-emerald-700'],
                'amber' => ['bg' => 'bg-[#FCEEC8]', 'text' => 'text-[#854D0E]'],
                'coral' => ['bg' => 'bg-coralsoft', 'text' => 'text-coralh'],
                'lilac' => ['bg' => 'bg-[#E6DFF3]', 'text' => 'text-[#5B21B6]'],
            ];
        @endphp
        @foreach($kpis as $k)
            @php $t = $tints[$k['tint']]; @endphp
            <div class="card p-4">
                <div class="text-2xs uppercase tracking-wider text-muted font-semibold">{{ $k['label'] }}</div>
                <div class="display text-2xl font-semibold mt-2 mono {{ $t['text'] }}">{{ $k['value'] }}</div>
                <div class="text-2xs text-muted mt-1">{{ $k['sub'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Daily trend (simple inline chart) --}}
    @if($daily->isNotEmpty())
        <div class="card p-5">
            <h2 class="display text-base font-semibold text-ink mb-4">Tendință zilnică</h2>
            @php
                $maxSearches = max($daily->pluck('searches')->all() ?: [1]);
            @endphp
            <div class="flex items-end gap-1 h-32">
                @foreach($daily as $d)
                    @php
                        $h = max(2, round(($d->searches / $maxSearches) * 100));
                        $zeroH = $d->searches > 0 ? round(($d->zeros / $d->searches) * 100) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center group relative">
                        <div class="w-full bg-cream rounded-t-md relative overflow-hidden" style="height: {{ $h }}%; min-height: 4px;">
                            <div class="absolute inset-x-0 bottom-0 bg-coral" style="height: {{ $zeroH }}%;"></div>
                            <div class="absolute inset-x-0 top-0 bg-emerald-500" style="height: {{ 100 - $zeroH }}%;"></div>
                        </div>
                        <div class="text-2xs text-muted mt-1 mono">{{ \Carbon\Carbon::parse($d->date)->format('d') }}</div>
                        <div class="absolute bottom-full mb-2 hidden group-hover:block bg-ink text-cream text-2xs px-2 py-1 rounded whitespace-nowrap z-10">
                            {{ $d->searches }} search · {{ $d->zeros }} zero
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 text-2xs text-muted mt-3">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 bg-emerald-500 rounded-sm"></span> cu match</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 bg-coral rounded-sm"></span> zero results</span>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Top zero-result queries --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-line">
                <h2 class="display text-base font-semibold text-ink">Top queries fără răspuns</h2>
                <p class="text-2xs text-muted mt-0.5">Aici e gap-ul knowledge base — adaugă conținut pe aceste subiecte.</p>
            </div>
            @if($topZeroQueries->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-muted">Niciun query cu 0 rezultate. Excelent!</div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-cream text-2xs uppercase tracking-wider text-muted">
                        <tr>
                            <th class="text-left px-4 py-2 font-semibold">Query</th>
                            <th class="text-right px-4 py-2 font-semibold">Apariții</th>
                            <th class="text-right px-4 py-2 font-semibold">Ultima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($topZeroQueries as $q)
                            <tr>
                                <td class="px-4 py-2 text-xs text-ink">{{ $q->query }}</td>
                                <td class="px-4 py-2 text-right mono text-xs font-semibold text-coralh">{{ $q->occurrences }}</td>
                                <td class="px-4 py-2 text-right text-2xs text-muted">{{ \Carbon\Carbon::parse($q->last_seen)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Worst bots --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-line">
                <h2 class="display text-base font-semibold text-ink">Boții cu cea mai slabă acoperire</h2>
                <p class="text-2xs text-muted mt-0.5">≥5 search-uri în interval, sortat după zero-results rate descrescător.</p>
            </div>
            @if($worstBots->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-muted">Date insuficiente — așteaptă mai multe search-uri.</div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-cream text-2xs uppercase tracking-wider text-muted">
                        <tr>
                            <th class="text-left px-4 py-2 font-semibold">Bot</th>
                            <th class="text-left px-4 py-2 font-semibold">Tenant</th>
                            <th class="text-right px-4 py-2 font-semibold">Searches</th>
                            <th class="text-right px-4 py-2 font-semibold">Zero%</th>
                            <th class="text-right px-4 py-2 font-semibold">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($worstBots as $b)
                            <tr>
                                <td class="px-4 py-2 text-xs">{{ $b->bot_name }}</td>
                                <td class="px-4 py-2 text-2xs text-muted">{{ $b->tenant_name }}</td>
                                <td class="px-4 py-2 text-right mono text-xs">{{ $b->searches }}</td>
                                <td class="px-4 py-2 text-right mono text-xs font-semibold {{ $b->zero_pct > 30 ? 'text-coralh' : ($b->zero_pct > 15 ? 'text-amber-700' : 'text-emerald-700') }}">{{ $b->zero_pct }}%</td>
                                <td class="px-4 py-2 text-right mono text-xs text-muted">{{ $b->avg_score }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>
@endsection
