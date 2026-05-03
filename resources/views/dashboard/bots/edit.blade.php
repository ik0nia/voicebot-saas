@extends('layouts.dashboard')

@section('title', 'Editează: ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft transition-colors">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Editează</span>
@endsection

@php
    // Settings snapshot for the form. Each block below reads from
    // `old()` first so validation errors preserve user input.
    $settings       = $bot->settings ?? [];
    $businessInfo   = old('settings.business_info', $settings['business_info'] ?? []);
    $faqs           = old('settings.faqs', $settings['faqs'] ?? []);
    $dontRules      = old('settings.dont_rules', $settings['dont_rules'] ?? []);
    $toneGuide      = old('settings.tone_guide', $settings['tone_guide'] ?? []);
    $useStructured  = (bool) old('settings.use_structured_prompt', $settings['use_structured_prompt'] ?? false);

    // Default weekly schedule skeleton. Days localised to Romanian so
    // both the UI label and the prompt block ("Luni: 09-18") stay
    // consistent with what StructuredPromptBuilder emits.
    $defaultDays = [
        ['key' => 'mon', 'label' => 'Luni',      'closed' => false, 'open' => '09:00', 'close' => '18:00', 'break_start' => null, 'break_end' => null],
        ['key' => 'tue', 'label' => 'Marți',     'closed' => false, 'open' => '09:00', 'close' => '18:00', 'break_start' => null, 'break_end' => null],
        ['key' => 'wed', 'label' => 'Miercuri',  'closed' => false, 'open' => '09:00', 'close' => '18:00', 'break_start' => null, 'break_end' => null],
        ['key' => 'thu', 'label' => 'Joi',       'closed' => false, 'open' => '09:00', 'close' => '18:00', 'break_start' => null, 'break_end' => null],
        ['key' => 'fri', 'label' => 'Vineri',    'closed' => false, 'open' => '09:00', 'close' => '18:00', 'break_start' => null, 'break_end' => null],
        ['key' => 'sat', 'label' => 'Sâmbătă',   'closed' => true,  'open' => '10:00', 'close' => '14:00', 'break_start' => null, 'break_end' => null],
        ['key' => 'sun', 'label' => 'Duminică',  'closed' => true,  'open' => '10:00', 'close' => '14:00', 'break_start' => null, 'break_end' => null],
    ];
    $hoursSchedule = $businessInfo['hours_schedule'] ?? null;
    if (is_string($hoursSchedule)) {
        $hoursSchedule = json_decode($hoursSchedule, true) ?: null;
    }
    if (!is_array($hoursSchedule) || empty($hoursSchedule)) {
        $hoursSchedule = $defaultDays;
    }

    $suggestedFaqs = $niche['suggested_faqs'] ?? [];
    $standardRules = $niche['standard_rules'] ?? [];
    $defaultTone   = $niche['default_tone'] ?? ['length' => 'medium', 'register' => 'tu', 'emoji_ok' => false, 'languages' => ['ro']];

    // Merge standard-rules with the bot's saved dont_rules so the
    // checkbox pre-check state reflects what's currently persisted.
    $savedRulesNormalized = array_map('trim', (array) $dontRules);
    $customLines = [];
    foreach ($savedRulesNormalized as $r) {
        if ($r === '') continue;
        if (!in_array($r, array_map('trim', $standardRules), true)) {
            $customLines[] = $r;
        }
    }
    // We key the standard-rules state by numeric index (not rule text)
    // because Alpine's x-model="standard[...]" needs a sane bracket
    // expression and Blade HTML-encodes quotes inside attributes —
    // keying on rule text with diacritics would render as
    // x-model="standard[&quot;Nu inventa prețuri&quot;]" which Alpine
    // 3 does parse but is fragile. Numeric keys keep it simple.
    $checkedStandard = [];
    foreach ($standardRules as $i => $rule) {
        $checkedStandard[$i] = empty($savedRulesNormalized)
            ? true
            : in_array(trim($rule), $savedRulesNormalized, true);
    }

    $nicheLabel = $niche['display_name'] ?? ($bot->niche_slug ?? 'afacerea ta');

    // Pre-compute the Alpine x-data payload in PHP then inject via one
    // JS encoding. Doing this inline with @js()/@json in an HTML
    // attribute gets messy: the @js directive regex is greedy about
    // parens and breaks when arguments contain escaped quotes
    // (e.g. implode("\n", ...)).
    $alpineInit = [
        'faqs' => array_values(array_filter(
            (array) ($faqs ?: []),
            fn ($f) => is_array($f) && (($f['question'] ?? '') !== '' || ($f['answer'] ?? '') !== '')
        )),
        'days' => $hoursSchedule,
        'standard' => (object) $checkedStandard, // object so JS keys preserve order even for numeric keys
        'standardTexts' => array_values($standardRules),
        'customLines' => implode("\n", $customLines),
        'tone' => [
            'length'   => $toneGuide['length']   ?? $defaultTone['length'],
            'register' => $toneGuide['register'] ?? $defaultTone['register'],
            'emoji_ok' => (bool) ($toneGuide['emoji_ok'] ?? $defaultTone['emoji_ok']),
            'languages' => array_values($toneGuide['languages'] ?? $defaultTone['languages']),
        ],
        'businessInfo' => (array) $businessInfo,
        'nicheSlug' => $bot->niche_slug,
        // Iter A: core "basic" fields kept reactive so the Bază tab and the
        // classic tabs share the same state. The name="..." posted inputs
        // live canonically inside the Bază section; the other tabs mirror
        // them via x-model (no name attribute, so they don't resubmit).
        'core' => [
            'name'             => old('name', $bot->name),
            'voice'            => old('voice', $bot->voice),
            'greeting'         => old('greeting_message', $bot->greeting_message),
            'is_active'        => (bool) old('is_active', $bot->is_active),
        ],
        'transfer' => [
            'enabled'          => (bool) old('transfer_enabled', $bot->settings['transfer_config']['enabled'] ?? false),
            'operator_number'  => (string) old('transfer_operator_number', $bot->settings['transfer_config']['operator_number'] ?? ''),
            'max_ring_seconds' => (int) old('transfer_max_ring_seconds', $bot->settings['transfer_config']['max_ring_seconds'] ?? 25),
        ],
    ];
@endphp

@section('content')
<div class="max-w-6xl mx-auto"
     x-data="botEditor({{ \Illuminate\Support\Js::from($alpineInit) }})">

    {{-- Flash + validation --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ============== HEADER ============== --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Editează agent AI</h1>
            <p class="mt-1 text-sm text-muted">
                <strong>{{ $bot->name }}</strong>
                @if($bot->niche_slug)
                    <span class="inline-flex items-center gap-1 ml-2 px-2 py-0.5 rounded-full bg-cream text-muted text-xs">
                        {{ $nicheLabel }}
                    </span>
                @endif
            </p>
            {{-- Completeness badge — clickable to show per-item breakdown (Iter A) --}}
            <div class="mt-3 relative inline-block" @click.away="checklistOpen = false">
                <button type="button" @click="checklistOpen = !checklistOpen"
                        class="group inline-flex items-center gap-2 rounded-md border border-line bg-white px-2.5 py-1.5 hover:border-red-300 hover:shadow-sm transition text-left">
                    <span class="text-xs text-muted">Profil:</span>
                    <span class="text-xs font-semibold text-ink" x-text="completenessPercent() + '%'"></span>
                    <div class="w-28 h-2 bg-sand rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-red-500 to-red-700 transition-all duration-300"
                             :style="'width: ' + completenessPercent() + '%'"></div>
                    </div>
                    <svg class="w-3 h-3 text-muted group-hover:text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="checklistOpen" x-cloak x-transition.origin.top.left
                     class="absolute z-40 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-lg border border-line p-3 left-0">
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Ce-ți mai trebuie agentului</p>
                    <ul class="space-y-1 text-sm">
                        <template x-for="(item, i) in completenessItems()" :key="'chk_' + i">
                            <li>
                                <button type="button"
                                        @click="item.done ? (checklistOpen = false) : (tab = item.tab, checklistOpen = false, $nextTick(() => focusFieldInTab(item.focus)))"
                                        class="w-full flex items-start gap-2 text-left px-2 py-1.5 rounded hover:bg-cream transition">
                                    <span class="mt-0.5 font-bold" x-text="item.done ? '✓' : '○'"
                                          :class="item.done ? 'text-green-600' : 'text-slate-300'"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium" :class="item.done ? 'text-muted' : 'text-inkSoft'" x-text="item.label"></div>
                                        <div x-show="item.hint" class="text-[11px] text-muted" x-text="item.hint"></div>
                                    </div>
                                    <svg x-show="!item.done" class="w-3.5 h-3.5 text-muted shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- ✨ Completează tot cu AI — primary eye-catcher --}}
            <button type="button" @click="openFullProfileModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-red-600 via-red-700 to-red-800 hover:from-red-700 hover:to-red-900 shadow-md hover:shadow-lg transition">
                <span>✨</span> Completează tot cu AI
            </button>
            {{-- 👁 Vezi cum răspunde --}}
            <a href="{{ route('dashboard.bots.testVocal', $bot) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border border-line text-inkSoft hover:bg-cream transition">
                <span>👁</span> Vezi cum răspunde
            </a>
        </div>
    </div>

    {{-- Booking callout — shown only for bots on the booking/hybrid
         engine. Kept minimal (one banner) to avoid clashing with the
         Iteration A restructuring of this file. --}}
    @if(in_array($bot->engine_type, ['booking', 'hybrid'], true))
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <div class="text-sm text-emerald-900">
                <span class="mr-1">📅</span>
                <strong>Configurează servicii și program</strong>
                — editează serviciile, prețurile și orele de lucru.
            </div>
            <a href="{{ route('dashboard.bots.booking', $bot) }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">
                Deschide Programări
            </a>
        </div>
    @endif

    {{-- ============== MAIN FORM ============== --}}
    <form method="POST" action="{{ route('dashboard.bots.update', $bot) }}" x-ref="editForm">
        @csrf
        @method('PUT')

        <div class="flex flex-col lg:flex-row gap-6">
            {{-- ============== LEFT TAB NAV (desktop) / SELECT DROPDOWN (mobile) ============== --}}
            @php
                $tabs = [
                    ['id' => 'baza',        'label' => 'Bază',                'icon' => '⚡'],
                    ['id' => 'identitate',  'label' => 'Identitate',          'icon' => '🎯'],
                    ['id' => 'business',    'label' => 'Informații business', 'icon' => '🏢'],
                    ['id' => 'faq',         'label' => 'FAQ',                 'icon' => '💬'],
                    ['id' => 'reguli',      'label' => 'Reguli stricte',      'icon' => '🚫'],
                    ['id' => 'ton',         'label' => 'Ton & stil',          'icon' => '🎨'],
                    ['id' => 'transfer',    'label' => 'Transfer operator',   'icon' => '📞'],
                    ['id' => 'avansat',     'label' => 'Avansat',             'icon' => '⚙️'],
                ];
            @endphp
            <aside class="lg:w-56 lg:shrink-0">
                {{-- Mobile: single-select dropdown (stacks cleaner than horizontal scroll) --}}
                <div class="lg:hidden mb-4">
                    <label for="tab-select-mobile" class="sr-only">Secțiune</label>
                    <select id="tab-select-mobile" x-model="tab"
                            class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-medium text-inkSoft focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        @foreach($tabs as $t)
                            <option value="{{ $t['id'] }}">{{ $t['icon'] }}  {{ $t['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Desktop: vertical tab nav --}}
                <nav class="hidden lg:flex bg-white rounded-xl border border-line shadow-sm p-1.5 flex-col gap-1">
                    @foreach($tabs as $t)
                    <button type="button"
                            @click="tab = '{{ $t['id'] }}'"
                            :class="tab === '{{ $t['id'] }}' ? 'bg-coral text-white shadow-sm' : 'text-muted hover:bg-coralsoft hover:text-coralh'"
                            class="flex items-center gap-2 px-3 py-2.5 text-sm font-medium rounded-lg whitespace-nowrap transition text-left">
                        <span>{{ $t['icon'] }}</span>
                        <span>{{ $t['label'] }}</span>
                    </button>
                    @endforeach
                </nav>
            </aside>

            <div class="flex-1 min-w-0">

            {{-- ============== TAB 0: BAZĂ (Quick Setup) — Iteration A ============== --}}
            <section x-show="tab === 'baza'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-start justify-between mb-1 gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">Setup de bază</h2>
                            <p class="text-sm text-muted">Strictul necesar ca agentul să fie funcțional. Restul setărilor pot fi ajustate mai târziu din celelalte secțiuni.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center gap-1 shrink-0 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-medium">⚡ Rapid</span>
                    </div>

                    <div class="mt-5 space-y-5">
                        {{-- Nume agent (canonical) --}}
                        <div>
                            <label for="baza_name" class="block text-sm font-medium text-inkSoft mb-1.5">Nume agent AI <span class="text-coral">*</span></label>
                            <input type="text" name="name" id="baza_name" x-model="core.name" required
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none" />
                        </div>

                        {{-- Voce (canonical) --}}
                        <div>
                            <label for="baza_voice" class="block text-sm font-medium text-inkSoft mb-1.5">Voce <span class="text-coral">*</span></label>
                            <select name="voice" id="baza_voice" x-model="core.voice" required
                                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <option value="coral">Coral (feminin, cald)</option>
                                <option value="sage">Sage (feminin, clar)</option>
                                <option value="shimmer">Shimmer (feminin)</option>
                                <option value="ballad">Ballad (masculin, blând)</option>
                                <option value="verse">Verse (masculin, expresiv)</option>
                                <option value="ash">Ash (masculin, neutru)</option>
                                <option value="alloy">Alloy (neutru)</option>
                                <option value="echo">Echo (masculin)</option>
                                <option value="marin">Marin</option>
                                <option value="cedar">Cedar</option>
                            </select>
                        </div>

                        {{-- Greeting (canonical) --}}
                        <div>
                            <label for="baza_greeting" class="block text-sm font-medium text-inkSoft mb-1.5">Mesaj de întâmpinare</label>
                            <p class="text-xs text-muted mb-2">Textul pe care îl spune agentul când răspunde. Lasă gol dacă preferi să aștepte clientul să vorbească primul.</p>
                            <input type="text" name="greeting_message" id="baza_greeting" x-model="core.greeting"
                                   placeholder="Bună ziua, sunt Greg de la Sambla. Cu ce vă pot ajuta?"
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        </div>

                        {{-- Adresă + Telefon + Email (canonical for business_info.address/phone/email) --}}
                        <div>
                            <label for="baza_address" class="block text-sm font-medium text-inkSoft mb-1.5">Adresă business</label>
                            <textarea name="settings[business_info][address]" id="baza_address" rows="2" x-model="businessInfo.address"
                                      placeholder="Str. Victoriei 10, Cluj-Napoca, jud. Cluj"
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="baza_phone" class="block text-sm font-medium text-inkSoft mb-1.5">Telefon</label>
                                <div class="flex rounded-lg border border-line overflow-hidden focus-within:border-coral focus-within:ring-2 focus-within:ring-coral/20 bg-white">
                                    <span class="inline-flex items-center gap-1 px-3 text-sm text-muted bg-cream border-r border-line">🇷🇴 +40</span>
                                    <input type="tel" name="settings[business_info][phone]" id="baza_phone" x-model="businessInfo.phone"
                                           @blur="normalizePhone()"
                                           placeholder="721 234 567" class="flex-1 px-3 py-2.5 text-sm outline-none bg-transparent">
                                </div>
                            </div>
                            <div>
                                <label for="baza_email" class="block text-sm font-medium text-inkSoft mb-1.5">Email</label>
                                <input type="email" name="settings[business_info][email]" id="baza_email"
                                       x-model="businessInfo.email"
                                       placeholder="contact@exemplu.ro"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                        </div>

                        {{-- Active toggle (canonical) --}}
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="is_active" value="0" />
                                <input type="checkbox" name="is_active" value="1" x-model="core.is_active"
                                       class="w-5 h-5 rounded border-line text-coralh focus:ring-coral/20" />
                                <div>
                                    <span class="text-sm font-medium text-inkSoft">Agent AI activ</span>
                                    <p class="text-xs text-muted">Poate primi și efectua apeluri / conversații.</p>
                                </div>
                            </label>
                        </div>

                        {{-- Marker that this save came from Bază tab — controller uses it for post-save CTA --}}
                        <input type="hidden" name="origin" value="baza" x-bind:value="tab === 'baza' ? 'baza' : ''">

                        <div class="pt-2 flex items-start gap-2 text-xs text-muted">
                            <span>💡</span>
                            <span>Pentru FAQ-uri, reguli sau ton, folosește secțiunile din stânga. Agentul merge și doar cu setările de aici.</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============== TAB 1: IDENTITATE ============== --}}
            <section x-show="tab === 'identitate'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-ink mb-1">Identitate</h2>
                    <p class="text-sm text-muted mb-6">Numele, limba, vocea și mesajul de întâmpinare.</p>

                    <div class="space-y-5">
                        <div>
                            <label for="ident_name" class="block text-sm font-medium text-inkSoft mb-1.5">Nume agent AI <span class="text-coral">*</span></label>
                            {{-- Iter A: mirror only (canonical input lives in Bază tab). --}}
                            <input type="text" id="ident_name" x-model="core.name" required
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Slug</label>
                            <input type="text" value="{{ $bot->slug }}" disabled
                                   class="w-full rounded-lg border border-line bg-cream px-4 py-2.5 text-sm text-muted cursor-not-allowed" />
                            <p class="text-xs text-muted mt-1">Identificator generat automat. Nu se poate modifica.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Site asociat</label>
                            @if($sites->isEmpty())
                                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm text-yellow-800">Nu ai niciun site adăugat. <a href="{{ route('dashboard.sites.create') }}" class="font-semibold underline">Adaugă un site</a> mai întâi.</p>
                                </div>
                            @else
                                <select name="site_id" class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                    <option value="">— Fără site asociat —</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}" {{ old('site_id', $bot->site_id) == $site->id ? 'selected' : '' }}>
                                            {{ $site->domain }} {{ $site->isVerified() ? '✓' : '(neverificat)' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div>
                            <label for="language" class="block text-sm font-medium text-inkSoft mb-1.5">Limbă principală <span class="text-coral">*</span></label>
                            <select name="language" id="language" required
                                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <option value="ro" {{ old('language', $bot->language) === 'ro' ? 'selected' : '' }}>Română</option>
                                <option value="en" {{ old('language', $bot->language) === 'en' ? 'selected' : '' }}>English</option>
                                <option value="de" {{ old('language', $bot->language) === 'de' ? 'selected' : '' }}>Deutsch</option>
                                <option value="fr" {{ old('language', $bot->language) === 'fr' ? 'selected' : '' }}>Français</option>
                                <option value="es" {{ old('language', $bot->language) === 'es' ? 'selected' : '' }}>Español</option>
                            </select>
                        </div>

                        @php
                            $chatLangs = old('chat_languages', $bot->settings['chat_languages'] ?? [$bot->language]);
                            if (!is_array($chatLangs)) $chatLangs = [$bot->language];
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Limbi pentru chat web + Meta</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (['ro' => 'Română', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español'] as $code => $label)
                                    <label class="flex items-center gap-2 text-sm text-inkSoft border border-line rounded-lg px-3 py-2 hover:bg-cream cursor-pointer">
                                        <input type="checkbox" name="chat_languages[]" value="{{ $code }}"
                                               {{ in_array($code, $chatLangs, true) ? 'checked' : '' }}
                                               class="rounded border-line text-coralh focus:ring-coral/20">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="voice_language" value="ro">

                        <div>
                            <label for="ident_voice" class="block text-sm font-medium text-inkSoft mb-1.5">Voce <span class="text-coral">*</span></label>
                            {{-- Iter A: mirror only (canonical lives in Bază tab). --}}
                            <select id="ident_voice" x-model="core.voice" required
                                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <option value="coral">Coral (feminin, cald)</option>
                                <option value="sage">Sage (feminin, clar)</option>
                                <option value="shimmer">Shimmer (feminin)</option>
                                <option value="ballad">Ballad (masculin, blând)</option>
                                <option value="verse">Verse (masculin, expresiv)</option>
                                <option value="ash">Ash (masculin, neutru)</option>
                                <option value="alloy">Alloy (neutru)</option>
                                <option value="echo">Echo (masculin)</option>
                                <option value="marin">Marin</option>
                                <option value="cedar">Cedar</option>
                            </select>
                        </div>

                        <div>
                            <label for="ident_greeting" class="block text-sm font-medium text-inkSoft mb-1.5">Mesaj de întâmpinare</label>
                            <p class="text-xs text-muted mb-2">Textul pe care îl spune agentul când răspunde. Lasă gol dacă vrei să aștepte clientul să vorbească primul.</p>
                            {{-- Iter A: mirror only. --}}
                            <input type="text" id="ident_greeting" x-model="core.greeting"
                                   placeholder="Bună ziua, sunt Greg de la Sambla. Cu ce vă pot ajuta?"
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        </div>

                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                {{-- Iter A: mirror only, canonical is_active posts from Bază tab. --}}
                                <input type="checkbox" x-model="core.is_active"
                                       class="w-5 h-5 rounded border-line text-coralh focus:ring-coral/20" />
                                <div>
                                    <span class="text-sm font-medium text-inkSoft">Agent AI activ</span>
                                    <p class="text-xs text-muted">Poate primi și efectua apeluri / conversații.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Preserve the voice cloning section exactly as before. --}}
                @include('dashboard.bots.partials._voice-clone-box', ['bot' => $bot, 'clonedVoice' => $clonedVoice])
            </section>

            {{-- ============== TAB 2: INFORMAȚII BUSINESS ============== --}}
            <section x-show="tab === 'business'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-ink mb-1">Informații business</h2>
                    <p class="text-sm text-muted mb-6">Ce spune agentul când clienții întreabă despre program, adresă, contact etc.</p>

                    <div class="space-y-6">
                        {{-- Adresă (Iter A: mirror only; canonical lives in Bază) --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Adresă</label>
                            <textarea rows="2" x-model="businessInfo.address"
                                      placeholder="Str. Victoriei 10, Cluj-Napoca, jud. Cluj"
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                            <div class="mt-2">
                                <a :href="businessInfo.address ? ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(businessInfo.address)) : '#'"
                                   :class="businessInfo.address ? '' : 'pointer-events-none opacity-40'"
                                   target="_blank"
                                   class="text-xs inline-flex items-center gap-1 text-coralh hover:text-coralh font-medium">
                                    📍 Vezi pe hartă
                                </a>
                            </div>
                        </div>

                        {{-- Program de lucru --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Program de lucru</label>
                            {{-- Iter A: stacked cards on mobile, 12-col grid on sm+ --}}
                            <div class="border border-line rounded-lg divide-y divide-slate-100 bg-cream/30">
                                <template x-for="(day, idx) in days" :key="day.key">
                                    <div class="flex flex-col sm:grid sm:grid-cols-12 sm:gap-2 sm:items-center px-3 py-3">
                                        <div class="sm:col-span-2 text-sm font-semibold text-ink sm:font-medium sm:text-inkSoft mb-2 sm:mb-0" x-text="day.label"></div>
                                        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-muted mb-2 sm:mb-0">
                                            <input type="checkbox" x-model="day.closed" class="rounded border-line text-coralh focus:ring-coral/20">
                                            <span>Închis</span>
                                        </label>
                                        <div class="sm:col-span-2 flex items-center gap-1 mb-2 sm:mb-0">
                                            <span class="sm:hidden text-[11px] text-muted w-20">Deschis</span>
                                            <input type="time" x-model="day.open" :disabled="day.closed"
                                                   class="w-full rounded-md border border-line px-2 py-1 text-xs disabled:bg-cream disabled:text-muted">
                                        </div>
                                        <div class="sm:col-span-2 flex items-center gap-1 mb-2 sm:mb-0">
                                            <span class="sm:hidden text-[11px] text-muted w-20">Închide</span>
                                            <input type="time" x-model="day.close" :disabled="day.closed"
                                                   class="w-full rounded-md border border-line px-2 py-1 text-xs disabled:bg-cream disabled:text-muted">
                                        </div>
                                        <div class="sm:col-span-4 flex flex-wrap items-center gap-2">
                                            <button type="button" @click="toggleBreak(day)" :disabled="day.closed"
                                                    class="text-xs text-muted hover:text-coralh disabled:opacity-40 px-2 py-1">
                                                <span x-text="day.break_start ? '✎ Pauză' : '+ Pauză'"></span>
                                            </button>
                                            <template x-if="day.break_start !== null && !day.closed">
                                                <div class="flex items-center gap-1 text-xs">
                                                    <input type="time" x-model="day.break_start" class="w-20 rounded-md border border-line px-1 py-0.5 text-xs">
                                                    <span class="text-muted">-</span>
                                                    <input type="time" x-model="day.break_end" class="w-20 rounded-md border border-line px-1 py-0.5 text-xs">
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button" @click="copyMonToWeekdays()"
                                        class="text-xs px-3 py-1.5 rounded-md border border-line hover:bg-cream text-inkSoft">
                                    Copiază Luni pe L-V
                                </button>
                                <button type="button" @click="markAllClosed(true)"
                                        class="text-xs px-3 py-1.5 rounded-md border border-line hover:bg-cream text-inkSoft">
                                    Weekend închis
                                </button>
                            </div>
                            {{-- Serialized schedule sent to the server. --}}
                            <input type="hidden" name="settings[business_info][hours_schedule]" :value="JSON.stringify(days)">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Notă specială program</label>
                            <textarea name="settings[business_info][hours_text]" rows="2"
                                      placeholder="ex: În august închidem între 10-20 august."
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">{{ $businessInfo['hours_text'] ?? '' }}</textarea>
                        </div>

                        {{-- Contact fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Telefon</label>
                                <div class="flex rounded-lg border border-line overflow-hidden focus-within:border-coral focus-within:ring-2 focus-within:ring-coral/20 bg-white">
                                    <span class="inline-flex items-center gap-1 px-3 text-sm text-muted bg-cream border-r border-line">🇷🇴 +40</span>
                                    {{-- Iter A: mirror only. --}}
                                    <input type="tel" x-model="businessInfo.phone"
                                           @blur="normalizePhone()"
                                           placeholder="721 234 567" class="flex-1 px-3 py-2.5 text-sm outline-none bg-transparent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Email</label>
                                {{-- Iter A: mirror only; canonical posts from Bază. Email is a plain input so we sync via Alpine. --}}
                                <input type="email" x-model="businessInfo.email"
                                       placeholder="contact@exemplu.ro"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Website</label>
                                <input type="url" name="settings[business_info][website]" x-model="businessInfo.website"
                                       @blur="normalizeWebsite()"
                                       placeholder="https://exemplu.ro"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">
                                    <span class="inline-flex items-center gap-1">💬 WhatsApp</span>
                                </label>
                                <input type="text" name="settings[business_info][whatsapp]" value="{{ $businessInfo['whatsapp'] ?? '' }}"
                                       placeholder="+40 721 234 567"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">
                                    <span class="inline-flex items-center gap-1">📘 Facebook</span>
                                </label>
                                <input type="text" name="settings[business_info][facebook]" value="{{ $businessInfo['facebook'] ?? '' }}"
                                       placeholder="facebook.com/pagina"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">
                                    <span class="inline-flex items-center gap-1">📷 Instagram</span>
                                </label>
                                <input type="text" name="settings[business_info][instagram]" value="{{ $businessInfo['instagram'] ?? '' }}"
                                       placeholder="@username"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                        </div>

                        {{-- Extras catch-all --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-medium text-inkSoft">Ce te face diferit / alte detalii</label>
                                <button type="button" @click="aiGenerate('extras_suggest', null, 'extras')"
                                        :disabled="aiLoading.extras"
                                        class="text-xs font-medium text-coralh hover:text-coralh disabled:opacity-50 inline-flex items-center gap-1">
                                    <span x-show="!aiLoading.extras">✨ Generează cu AI</span>
                                    <span x-show="aiLoading.extras">Se generează...</span>
                                </button>
                            </div>
                            <textarea name="settings[business_info][extras]" rows="5" x-ref="extrasField"
                                      placeholder="@if($bot->niche_slug === 'restaurant')ex: specialități de casă, bucătărie tradițională italiană, terasă de vară.@elseif($bot->niche_slug === 'stomatologie')ex: medici cu experiență internațională, tehnologie 3D, plan de plată în rate.@else Ce ar trebui să știe agentul AI despre afacerea ta? Ce o diferențiază? Ce oferte speciale aveți?@endif"
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y">{{ $businessInfo['extras'] ?? '' }}</textarea>
                            <p class="text-xs text-muted mt-1">4-5 fraze. Descriere sintetică pe care agentul să o folosească când e întrebat despre firmă.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============== TAB 3: FAQ ============== --}}
            <section x-show="tab === 'faq'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-ink mb-1">Întrebări frecvente</h2>
                            <p class="text-sm text-muted">Răspunsuri pe care agentul le folosește direct când clienții întreabă.</p>
                        </div>
                        <div class="text-sm text-muted">
                            <span class="font-semibold" :class="faqs.length >= 45 ? 'text-coralh' : 'text-ink'" x-text="faqs.length"></span>
                            / 50
                        </div>
                    </div>

                    {{-- Suggested FAQs (niche-driven) --}}
                    @if(!empty($suggestedFaqs))
                        <div class="mb-5">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Sugestii rapide pentru {{ $nicheLabel }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($suggestedFaqs as $sug)
                                    <button type="button"
                                            @click="addFaq({{ \Illuminate\Support\Js::from($sug) }})"
                                            class="shrink-0 px-3 py-1.5 bg-cream hover:bg-coralsoft hover:text-coralh rounded-full text-xs text-inkSoft transition">
                                        + {{ $sug['question'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- FAQ repeater --}}
                    <div class="space-y-3">
                        <template x-for="(faq, idx) in faqs" :key="'faq_' + idx">
                            <div class="border border-line rounded-lg p-4 bg-white" :class="faq._new ? 'ring-2 ring-red-200' : ''">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold text-muted" x-text="'#' + (idx + 1)"></span>
                                    <span x-show="faq._new" class="px-2 py-0.5 bg-coralsoft text-coralh rounded-full text-[10px] font-semibold">NEW</span>
                                </div>
                                <input x-model="faq.question" placeholder="Întrebare..." maxlength="300"
                                       class="w-full mb-2 rounded-md border border-line px-3 py-2 text-sm font-medium focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                {{-- Iter A: taller on mobile for easier editing. --}}
                                <textarea x-model="faq.answer" rows="3" placeholder="Răspuns..." maxlength="2000"
                                          class="w-full rounded-md border border-line px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y min-h-[7rem] sm:min-h-[5rem]"></textarea>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <button type="button" @click="rephraseFaqAnswer(idx)"
                                            :disabled="aiLoading['faq_a_' + idx] || !faq.question"
                                            class="text-xs px-2 py-1 rounded-md text-coralh hover:bg-coralsoft disabled:opacity-40">
                                        <span x-show="!aiLoading['faq_a_' + idx]">✨ Reformulează răspunsul</span>
                                        <span x-show="aiLoading['faq_a_' + idx]">Se lucrează...</span>
                                    </button>
                                    <button type="button" @click="rephraseFaqQuestion(idx)"
                                            :disabled="aiLoading['faq_q_' + idx] || !faq.question"
                                            class="text-xs px-2 py-1 rounded-md text-coralh hover:bg-coralsoft disabled:opacity-40">
                                        ✨ Reformulează întrebarea
                                    </button>
                                    <button type="button" @click="removeFaq(idx)" class="ml-auto text-xs text-coral hover:text-coralh px-2 py-1">
                                        🗑 Elimină
                                    </button>
                                </div>
                                <input type="hidden" :name="'settings[faqs][' + idx + '][question]'" :value="faq.question">
                                <input type="hidden" :name="'settings[faqs][' + idx + '][answer]'" :value="faq.answer">
                            </div>
                        </template>
                    </div>

                    <div x-show="faqs.length === 0" class="text-center text-sm text-muted py-8">
                        Nicio întrebare adăugată încă. Folosește sugestiile de mai sus sau adaugă manual.
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <button type="button" @click="addFaq()" :disabled="faqs.length >= 50"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-md border border-line hover:bg-cream disabled:opacity-40">
                            + Adaugă întrebare nouă
                        </button>
                        <button type="button" @click="generateFaqBulk(5)" :disabled="faqs.length >= 45 || aiLoading.faq_bulk"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-md text-coralh border border-coral/30 hover:bg-coralsoft disabled:opacity-40">
                            <span x-show="!aiLoading.faq_bulk">✨ Generează 5 întrebări cu AI</span>
                            <span x-show="aiLoading.faq_bulk">Se generează...</span>
                        </button>
                        <span x-show="faqs.length >= 45 && faqs.length < 50" class="text-xs text-amber-600">Aproape de limita de 50.</span>
                    </div>
                </div>
            </section>

            {{-- ============== TAB 4: REGULI STRICTE ============== --}}
            <section x-show="tab === 'reguli'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-ink mb-1">Reguli stricte — ce NU face agentul</h2>
                    <p class="text-sm text-muted mb-6">Lucruri pe care agentul nu trebuie să le spună sau să le promită. Apar ca "dont" în prompt.</p>

                    @if(!empty($standardRules))
                        <div class="mb-6">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Reguli standard pentru {{ $nicheLabel }}</p>
                            <div class="space-y-2">
                                @foreach($standardRules as $i => $rule)
                                    <label class="flex items-start gap-3 p-3 rounded-md border border-line hover:bg-cream cursor-pointer">
                                        <input type="checkbox" x-model="standard[{{ $i }}]"
                                               class="mt-0.5 rounded border-line text-coralh focus:ring-coral/20">
                                        <span class="text-sm text-inkSoft">{{ $rule }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-inkSoft">Reguli proprii</label>
                            <button type="button" @click="aiGenerate('rules_suggest', null, 'rules')"
                                    :disabled="aiLoading.rules"
                                    class="text-xs font-medium text-coralh hover:text-coralh disabled:opacity-50">
                                <span x-show="!aiLoading.rules">✨ Sugerează reguli cu AI</span>
                                <span x-show="aiLoading.rules">Se generează...</span>
                            </button>
                        </div>
                        <textarea x-model="customLines" rows="7"
                                  placeholder="O regulă pe rând. Ex: Nu oferi reduceri dacă nu apar în sistem."
                                  class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                        <p class="text-xs text-muted mt-1">O regulă pe rând. Liniile goale sunt ignorate.</p>
                    </div>
                </div>

                {{-- Hidden fields for merged dont_rules, rendered on submit via sync. --}}
                <template x-for="(rule, rIdx) in mergedRules()" :key="'rule_' + rIdx">
                    <input type="hidden" :name="'settings[dont_rules][' + rIdx + ']'" :value="rule">
                </template>
            </section>

            {{-- ============== TAB 5: TON & STIL ============== --}}
            <section x-show="tab === 'ton'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-ink mb-1">Ton &amp; stil</h2>
                            <p class="text-sm text-muted">Cum se exprimă agentul: scurt sau detaliat, formal sau colocvial.</p>
                        </div>
                        <button type="button" @click="aiGenerate('tone_suggest', null, 'tone')"
                                :disabled="aiLoading.tone"
                                class="text-xs font-medium text-coralh hover:text-coralh disabled:opacity-50">
                            <span x-show="!aiLoading.tone">✨ Sugerează tonul potrivit</span>
                            <span x-show="aiLoading.tone">Se generează...</span>
                        </button>
                    </div>

                    <div class="space-y-5">
                        {{-- Length segmented control --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Lungimea răspunsurilor</label>
                            <div class="inline-flex rounded-lg border border-line overflow-hidden">
                                <template x-for="opt in [['short','Scurt'], ['medium','Mediu'], ['long','Detaliat']]" :key="opt[0]">
                                    <button type="button" @click="tone.length = opt[0]"
                                            :class="tone.length === opt[0] ? 'bg-coral text-white' : 'bg-white text-inkSoft hover:bg-cream'"
                                            class="px-4 py-2 text-sm font-medium transition border-r last:border-r-0 border-line"
                                            x-text="opt[1]"></button>
                                </template>
                            </div>
                            <input type="hidden" name="settings[tone_guide][length]" :value="tone.length">
                        </div>

                        {{-- Register --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Adresare</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="settings[tone_guide][register]" value="tu" x-model="tone.register"
                                           class="border-line text-coralh focus:ring-coral/20">
                                    <span>Tutuiește (tu)</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="settings[tone_guide][register]" value="dvs" x-model="tone.register"
                                           class="border-line text-coralh focus:ring-coral/20">
                                    <span>Dumneavoastră (dvs.)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Emoji --}}
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="settings[tone_guide][emoji_ok]" value="0">
                                <input type="checkbox" name="settings[tone_guide][emoji_ok]" value="1" x-model="tone.emoji_ok"
                                       class="rounded border-line text-coralh focus:ring-coral/20">
                                <div>
                                    <span class="text-sm font-medium text-inkSoft">Poate folosi emoji</span>
                                    <p class="text-xs text-muted">cu moderație (max 1 per mesaj)</p>
                                </div>
                            </label>
                        </div>

                        {{-- Languages --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Limbi acceptate</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (['ro' => 'Română', 'en' => 'English', 'hu' => 'Magyar', 'de' => 'Deutsch', 'fr' => 'Français'] as $code => $label)
                                    <label class="flex items-center gap-2 text-sm border border-line rounded-lg px-3 py-2 hover:bg-cream cursor-pointer">
                                        <input type="checkbox" value="{{ $code }}" @change="toggleToneLang('{{ $code }}', $event.target.checked)"
                                               :checked="tone.languages.includes('{{ $code }}')"
                                               class="rounded border-line text-coralh focus:ring-coral/20">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <template x-for="(lang, lIdx) in tone.languages" :key="'tl_' + lIdx">
                                <input type="hidden" :name="'settings[tone_guide][languages][' + lIdx + ']'" :value="lang">
                            </template>
                        </div>

                        {{-- Live preview --}}
                        <div class="bg-cream border border-line p-4 rounded-lg">
                            <div class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Preview — cum va răspunde</div>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-muted">Client:</span> <span class="text-inkSoft" x-text="previewQuestion()"></span></div>
                                <div><span class="text-muted">Agent:</span> <span class="text-ink font-medium" x-text="previewAnswer()"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============== TAB: TRANSFER OPERATOR ============== --}}
            <section x-show="tab === 'transfer'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-start justify-between mb-4 gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">Transfer către operator uman</h2>
                            <p class="text-sm text-muted">
                                Când clientul cere să vorbească cu o persoană, agentul spune un mesaj scurt, sună operatorul pe numărul de mai jos,
                                îi citește un rezumat de 10-15 secunde despre ce cere clientul, apoi îi conectează pe amândoi pe același apel.
                            </p>
                        </div>
                        <span class="hidden sm:inline-flex items-center gap-1 shrink-0 px-2.5 py-1 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-xs font-medium">📞 Voice</span>
                    </div>

                    <div class="space-y-5">
                        <label class="flex items-start gap-3 p-4 rounded-lg border border-line bg-cream cursor-pointer hover:border-red-300 transition">
                            <input type="checkbox" x-model="transfer.enabled"
                                   class="mt-0.5 w-4 h-4 rounded border-line text-coralh focus:ring-coral">
                            <div>
                                <div class="text-sm font-medium text-ink">Activează transferul către operator</div>
                                <div class="text-xs text-muted mt-0.5">
                                    Funcționează doar pe apelurile telefonice (nu în chat). Costă timp de apel standard + minute outbound către numărul operatorului.
                                </div>
                            </div>
                        </label>
                        <input type="hidden" name="transfer_enabled" :value="transfer.enabled ? '1' : '0'">

                        <div x-show="transfer.enabled" x-cloak class="space-y-5 pl-1">
                            <div>
                                <label for="transfer_operator_number" class="block text-sm font-medium text-inkSoft mb-1.5">
                                    Numărul operatorului <span class="text-coral">*</span>
                                </label>
                                <input type="tel" id="transfer_operator_number" name="transfer_operator_number"
                                       x-model="transfer.operator_number"
                                       placeholder="+40 742 000 000 sau 0742000000"
                                       class="w-full max-w-md rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <p class="text-xs text-muted mt-1">Format acceptat: 07xxxxxxxx sau +40xxxxxxxxx. Un singur număr, operatorul confirmă preluarea cu tasta 1.</p>
                            </div>

                            <div>
                                <label for="transfer_max_ring_seconds" class="block text-sm font-medium text-inkSoft mb-1.5">
                                    Timp de sonerie înainte de renunțare (secunde)
                                </label>
                                <input type="number" id="transfer_max_ring_seconds" name="transfer_max_ring_seconds"
                                       x-model.number="transfer.max_ring_seconds"
                                       min="10" max="60" step="1"
                                       class="w-full max-w-[12rem] rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <p class="text-xs text-muted mt-1">Dacă operatorul nu răspunde în acest interval, agentul se scuză clientului și închide. 25 secunde e un default sănătos.</p>
                            </div>

                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 space-y-1">
                                <div class="font-semibold">Cum testezi:</div>
                                <ol class="list-decimal list-inside space-y-0.5">
                                    <li>Sună pe numărul agentului AI.</li>
                                    <li>Spune ceva de genul „vreau să vorbesc cu un om".</li>
                                    <li>Agentul va spune „Vă fac legătura cu un coleg. Rămâneți pe linie".</li>
                                    <li>Numărul configurat mai sus va suna. La răspuns auzi un rezumat scurt + „Apasă 1 pentru a prelua".</li>
                                    <li>Apasă 1 → ești conectat live cu clientul.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============== TAB 6: AVANSAT ============== --}}
            <section x-show="tab === 'avansat'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-ink">Avansat</h2>
                    </div>
                    <div class="mb-4 p-3 rounded-md bg-amber-50 border border-amber-200 text-xs text-amber-800">
                        Pentru utilizatori avansați. Setările din tab-urile de mai sus sunt suficiente pentru majoritatea cazurilor.
                    </div>

                    <div class="space-y-5">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border border-line hover:bg-cream"
                               :class="!nicheSlug ? 'opacity-60 cursor-not-allowed' : ''">
                            <input type="hidden" name="settings[use_structured_prompt]" value="0">
                            <input type="checkbox" name="settings[use_structured_prompt]" value="1"
                                   {{ $useStructured ? 'checked' : '' }}
                                   @if(!$bot->niche_slug) disabled @endif
                                   class="mt-0.5 rounded border-line text-coralh focus:ring-coral/20">
                            <div>
                                <span class="text-sm font-medium text-ink">Folosește prompt structurat</span>
                                <p class="text-xs text-muted mt-0.5">
                                    @if($bot->niche_slug)
                                        Când e bifată, agentul folosește secțiunile de mai sus (FAQ, Reguli, Ton) ca sursă principală, cu promptul liber adăugat la sfârșit.
                                    @else
                                        Trebuie să selectezi o nișă pentru acest agent înainte de a activa promptul structurat.
                                    @endif
                                </p>
                            </div>
                        </label>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="system_prompt" class="block text-sm font-medium text-inkSoft">Prompt sistem (instrucțiuni suplimentare)</label>
                                <button type="button" @click="openPromptPreview()"
                                        class="text-xs text-coralh hover:text-coralh font-medium">
                                    👁 Vezi promptul final
                                </button>
                            </div>
                            <textarea name="system_prompt" id="system_prompt" rows="8"
                                      placeholder="Instrucțiuni suplimentare pe care le vrei în prompt..."
                                      class="w-full rounded-lg border border-line bg-white px-4 py-3 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y">{{ old('system_prompt', $bot->system_prompt) }}</textarea>
                            <p class="text-xs text-muted mt-1">Când promptul structurat e activ, acest text apare în secțiunea "Instrucțiuni suplimentare" la finalul promptului.</p>
                        </div>

                        @php $adv = $bot->settings ?? []; @endphp
                        <details class="rounded-lg border border-line">
                            <summary class="px-4 py-3 text-sm font-medium text-inkSoft cursor-pointer select-none">Parametri tehnici</summary>
                            <div class="p-4 space-y-5 border-t border-line">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="vad_threshold" class="text-sm font-medium text-inkSoft">VAD Threshold</label>
                                        <span id="vad_threshold_value" class="text-sm font-mono text-muted">{{ old('settings.vad_threshold', $adv['vad_threshold'] ?? 0.5) }}</span>
                                    </div>
                                    <input type="range" name="settings[vad_threshold]" id="vad_threshold" min="0" max="1" step="0.05"
                                           value="{{ old('settings.vad_threshold', $adv['vad_threshold'] ?? 0.5) }}"
                                           oninput="document.getElementById('vad_threshold_value').textContent = this.value"
                                           class="w-full accent-red-800">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="silence_duration" class="text-sm font-medium text-inkSoft">Durată tăcere (ms)</label>
                                        <span id="silence_duration_value" class="text-sm font-mono text-muted">{{ old('settings.silence_duration_ms', $adv['silence_duration_ms'] ?? 500) }}</span>
                                    </div>
                                    <input type="range" name="settings[silence_duration_ms]" id="silence_duration" min="200" max="2000" step="50"
                                           value="{{ old('settings.silence_duration_ms', $adv['silence_duration_ms'] ?? 500) }}"
                                           oninput="document.getElementById('silence_duration_value').textContent = this.value"
                                           class="w-full accent-red-800">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="temperature" class="text-sm font-medium text-inkSoft">Temperatură</label>
                                        <span id="temperature_value" class="text-sm font-mono text-muted">{{ old('settings.temperature', $adv['temperature'] ?? 0.7) }}</span>
                                    </div>
                                    <input type="range" name="settings[temperature]" id="temperature" min="0" max="1" step="0.05"
                                           value="{{ old('settings.temperature', $adv['temperature'] ?? 0.7) }}"
                                           oninput="document.getElementById('temperature_value').textContent = this.value"
                                           class="w-full accent-red-800">
                                </div>
                                <div>
                                    <label for="max_tokens" class="block text-sm font-medium text-inkSoft mb-1.5">Tokeni maximi</label>
                                    <input type="number" name="settings[max_tokens]" id="max_tokens" min="64" max="4096"
                                           value="{{ old('settings.max_tokens', $adv['max_tokens'] ?? 1024) }}"
                                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label for="knowledge_search_limit" class="block text-sm font-medium text-inkSoft mb-1.5">Rezultate knowledge base</label>
                                    <input type="number" name="knowledge_search_limit" id="knowledge_search_limit" min="1" max="20"
                                           value="{{ old('knowledge_search_limit', $bot->knowledge_search_limit ?? 5) }}"
                                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label for="max_call_duration_minutes" class="block text-sm font-medium text-inkSoft mb-1.5">Durată maximă apel (minute)</label>
                                    <input type="number" name="max_call_duration_minutes" id="max_call_duration_minutes" min="5" max="60"
                                           value="{{ old('max_call_duration_minutes', intval(($bot->max_call_duration_seconds ?? 1800) / 60)) }}"
                                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm">
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </section>

            {{-- ============== ACTION BAR (desktop) ============== --}}
            <div class="hidden sm:flex items-center justify-between gap-3 mt-6 pb-2">
                <div class="text-xs text-muted flex items-center gap-3 flex-wrap">
                    <span>
                        AI folosit azi: <span x-text="aiCost.count"></span> (<span x-text="aiCost.cost_ron.toFixed(4)"></span> lei)
                    </span>
                    {{-- Iteration B: tenant-wide spend progress --}}
                    <span x-show="tenantUsage.limit_ron > 0" class="flex items-center gap-2">
                        <span class="inline-block w-24 h-1.5 bg-sand rounded-full overflow-hidden align-middle">
                            <span class="block h-full transition-all"
                                  :class="tenantUsage.pct_of_limit >= 80 ? 'bg-red-500' : (tenantUsage.pct_of_limit >= 50 ? 'bg-amber-400' : 'bg-emerald-500')"
                                  :style="'width: ' + Math.min(100, tenantUsage.pct_of_limit) + '%'"></span>
                        </span>
                        <span>
                            <span x-text="tenantUsage.cost_ron.toFixed(2)"></span>
                            / <span x-text="tenantUsage.limit_ron.toFixed(2)"></span> lei
                            (<span x-text="Math.round(tenantUsage.pct_of_limit)"></span>%)
                        </span>
                        <span x-show="!tenantUsage.flag_enabled" class="text-slate-300" title="Limita nu este încă aplicată — doar vizualizare.">(info)</span>
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard.bots.show', $bot) }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-medium text-inkSoft hover:bg-cream transition">
                        Anulează
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-coral px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-coralh transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Salvează modificările
                    </button>
                </div>
            </div>

            {{-- Spacer so the sticky bar on mobile doesn't cover content. --}}
            <div class="sm:hidden h-20"></div>

            </div>{{-- /main col --}}
        </div>{{-- /flex --}}

        {{-- Iter A: sticky bottom save bar on mobile --}}
        <div class="sm:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-line px-4 py-3 flex items-center gap-2 shadow-lg">
            <a href="{{ route('dashboard.bots.show', $bot) }}"
               class="inline-flex items-center justify-center rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-inkSoft hover:bg-cream transition shrink-0">
                Anulează
            </a>
            <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-coral px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-coralh transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                Salvează
            </button>
        </div>
    </form>

    {{-- ============== MODAL: Full profile AI ============== --}}
    <div x-show="modal.fullProfile" x-cloak @click.self="modal.fullProfile = false"
         class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold text-ink mb-2">Completează profilul cu AI</h3>
            <p class="text-sm text-muted mb-4">
                AI-ul va completa automat <strong>informații business</strong>, <strong>FAQ-uri</strong>,
                <strong>reguli</strong> și <strong>ton</strong> pe baza nișei și a site-ului tău.
                <br><span class="text-xs text-muted">Cost estimat: ~0,05 lei. Tot ce e generat poate fi editat după.</span>
            </p>
            <div x-show="aiLoading.full_profile" class="mb-3 text-sm text-inkSoft flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Se generează... poate dura 10-20 secunde.
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="modal.fullProfile = false" :disabled="aiLoading.full_profile"
                        class="px-4 py-2 text-sm rounded-lg border border-line hover:bg-cream">Anulează</button>
                <button type="button" @click="runFullProfile()" :disabled="aiLoading.full_profile"
                        class="px-4 py-2 text-sm rounded-lg bg-coral text-white hover:bg-coral disabled:opacity-50">
                    <span x-show="!aiLoading.full_profile">✨ Generează</span>
                    <span x-show="aiLoading.full_profile">Se lucrează...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============== MODAL: Prompt preview ============== --}}
    <div x-show="modal.prompt" x-cloak @click.self="modal.prompt = false"
         class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[85vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-semibold text-ink">Promptul final</h3>
                <button type="button" @click="modal.prompt = false" class="text-muted hover:text-muted">✕</button>
            </div>
            <div class="overflow-y-auto p-6 text-sm">
                <div x-show="promptPreview.loading" class="text-muted">Se încarcă...</div>
                <div x-show="!promptPreview.loading && !promptPreview.flag_on"
                     class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-md text-amber-800 text-xs">
                    Promptul structurat nu e activ — agentul va folosi DOAR secțiunea "Instrucțiuni suplimentare" de mai jos.
                    Pentru a folosi ton, reguli, FAQ și business info din tab-uri, activează toggle-ul din tab-ul Avansat.
                </div>
                <div x-show="!promptPreview.loading" class="space-y-3">
                    <template x-for="(section, sIdx) in promptPreview.sections" :key="'s_' + sIdx">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold uppercase tracking-wider"
                                      :class="section.enabled && section.content ? 'text-muted' : 'text-slate-300 line-through'"
                                      x-text="section.name"></span>
                                <span x-show="!section.content" class="text-xs text-slate-300">(gol)</span>
                            </div>
                            <pre class="whitespace-pre-wrap text-xs bg-cream border border-line p-3 rounded"
                                 x-text="section.content || '—'"></pre>
                        </div>
                    </template>
                    <div class="mt-4 pt-4 border-t border-line">
                        <div class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Compus integral</div>
                        <pre class="whitespace-pre-wrap text-xs bg-slate-900 text-slate-100 p-3 rounded" x-text="promptPreview.prompt || '(gol)'"></pre>
                    </div>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-line flex justify-end">
                <button type="button" @click="modal.prompt = false" class="px-4 py-2 text-sm rounded-lg border border-line hover:bg-cream">Închide</button>
            </div>
        </div>
    </div>

</div>

{{-- ============== ALPINE DATA ============== --}}
<script>
function botEditor(init) {
    return {
        // Iter A: Bază (Quick Setup) is the landing tab.
        tab: 'baza',
        faqs: (init.faqs || []).map(f => ({ question: f.question || '', answer: f.answer || '', _new: false })),
        days: init.days || [],
        standard: init.standard || {},
        standardTexts: init.standardTexts || [],
        customLines: init.customLines || '',
        tone: init.tone || { length: 'medium', register: 'tu', emoji_ok: false, languages: ['ro'] },
        businessInfo: Object.assign({ address: '', phone: '', website: '', email: '' }, init.businessInfo || {}),
        core: Object.assign({ name: '', voice: 'coral', greeting: '', is_active: false }, init.core || {}),
        transfer: Object.assign({ enabled: false, operator_number: '', max_ring_seconds: 25 }, init.transfer || {}),
        checklistOpen: false,
        nicheSlug: init.nicheSlug || null,
        aiLoading: {},
        aiCost: { count: 0, cost_ron: 0, full_profile_today: 0, full_profile_daily_cap: 20 },
        // Iteration B: tenant-wide AI spend progress for the footer bar.
        tenantUsage: { cost_ron: 0, limit_ron: 0, pct_of_limit: 0, calls: 0, flag_enabled: false },
        modal: { fullProfile: false, prompt: false },
        promptPreview: { loading: false, prompt: '', sections: [], flag_on: false },

        init() {
            // Fetch today's AI cost (tiny footer indicator).
            fetch('{{ route('dashboard.bots.ai-cost-today', $bot) }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(r => r.ok ? r.json() : null)
              .then(d => { if (d) this.aiCost = {
                  count: d.count || 0,
                  cost_ron: d.cost_ron || 0,
                  full_profile_today: d.full_profile_today || 0,
                  full_profile_daily_cap: d.full_profile_daily_cap || 20,
              }; })
              .catch(() => {});
            this.refreshTenantUsage();
        },

        refreshTenantUsage() {
            fetch('{{ route('dashboard.ai-usage-today') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(r => r.ok ? r.json() : null)
              .then(d => {
                  if (!d) return;
                  this.tenantUsage = {
                      cost_ron: Number(d.cost_ron) || 0,
                      limit_ron: Number(d.limit_ron) || 0,
                      pct_of_limit: Number(d.pct_of_limit) || 0,
                      calls: Number(d.calls) || 0,
                      flag_enabled: !!d.flag_enabled,
                  };
              })
              .catch(() => {});
        },

        // ---------- Completeness ----------
        completenessPercent() {
            return this.completenessItems().filter(i => i.done).length * 20;
        },

        // Iter A: list form of the completeness check — each item tracks
        // which tab to jump to and which DOM id to focus on.
        completenessItems() {
            const hasContact = (this.businessInfo.address || '').trim() !== ''
                || (this.businessInfo.phone || '').trim() !== ''
                || (this.businessInfo.email || '').trim() !== '';
            const greetingFilled = (this.core && (this.core.greeting || '').trim() !== '');
            const hasHours = Array.isArray(this.days) && this.days.some(d => !d.closed && d.open && d.close);
            return [
                {
                    label: 'Nume + voce',
                    hint: (this.core.name || '').trim() && this.core.voice ? '' : 'Dă-i un nume și alege vocea agentului',
                    done: !!(this.core.name && (this.core.name).trim() && this.core.voice),
                    tab: 'baza',
                    focus: 'baza_name',
                },
                {
                    label: 'Mesaj de întâmpinare',
                    hint: greetingFilled ? '' : 'Ce spune agentul când preia apelul',
                    done: greetingFilled,
                    tab: 'baza',
                    focus: 'baza_greeting',
                },
                {
                    label: 'Contact (telefon, email sau adresă)',
                    hint: hasContact ? '' : 'Cel puțin una: telefon, email sau adresă',
                    done: hasContact,
                    tab: 'baza',
                    focus: 'baza_phone',
                },
                {
                    label: 'Program de lucru',
                    hint: hasHours ? '' : 'Definește orele și zilele în care agentul răspunde',
                    done: hasHours,
                    tab: 'business',
                    focus: null,
                },
                {
                    label: 'FAQ-uri (recomandat 3+)',
                    hint: this.faqs.length >= 3 ? '' : `Ai ${this.faqs.length}/3 întrebări frecvente`,
                    done: this.faqs.length >= 3,
                    tab: 'faq',
                    focus: null,
                },
            ];
        },

        focusFieldInTab(focusId) {
            if (!focusId) return;
            const el = document.getElementById(focusId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                try { el.focus({ preventScroll: true }); } catch (e) { el.focus(); }
            }
        },

        // ---------- Schedule ----------
        toggleBreak(day) {
            if (day.break_start === null) {
                day.break_start = '13:00';
                day.break_end = '14:00';
            } else {
                day.break_start = null;
                day.break_end = null;
            }
        },
        copyMonToWeekdays() {
            const mon = this.days[0];
            for (let i = 1; i < 5; i++) {
                this.days[i].closed = mon.closed;
                this.days[i].open = mon.open;
                this.days[i].close = mon.close;
                this.days[i].break_start = mon.break_start;
                this.days[i].break_end = mon.break_end;
            }
        },
        markAllClosed(weekendOnly = false) {
            if (weekendOnly) {
                if (this.days[5]) this.days[5].closed = true;
                if (this.days[6]) this.days[6].closed = true;
            }
        },

        // ---------- Contact normalisation ----------
        normalizePhone() {
            let v = (this.businessInfo.phone || '').replace(/\s+/g, '');
            if (v && !v.startsWith('+') && /^0\d+/.test(v)) {
                v = '+40' + v.substring(1);
            }
            this.businessInfo.phone = v;
        },
        normalizeWebsite() {
            let v = (this.businessInfo.website || '').trim();
            if (v && !/^https?:\/\//i.test(v)) {
                v = 'https://' + v;
                this.businessInfo.website = v;
            }
        },

        // ---------- FAQ ----------
        addFaq(seed) {
            if (this.faqs.length >= 50) return;
            this.faqs.push({
                question: seed && seed.question ? seed.question : '',
                answer:   seed && seed.answer   ? seed.answer   : '',
                _new: !!seed,
            });
            if (seed) {
                // Fade the NEW badge after a few seconds.
                const idx = this.faqs.length - 1;
                setTimeout(() => { if (this.faqs[idx]) this.faqs[idx]._new = false; }, 4000);
            }
        },
        removeFaq(idx) { this.faqs.splice(idx, 1); },

        generateFaqBulk(count) {
            this.aiLoading.faq_bulk = true;
            this.callAi('faq_bulk', null, { existing_faqs: this.faqs.map(f => f.question) }, count)
                .then(data => {
                    const generated = data.generated || [];
                    if (Array.isArray(generated)) {
                        generated.forEach(pair => {
                            if (pair && pair.question && pair.answer) this.addFaq(pair);
                        });
                        this.flashCost(data.cost_ron);
                    }
                })
                .catch(err => alert('Eroare AI: ' + (err.message || err)))
                .finally(() => { this.aiLoading.faq_bulk = false; });
        },

        rephraseFaqAnswer(idx) {
            const faq = this.faqs[idx];
            if (!faq || !faq.question) return;
            const key = 'faq_a_' + idx;
            this.aiLoading[key] = true;
            this.callAi('faq_answer', faq.question, { business: this.businessInfo }, 1)
                .then(data => {
                    if (typeof data.generated === 'string') {
                        this.faqs[idx].answer = data.generated;
                        this.flashCost(data.cost_ron);
                    }
                })
                .catch(err => alert('Eroare: ' + (err.message || err)))
                .finally(() => { this.aiLoading[key] = false; });
        },

        rephraseFaqQuestion(idx) {
            const faq = this.faqs[idx];
            if (!faq || !faq.question) return;
            const key = 'faq_q_' + idx;
            this.aiLoading[key] = true;
            this.callAi('rephrase', 'Reformulează această întrebare mai natural: ' + faq.question, {}, 1)
                .then(data => {
                    if (typeof data.generated === 'string') {
                        this.faqs[idx].question = data.generated;
                        this.flashCost(data.cost_ron);
                    }
                })
                .catch(err => alert('Eroare: ' + (err.message || err)))
                .finally(() => { this.aiLoading[key] = false; });
        },

        // ---------- Rules merge ----------
        mergedRules() {
            const out = [];
            // standard is { [index]: bool } — keyed to the companion
            // standardTexts array so the rule strings survive submit.
            (this.standardTexts || []).forEach((text, i) => {
                if (this.standard[i]) out.push(String(text).trim());
            });
            (this.customLines || '').split('\n').forEach(line => {
                const t = line.trim();
                if (t) out.push(t);
            });
            // dedupe while preserving order
            return [...new Set(out.filter(Boolean))];
        },

        // ---------- Tone ----------
        toggleToneLang(code, on) {
            if (on) {
                if (!this.tone.languages.includes(code)) this.tone.languages.push(code);
            } else {
                this.tone.languages = this.tone.languages.filter(l => l !== code);
            }
        },
        previewQuestion() {
            return 'La ce oră închideți?';
        },
        previewAnswer() {
            const r = this.tone.register === 'dvs' ? 'dumneavoastră' : 'tu';
            const addr = this.tone.register === 'dvs' ? 'vă' : 'te';
            const emoji = this.tone.emoji_ok ? ' 😊' : '';
            if (this.tone.length === 'short') {
                return (this.tone.register === 'dvs' ? 'Închidem la 18:00.' : 'Închidem la 18.') + emoji;
            }
            if (this.tone.length === 'long') {
                return (this.tone.register === 'dvs'
                    ? 'Programul nostru se încheie la ora 18:00. Dacă doriți să ajungeți, vă recomandăm să veniți cu cel puțin 15 minute înainte, pentru a vă putea oferi atenția necesară.'
                    : 'Închidem la 18 fix. Dacă vrei să ajungi, încearcă să vii cu cel puțin un sfert de oră înainte ca să ' + addr + ' putem servi cu calm.') + emoji;
            }
            return (this.tone.register === 'dvs'
                ? 'Închidem la 18:00. Dacă doriți, ' + addr + ' putem reține.'
                : 'Închidem la 18. Dacă vrei, ' + addr + ' putem reține.') + emoji;
        },

        // ---------- Generic AI helpers ----------
        aiGenerate(target, hint, stateKey) {
            this.aiLoading[stateKey] = true;
            this.callAi(target, hint, { business: this.businessInfo, faqs: this.faqs.map(f => f.question) }, 1)
                .then(data => {
                    if (target === 'extras_suggest' && typeof data.generated === 'string') {
                        const el = this.$refs.extrasField;
                        if (el) el.value = data.generated;
                        this.businessInfo.extras = data.generated;
                    } else if (target === 'rules_suggest' && Array.isArray(data.generated)) {
                        const cur = (this.customLines || '').trim();
                        const add = data.generated.map(r => String(r).trim()).filter(Boolean).join('\n');
                        this.customLines = cur ? (cur + '\n' + add) : add;
                    } else if (target === 'tone_suggest' && data.generated && typeof data.generated === 'object') {
                        const g = data.generated;
                        if (g.length)   this.tone.length   = ['short','medium','long'].includes(g.length) ? g.length : this.tone.length;
                        if (g.register) this.tone.register = g.register === 'dvs' || g.register === 'formal' ? 'dvs' : 'tu';
                        if (typeof g.emoji_ok === 'boolean') this.tone.emoji_ok = g.emoji_ok;
                        if (Array.isArray(g.languages) && g.languages.length) this.tone.languages = g.languages.filter(l => ['ro','en','hu','de','fr'].includes(l));
                    }
                    this.flashCost(data.cost_ron);
                })
                .catch(err => alert('Eroare AI: ' + (err.message || err)))
                .finally(() => { this.aiLoading[stateKey] = false; });
        },

        openFullProfileModal() {
            // Iteration B: pre-click warning when projected spend would
            // take the tenant past 80 % of the daily limit. Claude Haiku
            // 4.5 full_profile ≈ 0.06 RON per call — tiny, but we still
            // show a transparency prompt so the user knows.
            const ESTIMATED_RON = 0.06;
            const limit = this.tenantUsage.limit_ron || 0;
            const spent = this.tenantUsage.cost_ron || 0;
            if (limit > 0) {
                const projected = spent + ESTIMATED_RON;
                if (projected > limit * 0.8) {
                    const msg = 'Aceasta va aduce costul total la aproximativ ' +
                                projected.toFixed(2) + ' lei (din limitul ' + limit.toFixed(2) + ' lei). Continui?';
                    if (!window.confirm(msg)) {
                        return;
                    }
                }
            }
            // Hardening H3: per-bot full_profile has a daily cap. Warn
            // before the user clicks into a guaranteed 429 so the UX
            // explains the limit instead of showing a raw error.
            const cap = this.aiCost.full_profile_daily_cap || 20;
            const used = this.aiCost.full_profile_today || 0;
            if (used >= cap) {
                alert('Ai atins limita zilnică de ' + cap + ' generări complete pentru acest agent. Revino mâine sau editează manual.');
                return;
            }
            if (used >= cap - 2) {
                if (!window.confirm('Ai folosit ' + used + '/' + cap + ' generări complete azi pentru acest agent. Continui?')) {
                    return;
                }
            }
            this.modal.fullProfile = true;
        },

        runFullProfile() {
            this.aiLoading.full_profile = true;
            this.callAi('full_profile', null, {
                business: this.businessInfo,
                existing_faqs: this.faqs.map(f => f.question),
            }, 1)
                .then(data => {
                    const g = data.generated || {};
                    if (g.business_info && !this.businessInfo.extras) {
                        this.businessInfo.extras = g.business_info;
                        const el = this.$refs.extrasField;
                        if (el) el.value = g.business_info;
                    }
                    if (Array.isArray(g.faqs)) {
                        g.faqs.forEach(pair => {
                            if (pair && pair.question && pair.answer && this.faqs.length < 50) {
                                this.addFaq(pair);
                            }
                        });
                    }
                    if (Array.isArray(g.rules)) {
                        const cur = (this.customLines || '').trim();
                        const add = g.rules.map(r => String(r).trim()).filter(Boolean).join('\n');
                        this.customLines = cur ? (cur + '\n' + add) : add;
                    }
                    if (g.tone) {
                        if (g.tone.length)   this.tone.length   = ['short','medium','long'].includes(g.tone.length) ? g.tone.length : this.tone.length;
                        if (g.tone.register) this.tone.register = g.tone.register === 'dvs' || g.tone.register === 'formal' ? 'dvs' : 'tu';
                        if (typeof g.tone.emoji_ok === 'boolean') this.tone.emoji_ok = g.tone.emoji_ok;
                        if (Array.isArray(g.tone.languages)) this.tone.languages = g.tone.languages.filter(l => ['ro','en','hu','de','fr'].includes(l));
                    }
                    this.flashCost(data.cost_ron);
                    this.modal.fullProfile = false;
                })
                .catch(err => alert('Eroare AI: ' + (err.message || err)))
                .finally(() => { this.aiLoading.full_profile = false; });
        },

        openPromptPreview() {
            this.modal.prompt = true;
            this.promptPreview.loading = true;
            fetch('{{ route('dashboard.bots.prompt-preview', $bot) }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .then(d => {
                    this.promptPreview.prompt = d.prompt || '';
                    this.promptPreview.sections = d.sections || [];
                    this.promptPreview.flag_on = !!d.flag_on;
                })
                .catch(() => { this.promptPreview.prompt = '(nu s-a putut încărca)'; })
                .finally(() => { this.promptPreview.loading = false; });
        },

        callAi(target, hint, context, count) {
            const body = { target, hint, context, count };
            return fetch('{{ route('dashboard.bots.ai-generate', $bot) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            }).then(async r => {
                if (r.status === 429) {
                    const d = await r.json().catch(() => ({}));
                    throw new Error(d.message || ('Limita de cereri atinsă. Încearcă în ' + (d.retry_after || 60) + 's.'));
                }
                if (!r.ok) {
                    const d = await r.json().catch(() => ({}));
                    throw new Error(d.message || ('Eroare ' + r.status));
                }
                return r.json();
            });
        },

        flashCost(delta) {
            if (typeof delta === 'number') {
                this.aiCost.count += 1;
                this.aiCost.cost_ron = Math.round((this.aiCost.cost_ron + delta) * 10000) / 10000;
            }
            // Refresh tenant-wide usage bar in the background.
            this.refreshTenantUsage();
        },
    }
}
</script>

{{-- Keep existing voice-clone scripts untouched (used by the identitate tab partial). --}}
<script>
function vcAction(url, method) {
    fetch(url, {
        method: method === 'DELETE' ? 'POST' : 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: method === 'DELETE' ? '_method=DELETE' : '',
        redirect: 'follow',
    }).then(() => window.location.reload()).catch(e => alert('Eroare: ' + e.message));
}

let vcRecorder = null, vcChunks = [], vcBlob = null, vcSec = 0, vcInterval = null;
function vcStart() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        vcChunks = []; vcSec = 0;
        vcRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
        vcRecorder.ondataavailable = e => { if (e.data.size > 0) vcChunks.push(e.data); };
        vcRecorder.onstop = () => {
            stream.getTracks().forEach(t => t.stop());
            vcBlob = new Blob(vcChunks, { type: 'audio/webm' });
            document.getElementById('vc-audio').src = URL.createObjectURL(vcBlob);
            document.getElementById('vc-preview').classList.remove('hidden');
            document.getElementById('vc-btn-upload').classList.remove('hidden');
            if (vcSec < 60) document.getElementById('vc-warn').classList.remove('hidden');
            else document.getElementById('vc-warn').classList.add('hidden');
        };
        vcRecorder.start(1000);
        document.getElementById('vc-btn-record').classList.add('hidden');
        document.getElementById('vc-btn-stop').classList.remove('hidden');
        document.getElementById('vc-timer').classList.remove('hidden');
        document.getElementById('vc-preview').classList.add('hidden');
        document.getElementById('vc-btn-upload').classList.add('hidden');
        vcInterval = setInterval(() => {
            vcSec++;
            const m = String(Math.floor(vcSec/60)).padStart(2,'0');
            const s = String(vcSec%60).padStart(2,'0');
            document.getElementById('vc-timer-val').textContent = m+':'+s;
        }, 1000);
    }).catch(err => alert('Nu s-a putut accesa microfonul: ' + err.message));
}
function vcStop() {
    if (vcRecorder && vcRecorder.state !== 'inactive') vcRecorder.stop();
    clearInterval(vcInterval);
    document.getElementById('vc-btn-record').classList.remove('hidden');
    document.getElementById('vc-btn-stop').classList.add('hidden');
    document.getElementById('vc-timer').classList.add('hidden');
}
function vcUpload() {
    const name = document.getElementById('vc-name').value.trim();
    if (!name) { alert('Introduceți un nume pentru voce.'); return; }
    if (!vcBlob) { alert('Înregistrați mai întâi vocea.'); return; }
    const fd = new FormData();
    fd.append('name', name);
    fd.append('audio', vcBlob, 'recording.webm');
    fd.append('_token', '{{ csrf_token() }}');
    document.getElementById('vc-btn-upload').classList.add('hidden');
    document.getElementById('vc-uploading').classList.remove('hidden');
    fetch('{{ route("dashboard.bots.voiceClone.store", $bot) }}', {
        method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then(r => { window.location.reload(); }).catch(err => {
        alert('Eroare: ' + err.message);
        document.getElementById('vc-btn-upload').classList.remove('hidden');
        document.getElementById('vc-uploading').classList.add('hidden');
    });
}

@if(isset($clonedVoice) && $clonedVoice && $clonedVoice->isPending())
(function pollClone() {
    let cd = 5;
    const cdEl = document.getElementById('clone-poll-cd');
    const msgEl = document.getElementById('clone-poll-msg');
    const tick = setInterval(() => { cd--; if(cdEl) cdEl.textContent = cd; }, 1000);
    setTimeout(() => {
        clearInterval(tick);
        if(msgEl) msgEl.textContent = 'Se verifică...';
        fetch('{{ route("dashboard.bots.voiceClone.status", [$bot, $clonedVoice ?? $bot]) }}')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ready' || data.status === 'failed') window.location.reload();
                else pollClone();
            }).catch(() => pollClone());
    }, 5000);
})();
@endif
</script>

<style>[x-cloak]{display:none !important;}</style>
@endsection
