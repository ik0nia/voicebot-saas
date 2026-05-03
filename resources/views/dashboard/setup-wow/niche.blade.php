@extends('layouts.dashboard')

@section('title', 'Alege nișa agentului tău')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="mb-8">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted uppercase">
            <span class="text-coralh">Pasul 1 din 4</span>
            <span class="text-slate-300">·</span>
            <span>Nișă</span>
        </div>
        <h1 class="mt-2 text-3xl font-extrabold text-ink">Ce face afacerea ta?</h1>
        <p class="mt-2 text-muted">Alege nișa care se potrivește cel mai bine. Agentul tău vine cu flow-uri, vocabular și prompt-uri gata-configurate.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-coralsoft border border-coral/30 rounded-lg text-sm text-coralh">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    @php
        // Iter A (UX): show only the 6 most common niches by default; let
        // users expand to see the rest. Order of the featured list drives
        // the visible top row.
        $featuredSlugs = ['magazin-online', 'stomatologie', 'beauty', 'florarii', 'restaurant', 'imobiliare'];
        $featuredNiches = [];
        $restNiches = [];
        foreach ($niches as $n) {
            if (in_array($n['slug'], $featuredSlugs, true)) {
                $featuredNiches[array_search($n['slug'], $featuredSlugs, true)] = $n;
            } else {
                $restNiches[] = $n;
            }
        }
        ksort($featuredNiches);
        $featuredNiches = array_values($featuredNiches);
        $savedSlug = $state['niche_slug'] ?? '';
        // Auto-expand if the saved niche lives in the "rest" bucket, so
        // the highlighted card is visible on back-nav.
        $expandByDefault = $savedSlug && !in_array($savedSlug, $featuredSlugs, true);
    @endphp

    <form method="POST" action="{{ route('dashboard.setup-wow.saveNiche') }}" id="niche-form"
          x-data="{ showAll: {{ $expandByDefault ? 'true' : 'false' }} }">
        @csrf
        <input type="hidden" name="niche_slug" id="niche_slug" value="{{ $savedSlug }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($featuredNiches as $n)
                <button type="button"
                        class="niche-card text-left p-4 rounded-xl border-2 border-line bg-white hover:border-red-300 hover:shadow-sm transition-all"
                        data-slug="{{ $n['slug'] }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold text-ink">{{ $n['display_name'] }}</p>
                        <span class="text-[10px] uppercase font-semibold px-1.5 py-0.5 rounded
                                   @switch($n['archetype'])
                                       @case('booking') bg-indigo-50 text-indigo-700 @break
                                       @case('ecommerce') bg-emerald-50 text-emerald-700 @break
                                       @case('lead') bg-amber-50 text-amber-700 @break
                                       @case('hybrid') bg-purple-50 text-purple-700 @break
                                       @case('hospitality') bg-sky-50 text-sky-700 @break
                                   @endswitch">
                            {{ $n['archetype'] }}
                        </span>
                    </div>
                    @if($n['wow_demo'])
                        <p class="mt-2 text-xs text-muted italic line-clamp-2">„{{ $n['wow_demo'] }}"</p>
                    @endif
                </button>
            @endforeach
        </div>

        @if(count($restNiches) > 0)
            <div class="mt-4 text-center">
                <button type="button" @click="showAll = !showAll"
                        class="inline-flex items-center gap-2 text-sm font-medium text-coralh hover:text-coralh transition">
                    <span x-show="!showAll">Vezi toate nișele ({{ count($restNiches) }} mai multe) ↓</span>
                    <span x-show="showAll" x-cloak>Ascunde nișele suplimentare ↑</span>
                </button>
            </div>

            <div x-show="showAll" x-cloak x-transition
                 class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($restNiches as $n)
                    <button type="button"
                            class="niche-card text-left p-4 rounded-xl border-2 border-line bg-white hover:border-red-300 hover:shadow-sm transition-all"
                            data-slug="{{ $n['slug'] }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-ink">{{ $n['display_name'] }}</p>
                            <span class="text-[10px] uppercase font-semibold px-1.5 py-0.5 rounded
                                       @switch($n['archetype'])
                                           @case('booking') bg-indigo-50 text-indigo-700 @break
                                           @case('ecommerce') bg-emerald-50 text-emerald-700 @break
                                           @case('lead') bg-amber-50 text-amber-700 @break
                                           @case('hybrid') bg-purple-50 text-purple-700 @break
                                           @case('hospitality') bg-sky-50 text-sky-700 @break
                                       @endswitch">
                                {{ $n['archetype'] }}
                            </span>
                        </div>
                        @if($n['wow_demo'])
                            <p class="mt-2 text-xs text-muted italic line-clamp-2">„{{ $n['wow_demo'] }}"</p>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        <div class="mt-6 flex justify-end">
            <button type="submit" id="niche-submit" disabled
                    class="px-6 py-2.5 rounded-lg bg-coral text-white text-sm font-semibold disabled:bg-slate-300 disabled:cursor-not-allowed hover:bg-coral">
                Continuă →
            </button>
        </div>
    </form>
</div>
<style>[x-cloak]{display:none !important;}</style>

<script>
document.querySelectorAll('.niche-card').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.niche-card').forEach(function (b) {
            b.classList.remove('border-red-500', 'bg-coralsoft', 'ring-2', 'ring-coral');
            b.classList.add('border-line', 'bg-white');
        });
        btn.classList.remove('border-line', 'bg-white');
        btn.classList.add('border-red-500', 'bg-coralsoft', 'ring-2', 'ring-coral');
        document.getElementById('niche_slug').value = btn.dataset.slug;
        document.getElementById('niche-submit').disabled = false;
    });
});
// Re-select saved niche on back-nav.
var saved = document.getElementById('niche_slug').value;
if (saved) {
    var match = document.querySelector('.niche-card[data-slug="' + saved + '"]');
    if (match) match.click();
}
</script>
@endsection
