@extends('layouts.dashboard')

@section('title', 'Activitate')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Activitate</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-2">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Activitate</h1>
            <p class="mt-2 text-sm text-muted">Cine a editat ce, când și de unde — istoric complet pentru organizația ta.</p>
        </div>
        <span class="text-2xs text-muted mono">{{ $logs->total() }} intrări totale</span>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card p-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-1.5">Utilizator</label>
            <select name="user_id" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                <option value="">Toți</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[180px]">
            <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-1.5">Acțiune</label>
            <select name="action" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                <option value="">Toate</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->hasAny(['user_id', 'action']))
            <a href="{{ route('dashboard.audit.index') }}"
               class="text-xs text-coralh hover:underline self-end pb-2.5">Resetează filtrele</a>
        @endif
    </form>

    {{-- Timeline --}}
    @if($logs->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-cream mx-auto mb-3 flex items-center justify-center">
                <svg class="w-6 h-6 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="display text-base font-semibold text-ink mb-1">Niciun istoric încă</h3>
            <p class="text-sm text-muted">Acțiunile pe agenți, canale, numere și site-uri vor apărea aici.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-2xs uppercase tracking-wider text-muted">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">Când</th>
                        <th class="text-left px-4 py-3 font-semibold">Cine</th>
                        <th class="text-left px-4 py-3 font-semibold">Acțiune</th>
                        <th class="text-left px-4 py-3 font-semibold">Subiect</th>
                        <th class="text-left px-4 py-3 font-semibold">Detalii</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($logs as $log)
                        <tr class="hover:bg-cream/50 transition">
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <div class="text-xs font-medium text-ink">{{ $log->created_at->translatedFormat('j M, H:i') }}</div>
                                <div class="text-2xs text-muted mono mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if($log->user)
                                    <div class="flex items-center gap-2">
                                        @php
                                            $userInitials = collect(explode(' ', $log->user->name))
                                                ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                                ->take(2)->join('');
                                        @endphp
                                        <div class="w-7 h-7 rounded-full bg-coralsoft text-coralh text-2xs font-semibold flex items-center justify-center shrink-0">
                                            {{ $userInitials ?: '?' }}
                                        </div>
                                        <span class="text-xs font-medium text-inkSoft">{{ $log->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-muted italic">sistem</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="text-xs font-medium text-ink">{{ $log->actionLabel() }}</span>
                                <div class="text-2xs text-muted mono mt-0.5">{{ $log->action }}</div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if($log->auditable_type)
                                    @php
                                        $shortType = class_basename($log->auditable_type);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-pill bg-cream text-2xs text-inkSoft border border-line mono">
                                        {{ $shortType }}#{{ $log->auditable_id }}
                                    </span>
                                @else
                                    <span class="text-2xs text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if($log->changes)
                                    <details class="group">
                                        <summary class="text-2xs text-coralh cursor-pointer hover:underline list-none">
                                            <span class="group-open:hidden">vezi diff ({{ count($log->changes) }} câmpuri)</span>
                                            <span class="hidden group-open:inline">ascunde</span>
                                        </summary>
                                        <pre class="mt-2 text-2xs font-mono text-inkSoft bg-cream/60 p-2 rounded-lg max-h-48 overflow-y-auto border border-line">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-2xs text-muted italic">—</span>
                                @endif
                                @if($log->ip)
                                    <div class="text-2xs text-muted mono mt-1">{{ $log->ip }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
