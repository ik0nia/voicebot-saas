@extends('layouts.dashboard')

@section('title', 'Webhooks')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Webhooks</span>
@endsection

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Webhooks</h1>
            <p class="mt-2 text-sm text-muted">Primește evenimente Sambla direct în sistemul tău (CRM, ERP, automation tool).</p>
        </div>
        <a href="{{ route('dashboard.webhooks.create') }}"
           class="btn-coral inline-flex items-center justify-center gap-2 rounded-pill px-5 py-2.5 text-sm font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Webhook nou
        </a>
    </div>

    @if(session('success'))
        <div class="card p-4 border-emerald-200 bg-emerald-50">
            <div class="text-sm text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif

    @if($endpoints->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-coralsoft text-coralh mx-auto mb-3 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="display text-lg font-semibold text-ink mb-2">Niciun webhook configurat</h3>
            <p class="text-sm text-muted max-w-md mx-auto mb-5">Configurează un URL la care Sambla să trimită lead-uri noi, programări sau apeluri terminate. Fiecare event e semnat HMAC ca să poți verifica autenticitatea.</p>
            <a href="{{ route('dashboard.webhooks.create') }}" class="btn-coral inline-flex items-center gap-2 rounded-pill px-5 py-2.5 text-sm font-medium">
                Configurează primul webhook →
            </a>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($endpoints as $e)
                @php $latest = $e->deliveries->first(); @endphp
                <a href="{{ route('dashboard.webhooks.show', $e) }}" class="card p-5 hover:no-underline block">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-ink truncate">{{ $e->name }}</h3>
                            <p class="text-2xs text-muted mono truncate mt-0.5">{{ $e->url }}</p>
                        </div>
                        @if($e->is_active)
                            <span class="shrink-0 inline-flex items-center gap-1.5 text-2xs font-medium text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Activ
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1.5 text-2xs font-medium text-muted">
                                <span class="w-1.5 h-1.5 rounded-full bg-line"></span>
                                Inactiv
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-1 mb-3">
                        @foreach(array_slice($e->events ?? [], 0, 5) as $ev)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-pill bg-cream text-2xs text-inkSoft border border-line mono">{{ $ev }}</span>
                        @endforeach
                        @if(count($e->events ?? []) > 5)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs text-muted">+{{ count($e->events) - 5 }}</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between text-2xs text-muted pt-3 border-t border-line">
                        @if($e->last_success_at)
                            <span class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Ultima livrare OK · {{ $e->last_success_at->diffForHumans() }}
                            </span>
                        @elseif($e->last_failure_at)
                            <span class="flex items-center gap-1.5 text-coralh">
                                <span class="w-1.5 h-1.5 rounded-full bg-coral"></span>
                                Eșec · {{ $e->last_failure_at->diffForHumans() }}
                            </span>
                        @else
                            <span class="italic">Nicio livrare încă</span>
                        @endif
                        @if($e->failure_count > 0)
                            <span class="font-medium text-coralh">{{ $e->failure_count }} eșecuri consecutive</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
