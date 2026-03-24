@extends('layouts.dashboard')

@section('title', $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boti</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">{{ $bot->name }}</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Inline update toast --}}
    <div id="save-toast" class="hidden fixed top-6 right-6 z-50 flex items-center gap-2 rounded-lg bg-green-600 text-white px-4 py-2.5 text-sm font-medium shadow-lg transition-all">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Salvat!
    </div>

    {{-- ============================================================ --}}
    {{-- HEADER --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <h1 id="bot-name-display" class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
            {{-- Active/Inactive toggle --}}
            <form method="POST" action="{{ route('dashboard.bots.toggle', $bot) }}" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors cursor-pointer
                    {{ $bot->is_active ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $bot->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                    {{ $bot->is_active ? 'Activ' : 'Inactiv' }}
                </button>
            </form>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.bots.testVocal', $bot) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m-4-4h8m-4-14a3 3 0 013 3v4a3 3 0 01-6 0V7a3 3 0 013-3z" />
                </svg>
                Test Vocal
            </a>
            <a href="{{ route('public.demo', $bot->slug) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Demo Link
            </a>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 1: QUICK SETUP GUIDE --}}
    {{-- ============================================================ --}}
    @php
        $hasPrompt = !empty($bot->system_prompt);
        $hasKnowledge = ($kbStats['total_documents'] ?? 0) > 0;
        $hasWordpress = isset($wcConnector) && $wcConnector;
        $setupComplete = $hasPrompt && $hasKnowledge && $hasWordpress;
    @endphp
    @if(!$setupComplete)
    <div x-data="{ open: true }" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Ghid configurare rapida</h2>
                        <p class="text-xs text-slate-500">{{ collect([$hasPrompt, $hasKnowledge, $hasWordpress])->filter()->count() }}/3 pasi completati</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-5 pb-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Step 1: Configure prompt --}}
                    <a href="#section-config" class="flex items-start gap-3 p-4 rounded-lg border {{ $hasPrompt ? 'border-green-200 bg-green-50/50' : 'border-slate-200 bg-slate-50 hover:border-red-200 hover:bg-red-50/30' }} transition-colors">
                        <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0 {{ $hasPrompt ? 'bg-green-100 text-green-600' : 'border-2 border-slate-300 text-slate-400' }}">
                            @if($hasPrompt)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <span class="text-xs font-bold">1</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium {{ $hasPrompt ? 'text-green-800' : 'text-slate-700' }}">Configureaza prompt-ul</p>
                            <p class="text-xs {{ $hasPrompt ? 'text-green-600' : 'text-slate-500' }}">{{ $hasPrompt ? 'Completat' : 'Seteaza instructiunile botului' }}</p>
                        </div>
                    </a>
                    {{-- Step 2: Build knowledge base --}}
                    <a href="#section-kb" class="flex items-start gap-3 p-4 rounded-lg border {{ $hasKnowledge ? 'border-green-200 bg-green-50/50' : 'border-slate-200 bg-slate-50 hover:border-red-200 hover:bg-red-50/30' }} transition-colors">
                        <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0 {{ $hasKnowledge ? 'bg-green-100 text-green-600' : 'border-2 border-slate-300 text-slate-400' }}">
                            @if($hasKnowledge)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <span class="text-xs font-bold">2</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium {{ $hasKnowledge ? 'text-green-800' : 'text-slate-700' }}">Construieste Knowledge Base</p>
                            <p class="text-xs {{ $hasKnowledge ? 'text-green-600' : 'text-slate-500' }}">{{ $hasKnowledge ? 'Completat' : 'Adauga documente si FAQ' }}</p>
                        </div>
                    </a>
                    {{-- Step 3: Connect to WordPress --}}
                    <a href="#section-wordpress" class="flex items-start gap-3 p-4 rounded-lg border {{ $hasWordpress ? 'border-green-200 bg-green-50/50' : 'border-slate-200 bg-slate-50 hover:border-red-200 hover:bg-red-50/30' }} transition-colors">
                        <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0 {{ $hasWordpress ? 'bg-green-100 text-green-600' : 'border-2 border-slate-300 text-slate-400' }}">
                            @if($hasWordpress)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <span class="text-xs font-bold">3</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium {{ $hasWordpress ? 'text-green-800' : 'text-slate-700' }}">Conecteaza la WordPress</p>
                            <p class="text-xs {{ $hasWordpress ? 'text-green-600' : 'text-slate-500' }}">{{ $hasWordpress ? 'Completat' : 'Instaleaza plugin-ul WooCommerce' }}</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- SECTION 2: INTEGRARE WORDPRESS --}}
    {{-- ============================================================ --}}
    <div id="section-wordpress" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900">Integrare WordPress</h2>
                </div>
                @if($wcConnector)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        WooCommerce conectat
                    </span>
                @endif
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Left: API Key --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Cheie API</label>
                            @if($apiTokens->count() > 0)
                                @php $latestToken = $apiTokens->first(); @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 relative">
                                        <input type="text" readonly value="{{ $latestToken->name }} ({{ Str::mask($latestToken->token ?? '****', '*', 4) }})" id="api-key-display"
                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono text-slate-600 pr-10">
                                        <button onclick="navigator.clipboard.writeText(document.getElementById('api-key-display').value).then(()=>{this.textContent='OK';setTimeout(()=>{this.textContent='Copiaza'},1500)})"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-medium text-red-800 hover:text-red-900 transition-colors">
                                            Copiaza
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Foloseste aceasta cheie in plugin-ul WordPress.</p>
                            @else
                                <p class="text-sm text-slate-500 mb-2">Nu ai nicio cheie API generata.</p>
                                <a href="{{ route('dashboard.settings.index', ['tab' => 'api']) }}"
                                   class="inline-flex items-center gap-1.5 text-sm font-medium text-red-800 hover:text-red-900 transition-colors">
                                    Genereaza cheie API &rarr;
                                </a>
                            @endif
                        </div>

                        {{-- Download WooCommerce plugin --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Plugin WooCommerce</label>
                            <a href="/downloads/sambla-woocommerce-1.0.0.zip"
                               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descarca sambla-woocommerce-1.0.0.zip
                            </a>
                        </div>
                    </div>

                    {{-- Right: Embed code --}}
                    <div>
                        @php
                            $embedCode = $bot->getEmbedCode();
                            $webChatbotChannel = $bot->channels->where('type', 'web_chatbot')->where('is_active', true)->first();
                            $siteVerified = $bot->site && $bot->site->isVerified();
                        @endphp
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Cod Embed Chatbot</label>
                        @if($webChatbotChannel && $siteVerified && $embedCode)
                            <div class="relative group">
                                <div class="flex items-start gap-2">
                                    <pre class="flex-1 rounded-lg bg-slate-900 text-slate-100 px-4 py-3 text-xs font-mono overflow-x-auto leading-relaxed select-all max-h-32 overflow-y-auto"><code>{{ $embedCode }}</code></pre>
                                    <button
                                        onclick="navigator.clipboard.writeText(this.getAttribute('data-code')).then(()=>{this.textContent='Copiat!';setTimeout(()=>{this.textContent='Copiaza'},2000)})"
                                        data-code="{{ $embedCode }}"
                                        class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-red-800 hover:bg-red-900 px-3 py-2 text-xs font-medium text-white transition-colors">
                                        Copiaza
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5">Domeniu autorizat: <span class="font-medium">{{ $bot->site->domain }}</span></p>
                        @elseif($webChatbotChannel && !$siteVerified)
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
                                <p class="font-medium mb-1">Verificare site necesara</p>
                                <p class="text-xs">Verifica site-ul pentru a obtine codul de embed.</p>
                                @if($bot->site)
                                    <a href="{{ route('dashboard.sites.show', $bot->site) }}" class="text-xs font-medium text-red-800 hover:text-red-900 mt-1 inline-block">Verifica site-ul &rarr;</a>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
                                <p>Activeaza un canal Web Chatbot si verifica site-ul.</p>
                                <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-xs font-medium text-red-800 hover:text-red-900 mt-1 inline-block">Gestioneaza canale &rarr;</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 3: CONFIGURARE BOT --}}
    {{-- ============================================================ --}}
    @php
        $langLabels = ['ro' => 'Romana', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Francais', 'es' => 'Espanol'];
        $voiceLabels = ['alloy' => 'Alloy (neutru)', 'echo' => 'Echo (masculin)', 'fable' => 'Fable (expresiv)', 'onyx' => 'Onyx (profund)', 'nova' => 'Nova (feminin)', 'shimmer' => 'Shimmer (cald)'];
        $languages = ['ro', 'en', 'de', 'fr', 'es'];
        $voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    @endphp
    <div id="section-config" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-50">
                    <svg class="w-4 h-4 text-red-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-slate-900">Configurare Bot</h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Left side: Basic settings --}}
                    <div class="space-y-5">
                        {{-- Name --}}
                        <div class="group">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nume</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="field-name" value="{{ $bot->name }}"
                                    class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors"
                                    onchange="updateField('name', this.value)">
                            </div>
                        </div>

                        {{-- Language --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Limba</label>
                            <select id="field-language" onchange="updateField('language', this.value)"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors">
                                @foreach($languages as $lang)
                                    <option value="{{ $lang }}" {{ $bot->language === $lang ? 'selected' : '' }}>{{ $langLabels[$lang] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Greeting message --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Mesaj de intampinare</label>
                            <textarea id="field-greeting_message" rows="2" placeholder="ex: Buna ziua, cu ce va pot ajuta?"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors resize-y"
                                onchange="updateField('greeting_message', this.value)">{{ $bot->greeting_message }}</textarea>
                            <p class="text-xs text-slate-400 mt-1">Primul mesaj pe care botul il spune. Lasa gol daca vrei sa astepte clientul.</p>
                        </div>
                    </div>

                    {{-- Right side: System prompt --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Prompt sistem</label>
                        @if($bot->system_prompt)
                            @php
                                $promptLines = explode("\n", $bot->system_prompt);
                                $previewLines = array_slice($promptLines, 0, 3);
                                $hasMore = count($promptLines) > 3;
                            @endphp
                            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700 font-mono leading-relaxed">
                                <div class="whitespace-pre-wrap">{{ implode("\n", $previewLines) }}@if($hasMore){{ "\n..." }}@endif</div>
                            </div>
                            <button onclick="openPromptModal()"
                                class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-red-800 hover:text-red-900 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editeaza prompt-ul
                            </button>
                        @else
                            <div class="rounded-lg bg-slate-50 border border-slate-200 border-dashed px-4 py-6 text-center">
                                <p class="text-sm text-slate-400 mb-2">Niciun prompt configurat.</p>
                                <button onclick="openPromptModal()"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-red-800 hover:text-red-900 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Adauga prompt
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION: VOCE --}}
    {{-- ============================================================ --}}
    <div class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50">
                    <svg class="w-4 h-4 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Voce</h2>
                    <p class="text-xs text-slate-500">Setari pentru apelurile vocale si demo</p>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Voice preset --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Voce presetata OpenAI</label>
                        <select id="field-voice" onchange="updateField('voice', this.value)"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors">
                            @foreach($voices as $v)
                                <option value="{{ $v }}" {{ $bot->voice === $v ? 'selected' : '' }}>{{ $voiceLabels[$v] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Folosita cand nu e activa o voce clonata</p>
                    </div>

                    {{-- Voice cloning --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Voce clonata</label>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div class="flex items-center gap-3">
                                @if($bot->cloned_voice_id && $bot->clonedVoice && $bot->clonedVoice->isReady())
                                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                    <div>
                                        <p class="text-sm font-medium text-green-700">{{ $bot->clonedVoice->name }}</p>
                                        <p class="text-xs text-green-600">Activa</p>
                                    </div>
                                @elseif(isset($clonedVoice) && $clonedVoice && $clonedVoice->isPending())
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <div>
                                        <p class="text-sm font-medium text-amber-700">{{ $clonedVoice->name }}</p>
                                        <p class="text-xs text-amber-600">Se proceseaza...</p>
                                    </div>
                                @elseif(isset($clonedVoice) && $clonedVoice && $clonedVoice->isReady())
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-700">{{ $clonedVoice->name }}</p>
                                        <p class="text-xs text-slate-500">Disponibila (neactivata)</p>
                                    </div>
                                @else
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                    <p class="text-sm text-slate-500">Nicio voce clonata</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if(isset($clonedVoice) && $clonedVoice && $clonedVoice->isReady() && $bot->cloned_voice_id !== $clonedVoice->id)
                                    <button onclick="vcAction('{{ route('dashboard.bots.voiceClone.activate', [$bot, $clonedVoice]) }}', 'POST')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-800 text-white hover:bg-red-900 transition-colors">Activeaza</button>
                                @elseif($bot->cloned_voice_id)
                                    <button onclick="vcAction('{{ route('dashboard.bots.voiceClone.deactivate', $bot) }}', 'POST')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-600 hover:bg-white transition-colors">Dezactiveaza</button>
                                @endif
                                <a href="{{ route('dashboard.bots.voiceClone.create', $bot) }}"
                                   class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-600 hover:bg-white transition-colors">
                                    {{ (isset($clonedVoice) && $clonedVoice) ? 'Gestioneaza' : 'Cloneaza voce' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Test vocal link --}}
                <div class="mt-4 flex items-center gap-3">
                    <a href="{{ route('dashboard.bots.testVocal', $bot) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-red-200 bg-red-50 text-red-800 hover:bg-red-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Testeaza vocal
                    </a>
                    <a href="{{ route('public.demo', $bot->slug) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Link public demo
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 4: KNOWLEDGE BASE --}}
    {{-- ============================================================ --}}
    @php
        $kbScore = $kbStats['score'];
        $kbScoreColor = $kbScore >= 80 ? 'text-green-600' : ($kbScore >= 40 ? 'text-amber-500' : 'text-red-600');
        $kbStrokeColor = $kbScore >= 80 ? 'stroke-green-500' : ($kbScore >= 40 ? 'stroke-amber-500' : 'stroke-red-500');
        $kbTrackColor = $kbScore >= 80 ? 'stroke-green-100' : ($kbScore >= 40 ? 'stroke-amber-100' : 'stroke-red-100');
        $circumference = 2 * 3.14159 * 40;
        $dashoffset = $circumference - ($kbScore / 100) * $circumference;
    @endphp
    <div id="section-kb" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50">
                        <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900">Knowledge Base</h2>
                </div>
                <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors">
                    Gestioneaza Knowledge Base
                </a>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- KB Health Score --}}
                    <div class="flex flex-col items-center">
                        <div class="relative w-24 h-24 mb-3">
                            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="{{ $kbTrackColor }}" />
                                <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="{{ $kbStrokeColor }}"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $dashoffset }}" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-bold {{ $kbScoreColor }}">{{ $kbScore }}%</span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wide">Scor</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900">{{ $kbStats['total_documents'] }}</p>
                            <p class="text-xs text-slate-500">documente ({{ $kbStats['total_chunks'] }} chunks)</p>
                        </div>
                    </div>

                    {{-- Recent documents --}}
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Documente recente</h3>
                        @if($recentKnowledge->count() > 0)
                            <ul class="space-y-2">
                                @foreach($recentKnowledge as $doc)
                                    <li class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-slate-50 transition-colors">
                                        <div class="flex items-center justify-center w-7 h-7 rounded bg-slate-100 shrink-0">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ $doc->title }}</p>
                                            <p class="text-xs text-slate-400">{{ $doc->source_type ?? 'manual' }} &middot; {{ $doc->created_at->diffForHumans() }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="rounded-lg bg-slate-50 border border-slate-200 border-dashed px-4 py-6 text-center">
                                <p class="text-sm text-slate-400">Niciun document in Knowledge Base.</p>
                                <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}" class="text-sm font-medium text-red-800 hover:text-red-900 mt-1 inline-block">Adauga documente &rarr;</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 5: STATS & APELURI --}}
    {{-- ============================================================ --}}
    <div id="section-stats" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-50">
                    <svg class="w-4 h-4 text-red-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-slate-900">Stats & Apeluri</h2>
            </div>
            <div class="p-5">
                {{-- Stat cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg border border-slate-200 p-4 text-center">
                        <p class="text-2xl font-bold text-slate-900">{{ $callsThisMonth }}</p>
                        <p class="text-xs text-slate-500 mt-1">Apeluri luna aceasta</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4 text-center">
                        <p class="text-2xl font-bold text-slate-900">{{ $avgDuration ? gmdate('i:s', (int) $avgDuration) : '---' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Durata medie</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4 text-center">
                        <p class="text-2xl font-bold text-slate-900">{{ $bot->calls_count ?? 0 }}</p>
                        <p class="text-xs text-slate-500 mt-1">Total apeluri</p>
                    </div>
                </div>

                {{-- Recent calls table --}}
                @if($recentCalls->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Telefon</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Durata</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($recentCalls as $call)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 font-mono text-xs text-slate-500">#{{ $call->id }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $call->phone_number ?? '---' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '---' }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusClasses = [
                                                    'completed' => 'bg-green-50 text-green-700',
                                                    'failed' => 'bg-red-50 text-red-700',
                                                    'in_progress' => 'bg-red-50 text-red-800',
                                                    'missed' => 'bg-amber-50 text-amber-700',
                                                ];
                                                $statusLabels = [
                                                    'completed' => 'Finalizat',
                                                    'failed' => 'Esuat',
                                                    'in_progress' => 'In curs',
                                                    'missed' => 'Ratat',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses[$call->status] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $statusLabels[$call->status] ?? $call->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-6 text-sm text-slate-400">
                        Niciun apel inregistrat.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 6: CANALE & NUMERE --}}
    {{-- ============================================================ --}}
    <div id="section-channels" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100">
                        <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900">Canale & Numere</h2>
                </div>
                <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Gestioneaza
                </a>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Channels --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Canale conectate</h3>
                        @if($bot->channels && $bot->channels->count() > 0)
                            @php
                                $channelDotColors = [
                                    'connected' => 'bg-green-500',
                                    'pending' => 'bg-amber-500',
                                    'error' => 'bg-red-500',
                                    'disconnected' => 'bg-slate-400',
                                ];
                                $channelIconColors = [
                                    'voice' => 'text-red-600',
                                    'whatsapp' => 'text-green-600',
                                    'facebook_messenger' => 'text-blue-600',
                                    'instagram_dm' => 'text-pink-600',
                                    'web_chatbot' => 'text-slate-600',
                                ];
                            @endphp
                            <ul class="space-y-2.5">
                                @foreach($bot->channels as $channel)
                                    <li class="flex items-center gap-3 text-sm text-slate-700">
                                        <div class="relative shrink-0">
                                            <div class="w-8 h-8 rounded-lg {{ $channel->is_active ? 'bg-slate-100' : 'bg-slate-50' }} flex items-center justify-center">
                                                @include('dashboard.bots.channels._channel-icon', ['type' => $channel->type, 'class' => 'w-4 h-4 ' . ($channelIconColors[$channel->type] ?? 'text-slate-500')])
                                            </div>
                                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white {{ $channelDotColors[$channel->status] ?? 'bg-slate-400' }}"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-slate-900 truncate text-sm">{{ $channel->name ?? $channel->getDisplayName() }}</p>
                                            <p class="text-xs text-slate-400">{{ $channel->is_active ? 'Activ' : 'Inactiv' }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-400 italic">Niciun canal conectat.</p>
                        @endif
                    </div>

                    {{-- Phone numbers --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Numere de telefon</h3>
                        @if($bot->phoneNumbers && $bot->phoneNumbers->count() > 0)
                            <ul class="space-y-2">
                                @foreach($bot->phoneNumbers as $number)
                                    <li class="flex items-center gap-2 text-sm text-slate-700 font-mono py-1.5 px-3 rounded-lg bg-slate-50">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $number->number }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-400 italic">Niciun numar asociat.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: SYSTEM PROMPT EDIT --}}
    {{-- ============================================================ --}}
    <div id="prompt-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col mx-4">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Editeaza Prompt Sistem</h3>
                <button onclick="closePromptModal()" class="text-slate-400 hover:text-slate-600 transition-colors text-xl leading-none">&times;</button>
            </div>
            <div class="p-6 flex-1 overflow-y-auto">
                <textarea id="prompt-textarea" class="w-full h-64 rounded-lg border border-slate-200 p-4 text-sm font-mono resize-y focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors">{{ $bot->system_prompt }}</textarea>
            </div>
            <div class="px-6 py-4 border-t flex justify-end gap-3">
                <button onclick="closePromptModal()" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Anuleaza</button>
                <button onclick="savePrompt()" class="px-4 py-2 text-sm font-medium rounded-lg bg-red-800 text-white hover:bg-red-900 transition-colors">Salveaza</button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================================================ --}}
    <script>
        const UPDATE_URL = "{{ route('dashboard.bots.updateField', $bot) }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";

        function showToast(message) {
            const toast = document.getElementById('save-toast');
            if (message) toast.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2000);
        }

        function vcAction(url, method) {
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/x-www-form-urlencoded' },
                body: method === 'DELETE' ? '_method=DELETE' : '',
            }).then(() => window.location.reload());
        }

        function updateField(field, value) {
            fetch(UPDATE_URL, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ field, value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Salvat!');
                    if (field === 'name') {
                        document.getElementById('bot-name-display').textContent = value;
                    }
                }
            })
            .catch(err => {
                console.error('Update failed:', err);
                alert('Eroare la salvare. Incearca din nou.');
            });
        }

        function openPromptModal() {
            document.getElementById('prompt-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePromptModal() {
            document.getElementById('prompt-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function savePrompt() {
            const value = document.getElementById('prompt-textarea').value;
            fetch(UPDATE_URL, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ field: 'system_prompt', value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Prompt salvat!');
                    closePromptModal();
                    // Reload to show updated preview
                    location.reload();
                }
            })
            .catch(err => {
                console.error('Save prompt failed:', err);
                alert('Eroare la salvare. Incearca din nou.');
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePromptModal();
        });

        // Close modal on backdrop click
        document.getElementById('prompt-modal').addEventListener('click', function(e) {
            if (e.target === this) closePromptModal();
        });
    </script>
@endsection
