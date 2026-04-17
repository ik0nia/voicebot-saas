@extends('layouts.dashboard')

@section('title', 'Alege nișa agentului tău')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="mb-8">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase">
            <span class="text-red-700">Pasul 1 din 4</span>
            <span class="text-slate-300">·</span>
            <span>Nișă</span>
        </div>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Ce face afacerea ta?</h1>
        <p class="mt-2 text-slate-600">Alege nișa care se potrivește cel mai bine. Agentul tău vine cu flow-uri, vocabular și prompt-uri gata-configurate.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-900">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.setup-wow.saveNiche') }}" id="niche-form">
        @csrf
        <input type="hidden" name="niche_slug" id="niche_slug" value="{{ $state['niche_slug'] ?? '' }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($niches as $n)
                <button type="button"
                        class="niche-card text-left p-4 rounded-xl border-2 border-slate-200 bg-white hover:border-red-300 hover:shadow-sm transition-all"
                        data-slug="{{ $n['slug'] }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold text-slate-900">{{ $n['display_name'] }}</p>
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
                        <p class="mt-2 text-xs text-slate-500 italic line-clamp-2">„{{ $n['wow_demo'] }}"</p>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" id="niche-submit" disabled
                    class="px-6 py-2.5 rounded-lg bg-red-700 text-white text-sm font-semibold disabled:bg-slate-300 disabled:cursor-not-allowed hover:bg-red-800">
                Continuă →
            </button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.niche-card').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.niche-card').forEach(b => b.classList.remove('border-red-500', 'bg-red-50'));
        btn.classList.add('border-red-500', 'bg-red-50');
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
