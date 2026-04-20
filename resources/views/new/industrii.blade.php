@extends('layouts.new')

@section('title', 'Industrii — Agenți AI pentru fiecare vertical | Sambla')
@section('meta_description', 'Agenți AI conversaționali antrenați pentru industria ta — stomatologie, estetică, service auto, imobiliare, HORECA, avocatură, contabilitate, e-commerce și mai multe. Fiecare vertical cu prompt-uri, integrări și ton adaptat.')
@section('canonical', url('/new/industrii'))

@section('content')

{{-- HERO --}}
<section class="hero-glow relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-outline mono text-[11px] uppercase tracking-wider inline-flex mb-7">◇ industrii</div>
        <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-6">
            Un agent AI pentru<br><em class="italic accent-text">fiecare vertical</em>.
        </h1>
        <p class="text-xl leading-relaxed text-muted max-w-3xl mx-auto">
            Nu livrăm un chatbot generic pe care îl forțezi să se potrivească. Pentru fiecare industrie avem prompt-uri, personalități, integrări și un ton de comunicare adaptat la specificul afacerilor românești din acea zonă.
        </p>
    </div>
</section>

@php
    /* Emoji per nișă pentru header-ul cardului — mai warm decât SVG
       generic; SVG-ul fin e deja în iconița din col. accent-soft. */
    $nicheEmojiMap = [
        'cabinete-stomatologice'     => '🦷',
        'cabinete-medicale'          => '🏥',
        'salon-beauty'               => '💆',
        'service-auto'               => '🔧',
        'magazine-online'            => '🛒',
        'agentii-imobiliare'         => '🏠',
        'birouri-avocatura'          => '⚖️',
        'firme-contabilitate'        => '📚',
        'restaurante-delivery'       => '🍽️',
        'pensiuni-hoteluri-mici'     => '🏨',
        'optica-medicala'            => '👓',
        'agentii-turism'             => '✈️',
        'birouri-notariale'          => '📜',
        'psihologie-psihoterapie'    => '🧠',
        'firme-curatenie'            => '🧹',
        'clinici-veterinare'         => '🐾',
        'scoli-limbi-straine'        => '🎓',
    ];

    /* Aceeași segmentare ca în mega menu — sursa unică la AppServiceProvider.
       Aici o ținem inline ca să nu cream dependență de composer. */
    $categories = [
        ['label' => 'Sănătate & Beauty',                 'slugs' => ['cabinete-medicale', 'cabinete-stomatologice', 'optica-medicala', 'clinici-veterinare', 'psihologie-psihoterapie', 'salon-beauty']],
        ['label' => 'Servicii profesionale',             'slugs' => ['birouri-avocatura', 'firme-contabilitate', 'birouri-notariale']],
        ['label' => 'Comerț & Auto',                     'slugs' => ['magazine-online', 'service-auto']],
        ['label' => 'HoReCa & Turism',                   'slugs' => ['restaurante-delivery', 'pensiuni-hoteluri-mici', 'agentii-turism']],
        ['label' => 'Imobiliare · Educație · Servicii',  'slugs' => ['agentii-imobiliare', 'scoli-limbi-straine', 'firme-curatenie']],
    ];

    $byKey = $niches->keyBy('slug');
    $placed = [];
@endphp

{{-- QUICK JUMP STRIP — ancore spre fiecare categorie --}}
<section class="py-6 bg-paper border-b border-line sticky top-20 z-30 backdrop-blur">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs font-medium">
            <span class="mono text-[10px] uppercase tracking-[0.15em]" style="color: var(--muted);">Sari la:</span>
            @foreach($categories as $i => $cat)
                <a href="#cat-{{ $i }}" class="hover:accent-text transition" style="color: var(--inkSoft);">{{ $cat['label'] }}</a>
            @endforeach
        </div>
    </div>
</section>

{{-- NICHES PER CATEGORIE --}}
<section class="py-16">
    <div class="max-w-7xl mx-auto px-6 space-y-16">
        @foreach($categories as $i => $cat)
            @php
                $items = collect($cat['slugs'])->map(fn ($s) => $byKey[$s] ?? null)->filter();
                foreach ($cat['slugs'] as $s) { $placed[$s] = true; }
            @endphp
            @if($items->isNotEmpty())
                <div id="cat-{{ $i }}" class="scroll-mt-36">
                    <div class="flex items-end justify-between mb-6 fade-up">
                        <div>
                            <div class="mono text-[10px] uppercase tracking-[0.2em] mb-2" style="color: var(--muted);">◇ categoria {{ $i + 1 }}</div>
                            <h2 class="display text-3xl md:text-4xl font-medium leading-tight">{{ $cat['label'] }}</h2>
                        </div>
                        <span class="hidden md:inline-flex mono text-[10px] uppercase tracking-wider" style="color: var(--muted);">{{ $items->count() }} vertical{{ $items->count() == 1 ? 'ă' : 'e' }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($items as $n)
                            <a href="{{ route('new.niche', $n->slug) }}" data-niche="{{ $n->color_theme }}" class="niche-card group block rounded-3xl overflow-hidden bg-paper border border-line fade-up">
                                <div class="accent-soft-bg p-6 flex items-start justify-between">
                                    <div>
                                        <div class="text-5xl mb-3">{{ $nicheEmojiMap[$n->slug] ?? '✦' }}</div>
                                        <div class="mono text-[10px] uppercase tracking-[0.15em] accent-text">{{ $n->vertical_label ?: 'Agent AI' }}</div>
                                    </div>
                                    <span class="w-10 h-10 rounded-full accent-bg flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:-translate-y-0.5 transition">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <h3 class="display text-xl font-semibold mb-3 leading-snug">{{ $n->name }}</h3>
                                    @if(!empty($n->hero_subtitle))
                                        <p class="text-sm text-muted leading-relaxed mb-4 line-clamp-3">{{ Str::limit(strip_tags($n->hero_subtitle), 160) }}</p>
                                    @endif
                                    <div class="pt-4 border-t border-line flex items-center justify-between text-xs">
                                        <span class="mono uppercase tracking-wider accent-text font-medium group-hover:translate-x-0.5 transition">Vezi pagina →</span>
                                        @if(!empty($n->benefits) && is_array($n->benefits))
                                            <span class="text-muted">{{ count($n->benefits) }} avantaje</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        {{-- Orphans — nișe active care nu sunt în nicio categorie mapată.
             Le scoatem separat, ca pagina să reflecte DB-ul complet. --}}
        @php
            $orphans = $niches->reject(fn ($n) => isset($placed[$n->slug]));
        @endphp
        @if($orphans->count())
            <div>
                <div class="flex items-end justify-between mb-6 fade-up">
                    <div>
                        <div class="mono text-[10px] uppercase tracking-[0.2em] mb-2" style="color: var(--muted);">◇ alte verticale</div>
                        <h2 class="display text-3xl md:text-4xl font-medium leading-tight">Restul</h2>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($orphans as $n)
                        <a href="{{ route('new.niche', $n->slug) }}" data-niche="{{ $n->color_theme }}" class="niche-card group block rounded-3xl overflow-hidden bg-paper border border-line fade-up">
                            <div class="accent-soft-bg p-6 flex items-start justify-between">
                                <div>
                                    <div class="text-5xl mb-3">{{ $nicheEmojiMap[$n->slug] ?? '✦' }}</div>
                                    <div class="mono text-[10px] uppercase tracking-[0.15em] accent-text">{{ $n->vertical_label ?: 'Agent AI' }}</div>
                                </div>
                                <span class="w-10 h-10 rounded-full accent-bg flex items-center justify-center shrink-0 group-hover:scale-110 transition">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </span>
                            </div>
                            <div class="p-6">
                                <h3 class="display text-xl font-semibold mb-3 leading-snug">{{ $n->name }}</h3>
                                @if(!empty($n->hero_subtitle))
                                    <p class="text-sm text-muted leading-relaxed mb-4 line-clamp-3">{{ Str::limit(strip_tags($n->hero_subtitle), 160) }}</p>
                                @endif
                                <div class="pt-4 border-t border-line flex items-center justify-between text-xs">
                                    <span class="mono uppercase tracking-wider accent-text font-medium">Vezi pagina →</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

{{-- NU GĂSEȘTI INDUSTRIA TA? --}}
<section class="py-20 bg-paper border-t border-line">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ nu-ți găsești industria?</div>
        <h2 class="display text-4xl md:text-5xl font-medium leading-[1.05] mb-5">
            Platforma e <em class="italic accent-text">flexibilă</em>.
        </h2>
        <p class="text-lg text-muted max-w-2xl mx-auto mb-8 leading-relaxed">
            Fiecare listare de aici e rezultatul muncii cu clienți reali din acea industrie. Dacă a ta nu e încă aici, înseamnă doar că încă n-am lucrat împreună. Agentul se antrenează pe documentele tale — deci funcționează pentru orice afacere care vorbește cu clienți.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ url('/register') }}" class="btn-primary">
                Încearcă 7 zile gratuit
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="{{ route('new.contact') }}" class="btn-outline">Vorbește cu noi întâi</a>
        </div>
    </div>
</section>

@endsection
