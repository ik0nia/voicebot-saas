@extends('layouts.admin')

@section('title', 'Analytics chips')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Analytics chips</h1>
            <p class="text-sm text-slate-500 mt-1">
                Conversion per quick-reply label — ultimele <strong>{{ $days }}</strong> zile
                (din {{ $since->format('d M Y') }}).
            </p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-600">Perioadă:</label>
            <select name="days" onchange="this.form.submit()"
                    class="rounded border border-slate-300 text-sm px-2 py-1.5 bg-white">
                @foreach([7, 14, 30, 60, 90] as $d)
                    <option value="{{ $d }}" @if($d == $days) selected @endif>{{ $d }} zile</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Overall KPIs --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">Chips afișate</div>
            <div class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($totalShown) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">Click-uri</div>
            <div class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($totalClicked) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">CTR global</div>
            <div class="text-3xl font-bold text-red-700 mt-1">{{ $overallCtr }}%</div>
        </div>
    </div>

    {{-- User-state distribution --}}
    @if(!empty($stateCounts))
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
            <div class="text-sm font-semibold text-slate-700 mb-2">Distribuție stări utilizator</div>
            <div class="flex flex-wrap gap-2">
                @foreach($stateCounts as $state => $count)
                    <div class="px-3 py-1.5 rounded-full text-xs bg-slate-100 text-slate-700">
                        <strong>{{ $state }}</strong>: {{ number_format($count) }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Per-chip table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-700">Top chips (sortate după click-uri)</div>
            <div class="text-xs text-slate-500 mt-0.5">
                Un rând per combinație label × page_type. Etichetele care apar fără click-uri
                sunt candidate pentru rewrite.
            </div>
        </div>
        @if(count($rows) === 0)
            <div class="p-6 text-center text-sm text-slate-500">
                Niciun eveniment încă — chips trebuie afișate și click-uite în widget
                pentru ca rapoartele să apară aici.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-2">Label</th>
                            <th class="text-left px-4 py-2">Page type</th>
                            <th class="text-right px-4 py-2">Afișări</th>
                            <th class="text-right px-4 py-2">Click-uri</th>
                            <th class="text-right px-4 py-2">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($rows as $r)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-2 font-medium text-slate-900">{{ $r['label'] }}</td>
                                <td class="px-4 py-2 text-slate-500">{{ $r['page_type'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format($r['shown']) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-emerald-700 font-semibold">
                                    {{ number_format($r['clicked']) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    @if($r['ctr'] === null)
                                        <span class="text-slate-300">—</span>
                                    @else
                                        <span class="@if($r['ctr'] >= 20) text-emerald-700 font-semibold @elseif($r['ctr'] < 5) text-slate-400 @else text-slate-700 @endif">
                                            {{ $r['ctr'] }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="text-xs text-slate-400 mt-4">
        CTR = click-uri / afișări per label. Afișările cresc la fiecare chip strip rendered server-side (chip_shown).
        Click-urile vin de pe widget (quick_reply_clicked). Etichete noi apar imediat ce sunt afișate prima dată.
    </p>
</div>
@endsection
