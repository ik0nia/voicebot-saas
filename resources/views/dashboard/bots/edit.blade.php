@extends('layouts.dashboard')

@section('title', 'Editează: ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft transition-colors">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">{{ $bot->name }}</a>
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
            'recording_enabled' => (bool) old('recording_enabled', $bot->recording_enabled ?? false),
        ],
        'transfer' => [
            'enabled'          => (bool) old('transfer_enabled', $bot->settings['transfer_config']['enabled'] ?? false),
            'operator_number'  => (string) old('transfer_operator_number', $bot->settings['transfer_config']['operator_number'] ?? ''),
            'max_ring_seconds' => (int) old('transfer_max_ring_seconds', $bot->settings['transfer_config']['max_ring_seconds'] ?? 25),
        ],
        // Snapshot pentru completeness widget — Alpine nu poate ști câte
        // documente sunt în DB sau ce setări globale există fără un fetch.
        'meta' => [
            'knowledge_count'   => (int) ($knowledgeCount ?? 0),
            'dont_rules_count'  => is_array($dontRules) ? count(array_filter(array_map('trim', $dontRules))) : 0,
            'tone_configured'   => !empty($toneGuide) && (
                ($toneGuide['length'] ?? null) !== ($defaultTone['length'] ?? null)
                || ($toneGuide['register'] ?? null) !== ($defaultTone['register'] ?? null)
                || (bool) ($toneGuide['emoji_ok'] ?? false) !== (bool) ($defaultTone['emoji_ok'] ?? false)
            ),
            'notifications_email' => (string) ($settings['notifications']['email'] ?? ''),
            'after_hours_message' => (string) ($settings['business_info']['after_hours_message'] ?? ''),
            'recording_decided'   => array_key_exists('voice', (array) $settings) && array_key_exists('recording_enabled_override', (array) ($settings['voice'] ?? [])),
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
                        class="group inline-flex items-center gap-2 rounded-md border border-line bg-white px-2.5 py-1.5 hover:border-coral/40 hover:shadow-sm transition text-left">
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
                                        @click="item.done ? (checklistOpen = false) : (item.link ? (window.location.href = item.link) : (tab = item.tab, checklistOpen = false, $nextTick(() => focusFieldInTab(item.focus))))"
                                        class="w-full flex items-start gap-2 text-left px-2 py-1.5 rounded hover:bg-cream transition">
                                    <span class="mt-0.5 font-bold" x-text="item.done ? '✓' : '○'"
                                          :class="item.done ? 'text-green-600' : 'text-line'"></span>
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

    {{-- ============== SETUP COMPLETENESS CARD (Iter B 2026-06-21) ==============
         Vizibil când profilul nu e ≥ 80% complet. Lista compactă cu next
         actions, click → sare la tab + focusează câmp. Se ascunde după ce
         completezi tot, ca să nu ocupe loc inutil pe agenții finiți. --}}
    <div x-show="completenessPercent() < 80" x-cloak
         x-data="{ expanded: completenessPercent() < 50 }"
         class="mb-6 bg-white rounded-xl border border-coral/20 shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center gap-4 cursor-pointer hover:bg-coralsoft/30"
             @click="expanded = !expanded">
            <div class="shrink-0 w-12 h-12 rounded-full bg-coralsoft flex items-center justify-center">
                <span class="text-lg font-bold text-coralh" x-text="completenessPercent() + '%'"></span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-ink">Setup agent — în progres</div>
                <div class="text-xs text-muted mt-0.5">
                    <span x-text="completenessItems().filter(i => i.done).length"></span>
                    din
                    <span x-text="completenessItems().length"></span>
                    secțiuni configurate.
                    <span x-show="completenessPercent() < 50">Începe cu cele esențiale, restul pot aștepta.</span>
                    <span x-show="completenessPercent() >= 50">Aproape gata — mai sunt câteva detalii.</span>
                </div>
                <div class="w-full h-1.5 bg-sand rounded-full overflow-hidden mt-2">
                    <div class="h-full bg-gradient-to-r from-coral to-coralh transition-all duration-500"
                         :style="'width: ' + completenessPercent() + '%'"></div>
                </div>
            </div>
            <svg class="w-4 h-4 text-muted shrink-0 transition-transform" :class="expanded ? 'rotate-180' : ''"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
        <div x-show="expanded" x-collapse class="px-5 pb-5 pt-1 border-t border-line">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
                <template x-for="(item, i) in completenessItems()" :key="'card_chk_' + i">
                    <button type="button"
                            @click.prevent="item.done ? null : (item.link ? (window.location.href = item.link) : (tab = item.tab, $nextTick(() => focusFieldInTab(item.focus))))"
                            :disabled="item.done"
                            :class="item.done ? 'bg-emerald-50 border-emerald-200 cursor-default' : 'bg-cream border-line hover:border-coral/40 hover:shadow-sm cursor-pointer'"
                            class="flex items-start gap-2.5 text-left px-3 py-2.5 rounded-lg border transition">
                        <span class="mt-0.5 text-base shrink-0"
                              x-text="item.done ? '✓' : '○'"
                              :class="item.done ? 'text-emerald-600 font-bold' : 'text-line'"></span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold" :class="item.done ? 'text-emerald-900' : 'text-ink'" x-text="item.label"></div>
                            <div x-show="item.hint" class="text-[11px] mt-0.5" :class="item.done ? 'text-emerald-700' : 'text-muted'" x-text="item.hint"></div>
                        </div>
                        <svg x-show="!item.done" class="w-3 h-3 text-muted shrink-0 mt-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>

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
            @include('dashboard.bots.edit._tab_baza')

            {{-- ============== TAB 1: IDENTITATE ============== --}}
            @include('dashboard.bots.edit._tab_identitate')

            {{-- ============== TAB 2: INFORMAȚII BUSINESS ============== --}}
            @include('dashboard.bots.edit._tab_business')

            {{-- ============== TAB 3: FAQ ============== --}}
            @include('dashboard.bots.edit._tab_faq')

            {{-- ============== TAB 4: REGULI STRICTE ============== --}}
            @include('dashboard.bots.edit._tab_reguli')

            {{-- ============== TAB 5: TON & STIL ============== --}}
            @include('dashboard.bots.edit._tab_ton')

            {{-- ============== TAB: TRANSFER OPERATOR ============== --}}
            @include('dashboard.bots.edit._tab_transfer')

            {{-- ============== TAB 6: AVANSAT ============== --}}
            @include('dashboard.bots.edit._tab_avansat')

            {{-- ============== ACTION BAR (desktop) ============== --}}
            <div class="hidden sm:flex items-center justify-between gap-3 mt-6 pb-2">
                <div class="text-xs text-muted flex items-center gap-3 flex-wrap">
                    <span>
                        Mesaje azi: <span x-text="aiCost.count"></span>
                        @if(auth()->user()->isSuperAdmin())
                            <span class="text-line">· <span x-text="aiCost.cost_ron.toFixed(4)"></span> lei</span>
                        @endif
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
                        <span x-show="!tenantUsage.flag_enabled" class="text-line" title="Limita nu este încă aplicată — doar vizualizare.">(info)</span>
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
                        class="px-4 py-2 text-sm rounded-lg bg-coral text-white hover:bg-coralh disabled:opacity-50">
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
                                      :class="section.enabled && section.content ? 'text-muted' : 'text-line line-through'"
                                      x-text="section.name"></span>
                                <span x-show="!section.content" class="text-xs text-line">(gol)</span>
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
            const items = this.completenessItems();
            if (!items.length) return 0;
            return Math.round(items.filter(i => i.done).length / items.length * 100);
        },

        // Iter A: list form of the completeness check — each item tracks
        // which tab to jump to and which DOM id to focus on.
        // Iter B (2026-06-21): extended from 5 → 8 items + grouped into
        // tiers (esențial / recomandat / avansat) for the new card UI.
        completenessItems() {
            const hasContact = (this.businessInfo.address || '').trim() !== ''
                || (this.businessInfo.phone || '').trim() !== ''
                || (this.businessInfo.email || '').trim() !== '';
            const greetingFilled = (this.core && (this.core.greeting || '').trim() !== '');
            const hasHours = Array.isArray(this.days) && this.days.some(d => !d.closed && d.open && d.close);
            const m = this.meta || {};
            const transferOk = !this.transfer.enabled
                || (this.transfer.enabled && (this.transfer.operator_number || '').trim() !== '');
            return [
                {
                    label: 'Nume + voce',
                    hint: (this.core.name || '').trim() && this.core.voice ? '' : 'Dă-i un nume și alege vocea',
                    done: !!(this.core.name && (this.core.name).trim() && this.core.voice),
                    tab: 'baza', focus: 'baza_name', tier: 'essential',
                },
                {
                    label: 'Mesaj de întâmpinare',
                    hint: greetingFilled ? '' : 'Ce spune agentul când preia apelul',
                    done: greetingFilled,
                    tab: 'baza', focus: 'baza_greeting', tier: 'essential',
                },
                {
                    label: 'Contact (telefon, email, adresă)',
                    hint: hasContact ? '' : 'Cel puțin unul din ele',
                    done: hasContact,
                    tab: 'baza', focus: 'baza_phone', tier: 'essential',
                },
                {
                    label: 'Program de lucru',
                    hint: hasHours ? '' : 'Cel puțin o zi cu ore (Tab Business)',
                    done: hasHours,
                    tab: 'business', focus: null, tier: 'essential',
                },
                {
                    label: 'FAQ-uri (3+)',
                    hint: this.faqs.length >= 3 ? '' : `Ai ${this.faqs.length}/3 — agentul învață rapid din ele`,
                    done: this.faqs.length >= 3,
                    tab: 'faq', focus: null, tier: 'recommended',
                },
                {
                    label: 'Reguli stricte (3+)',
                    hint: (m.dont_rules_count || 0) >= 3 ? '' : `Spune-i ce să NU facă (${m.dont_rules_count || 0}/3)`,
                    done: (m.dont_rules_count || 0) >= 3,
                    tab: 'reguli', focus: null, tier: 'recommended',
                },
                {
                    label: 'Documente sau site indexat',
                    hint: (m.knowledge_count || 0) > 0 ? `${m.knowledge_count} surse de cunoștințe` : 'Urcă PDF/DOCX sau indexează site-ul ca agentul să răspundă din ele',
                    done: (m.knowledge_count || 0) > 0,
                    tab: null, focus: null, tier: 'recommended',
                    link: '/dashboard/agenti/{{ $bot->id }}/knowledge',
                },
                {
                    label: 'Transfer la operator (voice)',
                    hint: transferOk ? (this.transfer.enabled ? 'Operator configurat' : 'Dezactivat — agentul rezolvă singur') : 'Pune un număr sau dezactivează',
                    done: transferOk,
                    tab: 'transfer', focus: 'transfer_operator_number', tier: 'recommended',
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

// Google Places autocomplete component (Iter audit 2026-06-22).
// Proxy server-side la /dashboard/api/places-autocomplete. Cache hit pe
// majoritatea queries → response ~50ms.
function placesAutocomplete() {
    return {
        suggestions: [],
        loading: false,
        open: false,
        lastQuery: '',
        async search(text) {
            const q = (text || '').trim();
            if (q.length < 3 || q === this.lastQuery) return;
            this.lastQuery = q;
            this.loading = true;
            try {
                const r = await fetch('/dashboard/api/places-autocomplete?q=' + encodeURIComponent(q) + '&country=ro', {
                    headers: { Accept: 'application/json' },
                });
                if (r.ok) {
                    const d = await r.json();
                    this.suggestions = d.suggestions || [];
                    this.open = this.suggestions.length > 0;
                }
            } catch (e) { /* silent */ }
            finally { this.loading = false; }
        },
        pick(suggestion) {
            // Folosim contextul botEditor parent — businessInfo.address e
            // în scope-ul lui.
            this.$root.businessInfo.address = suggestion.full;
            this.open = false;
            this.suggestions = [];
        },
    };
}
</script>

<style>[x-cloak]{display:none !important;}</style>
@endsection
