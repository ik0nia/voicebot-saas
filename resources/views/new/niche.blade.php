@extends('layouts.new')

@php
    $benefits   = is_array($niche->benefits) ? $niche->benefits : [];
    $faq        = is_array($niche->faq) ? $niche->faq : [];
    $demo       = is_array($niche->demo_messages) ? $niche->demo_messages : [];
    $nicheIcons = config('niche-icons.' . $niche->slug, []);
    $heroTitle    = $niche->hero_title    ?: ('Agent AI pentru ' . $niche->name);
    $heroSubtitle = $niche->hero_subtitle ?: ('Un agent AI antrenat pentru ' . $niche->name . ' — răspunde 24/7, în limba română, din documentele tale.');
    $heroEyebrow  = $niche->hero_eyebrow  ?: ('AGENT AI PENTRU ' . strtoupper($niche->name));
    $ctaPrimary   = $niche->cta_primary_text ?: 'Încearcă 7 zile gratuit';
    $ctaPrimaryHref = $niche->cta_primary_href ?: url('/register');
    $ctaSecondary = $niche->cta_secondary_text ?: 'Cere demo personalizat';
    $ctaSecondaryHref = $niche->cta_secondary_href ?: '#contact';
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

{{-- HERO --}}
<section class="hero-glow relative overflow-hidden">
    {{-- Floating niche-specific background icons (5 SVGs scattered cu opacity 0.07–0.10) --}}
    @if(!empty($nicheIcons))
        <div class="absolute inset-0 overflow-hidden pointer-events-none hidden md:block" aria-hidden="true">
            @foreach([
                ['t' => 'top-8 left-[4%]',       's' => 'w-24 h-24', 'r' => '-rotate-12', 'o' => 'opacity-[0.10]'],
                ['t' => 'top-32 right-[6%]',     's' => 'w-32 h-32', 'r' => 'rotate-6',   'o' => 'opacity-[0.08]'],
                ['t' => 'bottom-24 left-[10%]',  's' => 'w-20 h-20', 'r' => 'rotate-12',  'o' => 'opacity-[0.09]'],
                ['t' => 'bottom-10 right-[18%]', 's' => 'w-16 h-16', 'r' => '-rotate-6',  'o' => 'opacity-[0.10]'],
                ['t' => 'top-1/2 left-[46%]',   's' => 'w-10 h-10', 'r' => 'rotate-3',    'o' => 'opacity-[0.07]'],
            ] as $i => $pos)
                <svg class="absolute {{ $pos['t'] }} {{ $pos['s'] }} {{ $pos['r'] }} {{ $pos['o'] }} accent-text" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    {!! $nicheIcons[$i % count($nicheIcons)] !!}
                </svg>
            @endforeach
        </div>
    @endif

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

{{-- ANNOTATED CHAT SHOWCASE — 4 adnotări + săgeți curbe (hidden pe mobile) --}}
<section class="py-20 bg-cream border-y border-line relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ cum vorbește cu clienții tăi</div>
            <h2 class="display h-display-m">Iată cum <em class="italic accent-text">sună o conversație reală</em>.</h2>
        </div>

        <div class="grid lg:grid-cols-12 gap-8 items-center">

            {{-- Adnotări stânga (hidden pe mobile) --}}
            <div class="hidden lg:block lg:col-span-3 space-y-12">
                @foreach([
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>', 'title' => 'Răspuns instant', 'desc' => 'Sub 2 secunde, oricând'],
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>', 'title' => 'Disponibil 24/7', 'desc' => 'Inclusiv noaptea și weekend'],
                ] as $note)
                    <div class="text-right">
                        <div class="inline-flex items-center justify-end gap-3">
                            <div>
                                <div class="font-semibold text-sm">{{ $note['title'] }}</div>
                                <div class="text-xs" style="color: var(--muted);">{{ $note['desc'] }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-xl accent-bg text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $note['icon'] !!}</svg>
                            </div>
                        </div>
                        <svg class="ml-auto mt-2 w-24 h-6 accent-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 100 20">
                            <path d="M0 10 Q 30 0, 60 10 T 95 10" stroke-linecap="round" stroke-dasharray="3 4"/>
                            <path d="M88 5 L 95 10 L 88 15" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                    </div>
                @endforeach
            </div>

            {{-- Chat în mijloc --}}
            <div class="lg:col-span-6">
                <div class="relative">
                    <div class="absolute -inset-6 rounded-[2.5rem] blur-2xl opacity-50" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%);"></div>
                    <div class="relative rounded-[2rem] overflow-hidden bg-paper border border-line" style="box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
                        <div class="flex items-center gap-3 px-5 py-3.5 border-b border-line bg-cream">
                            <div class="w-9 h-9 rounded-full accent-bg flex items-center justify-center">
                                <span class="text-white display text-sm font-semibold">S</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold">Agent AI · {{ $nicheLabel }}</div>
                                <div class="text-[11px] flex items-center gap-1.5" style="color: #047857;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Online acum
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-5 space-y-3 bg-paper" style="min-height: 320px;">
                            @forelse(array_slice($demo, 0, 4) as $msg)
                                @if(($msg['role'] ?? '') === 'bot')
                                    <div class="flex justify-end"><div class="max-w-[80%] px-4 py-2.5 rounded-2xl rounded-br-sm text-[14px] leading-relaxed accent-bg text-white">{{ $msg['text'] ?? '' }}</div></div>
                                @else
                                    <div class="flex"><div class="max-w-[80%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14px] leading-relaxed bg-sand text-ink">{{ $msg['text'] ?? '' }}</div></div>
                                @endif
                            @empty
                                <div class="flex"><div class="max-w-[80%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14px] bg-sand text-ink">Bună ziua! Cu ce vă putem ajuta?</div></div>
                                <div class="flex justify-end"><div class="max-w-[80%] px-4 py-2.5 rounded-2xl rounded-br-sm text-[14px] accent-bg text-white">Vă pot oferi informații despre serviciile și programările noastre. Ce vă interesează?</div></div>
                            @endforelse
                        </div>
                        <div class="px-4 py-3 border-t border-line flex items-center gap-2 bg-cream">
                            <div class="flex-1 bg-white rounded-full px-4 py-2 text-xs text-muted border border-line">Scrie un mesaj…</div>
                            <button class="w-8 h-8 rounded-full accent-bg text-white flex items-center justify-center" aria-label="Trimite">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Adnotări dreapta (hidden pe mobile) --}}
            <div class="hidden lg:block lg:col-span-3 space-y-12">
                @foreach([
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>', 'title' => 'Antrenat pe datele tale', 'desc' => 'Tarife, produse, programe'],
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>', 'title' => 'Escaladează la operator', 'desc' => 'Când e nevoie de un om'],
                ] as $note)
                    <div class="text-left">
                        <svg class="mr-auto mb-2 w-24 h-6 accent-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 100 20">
                            <path d="M5 10 Q 30 20, 60 10 T 100 10" stroke-linecap="round" stroke-dasharray="3 4"/>
                            <path d="M12 5 L 5 10 L 12 15" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <div class="inline-flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl accent-bg text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $note['icon'] !!}</svg>
                            </div>
                            <div>
                                <div class="font-semibold text-sm">{{ $note['title'] }}</div>
                                <div class="text-xs" style="color: var(--muted);">{{ $note['desc'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

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

{{-- DEMO 3 SCENARII — chat bubbles laterale, ca pe site-ul actual --}}
@php
    /* Generate 3 demo scenarios from the niche's demo_messages + niche context */
    $demoScenarios = [];
    if (count($demo) >= 2) {
        /* First scenario: programare / main intent from real demo_messages */
        $demoScenarios[] = [
            'title'    => 'Cea mai frecventă cerere',
            'subtitle' => 'Răspuns din baza de cunoștințe',
            'icon'     => '📅',
            'msgs'     => array_slice($demo, 0, 4),
        ];
    }
    /* Always show two extra generic scenarios tailored to the niche */
    $demoScenarios[] = [
        'title'    => 'Întrebare de preț',
        'subtitle' => 'Din lista ta de servicii',
        'icon'     => '💰',
        'msgs'     => [
            ['role'=>'user','text'=>'Cât costă?'],
            ['role'=>'bot','text'=>'Tariful depinde de ce vă interesează. Pot să vă trimit lista completă cu prețuri, sau preferați să vă recomand ceva în funcție de nevoie?'],
            ['role'=>'user','text'=>'Dați-mi lista.'],
            ['role'=>'bot','text'=>'✓ V-am trimis pe WhatsApp. Aveți vreo întrebare specifică la care să vă ajut chiar acum?'],
        ],
    ];
    $demoScenarios[] = [
        'title'    => 'Situație sensibilă',
        'subtitle' => 'Detectează + escaladează',
        'icon'     => '🚨',
        'msgs'     => [
            ['role'=>'user','text'=>'Am o problemă urgentă și trebuie să vorbesc cu cineva acum.'],
            ['role'=>'bot','text'=>'Înțeleg, vă ajut imediat. Puteți să îmi spuneți scurt despre ce e vorba, ca să anunț colegul potrivit?'],
            ['role'=>'user','text'=>'(descrie situația)'],
            ['role'=>'bot','text'=>'✓ Colegul de gardă e notificat. Vă sună în maximum 10 minute pe numărul de pe care vorbim.'],
        ],
    ];
    $demoScenarios = array_slice($demoScenarios, 0, 3);
@endphp

@if(count($demoScenarios) > 0)
<section id="demo" class="py-24 bg-paper border-y border-line">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ conversații reale</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Așa vorbește cu <em class="italic accent-text">clienții tăi</em>.</h2>
            <p class="text-lg text-muted">Trei scenarii tipice pentru {{ strtolower($niche->name) }} — exact tipul de răspunsuri pe care le dă agentul AI.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            @foreach($demoScenarios as $scenario)
                <div class="niche-card rounded-3xl overflow-hidden bg-cream border border-line fade-up">
                    <div class="p-6 pb-4 border-b border-line accent-soft-bg">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ $scenario['icon'] }}</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">{{ $scenario['title'] }}</div>
                                <div class="text-xs text-muted">{{ $scenario['subtitle'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 space-y-2.5 min-h-[260px]">
                        @foreach($scenario['msgs'] as $m)
                            @if(($m['role'] ?? '') === 'bot')
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] px-3.5 py-2 rounded-2xl rounded-br-sm text-[13px] leading-relaxed text-white accent-bg">{{ $m['text'] ?? '' }}</div>
                                </div>
                            @else
                                <div class="flex">
                                    <div class="max-w-[85%] px-3.5 py-2 rounded-2xl rounded-bl-sm text-[13px] leading-relaxed bg-sand text-ink">{{ $m['text'] ?? '' }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

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

{{-- LEAD FORM — demo personalizat pentru nișă --}}
<section id="contact" class="py-20 bg-cream border-t border-line relative overflow-hidden">
    <div class="max-w-2xl mx-auto px-6 relative">
        <div class="text-center mb-10 fade-up">
            <div class="chip chip-soft mb-5 mono text-[11px] uppercase tracking-wider inline-flex">◇ hai să vorbim</div>
            <h2 class="display h-display-lg mb-4">Vrei un demo <em class="italic accent-text">personalizat</em>?</h2>
            <p class="text-lg leading-relaxed" style="color: var(--muted);">Trimite-ne link-ul site-ului sau al paginii de Facebook — îți arătăm agentul rulând pe datele afacerii tale, nu pe un demo generic.</p>
        </div>

        @if(session('niche_lead_success'))
            <div class="mb-6 rounded-2xl px-5 py-4 text-sm font-semibold" style="background:#D1FAE5; color:#047857;">
                {{ session('niche_lead_success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl px-5 py-4 text-sm" style="background: var(--accent-soft); color: var(--accent-dark);">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <form action="{{ route('public.niche.lead', $niche->slug) }}" method="POST" class="rounded-3xl p-7 md:p-8 bg-paper border border-line space-y-5 fade-up">
            @csrf
            <input type="hidden" name="source_url" value="{{ url()->current() }}">
            {{-- Honeypot — real users leave it empty --}}
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">

            <label class="block">
                <span class="text-sm font-medium mb-1 block">Nume <span style="color: var(--accent);">*</span></span>
                <input required name="name" type="text" value="{{ old('name') }}" placeholder="Numele tău"
                       class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                       style="--tw-ring-color: var(--accent);">
            </label>

            <label class="block">
                <span class="text-sm font-medium mb-1 block">Site sau pagina de Facebook <span style="color: var(--muted);" class="font-normal">— ca să personalizăm demo-ul</span></span>
                <input name="website_url" type="text" value="{{ old('website_url') }}" placeholder="ex: www.afacereata.ro sau facebook.com/afacereata"
                       class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                       style="--tw-ring-color: var(--accent);">
            </label>

            <div class="grid sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm font-medium mb-1 block">E-mail <span style="color: var(--accent);">*</span></span>
                    <input required name="email" type="email" value="{{ old('email') }}" placeholder="nume@afacerea-ta.ro"
                           class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                           style="--tw-ring-color: var(--accent);">
                </label>
                <label class="block">
                    <span class="text-sm font-medium mb-1 block">Telefon <span style="color: var(--accent);">*</span></span>
                    <input required name="phone" type="tel" value="{{ old('phone') }}" placeholder="+40 7xx xxx xxx"
                           class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                           style="--tw-ring-color: var(--accent);">
                </label>
            </div>

            <label class="block">
                <span class="text-sm font-medium mb-1 block">Mesaj <span style="color: var(--muted);" class="font-normal">(opțional)</span></span>
                <textarea name="message" rows="4"
                          placeholder="Ce ai vrea să facă agentul? Câți clienți îți sună zilnic?"
                          class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                          style="--tw-ring-color: var(--accent);">{{ old('message') }}</textarea>
            </label>

            <button type="submit" class="btn btn-primary w-full justify-center">
                Vreau demo personalizat
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>

            <p class="text-xs text-center pt-1" style="color: var(--muted);">
                Prin trimiterea formularului ești de acord cu <a href="{{ route('new.legal.confidentialitate') }}" class="underline accent-text">politica de confidențialitate</a>.
            </p>
        </form>
    </div>
</section>

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
