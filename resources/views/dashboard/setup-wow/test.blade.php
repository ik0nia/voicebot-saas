@extends('layouts.dashboard')

@section('title', 'Testează agentul')

@section('content')
@php
    $channelId  = session('wow_wizard.channel_id');
    $nicheCfg   = $bot && $bot->niche_slug ? config('niches.' . $bot->niche_slug) : null;
    $wowDemo    = $nicheCfg['wow_demo'] ?? null;
    $nicheLabel = $nicheCfg['display_name'] ?? ($bot?->niche_slug ?? 'afacerea ta');
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

    {{-- Iter A (UX): purposeful post-wizard CTAs instead of a single "publish" button. --}}
    <div class="mt-6 p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-white border border-emerald-200">
        <p class="text-sm font-semibold text-emerald-900">🎉 Agentul tău e gata! Configurat automat pentru {{ $nicheLabel }}.</p>
        <p class="mt-1 text-xs text-slate-600">Testează-l prin voce, ajustează detaliile, sau intră direct în dashboard.</p>

        <div class="mt-4 flex flex-col sm:flex-row sm:items-stretch gap-2">
            @if($bot)
                <a href="{{ route('dashboard.bots.testVocal', $bot) }}" target="_blank"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-red-700 text-white text-sm font-semibold hover:bg-red-800 shadow-sm transition">
                    🎙 Testează prin voce
                </a>
                <a href="{{ route('dashboard.bots.edit', $bot) }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
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
        <a href="{{ route('dashboard.setup-wow.step', ['step' => 'agent']) }}" class="text-sm text-slate-600 hover:text-slate-900">← Modifică agentul</a>
    </div>
</div>
@endsection
