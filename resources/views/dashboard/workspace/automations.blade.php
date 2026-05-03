@extends('layouts.dashboard')

@section('title', 'Automatizări — ' . $bot->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted uppercase">{{ $bot->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-ink">Automatizări & nișă</h1>
        </div>
        <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-sm px-4 py-2 rounded-lg border border-line bg-white hover:bg-cream">
            ← Înapoi la Workspace
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    {{-- Niche preview --}}
    <div class="bg-white rounded-xl border border-line p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase text-muted">Nișă activă</p>
                <h2 class="text-lg font-bold text-ink mt-1">
                    {{ $nichConfig['display_name'] ?? '(generic)' }}
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Engine: <span class="font-mono">{{ $engine->type() }}</span> · archetype:
                    <span class="font-mono">{{ $nichConfig['archetype'] ?? '—' }}</span>
                </p>
            </div>
            @if(!$bot->niche_slug)
                <a href="{{ route('dashboard.setup-wow.start') }}" class="text-sm text-coralh hover:underline">Alege o nișă →</a>
            @endif
        </div>

        @if(!empty($nichConfig['prompt_addon']))
            <details class="mt-4 group">
                <summary class="cursor-pointer text-sm font-medium text-inkSoft hover:text-ink">📜 Prompt nișă (extras)</summary>
                <pre class="mt-2 p-3 bg-cream rounded-lg text-xs text-inkSoft whitespace-pre-wrap font-mono max-h-64 overflow-y-auto">{{ $nichConfig['prompt_addon'] }}</pre>
            </details>
        @endif

        @if(!empty($nichConfig['chat_tools']))
            <div class="mt-4">
                <p class="text-xs font-semibold uppercase text-muted mb-1">Instrumente disponibile AI-ului</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($nichConfig['chat_tools'] as $tool)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-mono">{{ $tool }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if($serviceTypes->isNotEmpty())
            <div class="mt-4">
                <p class="text-xs font-semibold uppercase text-muted mb-2">Servicii ({{ $serviceTypes->count() }})</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($serviceTypes as $s)
                        <div class="p-2.5 rounded-lg border border-line bg-cream">
                            <p class="text-sm font-semibold text-ink">{{ $s->name }}</p>
                            <p class="text-xs text-muted mt-0.5">
                                {{ $s->duration_minutes }} min
                                @if($s->price) · {{ number_format($s->price, 0) }} {{ $s->currency }}@endif
                                @if($s->is_urgent) · <span class="text-coralh font-semibold">urgență</span>@endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Automation toggles --}}
    <form method="POST" action="{{ route('dashboard.workspace.automations.update', $bot) }}" class="bg-white rounded-xl border border-line p-5">
        @csrf
        @method('PUT')
        <h2 class="text-lg font-bold text-ink mb-1">Automatizări</h2>
        <p class="text-sm text-muted mb-4">Opt-in per agent. Nimic nu trimite nimic până nu bifezi.</p>

        <div class="space-y-3">
            @foreach($flags as $key => $meta)
                @php
                    $requires = $meta['requires'] ?? null;
                    $disabled = !empty($meta['disabled'])
                        || ($requires === 'booking' && !in_array($bot->engine_type, ['booking', 'hybrid']))
                        || ($requires === 'ecommerce' && !in_array($bot->engine_type, ['ecommerce', 'hybrid']));
                    $checked = !$disabled && !empty($currentValues[$key]);
                @endphp
                <label class="flex items-start gap-3 p-3 rounded-lg border border-line {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-line' }}">
                    <input type="checkbox" name="{{ $key }}" value="1"
                           {{ $checked ? 'checked' : '' }}
                           {{ $disabled ? 'disabled' : '' }}
                           class="mt-1">
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ $meta['label'] }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $meta['desc'] }}</p>
                        @if($requires === 'booking' && !in_array($bot->engine_type, ['booking', 'hybrid']))
                            <p class="text-xs text-amber-700 mt-1">Necesită engine booking sau hybrid.</p>
                        @elseif($requires === 'ecommerce' && !in_array($bot->engine_type, ['ecommerce', 'hybrid']))
                            <p class="text-xs text-amber-700 mt-1">Necesită engine ecommerce sau hybrid.</p>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-coral text-white text-sm font-semibold hover:bg-coralh">
                Salvează
            </button>
        </div>
    </form>
</div>
@endsection
