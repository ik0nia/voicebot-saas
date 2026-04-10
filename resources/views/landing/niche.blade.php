@extends('layouts.app')

@php
    /** @var \App\Models\Niche $niche */
    /** @var array $theme */
    $metaTitle = $niche->meta_title ?: ("Agent AI și chatbot pentru {$niche->name} — Sambla");
    $metaDesc = $niche->meta_description ?: ("Sambla oferă agenți AI conversaționali pentru " . $niche->vertical_label . " — răspund clienților 24/7, în limba română, pe chat și telefon.");
    $pageUrl = url()->current();

    $faqItems = is_array($niche->faq) ? $niche->faq : [];
    $benefits = is_array($niche->benefits) ? $niche->benefits : [];
    $rawDemo = is_array($niche->demo_messages) ? $niche->demo_messages : [];
    // Normalize: JSON seeds use inconsistent keys (role/sender/from) and
    // values (user/client, bot/assistant). Coerce to {role: user|bot, text}.
    $demoMessages = array_values(array_filter(array_map(function ($m) {
        $r = strtolower((string)($m['role'] ?? $m['sender'] ?? $m['from'] ?? ''));
        $isUser = in_array($r, ['user', 'client', 'customer', 'human'], true);
        $text = $m['text'] ?? $m['message'] ?? $m['content'] ?? '';
        return $text ? ['role' => $isUser ? 'user' : 'bot', 'text' => $text] : null;
    }, $rawDemo)));
    $nicheIcons = config('niche-icons.' . $niche->slug, []);

    // Map color theme to concrete Tailwind utilities. Single source of truth
    // — avoids the dynamic class hell. Each theme provides high-contrast
    // foreground/background pairs.
    $palettes = [
        'red'     => ['bg600' => 'bg-red-600',     'bg700' => 'bg-red-700',     'text600' => 'text-red-600',     'text700' => 'text-red-700',     'bg50' => 'bg-red-50',     'bg100' => 'bg-red-100',     'border200' => 'border-red-200',     'ring' => 'ring-red-500/30',     'hex' => '#dc2626'],
        'emerald' => ['bg600' => 'bg-emerald-600', 'bg700' => 'bg-emerald-700', 'text600' => 'text-emerald-600', 'text700' => 'text-emerald-700', 'bg50' => 'bg-emerald-50', 'bg100' => 'bg-emerald-100', 'border200' => 'border-emerald-200', 'ring' => 'ring-emerald-500/30', 'hex' => '#059669'],
        'blue'    => ['bg600' => 'bg-blue-600',    'bg700' => 'bg-blue-700',    'text600' => 'text-blue-600',    'text700' => 'text-blue-700',    'bg50' => 'bg-blue-50',    'bg100' => 'bg-blue-100',    'border200' => 'border-blue-200',    'ring' => 'ring-blue-500/30',    'hex' => '#2563eb'],
        'amber'   => ['bg600' => 'bg-amber-600',   'bg700' => 'bg-amber-700',   'text600' => 'text-amber-600',   'text700' => 'text-amber-700',   'bg50' => 'bg-amber-50',   'bg100' => 'bg-amber-100',   'border200' => 'border-amber-200',   'ring' => 'ring-amber-500/30',   'hex' => '#d97706'],
        'rose'    => ['bg600' => 'bg-rose-600',    'bg700' => 'bg-rose-700',    'text600' => 'text-rose-600',    'text700' => 'text-rose-700',    'bg50' => 'bg-rose-50',    'bg100' => 'bg-rose-100',    'border200' => 'border-rose-200',    'ring' => 'ring-rose-500/30',    'hex' => '#e11d48'],
        'purple'  => ['bg600' => 'bg-purple-600',  'bg700' => 'bg-purple-700',  'text600' => 'text-purple-600',  'text700' => 'text-purple-700',  'bg50' => 'bg-purple-50',  'bg100' => 'bg-purple-100',  'border200' => 'border-purple-200',  'ring' => 'ring-purple-500/30',  'hex' => '#9333ea'],
        'indigo'  => ['bg600' => 'bg-indigo-600',  'bg700' => 'bg-indigo-700',  'text600' => 'text-indigo-600',  'text700' => 'text-indigo-700',  'bg50' => 'bg-indigo-50',  'bg100' => 'bg-indigo-100',  'border200' => 'border-indigo-200',  'ring' => 'ring-indigo-500/30',  'hex' => '#4f46e5'],
        'teal'    => ['bg600' => 'bg-teal-600',    'bg700' => 'bg-teal-700',    'text600' => 'text-teal-600',    'text700' => 'text-teal-700',    'bg50' => 'bg-teal-50',    'bg100' => 'bg-teal-100',    'border200' => 'border-teal-200',    'ring' => 'ring-teal-500/30',    'hex' => '#0d9488'],
        'cyan'    => ['bg600' => 'bg-cyan-600',    'bg700' => 'bg-cyan-700',    'text600' => 'text-cyan-600',    'text700' => 'text-cyan-700',    'bg50' => 'bg-cyan-50',    'bg100' => 'bg-cyan-100',    'border200' => 'border-cyan-200',    'ring' => 'ring-cyan-500/30',    'hex' => '#0891b2'],
        'orange'  => ['bg600' => 'bg-orange-600',  'bg700' => 'bg-orange-700',  'text600' => 'text-orange-600',  'text700' => 'text-orange-700',  'bg50' => 'bg-orange-50',  'bg100' => 'bg-orange-100',  'border200' => 'border-orange-200',  'ring' => 'ring-orange-500/30',  'hex' => '#ea580c'],
    ];
    $p = $palettes[$niche->color_theme] ?? $palettes['red'];

    // Possessive form per niche — "cabinetul tău", "biroul tău", etc.
    // Used in headings where "afacerea ta" doesn't fit (notari, avocați, cabinete).
    $possessive = [
        'cabinete-stomatologice' => 'cabinetul tău stomatologic',
        'cabinete-medicale'      => 'cabinetul tău medical',
        'birouri-avocatura'      => 'biroul tău de avocatură',
        'birouri-notariale'      => 'biroul tău notarial',
        'firme-contabilitate'    => 'firma ta de contabilitate',
        'firme-curatenie'        => 'firma ta de curățenie',
        'salon-beauty'           => 'salonul tău',
        'service-auto'           => 'service-ul tău auto',
        'magazine-online'        => 'magazinul tău online',
        'agentii-imobiliare'     => 'agenția ta imobiliară',
        'restaurante-delivery'   => 'restaurantul tău',
        'psihologie-psihoterapie'=> 'cabinetul tău de psihologie',
        'scoli-limbi-straine'    => 'școala ta de limbi străine',
        'agentii-turism'         => 'agenția ta de turism',
        'clinici-veterinare'     => 'clinica ta veterinară',
        'pensiuni-hoteluri-mici' => 'pensiunea ta',
        'optica-medicala'        => 'optica ta medicală',
    ][$niche->slug] ?? 'afacerea ta';
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDesc)

@section('jsonld')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            ['@type' => 'WebPage', '@id' => $pageUrl . '#webpage', 'url' => $pageUrl, 'name' => $metaTitle, 'description' => $metaDesc, 'inLanguage' => 'ro-RO', 'isPartOf' => ['@id' => 'https://sambla.ro/#website']],
            ['@type' => 'Service', '@id' => $pageUrl . '#service', 'serviceType' => 'Agent AI conversațional pentru ' . $niche->vertical_label, 'name' => 'Sambla AI pentru ' . $niche->vertical_label, 'description' => $metaDesc, 'provider' => ['@id' => 'https://sambla.ro/#organization'], 'areaServed' => ['@type' => 'Country', 'name' => 'Romania']],
            !empty($faqItems) ? ['@type' => 'FAQPage', '@id' => $pageUrl . '#faq', 'mainEntity' => array_map(fn($item) => ['@type' => 'Question', 'name' => $item['question'] ?? ($item['q'] ?? ''), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer'] ?? ($item['a'] ?? '')]], $faqItems)] : null,
        ])),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('content')

{{-- ============================== HERO ============================== --}}
<section class="relative overflow-hidden {{ $p['bg50'] }}">
    {{-- Floating background icons specific to this niche --}}
    @if(!empty($nicheIcons))
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            @foreach([
                ['t' => 'top-12 left-[6%]',     's' => 'w-20 h-20', 'r' => '-rotate-12',  'o' => 'opacity-[0.10]'],
                ['t' => 'top-24 right-[8%]',    's' => 'w-28 h-28', 'r' => 'rotate-6',    'o' => 'opacity-[0.08]'],
                ['t' => 'bottom-16 left-[12%]', 's' => 'w-24 h-24', 'r' => 'rotate-12',   'o' => 'opacity-[0.09]'],
                ['t' => 'bottom-24 right-[15%]','s' => 'w-16 h-16', 'r' => '-rotate-6',   'o' => 'opacity-[0.10]'],
                ['t' => 'top-1/2 left-[42%]',   's' => 'w-12 h-12', 'r' => 'rotate-3',    'o' => 'opacity-[0.07]'],
            ] as $i => $pos)
                <svg class="absolute {{ $pos['t'] }} {{ $pos['s'] }} {{ $pos['r'] }} {{ $pos['o'] }} {{ $p['text700'] }}" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    {!! $nicheIcons[$i % count($nicheIcons)] !!}
                </svg>
            @endforeach
        </div>
    @endif

    {{-- Soft gradient blob --}}
    <div class="absolute -top-24 -right-24 w-[500px] h-[500px] rounded-full {{ $p['bg100'] }} blur-3xl opacity-60"></div>

    <div class="container-custom relative z-10 pt-16 pb-20 lg:pt-24 lg:pb-28">
        {{-- Breadcrumb --}}
        <nav class="text-xs font-semibold mb-10 flex items-center gap-2 text-slate-500" aria-label="Breadcrumb">
            <a href="/" class="hover:text-slate-900 transition">Sambla</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-500">Pentru afaceri</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="{{ $p['text700'] }}">{{ $niche->name }}</span>
        </nav>

        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            <div class="lg:col-span-7">
                @if($niche->hero_eyebrow)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider {{ $p['bg600'] }} text-white shadow-lg shadow-slate-900/10 mb-7">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                        {{ $niche->hero_eyebrow }}
                    </span>
                @endif

                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold tracking-tight text-slate-900 leading-[1.05] mb-7">
                    {{ $niche->hero_title }}
                </h1>

                <p class="text-lg lg:text-xl text-slate-700 leading-relaxed mb-10 max-w-2xl font-medium">
                    {{ $niche->hero_subtitle }}
                </p>

                <div class="flex flex-col sm:flex-row gap-4 mb-10">
                    <a href="{{ $niche->cta_primary_href ?: '/register' }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl font-bold text-base text-white {{ $p['bg700'] }} hover:{{ $p['bg600'] }} shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-0.5">
                        {{ $niche->cta_primary_text ?: 'Începe gratuit' }}
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl font-bold text-base text-slate-900 bg-white border-2 border-slate-900 hover:bg-slate-900 hover:text-white transition-all duration-300">
                        {{ $niche->cta_secondary_text ?: 'Vorbește cu noi' }}
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm font-semibold text-slate-700">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 {{ $p['text600'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        100% în română
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 {{ $p['text600'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Setup în 10 min
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 {{ $p['text600'] }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Hosting în România
                    </span>
                </div>
            </div>

            {{-- Hero illustration --}}
            <div class="lg:col-span-5">
                <div class="relative">
                    @if($niche->image_hero)
                        <div class="absolute -inset-8 {{ $p['bg100'] }} rounded-[3rem] blur-2xl opacity-70"></div>
                        <img src="{{ asset($niche->image_hero) }}" alt="Agent AI pentru {{ $niche->vertical_label }}" class="relative w-full rounded-3xl">
                    @else
                        <div class="aspect-square rounded-[2.5rem] {{ $p['bg100'] }} border-2 {{ $p['border200'] }} flex items-center justify-center">
                            <div class="w-32 h-32 {{ $p['text700'] }}">
                                @if(!empty($nicheIcons[0]))
                                    <svg class="w-full h-full" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $nicheIcons[0] !!}</svg>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================== ANNOTATED CHAT SHOWCASE ============================== --}}
<section class="relative bg-white section-padding overflow-hidden">
    {{-- Background icon scatter --}}
    @if(!empty($nicheIcons))
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <svg class="absolute top-20 left-[5%] w-16 h-16 opacity-[0.05] {{ $p['text700'] }} -rotate-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">{!! $nicheIcons[0] !!}</svg>
            <svg class="absolute bottom-20 right-[5%] w-20 h-20 opacity-[0.05] {{ $p['text700'] }} rotate-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">{!! $nicheIcons[1] ?? $nicheIcons[0] !!}</svg>
        </div>
    @endif

    <div class="container-custom relative">
        <div class="max-w-3xl mx-auto text-center mb-14">
            <span class="inline-block text-xs font-bold uppercase tracking-widest {{ $p['text700'] }} mb-4">DEMO LIVE</span>
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-5">
                Iată cum vorbește bot-ul tău cu clienții
            </h2>
            <p class="text-lg text-slate-600 leading-relaxed">
                Conversație reală, în limba română, antrenată pe afacerea ta. Conversa rulează aici live ↓
            </p>
        </div>

        <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center max-w-6xl mx-auto">
            {{-- Left annotations --}}
            <div class="hidden lg:block lg:col-span-3 space-y-10">
                @foreach([
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>', 'title' => 'Răspuns instant', 'desc' => 'Sub 2 secunde, oricând'],
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>', 'title' => 'Disponibil 24/7', 'desc' => 'Inclusiv noaptea și în weekend'],
                ] as $note)
                    <div class="text-right group">
                        <div class="inline-flex items-center justify-end gap-3">
                            <div>
                                <div class="font-bold text-slate-900 text-base">{{ $note['title'] }}</div>
                                <div class="text-sm text-slate-500">{{ $note['desc'] }}</div>
                            </div>
                            <div class="w-11 h-11 rounded-2xl {{ $p['bg600'] }} text-white flex items-center justify-center shadow-lg flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $note['icon'] !!}</svg>
                            </div>
                        </div>
                        {{-- Arrow pointing right toward chat --}}
                        <svg class="ml-auto mt-2 w-24 h-6 {{ $p['text600'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 100 20">
                            <path d="M0 10 Q 30 0, 60 10 T 95 10" stroke-linecap="round" stroke-dasharray="3 4"/>
                            <path d="M88 5 L 95 10 L 88 15" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                    </div>
                @endforeach
            </div>

            {{-- Chat in middle --}}
            <div class="lg:col-span-6">
                <div class="relative">
                    <div class="absolute -inset-6 {{ $p['bg100'] }} rounded-[2.5rem] blur-2xl opacity-70"></div>
                    <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="w-10 h-10 rounded-full {{ $p['bg600'] }} text-white flex items-center justify-center font-bold">S</div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-slate-900">Sambla AI · {{ $niche->name }}</div>
                                <div class="text-[11px] text-emerald-600 flex items-center gap-1 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Online acum
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span>
                            </div>
                        </div>
                        <div id="niche-demo-bubbles" class="px-4 py-5 h-[420px] overflow-y-auto space-y-3 bg-slate-50/50"></div>
                        <div class="px-4 py-3 border-t border-slate-100 bg-white flex items-center gap-2">
                            <div class="flex-1 bg-slate-100 rounded-full px-4 py-2.5 text-xs text-slate-400">Scrie un mesaj…</div>
                            <button class="w-9 h-9 rounded-full {{ $p['bg600'] }} text-white flex items-center justify-center" aria-label="Trimite">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12zm0 0h7.5"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right annotations --}}
            <div class="hidden lg:block lg:col-span-3 space-y-10">
                @foreach([
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>', 'title' => 'Antrenat pe datele tale', 'desc' => 'Tarife, programe, servicii'],
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>', 'title' => 'Escaladează la operator', 'desc' => 'Când e nevoie de un om'],
                ] as $note)
                    <div class="text-left group">
                        {{-- Arrow pointing left toward chat --}}
                        <svg class="mr-auto mb-2 w-24 h-6 {{ $p['text600'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 100 20">
                            <path d="M5 10 Q 30 20, 60 10 T 100 10" stroke-linecap="round" stroke-dasharray="3 4"/>
                            <path d="M12 5 L 5 10 L 12 15" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <div class="inline-flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl {{ $p['bg600'] }} text-white flex items-center justify-center shadow-lg flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $note['icon'] !!}</svg>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 text-base">{{ $note['title'] }}</div>
                                <div class="text-sm text-slate-500">{{ $note['desc'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<x-motif-border />

{{-- ============================== STATS BAND ============================== --}}
<section class="{{ $p['bg700'] }} py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" aria-hidden="true">
        @foreach($nicheIcons as $i => $icon)
            <svg class="absolute w-32 h-32" style="top: {{ ($i*30)+10 }}%; left: {{ ($i*22)+8 }}%; transform: rotate({{ $i*15-30 }}deg);" fill="none" stroke="white" stroke-width="1" viewBox="0 0 24 24">{!! $icon !!}</svg>
        @endforeach
    </div>
    <div class="container-custom relative">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            @foreach([
                ['v' => '< 2s', 'l' => 'Răspuns mediu'],
                ['v' => '24/7', 'l' => 'Disponibil non-stop'],
                ['v' => '100%', 'l' => 'În limba română'],
                ['v' => '10 min', 'l' => 'Setup complet'],
            ] as $stat)
                <div>
                    <div class="text-4xl lg:text-5xl font-extrabold text-white mb-2">{{ $stat['v'] }}</div>
                    <div class="text-sm font-bold text-white/80 uppercase tracking-wider">{{ $stat['l'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== PROBLEM ============================== --}}
<section class="bg-white section-padding relative overflow-hidden">
    @if(!empty($nicheIcons))
        <svg class="absolute -top-10 -left-10 w-64 h-64 opacity-[0.04] {{ $p['text700'] }}" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">{!! $nicheIcons[0] !!}</svg>
    @endif
    <div class="container-custom relative">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-900 text-white mb-6">
                    PROBLEMA
                </span>
                <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-7 leading-[1.1]">
                    {{ $niche->problem_title }}
                </h2>
                <p class="text-lg lg:text-xl text-slate-700 leading-relaxed whitespace-pre-line font-medium">
                    {{ $niche->problem_text }}
                </p>
            </div>
            <div class="relative">
                <div class="absolute -inset-6 {{ $p['bg100'] }} rounded-[2.5rem] blur-2xl opacity-60"></div>
                @if($niche->image_path)
                    <img src="{{ asset($niche->image_path) }}" alt="{{ $niche->name }}" class="relative w-full rounded-3xl shadow-2xl border-4 border-white" loading="lazy">
                @else
                    <div class="relative aspect-[4/3] rounded-3xl {{ $p['bg100'] }} border-2 {{ $p['border200'] }} flex items-center justify-center">
                        @if(!empty($nicheIcons[1]))
                            <svg class="w-32 h-32 {{ $p['text700'] }}" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">{!! $nicheIcons[1] !!}</svg>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ============================== SOLUTION ============================== --}}
<section class="{{ $p['bg50'] }} section-padding relative overflow-hidden">
    @if(!empty($nicheIcons))
        <svg class="absolute top-10 right-10 w-48 h-48 opacity-[0.06] {{ $p['text700'] }} rotate-12" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">{!! $nicheIcons[2] ?? $nicheIcons[0] !!}</svg>
    @endif
    <div class="container-custom relative">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center mb-14">
            <div class="lg:col-span-7">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $p['bg600'] }} text-white mb-6">
                    SOLUȚIA
                </span>
                <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-6 leading-[1.1]">
                    {{ $niche->solution_title }}
                </h2>
                <p class="text-lg lg:text-xl text-slate-700 leading-relaxed font-medium">
                    {{ $niche->solution_text }}
                </p>
            </div>
            <div class="lg:col-span-5">
                @if($niche->image_accent)
                    <div class="relative">
                        <div class="absolute -inset-6 {{ $p['bg100'] }} rounded-[2.5rem] blur-2xl opacity-70"></div>
                        <img src="{{ asset($niche->image_accent) }}" alt="{{ $niche->name }}" class="relative w-full max-w-md mx-auto rounded-3xl" loading="lazy">
                    </div>
                @elseif(!empty($nicheIcons))
                    <div class="aspect-square rounded-[2.5rem] bg-white border-2 {{ $p['border200'] }} flex items-center justify-center max-w-sm mx-auto">
                        <svg class="w-32 h-32 {{ $p['text700'] }}" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">{!! $nicheIcons[0] !!}</svg>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['n' => '01', 'title' => 'Învață din datele tale', 'desc' => 'Conectează site-ul, produsele și documentele. Agentul citește tot și răspunde doar din surse reale.'],
                ['n' => '02', 'title' => 'Răspunde 24/7', 'desc' => 'Pe site, telefon, WhatsApp, Messenger, Instagram — într-o română naturală.'],
                ['n' => '03', 'title' => 'Învață ce nu știe', 'desc' => 'Îți semnalează automat întrebările la care n-a știut, ca să închizi golurile.'],
                ['n' => '04', 'title' => 'Escaladează la operator', 'desc' => 'Când detectează frustrare sau cerere complexă, transferă către omul potrivit.'],
            ] as $step)
                <div class="bg-white rounded-3xl p-8 border-2 border-white shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="text-3xl font-extrabold {{ $p['text700'] }} mb-4">{{ $step['n'] }}</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== BENEFITS ============================== --}}
@if(!empty($benefits))
<section class="bg-white section-padding relative overflow-hidden">
    @if(!empty($nicheIcons))
        <svg class="absolute -bottom-10 -right-10 w-64 h-64 opacity-[0.04] {{ $p['text700'] }}" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">{!! $nicheIcons[3] ?? $nicheIcons[0] !!}</svg>
    @endif
    <div class="container-custom relative">
        <div class="max-w-3xl mb-14">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $p['bg600'] }} text-white mb-6">
                AVANTAJE
            </span>
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 tracking-tight leading-[1.1]">
                Avantajele Sambla pentru {{ $possessive }}
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($benefits as $i => $benefit)
                <div class="bg-white rounded-3xl p-8 border-2 border-slate-100 hover:{{ $p['border200'] }} hover:shadow-xl transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl {{ $p['bg600'] }} text-white flex items-center justify-center mb-5 shadow-lg group-hover:scale-110 transition-transform">
                        @if(!empty($nicheIcons[$i % count($nicheIcons)]))
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $nicheIcons[$i % count($nicheIcons)] !!}</svg>
                        @else
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3 leading-tight">{{ $benefit['title'] ?? '' }}</h3>
                    <p class="text-base text-slate-600 leading-relaxed">{{ $benefit['description'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================== RO TRUST BAND ============================== --}}
@if(false)
<section class="bg-slate-900 py-20 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        @foreach($nicheIcons as $i => $icon)
            <svg class="absolute w-40 h-40" style="top: {{ ($i*25)+5 }}%; right: {{ ($i*20)+5 }}%; transform: rotate({{ $i*20 }}deg);" fill="none" stroke="white" stroke-width="1" viewBox="0 0 24 24">{!! $icon !!}</svg>
        @endforeach
    </div>
    <div class="container-custom relative">
        <div class="grid lg:grid-cols-2 gap-12 items-center max-w-5xl mx-auto">
            <div>
                <img src="{{ asset($niche->image_accent) }}" alt="{{ $niche->name }}" class="w-full max-w-sm mx-auto rounded-3xl" loading="lazy">
            </div>
            <div class="text-white">
                <h3 class="text-3xl lg:text-4xl font-extrabold mb-5 leading-tight">
                    Construit, antrenat și hostat în România
                </h3>
                @if($niche->social_proof_text)
                    <p class="text-lg text-white/80 leading-relaxed mb-6">{{ $niche->social_proof_text }}</p>
                @endif
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold bg-white/10 text-white border border-white/20">🇷🇴 Hosting RO</span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold bg-white/10 text-white border border-white/20">GDPR by default</span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold bg-white/10 text-white border border-white/20">5 canale</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============================== LEAD FORM ============================== --}}
<section id="contact" class="{{ $p['bg50'] }} section-padding relative overflow-hidden">
    @if(!empty($nicheIcons))
        <svg class="absolute top-20 left-20 w-48 h-48 opacity-[0.06] {{ $p['text700'] }} -rotate-12" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">{!! $nicheIcons[0] !!}</svg>
        <svg class="absolute bottom-20 right-20 w-56 h-56 opacity-[0.06] {{ $p['text700'] }} rotate-12" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">{!! $nicheIcons[2] ?? $nicheIcons[0] !!}</svg>
    @endif

    <div class="container-custom relative">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-10">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $p['bg600'] }} text-white mb-6">
                    HAI SĂ VORBIM
                </span>
                <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-5 leading-[1.1]">
                    Vrei un demo personalizat pentru {{ $possessive }}?
                </h2>
                <p class="text-lg text-slate-700 leading-relaxed font-medium">
                    Trimite-ne link-ul site-ului sau paginii de Facebook — primești un demo personalizat pe afacerea ta.
                </p>
            </div>

            @if(session('niche_lead_success'))
                <div class="mb-6 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-800 px-5 py-4 text-sm font-semibold">
                    {{ session('niche_lead_success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-2xl bg-red-50 border-2 border-red-200 text-red-800 px-5 py-4 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('public.niche.lead', $niche->slug) }}" method="POST" class="bg-white rounded-3xl border-2 border-slate-100 p-8 lg:p-10 shadow-2xl space-y-5">
                @csrf
                <input type="hidden" name="source_url" value="{{ url()->current() }}">
                <div class="absolute left-[-9999px] top-[-9999px]" aria-hidden="true">
                    <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-2">Nume <span class="text-red-600">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}" class="w-full px-4 py-3.5 rounded-xl bg-slate-50 border-2 border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:bg-white transition font-medium">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-2">Site-ul sau pagina de Facebook</label>
                    <input type="text" name="website_url" value="{{ old('website_url') }}" placeholder="ex: www.cabinetdental.ro sau facebook.com/cabinetulmeu" class="w-full px-4 py-3.5 rounded-xl bg-slate-50 border-2 border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:bg-white transition font-medium">
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-900 mb-2">Email <span class="text-red-600">*</span></label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-4 py-3.5 rounded-xl bg-slate-50 border-2 border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:bg-white transition font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-900 mb-2">Telefon <span class="text-red-600">*</span></label>
                        <input type="tel" name="phone" required value="{{ old('phone') }}" class="w-full px-4 py-3.5 rounded-xl bg-slate-50 border-2 border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:bg-white transition font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-2">Mesaj</label>
                    <textarea name="message" rows="4" class="w-full px-4 py-3.5 rounded-xl bg-slate-50 border-2 border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:bg-white transition font-medium resize-none">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-bold text-base text-white {{ $p['bg700'] }} hover:{{ $p['bg600'] }} shadow-xl hover:shadow-2xl transition-all duration-300">
                    Vreau demo personalizat
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>

                <p class="text-xs text-slate-500 text-center pt-2">
                    Prin trimiterea formularului ești de acord cu <a href="{{ route('legal.confidentialitate') }}" class="underline font-semibold {{ $p['text700'] }}">politica de confidențialitate</a>.
                </p>
            </form>
        </div>
    </div>
</section>

{{-- ============================== FAQ ============================== --}}
@if(!empty($faqItems))
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $p['bg600'] }} text-white mb-6">
                    ÎNTREBĂRI FRECVENTE
                </span>
                <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 tracking-tight leading-[1.1]">
                    Ce vor să știe clienții despre Sambla pentru {{ $possessive }}
                </h2>
            </div>

            <div class="space-y-3">
                @foreach($faqItems as $item)
                    @php
                        $q = $item['question'] ?? ($item['q'] ?? '');
                        $a = $item['answer'] ?? ($item['a'] ?? '');
                    @endphp
                    <details class="group bg-white rounded-2xl border-2 border-slate-100 hover:{{ $p['border200'] }} overflow-hidden transition-colors">
                        <summary class="flex items-center justify-between gap-4 px-6 py-5 cursor-pointer list-none">
                            <span class="font-bold text-slate-900 text-base lg:text-lg">{{ $q }}</span>
                            <span class="w-9 h-9 rounded-full {{ $p['bg600'] }} text-white flex items-center justify-center flex-shrink-0 group-open:rotate-45 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </span>
                        </summary>
                        <div class="px-6 pb-6 text-slate-700 leading-relaxed whitespace-pre-line">{{ $a }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============================== FINAL CTA ============================== --}}
<x-cta-section
    title="Gata să automatizezi {{ $possessive }}?"
    subtitle="Configurezi agentul în 10 minute. Primele conversații reale din prima zi."
    primary-text="Începe gratuit"
    secondary-text="Vorbește cu noi"
    primary-href="/register"
    secondary-href="/contact"
/>

@endsection

@push('scripts')
<script>
(() => {
    const messages = @json($demoMessages);
    const container = document.getElementById('niche-demo-bubbles');
    if (!container || !messages.length) return;

    const botBg = @json($p['bg100']);
    const botText = @json($p['text700']);
    let i = 0;
    let timer = null;

    const appendBubble = (msg) => {
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (msg.role === 'user' ? 'justify-end' : 'justify-start') + ' opacity-0 translate-y-1 transition-all duration-300';
        const bubble = document.createElement('div');
        bubble.className = 'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed font-medium ' +
            (msg.role === 'user'
                ? 'rounded-tr-sm bg-slate-900 text-white'
                : 'rounded-tl-sm ' + botBg + ' ' + botText);
        bubble.textContent = msg.text || '';
        wrap.appendChild(bubble);
        container.appendChild(wrap);
        requestAnimationFrame(() => {
            wrap.classList.remove('opacity-0', 'translate-y-1');
        });
        container.scrollTop = container.scrollHeight;
    };

    const tick = () => {
        if (i >= messages.length) {
            clearInterval(timer);
            setTimeout(() => {
                container.innerHTML = '';
                i = 0;
                timer = setInterval(tick, 3000);
            }, 6000);
            return;
        }
        appendBubble(messages[i]);
        i++;
    };

    tick();
    timer = setInterval(tick, 3000);
})();
</script>
@endpush
