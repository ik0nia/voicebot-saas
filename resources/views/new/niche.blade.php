@extends('layouts.new')

@php
    $benefits = is_array($niche->benefits) ? $niche->benefits : [];
    $faq      = is_array($niche->faq) ? $niche->faq : [];
    $demo     = is_array($niche->demo_messages) ? $niche->demo_messages : [];
    $heroTitle    = $niche->hero_title    ?: ('Agent AI pentru ' . $niche->name);
    $heroSubtitle = $niche->hero_subtitle ?: ('Un agent AI antrenat pentru ' . $niche->name . ' — răspunde 24/7, în limba română, din documentele tale.');
    $heroEyebrow  = $niche->hero_eyebrow  ?: ('AGENT AI PENTRU ' . strtoupper($niche->name));
    $ctaPrimary   = $niche->cta_primary_text ?: 'Încearcă 7 zile gratuit';
    $ctaPrimaryHref = $niche->cta_primary_href ?: url('/register');
    $ctaSecondary = $niche->cta_secondary_text ?: 'Vorbește cu echipa';
    $ctaSecondaryHref = $niche->cta_secondary_href ?: route('new.contact');
    $nicheLabel   = $niche->vertical_label ?: $niche->name;
@endphp

@section('title', ($niche->meta_title ?: ('Agent AI pentru ' . $niche->name . ' — Sambla')))
@section('meta_description', $niche->meta_description ?: ('Agent AI conversațional pentru ' . $niche->name . '. Răspunde 24/7 în limba română, programări automate, integrări native, GDPR nativ.'))
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

{{-- TICKER --}}
<div class="bg-sand border-b border-line py-2 overflow-hidden">
    <div class="ticker flex gap-10 whitespace-nowrap text-xs mono text-muted">
        @for($i=0; $i<3; $i++)
            <span>✦ Agent AI pentru {{ strtolower($niche->name) }}</span>
            <span class="accent-text">●</span>
            <span>✦ Integrare Google Calendar</span>
            <span class="accent-text">●</span>
            <span>✦ Răspunsuri verificate</span>
            <span class="accent-text">●</span>
            <span>✦ GDPR nativ</span>
            <span class="accent-text">●</span>
            <span>✦ Română + engleză</span>
            <span class="accent-text">●</span>
        @endfor
    </div>
</div>

{{-- Breadcrumbs --}}
<nav class="max-w-7xl mx-auto px-6 pt-4 text-xs text-muted mono" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 flex-wrap">
        <li><a href="{{ route('new.home') }}" class="hover:text-ink">Sambla</a></li>
        <li>/</li>
        <li><a href="{{ route('new.home') }}#industrii" class="hover:text-ink">Industrii</a></li>
        <li>/</li>
        <li class="text-ink">{{ $niche->name }}</li>
    </ol>
</nav>

{{-- HERO --}}
<section class="hero-glow relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 pt-10 pb-20 lg:pt-14 lg:pb-24 grid lg:grid-cols-12 gap-12 items-start relative">
        <div class="lg:col-span-6 fade-up">
            <div class="chip accent-soft-bg mono text-[10px] mb-7" style="color:var(--accent-dark);">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full rounded-full accent-bg opacity-60 animate-ping"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 accent-bg"></span>
                </span>
                <span class="uppercase tracking-wider">{{ $heroEyebrow }}</span>
            </div>

            <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-7">
                {{ $heroTitle }}
            </h1>

            <p class="text-xl leading-relaxed text-muted mb-9 max-w-xl">{{ $heroSubtitle }}</p>

            <div class="flex flex-wrap gap-3 mb-9">
                <a href="{{ $ctaPrimaryHref }}" class="btn-primary">
                    {{ $ctaPrimary }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="{{ $ctaSecondaryHref }}" class="btn-outline">{{ $ctaSecondary }}</a>
            </div>

            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> 100% în română</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Setup 10 min</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> GDPR nativ</span>
            </div>
        </div>

        {{-- Dynamic demo chat from $niche->demo_messages --}}
        <div class="lg:col-span-6 fade-up" style="transition-delay: .15s">
            <div class="relative">
                <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%);"></div>
                <div class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid #E7E0CE; box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-line accent-soft-bg">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full accent-bg flex items-center justify-center">
                                <span class="text-white display text-base font-semibold">S</span>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-paper">
                                <span class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-60"></span>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">Agentul AI · {{ $nicheLabel }}</div>
                            <div class="text-xs text-muted flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Online · răspunde în sub 2 secunde
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-3" style="min-height: 380px; max-height: 480px; overflow:hidden;">
                        @forelse($demo as $msg)
                            @if(($msg['role'] ?? '') === 'bot')
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-br-sm text-[14.5px] leading-relaxed accent-bg text-white">{{ $msg['text'] ?? '' }}</div>
                                </div>
                            @else
                                <div class="flex">
                                    <div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14.5px] leading-relaxed bg-sand text-ink">{{ $msg['text'] ?? '' }}</div>
                                </div>
                            @endif
                        @empty
                            <div class="flex"><div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14.5px] bg-sand text-ink">Bună ziua! Cu ce vă putem ajuta?</div></div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-t border-line bg-paper flex items-center gap-2">
                        <svg class="w-4 h-4 accent-text shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
                        <span class="text-xs text-muted font-medium truncate">✓ Răspunsuri din documentele afacerii tale · GDPR nativ</span>
                    </div>
                </div>

                <div class="absolute -left-4 -bottom-4 bg-white rounded-2xl shadow-xl p-4 pr-5 flex items-center gap-3 border border-line max-w-[280px] float" style="animation-delay:.5s;">
                    <div class="w-10 h-10 rounded-xl accent-soft-bg accent-text flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold leading-tight">Acțiune completată</div>
                        <div class="text-xs text-muted">automat · fără operator</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick stats strip --}}
    <div class="max-w-7xl mx-auto px-6 pb-14">
        <div class="rounded-3xl bg-paper border border-line p-6 md:p-8 grid grid-cols-2 md:grid-cols-4 gap-6 fade-up">
            @foreach([
                ['&lt;2s','Răspuns mediu'],
                ['24/7','Disponibil non-stop'],
                ['100%','În limba română'],
                ['10 min','Setup complet'],
            ] as $s)
                <div class="text-center md:text-left">
                    <div class="display text-4xl md:text-5xl font-medium mb-1 stat-num accent-text">{!! $s[0] !!}</div>
                    <div class="text-xs mono uppercase tracking-wider text-muted">{{ $s[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PROBLEMA --}}
@if(!empty($niche->problem_text))
<section id="problema" class="py-24 bg-paper border-y border-line relative grain overflow-hidden">
    <div class="max-w-6xl mx-auto px-6 relative grid lg:grid-cols-12 gap-12 items-start">
        <div class="lg:col-span-5 fade-up lg:sticky lg:top-28">
            <div class="mono text-[11px] uppercase tracking-[0.2em] accent-text mb-4">◇ problema</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">
                {{ $niche->problem_title ?: 'Știi exact cum sună.' }}
            </h2>
        </div>

        <div class="lg:col-span-7 fade-up space-y-6" style="transition-delay:.1s;">
            @foreach(preg_split('/\n\n+|(?<=[.!?])\s+(?=[A-Z„ÎÂȘȚ])/u', $niche->problem_text, -1, PREG_SPLIT_NO_EMPTY) as $idx => $para)
                @continue(trim($para) === '')
                <p class="text-lg leading-relaxed {{ $idx === 0 ? 'text-ink text-xl' : 'text-muted' }}">{{ trim($para) }}</p>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- SOLUȚIA --}}
@if(!empty($niche->solution_text))
<section id="solutia" class="py-24 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-6 relative grid lg:grid-cols-12 gap-12 items-start">
        <div class="lg:col-span-7 fade-up space-y-6">
            <div class="mono text-[11px] uppercase tracking-[0.2em] accent-text mb-4">◇ soluția</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">
                {{ $niche->solution_title ?: 'Cum funcționează Sambla' }}
            </h2>
            @foreach(preg_split('/\n\n+|(?<=[.!?])\s+(?=[A-Z„ÎÂȘȚ])/u', $niche->solution_text, -1, PREG_SPLIT_NO_EMPTY) as $para)
                @continue(trim($para) === '')
                <p class="text-lg leading-relaxed text-muted">{{ trim($para) }}</p>
            @endforeach
        </div>

        <div class="lg:col-span-5 fade-up space-y-3" style="transition-delay:.1s;">
            @foreach([
                ['01','📚','Învață din datele tale','Conectezi site-ul, lista de servicii și documentele. Agentul citește tot și răspunde doar din surse reale.'],
                ['02','☎️','Răspunde 24/7','Pe telefon, site, WhatsApp, Messenger, Instagram — într-o română naturală.'],
                ['03','🔍','Învață ce nu știe','Îți semnalează automat întrebările la care n-a știut, ca să închizi golurile din baza de cunoștințe.'],
                ['04','🚨','Escaladează inteligent','Când detectează urgență sau frustrare, transferă instant la echipa ta cu tot contextul.'],
            ] as $step)
                <div class="niche-card rounded-2xl p-5 bg-paper border border-line flex items-start gap-4">
                    <div class="mono text-2xl font-semibold accent-text w-10 shrink-0">{{ $step[0] }}</div>
                    <div class="text-3xl shrink-0">{{ $step[1] }}</div>
                    <div class="flex-1">
                        <h4 class="font-semibold mb-1">{{ $step[2] }}</h4>
                        <p class="text-sm text-muted leading-relaxed">{{ $step[3] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- AVANTAJE / BENEFITS --}}
@if(count($benefits) > 0)
<section id="avantaje" class="py-24 bg-paper border-y border-line">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ avantaje</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
                Ce primești<br><em class="italic accent-text">din prima zi.</em>
            </h2>
            <p class="text-lg text-muted leading-relaxed">{{ count($benefits) }} capabilități care se traduc direct în clienți câștigați și timp eliberat pentru echipă.</p>
        </div>

        @php $emojis = ['📞','⏰','💰','🚨','💊','🌍','🎯','📊','🔔','✨']; @endphp

        <div class="grid md:grid-cols-2 gap-5">
            @foreach($benefits as $i => $b)
                <div class="niche-card rounded-3xl p-7 bg-cream border border-line fade-up" style="transition-delay: {{ 0.05 * $i }}s;">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center text-2xl shrink-0">{{ $emojis[$i % count($emojis)] }}</div>
                        <div class="flex-1">
                            <h3 class="display text-xl font-semibold mb-2">{{ $b['title'] ?? '' }}</h3>
                            <p class="text-sm text-muted leading-relaxed">{{ $b['description'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- BIG STATS --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ impact tipic</div>
            <h2 class="display text-4xl md:text-5xl font-medium leading-tight">Ce schimbă un agent AI<br>pentru <em class="italic accent-text">afacerea ta</em>.</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center">
                <div class="display text-[6rem] md:text-[7rem] stat-num leading-none font-medium accent-text">94<span class="text-ink">%</span></div>
                <div class="mt-2 text-sm font-semibold">rată preluare</div>
                <div class="mono text-[10px] text-muted mt-1">vs 58% recepție umană</div>
            </div>
            <div class="fade-up rounded-3xl p-8 bg-ink text-cream text-center" style="transition-delay:.1s;">
                <div class="display text-[6rem] md:text-[7rem] stat-num leading-none font-medium" style="color:#F2E59A;">−50<span class="text-ink">%</span></div>
                <div class="mt-2 text-sm font-semibold">reducere no-show</div>
                <div class="mono text-[10px] mt-1" style="color:#A8A29E;">cu reminder automat</div>
            </div>
            <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center" style="transition-delay:.2s;">
                <div class="display text-[6rem] md:text-[7rem] stat-num leading-none font-medium accent-text">3h</div>
                <div class="mt-2 text-sm font-semibold">eliberate / zi</div>
                <div class="mono text-[10px] text-muted mt-1">echipă eliberată de rutină</div>
            </div>
            <div class="fade-up rounded-3xl p-8 text-center" style="transition-delay:.3s; background: linear-gradient(135deg, var(--accent-soft) 0%, #FEC796 100%);">
                <div class="display text-[6rem] md:text-[7rem] leading-none font-medium">24/7</div>
                <div class="mt-2 text-sm font-semibold">disponibilitate</div>
                <div class="mono text-[10px] text-muted mt-1">zi & noapte, weekend</div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
@if(count($faq) > 0)
<section id="faq" class="py-24 bg-paper border-y border-line">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ întrebări frecvente</div>
            <h2 class="display text-5xl font-medium">Răspunsuri <em class="italic accent-text">sincere.</em></h2>
        </div>
        <div class="space-y-3 fade-up">
            @foreach($faq as $f)
                <details class="rounded-2xl bg-cream border border-line overflow-hidden group">
                    <summary class="px-6 py-5 flex items-center justify-between cursor-pointer list-none font-medium hover:bg-sand/50 transition">
                        <span class="pr-6">{{ $f['question'] ?? '' }}</span>
                        <svg class="chev w-4 h-4 text-muted transition-transform shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="px-6 pb-5 text-sm text-muted leading-relaxed whitespace-pre-line">{{ $f['answer'] ?? '' }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- RELATED --}}
@if(isset($relatedNiches) && $relatedNiches->count() > 0)
<section class="py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-end justify-between mb-10 fade-up flex-wrap gap-3">
            <div>
                <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ alte verticale</div>
                <h2 class="display text-3xl md:text-4xl font-medium">Explorează și alte <em class="italic">industrii</em></h2>
            </div>
            <a href="{{ route('new.home') }}#industrii" class="inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-ink">
                Vezi toate
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($relatedNiches as $rn)
                <a href="{{ route('new.niche', $rn->slug) }}" data-niche="{{ $rn->color_theme }}" class="niche-card block rounded-2xl p-4 bg-paper border border-line">
                    <div class="w-9 h-9 rounded-lg accent-soft-bg flex items-center justify-center mb-3">
                        <span class="w-2 h-2 rounded-full accent-bg"></span>
                    </div>
                    <div class="text-sm font-semibold leading-tight">{{ $rn->name }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA FINAL --}}
<section class="py-24">
    <div class="max-w-4xl mx-auto px-6">
        <div class="rounded-[2.5rem] bg-ink p-10 md:p-16 text-center relative overflow-hidden fade-up">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, color-mix(in srgb, var(--accent) 40%, transparent) 0%, transparent 70%);"></div>
            <div class="relative">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6" style="color:#F2E59A;">◇ gata să începi?</div>
                <h2 class="display text-4xl md:text-6xl font-medium leading-[1.05] mb-5 text-cream">
                    Agentul tău AI pentru<br>
                    <em class="italic accent-text">{{ strtolower($niche->name) }}</em><br>
                    te așteaptă.
                </h2>
                <p class="text-lg mb-10" style="color:#D7D3CA;">7 zile gratuit. Fără card. Îl pornești în 10 minute.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ $ctaPrimaryHref }}" class="btn-primary" style="background:#F2E59A; color:#1C1917;">
                        {{ $ctaPrimary }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ $ctaSecondaryHref }}" class="btn-outline" style="border-color: rgba(255,255,255,.4); color: #F5F1E8;">{{ $ctaSecondary }}</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
