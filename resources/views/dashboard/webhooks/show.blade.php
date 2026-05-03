@extends('layouts.dashboard')

@section('title', $endpoint->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.webhooks.index') }}" class="text-muted hover:text-inkSoft">Webhooks</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">{{ $endpoint->name }}</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none truncate">{{ $endpoint->name }}</h1>
                @if($endpoint->is_active)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-pill">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activ
                    </span>
                @else
                    <span class="text-xs text-muted bg-cream px-2 py-0.5 rounded-pill">Inactiv</span>
                @endif
            </div>
            <p class="text-sm text-muted font-mono mt-1 truncate">{{ $endpoint->url }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('dashboard.webhooks.testFire', $endpoint) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-pill border border-line bg-white text-sm hover:bg-cream">
                    Trimite ping de test
                </button>
            </form>
            <a href="{{ route('dashboard.webhooks.edit', $endpoint) }}"
               class="px-4 py-2 rounded-pill border border-line bg-white text-sm hover:bg-cream">
                Editează
            </a>
            <form method="POST" action="{{ route('dashboard.webhooks.destroy', $endpoint) }}"
                  onsubmit="return confirm('Sigur ștergi webhook-ul „{{ $endpoint->name }}"? Evenimentele nu se mai trimit.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-pill border border-coral/30 bg-coralsoft text-coralh text-sm hover:bg-coral hover:text-cream">
                    Șterge
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="card p-4 border-emerald-200 bg-emerald-50">
            <div class="text-sm text-emerald-800">{{ session('success') }}</div>
            @if(session('webhook_secret'))
                <div class="mt-3">
                    <p class="text-xs font-medium text-emerald-900 mb-1">Secretul HMAC pentru acest webhook (apare doar acum):</p>
                    <code class="block bg-white border border-emerald-200 rounded-lg p-3 text-xs font-mono text-ink select-all">{{ session('webhook_secret') }}</code>
                    <p class="text-2xs text-emerald-800 mt-1">Folosește-l pentru a verifica X-Sambla-Signature pe endpoint-ul tău.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Events subscribed --}}
    <div class="card p-5">
        <h2 class="display text-base font-semibold text-ink mb-3">Evenimente</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($endpoint->events as $ev)
                <span class="inline-flex items-center px-2.5 py-1 rounded-pill bg-cream text-xs text-inkSoft border border-line mono">{{ $ev }}</span>
            @endforeach
        </div>
    </div>

    {{-- Quickstart --}}
    <div class="card p-5">
        <h2 class="display text-base font-semibold text-ink mb-3">Cum verifici semnătura</h2>
        <p class="text-sm text-inkSoft mb-3">Fiecare POST aduce trei headere — verifică-le pe endpoint-ul tău:</p>
        <pre class="bg-cream/60 border border-line rounded-lg p-3 text-2xs font-mono text-inkSoft overflow-x-auto">X-Sambla-Event:     lead.created
X-Sambla-Timestamp: 1759497600
X-Sambla-Signature: sha256=&lt;hmac_hex&gt;

# Compute expected:
expected = hmac_sha256(timestamp + "." + raw_body, your_secret)
# Compare cu hash_equals la signature.</pre>
    </div>

    {{-- Deliveries --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-line flex items-center justify-between">
            <h2 class="display text-base font-semibold text-ink">Istoric livrări</h2>
            <span class="text-2xs text-muted mono">{{ $deliveries->total() }} totale</span>
        </div>
        @if($deliveries->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-muted">
                Niciun event trimis încă. Apasă „Trimite ping de test" sus pentru a verifica.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-cream text-2xs uppercase tracking-wider text-muted">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold">Când</th>
                        <th class="text-left px-4 py-2 font-semibold">Event</th>
                        <th class="text-left px-4 py-2 font-semibold">Status</th>
                        <th class="text-left px-4 py-2 font-semibold">Try</th>
                        <th class="text-left px-4 py-2 font-semibold">Latență</th>
                        <th class="text-left px-4 py-2 font-semibold">Detalii</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($deliveries as $d)
                        <tr class="hover:bg-cream/50 transition">
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                <div class="text-xs text-ink">{{ $d->created_at->translatedFormat('j M, H:i:s') }}</div>
                                <div class="text-2xs text-muted">{{ $d->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-2 align-top">
                                <span class="text-2xs font-mono text-inkSoft">{{ $d->event }}</span>
                            </td>
                            <td class="px-4 py-2 align-top">
                                @if($d->succeeded)
                                    <span class="inline-flex items-center gap-1 text-2xs text-emerald-700 font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ $d->response_code }} OK
                                    </span>
                                @elseif($d->response_code)
                                    <span class="inline-flex items-center gap-1 text-2xs text-coralh font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-coral"></span> {{ $d->response_code }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-2xs text-muted font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-line"></span> eroare
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-top text-2xs mono">{{ $d->attempt }}</td>
                            <td class="px-4 py-2 align-top text-2xs mono text-muted">{{ $d->duration_ms ? $d->duration_ms . 'ms' : '—' }}</td>
                            <td class="px-4 py-2 align-top">
                                <details class="group">
                                    <summary class="text-2xs text-coralh cursor-pointer hover:underline list-none">
                                        <span class="group-open:hidden">vezi payload</span>
                                        <span class="hidden group-open:inline">ascunde</span>
                                    </summary>
                                    @if($d->error_message)
                                        <div class="mt-1 text-2xs text-coralh italic">{{ $d->error_message }}</div>
                                    @endif
                                    <pre class="mt-2 text-2xs font-mono text-inkSoft bg-cream/60 p-2 rounded-lg max-h-48 overflow-y-auto border border-line">{{ json_encode($d->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @if($d->response_body)
                                        <div class="mt-1 text-2xs text-muted">Răspuns:</div>
                                        <pre class="text-2xs font-mono text-inkSoft bg-cream/60 p-2 rounded-lg max-h-32 overflow-y-auto border border-line">{{ $d->response_body }}</pre>
                                    @endif
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t border-line">{{ $deliveries->links() }}</div>
        @endif
    </div>

</div>
@endsection
