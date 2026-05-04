@extends('layouts.dashboard')

@section('title', 'Testează agentul')

@section('content')
@php
    $channelId  = session('wow_wizard.channel_id');
    $nicheCfg   = $bot && $bot->niche_slug ? config('niches.' . $bot->niche_slug) : null;
    $wowDemo    = $nicheCfg['wow_demo'] ?? null;
    $nicheLabel = $nicheCfg['display_name'] ?? ($bot?->niche_slug ?? 'afacerea ta');

    // For booking/hybrid bots, surface the count of seeded services so
    // the tenant sees proof the wizard configured something (seeds ran
    // silently in saveAgent before this card existed).
    $bookingServicesCount = null;
    if ($bot && in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
        $bookingServicesCount = \App\Models\ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->count();
    }

    // Same idea for hospitality bots — surface seeded resource_inventory
    // (tables / rooms) so the tenant sees the wizard did configure them.
    $hospitalityResourceCount = null;
    if ($bot && $bot->engine_type === 'hospitality') {
        $hospitalityResourceCount = \App\Models\ResourceInventory::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->count();
    }
@endphp
<div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted uppercase">
            <span class="text-coralh">Pasul 4 din 4</span>
            <span class="text-line">·</span>
            <span>Test live</span>
        </div>
        <h1 class="mt-2 text-3xl font-extrabold text-ink">Vorbește cu agentul tău 🎙️</h1>
        <p class="mt-2 text-muted">Agentul <strong>{{ $bot?->name ?? 'tău' }}</strong> e gata. Testează-l aici, pe site-ul tău de „staging", înainte să-l publici.</p>
    </div>

    @if($wowDemo)
        <div class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-900">
            💡 <strong>Încearcă:</strong> „{{ $wowDemo }}"
        </div>
    @endif

    @if($bookingServicesCount !== null)
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-900 flex items-start gap-3">
            <span class="text-lg">📅</span>
            <div class="flex-1">
                <div class="font-semibold">Programări pregătite</div>
                <div class="mt-0.5">
                    Am pregătit <strong>{{ $bookingServicesCount }}</strong>
                    {{ $bookingServicesCount === 1 ? 'serviciu' : 'servicii' }}
                    și program L-V 09:00-19:00. Poți edita oricând după ce termini.
                </div>
                <a href="{{ route('dashboard.bots.booking', $bot) }}"
                   class="inline-flex items-center gap-1 mt-2 text-emerald-800 font-semibold hover:underline">
                    Deschide Programări →
                </a>
            </div>
        </div>
    @endif

    @if($hospitalityResourceCount !== null && $hospitalityResourceCount > 0)
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-900 flex items-start gap-3">
            <span class="text-lg">🪑</span>
            <div class="flex-1">
                <div class="font-semibold">Rezervări pregătite</div>
                <div class="mt-0.5">
                    Am pregătit <strong>{{ $hospitalityResourceCount }}</strong>
                    {{ $hospitalityResourceCount === 1 ? 'resursă' : 'resurse' }}
                    (mese / camere). Poți edita capacitățile și prețurile după ce termini.
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl border border-line min-h-[520px]">
        <p class="text-sm text-muted mb-3">Widget-ul se va deschide în colțul din dreapta-jos. Apasă pe iconul de chat.</p>
        @if($channelId)
            <script src="@samblaWidgetUrl"
                    data-channel-id="{{ $channelId }}"
                    data-bot-name="{{ $bot?->name ?? 'Asistent' }}"
                    data-color="#991b1b"
                    data-lang="ro"
                    async defer></script>
        @else
            <p class="text-sm text-coralh">Nu am putut încărca widget-ul — revino la pasul anterior.</p>
        @endif
    </div>

    {{-- Iter A (UX): purposeful post-wizard CTAs instead of a single "publish" button. --}}
    <div class="mt-6 p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-white border border-emerald-200">
        <p class="text-sm font-semibold text-emerald-900">🎉 Agentul tău e gata! Configurat automat pentru {{ $nicheLabel }}.</p>
        <p class="mt-1 text-xs text-muted">Testează-l prin voce, ajustează detaliile, sau intră direct în dashboard.</p>

        <div class="mt-4 flex flex-col sm:flex-row sm:items-stretch gap-2">
            @if($bot)
                <a href="{{ route('dashboard.bots.testVocal', $bot) }}" target="_blank"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-coral text-white text-sm font-semibold hover:bg-coralh shadow-sm transition">
                    🎙 Testează prin voce
                </a>
                <a href="{{ route('dashboard.bots.edit', $bot) }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-line bg-white text-inkSoft text-sm font-medium hover:bg-cream transition">
                    📝 Editează detaliile
                </a>
            @endif
            <form method="POST" action="{{ route('dashboard.setup-wow.publish') }}" class="inline-flex">
                @csrf
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-emerald-600 text-emerald-700 text-sm font-medium hover:bg-emerald-50 transition">
                    Du-mă în dashboard →
                </button>
            </form>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between gap-4">
        <a href="{{ route('dashboard.setup-wow.step', ['step' => 'agent']) }}" class="text-sm text-muted hover:text-ink">← Modifică agentul</a>
    </div>
</div>
@endsection
