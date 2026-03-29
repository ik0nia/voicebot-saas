@extends('layouts.dashboard')
@section('title', 'Conversii & Revenue')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Conversii</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Period selector --}}
    <form method="GET" class="flex gap-3 items-end">
        <div>
            <label class="text-xs text-slate-500">De la</label>
            <input type="date" name="from" value="{{ $from }}" class="block mt-1 border-slate-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="text-xs text-slate-500">Până la</label>
            <input type="date" name="to" value="{{ $to }}" class="block mt-1 border-slate-300 rounded-lg text-sm">
        </div>
        <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm">Aplică</button>
    </form>

    {{-- Funnel --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-900 mb-4">Funnel Conversie</h3>
        @php
            $max = max($funnel['conversations'], 1);
            $bars = [
                ['label' => 'Conversații', 'value' => $funnel['conversations'], 'color' => 'bg-slate-400'],
                ['label' => 'Produse afișate', 'value' => $funnel['products_shown'], 'color' => 'bg-blue-400'],
                ['label' => 'Click-uri produse', 'value' => $funnel['product_clicks'], 'color' => 'bg-blue-600'],
                ['label' => 'Add to Cart', 'value' => $funnel['add_to_cart'], 'color' => 'bg-green-500'],
                ['label' => 'Cumpărături', 'value' => $funnel['purchases'], 'color' => 'bg-purple-600'],
            ];
        @endphp
        <div class="space-y-3">
            @foreach($bars as $bar)
            <div class="flex items-center gap-3">
                <div class="w-36 text-sm text-slate-600">{{ $bar['label'] }}</div>
                <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                    <div class="{{ $bar['color'] }} h-full rounded-full transition-all" style="width: {{ ($bar['value'] / $max) * 100 }}%"></div>
                </div>
                <div class="w-20 text-right text-sm font-semibold text-slate-900">{{ number_format($bar['value']) }}</div>
                <div class="w-16 text-right text-xs text-slate-500">{{ $max > 0 ? round(($bar['value'] / $max) * 100, 1) : 0 }}%</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Revenue + Attribution --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm text-slate-500">Venit Total Atribuit</div>
            <div class="text-3xl font-bold text-green-600">{{ number_format($totalRevenue / 100, 2) }} RON</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm text-slate-500">Leads → Clienți</div>
            <div class="text-2xl font-bold text-blue-600">{{ $leadStats['converted_leads'] }} / {{ $leadStats['total_leads'] }}</div>
            <div class="text-xs text-slate-400">{{ $leadStats['total_leads'] > 0 ? round(($leadStats['converted_leads'] / $leadStats['total_leads']) * 100) : 0 }}% rată conversie</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm text-slate-500">Oportunități active</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $leadStats['total_opportunities'] }}</div>
        </div>
    </div>

    {{-- Attribution breakdown --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3">Atribuire Conversii</h3>
        <table class="w-full text-sm">
            <thead><tr><th class="text-left py-2 text-slate-500">Tip</th><th class="text-right py-2 text-slate-500">Conversii</th><th class="text-right py-2 text-slate-500">Revenue</th></tr></thead>
            <tbody>
                @foreach(['strict' => '🟢 Directe din bot', 'probable' => '🟡 Influențate', 'assisted' => '🔵 Asistate'] as $mode => $label)
                <tr class="border-t border-slate-100">
                    <td class="py-2">{{ $label }}</td>
                    <td class="py-2 text-right font-medium">{{ $attribution[$mode]->cnt ?? 0 }}</td>
                    <td class="py-2 text-right font-medium">{{ number_format(($attribution[$mode]->revenue ?? 0) / 100, 2) }} RON</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Top failed searches --}}
    @if($failedSearches->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3">Top Căutări Fără Rezultat</h3>
        <div class="space-y-1">
            @foreach($failedSearches as $fs)
            <div class="flex justify-between text-sm py-1 border-b border-slate-50">
                <span class="text-slate-700">"{{ $fs->query }}"</span>
                <span class="text-slate-500">{{ $fs->cnt }}×</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
