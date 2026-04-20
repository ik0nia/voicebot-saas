@extends('layouts.new')

@php
    $benefits = is_array($niche->benefits) ? $niche->benefits : [];
    $faq      = is_array($niche->faq) ? $niche->faq : [];
    $demo     = is_array($niche->demo_messages) ? $niche->demo_messages : [];
    $heroTitle    = $niche->hero_title    ?: ('Agent AI pentru ' . $niche->name);
    $heroSubtitle = $niche->hero_subtitle ?: ('Un agent AI antrenat pentru ' . $niche->name . ' — răspunde clienților 24/7, în limba română, din documentele tale reale.');
    $heroEyebrow  = $niche->hero_eyebrow  ?: ('Agent AI pentru ' . strtoupper($niche->name));
    $ctaPrimary   = $niche->cta_primary_text ?: 'Începe gratuit 7 zile';
    $ctaPrimaryHref = $niche->cta_primary_href ?: url('/register');
    $ctaSecondary = $niche->cta_secondary_text ?: 'Vorbește cu echipa';
    $ctaSecondaryHref = $niche->cta_secondary_href ?: route('new.contact');
@endphp

@section('title', ($niche->meta_title ?: ('Agent AI pentru ' . $niche->name . ' — Sambla')) . ' | Sambla')
@section('meta_description', $niche->meta_description ?: ('Agent AI conversațional pentru ' . $niche->name . '. Răspunde clienților 24/7, programări automate, integrări native, GDPR nativ.'))
@section('og_title', 'Agent AI pentru ' . $niche->name)
@section('og_description', $niche->meta_description ?: $heroSubtitle)
@section('canonical', url('/new/pentru/' . $niche->slug))

@section('jsonld')
<script type="application/ld+json">
{
    "@context":"https://schema.org",
    "@graph":[
        {
            "@type":"Service",
            "name":{!! json_encode('Agent AI pentru ' . $niche->name, JSON_UNESCAPED_UNICODE) !!},
            "provider":{"@id":"https://sambla.ro/#organization"},
            "areaServed":{"@type":"Country","name":"Romania"},
            "description":{!! json_encode($heroSubtitle, JSON_UNESCAPED_UNICODE) !!},
            "url":{!! json_encode(url('/new/pentru/' . $niche->slug), JSON_UNESCAPED_UNICODE) !!}
        },
        {
            "@type":"BreadcrumbList",
            "itemListElement":[
                {"@type":"ListItem","position":1,"name":"Acasă","item":{!! json_encode(url('/new'), JSON_UNESCAPED_UNICODE) !!}},
                {"@type":"ListItem","position":2,"name":"Industrii","item":{!! json_encode(url('/new') . '#industrii', JSON_UNESCAPED_UNICODE) !!}},
                {"@type":"ListItem","position":3,"name":{!! json_encode($niche->name, JSON_UNESCAPED_UNICODE) !!},"item":{!! json_encode(url('/new/pentru/' . $niche->slug), JSON_UNESCAPED_UNICODE) !!}}
            ]
        }
        @if(count($faq) > 0)
        ,{
            "@type":"FAQPage",
            "mainEntity":[
                @foreach($faq as $i => $item)
                {"@type":"Question","name":{!! json_encode($item['question'] ?? '', JSON_UNESCAPED_UNICODE) !!},"acceptedAnswer":{"@type":"Answer","text":{!! json_encode($item['answer'] ?? '', JSON_UNESCAPED_UNICODE) !!}}}@if(!$loop->last),@endif
                @endforeach
            ]
        }
        @endif
    ]
}
</script>
@endsection

@section('content')

{{-- Breadcrumbs --}}
<nav class="max-w-7xl mx-auto px-6 pt-6 text-xs mono" style="color: var(--muted);" aria-label="Breadcrumb">
    <ol class="flex items-center gap-2 flex-wrap">
        <li><a href="{{ route('new.home') }}" class="hover:text-ink">Acasă</a></li>
        <li>/</li>
        <li><a href="{{ route('new.home') }}#industrii" class="hover:text-ink">Industrii</a></li>
        <li>/</li>
        <li class="text-ink">{{ $niche->name }}</li>
    </ol>
</nav>

{{-- HERO --}}
<section class="hero-glow relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 pt-12 pb-20 lg:pt-16 lg:pb-28 grid lg:grid-cols-12 gap-12 items-start relative">
        <div class="lg:col-span-7 fade-up">
            <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider">
                @if(!empty($niche->icon_svg))
                    <span class="w-3.5 h-3.5 inline-flex">{!! $niche->icon_svg !!}</span>
                @endif
                <span>{{ $heroEyebrow }}</span>
            </div>

            <h1 class="display h-display-xl mb-6">
                {{ $heroTitle }}
            </h1>

            <p class="text-lg md:text-xl leading-relaxed mb-8 max-w-2xl" style="color: var(--muted);">{{ $heroSubtitle }}</p>

            <div class="flex flex-wrap gap-3 mb-8">
                <a href="{{ $ctaPrimaryHref }}" class="btn btn-primary">
                    {{ $ctaPrimary }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="{{ $ctaSecondaryHref }}" class="btn btn-outline">{{ $ctaSecondary }}</a>
            </div>

            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm" style="color: var(--muted);">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    Fără card
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    Setup 10 min
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    Construit în RO
                </span>
            </div>
        </div>

        {{-- Dynamic demo chat from niche.demo_messages --}}
        <div class="lg:col-span-5 fade-up" style="transition-delay: .15s">
            <div class="relative">
                <div class="absolute -inset-6 rounded-[3rem] blur-3xl opacity-30" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%);"></div>
                <div class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid var(--line); box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-line accent-soft-bg">
                        <div class="w-10 h-10 rounded-full accent-bg flex items-center justify-center">
                            <span class="display text-base font-semibold" style="color:#fff;">S</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">Agentul AI · {{ $niche->name }}</div>
                            <div class="text-xs flex items-center gap-1.5" style="color: var(--muted);">
                                <span class="w-1.5 h-1.5 rounded-full" style="background: var(--emerald);"></span>
                                Online · răspunde instant
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-3" style="min-height: 380px; max-height: 480px; overflow:hidden;">
                        @forelse($demo as $msg)
                            @if(($msg['role'] ?? '') === 'bot')
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-br-sm text-sm accent-bg" style="color:#fff;">
                                        {{ $msg['text'] ?? '' }}
                                    </div>
                                </div>
                            @else
                                <div class="flex">
                                    <div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-sm bg-white border border-line">
                                        {{ $msg['text'] ?? '' }}
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="flex">
                                <div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-sm bg-white border border-line">
                                    Bună ziua! Cu ce vă putem ajuta?
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-t border-line bg-paper flex items-center gap-2">
                        <svg class="w-4 h-4 accent-text shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.86-9.81a.75.75 0 00-1.21-.88l-3.48 4.79-1.88-1.88a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.14-.09l4-5.5z"/></svg>
                        <span class="text-xs font-medium" style="color: var(--muted);">Răspunsuri din documentele afacerii tale</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- PROBLEMA --}}
@if(!empty($niche->problem_text))
<section class="py-20 bg-paper">
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-12 gap-10">
        <div class="md:col-span-4 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ contextul tău</div>
            <h2 class="display h-display-m mb-4">{{ $niche->problem_title ?: 'Problema reală pe care o rezolvăm' }}</h2>
        </div>
        <div class="md:col-span-8 fade-up" style="transition-delay:.1s;">
            <p class="text-lg leading-relaxed whitespace-pre-line" style="color: var(--muted);">{{ $niche->problem_text }}</p>
        </div>
    </div>
</section>
@endif

{{-- SOLUȚIA --}}
@if(!empty($niche->solution_text))
<section class="py-20">
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-12 gap-10">
        <div class="md:col-span-4 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ cum ajută Sambla</div>
            <h2 class="display h-display-m mb-4">{{ $niche->solution_title ?: 'Cum funcționează pentru tine' }}</h2>
        </div>
        <div class="md:col-span-8 fade-up" style="transition-delay:.1s;">
            <p class="text-lg leading-relaxed whitespace-pre-line" style="color: var(--muted);">{{ $niche->solution_text }}</p>
        </div>
    </div>
</section>
@endif

{{-- BENEFICII --}}
@if(count($benefits) > 0)
<section class="py-20 md:py-24 bg-paper">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ beneficii concrete</div>
            <h2 class="display h-display-l mb-5">
                Ce primești<br>
                <span class="italic accent-text">din prima zi.</span>
            </h2>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($benefits as $i => $b)
                <div class="fade-up rounded-3xl p-7 bg-cream border border-line" style="transition-delay: {{ 0.05 * $i }}s;">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl accent-soft-bg flex items-center justify-center">
                            <svg class="w-5 h-5 accent-text" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="mono text-[10px] uppercase tracking-wider" style="color: var(--muted);">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3 class="display text-lg font-semibold mb-2 leading-tight">{{ $b['title'] ?? '' }}</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $b['description'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- MOD DE LUCRU SCURT — 3 pași, nu expun arhitectura --}}
<section class="py-20 md:py-24 grain relative">
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="max-w-xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ cum arată traseul</div>
            <h2 class="display h-display-l">
                De la prima conversație<br>
                <span class="italic accent-text">la client fidel.</span>
            </h2>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="fade-up rounded-3xl p-7 bg-paper border border-line">
                <div class="display text-5xl font-semibold accent-text mb-3">01</div>
                <h3 class="display text-xl font-semibold mb-2">Contact</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">Clientul sună sau scrie pe site. Agentul răspunde instant, în tonul afacerii tale, în limba română.</p>
            </div>
            <div class="fade-up rounded-3xl p-7 bg-paper border border-line" style="transition-delay:.1s;">
                <div class="display text-5xl font-semibold accent-text mb-3">02</div>
                <h3 class="display text-xl font-semibold mb-2">Acțiune</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">Programare în calendar, recomandare produs, captare lead — orice decidem împreună că înseamnă un rezultat.</p>
            </div>
            <div class="fade-up rounded-3xl p-7 bg-ink" style="transition-delay:.2s;">
                <div class="display text-5xl font-semibold mb-3" style="color: var(--sun);">03</div>
                <h3 class="display text-xl font-semibold mb-2" style="color: var(--cream);">Transfer inteligent</h3>
                <p class="text-sm leading-relaxed" style="color:#D7D3CA;">Când apare ceva sensibil sau complex, agentul escaladează la echipa ta cu tot contextul deja scris.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
@if(count($faq) > 0)
<section class="py-20 md:py-24 bg-paper">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ întrebări frecvente</div>
            <h2 class="display h-display-l">Răspunsuri <em class="italic accent-text">sincere.</em></h2>
        </div>
        <div class="space-y-3 fade-up">
            @foreach($faq as $f)
                <details class="rounded-2xl bg-cream border border-line px-5 py-4">
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <span class="font-semibold pr-6">{{ $f['question'] ?? '' }}</span>
                        <svg class="chev w-4 h-4 shrink-0 transition" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-3 leading-relaxed text-sm whitespace-pre-line" style="color: var(--muted);">{{ $f['answer'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- RELATED INDUSTRIES --}}
@if(isset($relatedNiches) && $relatedNiches->count() > 0)
<section class="py-20">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-end justify-between mb-10 fade-up">
            <div>
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ alte industrii</div>
                <h2 class="display h-display-m">Explorează și alte <em class="italic">verticale</em></h2>
            </div>
            <a href="{{ route('new.home') }}#industrii" class="hidden md:inline-flex items-center gap-2 text-sm font-medium hover:text-ink" style="color: var(--muted);">
                Vezi toate
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($relatedNiches as $rn)
                <a href="{{ route('new.niche', $rn->slug) }}" class="niche-card block rounded-2xl p-4 bg-paper border border-line">
                    <div class="w-9 h-9 rounded-lg accent-soft-bg flex items-center justify-center mb-3">
                        @if(!empty($rn->icon_svg))
                            <span class="accent-text w-4 h-4 inline-flex">{!! $rn->icon_svg !!}</span>
                        @else
                            <svg class="w-4 h-4 accent-text" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        @endif
                    </div>
                    <div class="text-sm font-semibold leading-tight">{{ $rn->name }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA FINAL --}}
<section class="py-20 md:py-24">
    <div class="max-w-4xl mx-auto px-6">
        <div class="rounded-[2rem] p-10 md:p-14 text-center relative overflow-hidden" style="background: var(--ink);">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, color-mix(in srgb, var(--accent) 40%, transparent) 0%, transparent 70%);"></div>
            <div class="relative">
                <h2 class="display h-display-l mb-5" style="color: var(--cream);">
                    Gata pentru primul agent AI<br>
                    <span class="italic" style="color: var(--sun);">pentru {{ $niche->name }}?</span>
                </h2>
                <p class="text-lg mb-8" style="color:#D7D3CA;">7 zile gratuit. Fără card. Îl pornești în 10 minute.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ $ctaPrimaryHref }}" class="btn btn-primary" style="background: var(--sun); color: var(--ink);">
                        {{ $ctaPrimary }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ $ctaSecondaryHref }}" class="btn btn-outline" style="border-color: rgba(255,255,255,.4); color: var(--cream);">{{ $ctaSecondary }}</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
