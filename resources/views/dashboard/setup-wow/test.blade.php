@extends('layouts.dashboard')

@section('title', 'Testează agentul')

@section('content')
@php
    $channelId = session('wow_wizard.channel_id');
    $wowDemo = $bot && $bot->niche_slug ? (config('niches.' . $bot->niche_slug . '.wow_demo') ?? null) : null;

    // For booking/hybrid bots, surface the count of seeded services so
    // the tenant sees proof the wizard configured something (seeds ran
    // silently in saveAgent before this card existed).
    $bookingServicesCount = null;
    if ($bot && in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
        $bookingServicesCount = \App\Models\ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)->count();
    }
@endphp
<div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase">
            <span class="text-red-700">Pasul 4 din 4</span>
            <span class="text-slate-300">·</span>
            <span>Test live</span>
        </div>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Vorbește cu agentul tău 🎙️</h1>
        <p class="mt-2 text-slate-600">Agentul <strong>{{ $bot?->name ?? 'tău' }}</strong> e gata. Testează-l aici, pe site-ul tău de „staging", înainte să-l publici.</p>
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

    <div class="bg-white p-6 rounded-xl border border-slate-200 min-h-[520px]">
        <p class="text-sm text-slate-500 mb-3">Widget-ul se va deschide în colțul din dreapta-jos. Apasă pe iconul de chat.</p>
        @if($channelId)
            <script src="{{ rtrim(config('app.cdn_url') ?: config('app.url'), '/') }}/widget/sambla-chat.min.js"
                    data-channel-id="{{ $channelId }}"
                    data-bot-name="{{ $bot?->name ?? 'Asistent' }}"
                    data-color="#991b1b"
                    data-lang="ro"
                    async defer></script>
        @else
            <p class="text-sm text-red-700">Nu am putut încărca widget-ul — revino la pasul anterior.</p>
        @endif
    </div>

    <div class="mt-6 flex items-center justify-between gap-4">
        <a href="{{ route('dashboard.setup-wow.step', ['step' => 'agent']) }}" class="text-sm text-slate-600 hover:text-slate-900">← Modifică agentul</a>
        <form method="POST" action="{{ route('dashboard.setup-wow.publish') }}">
            @csrf
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                ✓ Publică agentul și intră în dashboard
            </button>
        </form>
    </div>
</div>
@endsection
