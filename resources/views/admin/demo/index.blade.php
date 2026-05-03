@extends('layouts.admin')
@section('title', 'Demo pe landing pages')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-ink">Demo pe landing pages</h1>
    <p class="text-sm text-muted mt-1">
        Funnel: vizualizare → mesaj → calificat (≥3 mesaje) → înregistrare.
        Fereastra: ultimele {{ $days }} zile.
    </p>
</div>

<form method="GET" class="mb-4 bg-white rounded-xl border border-line p-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-muted mb-1">Perioadă (zile)</label>
        <select name="days" onchange="this.form.submit()" class="rounded-lg border border-line px-3 py-2 text-sm">
            @foreach([7,14,30,60,90] as $d)
                <option value="{{ $d }}" {{ $days === $d ? 'selected' : '' }}>{{ $d }} zile</option>
            @endforeach
        </select>
    </div>
</form>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-line p-4">
        <div class="text-xs text-muted font-semibold uppercase tracking-wide">Vizualizate</div>
        <div class="text-3xl font-bold text-ink mt-1">{{ number_format($stats['viewed']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <div class="text-xs text-muted font-semibold uppercase tracking-wide">Mesaje</div>
        <div class="text-3xl font-bold text-ink mt-1">{{ number_format($stats['messages']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <div class="text-xs text-muted font-semibold uppercase tracking-wide">Calificate</div>
        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($stats['qualified']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <div class="text-xs text-muted font-semibold uppercase tracking-wide">Rata calificare</div>
        <div class="text-3xl font-bold text-ink mt-1">{{ $stats['rate'] }}%</div>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <div class="text-xs text-muted font-semibold uppercase tracking-wide">Înregistrări</div>
        <div class="text-3xl font-bold text-indigo-600 mt-1">{{ number_format($stats['signups']) }}</div>
    </div>
</div>

<div class="bg-white rounded-xl border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs font-semibold text-muted uppercase bg-cream border-b border-line">
                <th class="px-4 py-3">Nișă</th>
                <th class="px-4 py-3 text-right">Vizualizate</th>
                <th class="px-4 py-3 text-right">Mesaje</th>
                <th class="px-4 py-3 text-right">Calificate</th>
                <th class="px-4 py-3 text-right">Rata</th>
                <th class="px-4 py-3 text-right">Signups</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stats['by_niche'] as $row)
                <tr class="border-b border-line hover:bg-cream">
                    <td class="px-4 py-3">
                        <a href="{{ url('/pentru/' . $row['niche']) }}" target="_blank" class="font-mono text-xs text-indigo-600 hover:underline">{{ $row['niche'] }}</a>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['viewed']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums text-muted">{{ number_format($row['messages']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums text-emerald-600 font-semibold">{{ number_format($row['qualified']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $row['rate'] }}%</td>
                    <td class="px-4 py-3 text-right tabular-nums text-indigo-600 font-semibold">{{ number_format($row['signups']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">
                        Niciun eveniment înregistrat în fereastra selectată. Verifică flag-ul <code>NICHE_PUBLIC_DEMO_ENABLED</code> și că au fost rulate <code>niche-demo:seed</code>.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
