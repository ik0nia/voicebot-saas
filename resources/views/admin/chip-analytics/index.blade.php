@extends('layouts.admin')

@section('title', 'Analytics chips')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-ink">Analytics chips</h1>
            <p class="text-sm text-muted mt-1">
                Conversion per quick-reply label — ultimele <strong>{{ $days }}</strong> zile
                (din {{ $since->format('d M Y') }}).
            </p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-muted">Perioadă:</label>
            <select name="days" onchange="this.form.submit()"
                    class="rounded border border-line text-sm px-2 py-1.5 bg-white">
                @foreach([7, 14, 30, 60, 90] as $d)
                    <option value="{{ $d }}" @if($d == $days) selected @endif>{{ $d }} zile</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Overall KPIs --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-line p-4">
            <div class="text-xs font-semibold text-muted uppercase">Chips afișate</div>
            <div class="text-3xl font-bold text-ink mt-1">{{ number_format($totalShown) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-line p-4">
            <div class="text-xs font-semibold text-muted uppercase">Click-uri</div>
            <div class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($totalClicked) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-line p-4">
            <div class="text-xs font-semibold text-muted uppercase">CTR global</div>
            <div class="text-3xl font-bold text-coralh mt-1">{{ $overallCtr }}%</div>
        </div>
    </div>

    {{-- User-state distribution --}}
    @if(!empty($stateCounts))
        <div class="bg-white rounded-xl border border-line p-4 mb-6">
            <div class="text-sm font-semibold text-inkSoft mb-2">Distribuție stări utilizator</div>
            <div class="flex flex-wrap gap-2">
                @foreach($stateCounts as $state => $count)
                    <div class="px-3 py-1.5 rounded-full text-xs bg-cream text-inkSoft">
                        <strong>{{ $state }}</strong>: {{ number_format($count) }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Per-chip table --}}
    <div class="bg-white rounded-xl border border-line overflow-hidden">
        <div class="px-4 py-3 border-b border-line">
            <div class="text-sm font-semibold text-inkSoft">Top chips (sortate după click-uri)</div>
            <div class="text-xs text-muted mt-0.5">
                Un rând per combinație label × page_type. Etichetele care apar fără click-uri
                sunt candidate pentru rewrite.
            </div>
        </div>
        @if(count($rows) === 0)
            <div class="p-6 text-center text-sm text-muted">
                Niciun eveniment încă — chips trebuie afișate și click-uite în widget
                pentru ca rapoartele să apară aici.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-muted bg-cream">
                        <tr>
                            <th class="text-left px-4 py-2">Label</th>
                            <th class="text-left px-4 py-2">Page type</th>
                            <th class="text-right px-4 py-2">Afișări</th>
                            <th class="text-right px-4 py-2">Click-uri</th>
                            <th class="text-right px-4 py-2">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($rows as $r)
                            <tr class="hover:bg-cream/50">
                                <td class="px-4 py-2 font-medium text-ink">{{ $r['label'] }}</td>
                                <td class="px-4 py-2 text-muted">{{ $r['page_type'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format($r['shown']) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-emerald-700 font-semibold">
                                    {{ number_format($r['clicked']) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    @if($r['ctr'] === null)
                                        <span class="text-line">—</span>
                                    @else
                                        <span class="@if($r['ctr'] >= 20) text-emerald-700 font-semibold @elseif($r['ctr'] < 5) text-muted @else text-inkSoft @endif">
                                            {{ $r['ctr'] }}%
                                        </span>
                                        @if($r['shown'] >= 100 && $r['ctr'] < 1.0)
                                            {{-- X6: CTR alert — chip had enough exposure to judge AND
                                                 converts below 1%. Candidate for rewrite or removal. --}}
                                            <span class="ml-1 inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-coralsoft text-coralh align-middle" title="Underperformer: >100 afișări, CTR <1%">⚠ slab</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="text-xs text-muted mt-4">
        CTR = click-uri / afișări per label. Afișările cresc la fiecare chip strip rendered server-side (chip_shown).
        Click-urile vin de pe widget (quick_reply_clicked). Etichete noi apar imediat ce sunt afișate prima dată.
    </p>
</div>
@endsection
