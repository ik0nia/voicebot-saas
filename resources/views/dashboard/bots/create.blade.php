@extends('layouts.dashboard')

@section('title', 'Creează bot nou')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boți</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Creează bot nou</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Creează bot nou</h1>
            <p class="mt-1 text-sm text-slate-500">Configurează un nou asistent vocal în 3 pași simpli.</p>
        </div>

        {{-- Step indicators --}}
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-0">
                {{-- Step 1 --}}
                <div id="step-indicator-1" class="flex items-center">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-red-800 text-white text-sm font-semibold transition-colors">1</div>
                    <span class="ml-2 text-sm font-medium text-red-800 hidden sm:inline">Informații de bază</span>
                </div>
                <div class="w-10 sm:w-16 h-px bg-slate-300 mx-3"></div>
                {{-- Step 2 --}}
                <div id="step-indicator-2" class="flex items-center">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-slate-200 text-slate-500 text-sm font-semibold transition-colors">2</div>
                    <span class="ml-2 text-sm font-medium text-slate-500 hidden sm:inline">Prompt sistem</span>
                </div>
                <div class="w-10 sm:w-16 h-px bg-slate-300 mx-3"></div>
                {{-- Step 3 --}}
                <div id="step-indicator-3" class="flex items-center">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-slate-200 text-slate-500 text-sm font-semibold transition-colors">3</div>
                    <span class="ml-2 text-sm font-medium text-slate-500 hidden sm:inline">Configurări</span>
                </div>
            </div>
        </div>

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.bots.store') }}" id="bot-create-form">
            @csrf

            {{-- Step 1: Informații de bază --}}
            <div id="step-1" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-1">Informații de bază</h2>
                <p class="text-sm text-slate-500 mb-6">Alege numele si limba botului tau.</p>

                <div class="space-y-5">
                    {{-- Nume bot --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nume bot <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               placeholder="Ex: Asistent Clinică, Suport Tehnic..."
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                    </div>

                    {{-- Site asociat --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Site asociat</label>
                        @if($sites->isEmpty())
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm text-yellow-800">Nu ai niciun site adaugat. <a href="{{ route('dashboard.sites.create') }}" class="font-semibold underline">Adauga un site</a> mai intai.</p>
                            </div>
                        @else
                            <select name="site_id" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                                <option value="">— Fara site asociat —</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->domain }} {{ $site->isVerified() ? '✓' : '(neverificat)' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-400 mt-1">Chatbot-ul va functiona doar pe acest site</p>
                        @endif
                    </div>

                    {{-- Limbă --}}
                    <div>
                        <label for="language" class="block text-sm font-medium text-slate-700 mb-1.5">Limbă <span class="text-red-500">*</span></label>
                        <select name="language" id="language" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                            <option value="">Selectează limba...</option>
                            <option value="ro" {{ old('language') === 'ro' ? 'selected' : '' }}>Română</option>
                            <option value="en" {{ old('language') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="de" {{ old('language') === 'de' ? 'selected' : '' }}>Deutsch</option>
                            <option value="fr" {{ old('language') === 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="es" {{ old('language') === 'es' ? 'selected' : '' }}>Español</option>
                        </select>
                    </div>

                    {{-- Voce — set default, configurable later in bot settings --}}
                    <input type="hidden" name="voice" value="alloy">
                </div>
            </div>

            {{-- Step 2: Prompt sistem --}}
            <div id="step-2" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hidden">
                <h2 class="text-lg font-semibold text-slate-900 mb-1">Prompt sistem</h2>
                <p class="text-sm text-slate-500 mb-6">Descrie personalitatea și comportamentul botului. Ex: Ești un asistent vocal prietenos pentru o clinică dentară...</p>

                {{-- Template suggestions --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Șabloane rapide</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="applyTemplate('general')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-800 hover:border-red-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                            Asistent general
                        </button>
                        <button type="button" onclick="applyTemplate('programari')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-800 hover:border-red-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            Programări
                        </button>
                        <button type="button" onclick="applyTemplate('suport')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-800 hover:border-red-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Suport tehnic
                        </button>
                    </div>
                </div>

                {{-- Textarea --}}
                <div class="mb-5">
                    <label for="system_prompt" class="block text-sm font-medium text-slate-700 mb-1.5">Prompt</label>
                    <textarea name="system_prompt" id="system_prompt" rows="8"
                              placeholder="Descrie cum trebuie să se comporte botul..."
                              oninput="updatePreview()"
                              class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition resize-y">{{ old('system_prompt') }}</textarea>
                </div>

                {{-- Preview --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Previzualizare</label>
                    <div id="prompt-preview" class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600 min-h-[60px] whitespace-pre-wrap">
                        <span class="text-slate-400 italic">Promptul va apărea aici...</span>
                    </div>
                </div>
            </div>

            {{-- Step 3: Configurări avansate --}}
            <div id="step-3" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hidden">
                <h2 class="text-lg font-semibold text-slate-900 mb-1">Configurări avansate</h2>
                <p class="text-sm text-slate-500 mb-6">Ajustează parametrii tehnici ai botului. Valorile implicite funcționează bine pentru majoritatea cazurilor.</p>

                <div class="space-y-6">
                    {{-- VAD Threshold --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="vad_threshold" class="text-sm font-medium text-slate-700">VAD Threshold</label>
                            <span id="vad_threshold_value" class="text-sm font-mono text-slate-500">0.5</span>
                        </div>
                        <input type="range" name="settings[vad_threshold]" id="vad_threshold" min="0" max="1" step="0.05" value="{{ old('settings.vad_threshold', '0.5') }}"
                               oninput="document.getElementById('vad_threshold_value').textContent = this.value"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-800" />
                        <p class="mt-1 text-xs text-slate-400">Sensibilitatea detectării vocii (0 = foarte sensibil, 1 = strict)</p>
                    </div>

                    {{-- Silence Duration --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="silence_duration" class="text-sm font-medium text-slate-700">Durată tăcere (ms)</label>
                            <span id="silence_duration_value" class="text-sm font-mono text-slate-500">500</span>
                        </div>
                        <input type="range" name="settings[silence_duration_ms]" id="silence_duration" min="200" max="2000" step="50" value="{{ old('settings.silence_duration_ms', '500') }}"
                               oninput="document.getElementById('silence_duration_value').textContent = this.value"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-800" />
                        <p class="mt-1 text-xs text-slate-400">Cât timp de tăcere așteaptă botul înainte să considere că vorbitorul a terminat (200-2000ms)</p>
                    </div>

                    {{-- Temperature --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="temperature" class="text-sm font-medium text-slate-700">Temperatură</label>
                            <span id="temperature_value" class="text-sm font-mono text-slate-500">0.7</span>
                        </div>
                        <input type="range" name="settings[temperature]" id="temperature" min="0" max="1" step="0.05" value="{{ old('settings.temperature', '0.7') }}"
                               oninput="document.getElementById('temperature_value').textContent = this.value"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-800" />
                        <p class="mt-1 text-xs text-slate-400">Creativitatea răspunsurilor (0 = precis, 1 = creativ)</p>
                    </div>

                    {{-- Max Tokens --}}
                    <div>
                        <label for="max_tokens" class="block text-sm font-medium text-slate-700 mb-1.5">Tokeni maximi</label>
                        <input type="number" name="settings[max_tokens]" id="max_tokens" min="64" max="4096" value="{{ old('settings.max_tokens', '1024') }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        <p class="mt-1 text-xs text-slate-400">Lungimea maximă a răspunsurilor generate (64-4096)</p>
                    </div>
                </div>
            </div>

            {{-- Navigation buttons --}}
            <div class="flex items-center justify-between mt-6">
                <button type="button" id="btn-prev" onclick="prevStep()" class="hidden inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    Înapoi
                </button>
                <div class="flex-1"></div>
                <button type="button" id="btn-next" onclick="nextStep()"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                    Următorul
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
                <button type="submit" id="btn-submit" class="hidden inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Creează botul
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    var currentStep = 1;
    var totalSteps = 3;

    var templates = {
        general: "Ești un asistent vocal prietenos și profesional. Răspunzi la întrebările clienților cu claritate și empatie. Dacă nu cunoști un răspuns, redirecționează politic apelul către un operator uman. Vorbești concis și la obiect, evitând răspunsurile prea lungi.",
        programari: "Ești un asistent vocal specializat în programări. Ajuți clienții să își facă, modifice sau anuleze programări. Ceri informațiile necesare pas cu pas: numele complet, data și ora preferată, tipul serviciului. Confirmi întotdeauna detaliile înainte de a finaliza programarea. Dacă intervalul dorit nu este disponibil, propui alternative.",
        suport: "Ești un asistent vocal de suport tehnic. Ajuți clienții să rezolve probleme tehnice pas cu pas. Întrebi despre simptome, verifici soluțiile cele mai comune și ghidezi utilizatorul prin procesul de depanare. Dacă problema nu poate fi rezolvată, creezi un tichet de suport și asiguri clientul că va fi contactat de un specialist."
    };

    function goToStep(step) {
        for (var i = 1; i <= totalSteps; i++) {
            document.getElementById('step-' + i).classList.add('hidden');

            var indicator = document.getElementById('step-indicator-' + i);
            var circle = indicator.querySelector('div');
            var label = indicator.querySelector('span');

            if (i < step) {
                circle.className = 'flex items-center justify-center w-9 h-9 rounded-full bg-green-500 text-white text-sm font-semibold transition-colors';
                circle.innerHTML = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';
                if (label) { label.className = 'ml-2 text-sm font-medium text-green-600 hidden sm:inline'; }
            } else if (i === step) {
                circle.className = 'flex items-center justify-center w-9 h-9 rounded-full bg-red-800 text-white text-sm font-semibold transition-colors';
                circle.textContent = i;
                if (label) { label.className = 'ml-2 text-sm font-medium text-red-800 hidden sm:inline'; }
            } else {
                circle.className = 'flex items-center justify-center w-9 h-9 rounded-full bg-slate-200 text-slate-500 text-sm font-semibold transition-colors';
                circle.textContent = i;
                if (label) { label.className = 'ml-2 text-sm font-medium text-slate-500 hidden sm:inline'; }
            }
        }

        document.getElementById('step-' + step).classList.remove('hidden');
        currentStep = step;

        // Show/hide buttons
        document.getElementById('btn-prev').classList.toggle('hidden', step === 1);
        document.getElementById('btn-next').classList.toggle('hidden', step === totalSteps);
        document.getElementById('btn-submit').classList.toggle('hidden', step !== totalSteps);
    }

    function nextStep() {
        // Validate step 1
        if (currentStep === 1) {
            var name = document.getElementById('name');
            var language = document.getElementById('language');
            var voice = { value: 'alloy' };
            if (!name.value.trim()) { name.focus(); return; }
            if (!language.value) { language.focus(); return; }
            // voice is set as hidden field with default 'alloy'
        }
        if (currentStep < totalSteps) {
            goToStep(currentStep + 1);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            goToStep(currentStep - 1);
        }
    }

    function applyTemplate(key) {
        var textarea = document.getElementById('system_prompt');
        textarea.value = templates[key] || '';
        updatePreview();
    }

    function updatePreview() {
        var textarea = document.getElementById('system_prompt');
        var preview = document.getElementById('prompt-preview');
        if (textarea.value.trim()) {
            preview.innerHTML = '';
            preview.textContent = textarea.value;
        } else {
            preview.innerHTML = '<span class="text-slate-400 italic">Promptul va apărea aici...</span>';
        }
    }
</script>
@endpush
