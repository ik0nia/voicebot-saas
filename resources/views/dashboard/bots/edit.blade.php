@extends('layouts.dashboard')

@section('title', 'Editează: ' . $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boți</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Editează</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editează bot</h1>
                <p class="mt-1 text-sm text-slate-500">Modifică setările pentru <strong>{{ $bot->name }}</strong>.</p>
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

        <form method="POST" action="{{ route('dashboard.bots.update', $bot) }}">
            @csrf
            @method('PUT')

            {{-- Basic info --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-1">Informații de bază</h2>
                <p class="text-sm text-slate-500 mb-6">Numele, limba și vocea botului.</p>

                <div class="space-y-5">
                    {{-- Nume bot --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nume bot <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $bot->name) }}" required
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
                                    <option value="{{ $site->id }}" {{ old('site_id', $bot->site_id) == $site->id ? 'selected' : '' }}>
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
                            <option value="ro" {{ old('language', $bot->language) === 'ro' ? 'selected' : '' }}>Română</option>
                            <option value="en" {{ old('language', $bot->language) === 'en' ? 'selected' : '' }}>English</option>
                            <option value="de" {{ old('language', $bot->language) === 'de' ? 'selected' : '' }}>Deutsch</option>
                            <option value="fr" {{ old('language', $bot->language) === 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="es" {{ old('language', $bot->language) === 'es' ? 'selected' : '' }}>Español</option>
                        </select>
                    </div>

                    {{-- Voce --}}
                    <div>
                        <label for="voice" class="block text-sm font-medium text-slate-700 mb-1.5">Voce <span class="text-red-500">*</span></label>
                        <select name="voice" id="voice" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                            <option value="alloy" {{ old('voice', $bot->voice) === 'alloy' ? 'selected' : '' }}>Alloy (neutru)</option>
                            <option value="echo" {{ old('voice', $bot->voice) === 'echo' ? 'selected' : '' }}>Echo (masculin)</option>
                            <option value="fable" {{ old('voice', $bot->voice) === 'fable' ? 'selected' : '' }}>Fable (expresiv)</option>
                            <option value="onyx" {{ old('voice', $bot->voice) === 'onyx' ? 'selected' : '' }}>Onyx (profund)</option>
                            <option value="nova" {{ old('voice', $bot->voice) === 'nova' ? 'selected' : '' }}>Nova (feminin)</option>
                            <option value="shimmer" {{ old('voice', $bot->voice) === 'shimmer' ? 'selected' : '' }}>Shimmer (cald)</option>
                        </select>

                        {{-- Voice Cloning Section --}}
                        <div class="mt-4 p-4 border border-slate-200 rounded-lg bg-slate-50" id="voice-clone-section">
                            <h4 class="text-sm font-semibold text-slate-900 mb-2">
                                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                                Voce clonata
                            </h4>

                            @if($clonedVoice && $clonedVoice->isReady())
                                {{-- Voice is ready --}}
                                <div class="flex items-center justify-between bg-white rounded-lg border border-slate-200 p-3">
                                    <div>
                                        <span class="text-sm font-medium text-slate-900">{{ $clonedVoice->name }}</span>
                                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Gata
                                        </span>
                                        @if($bot->cloned_voice_id === $clonedVoice->id)
                                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">Activa</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($bot->cloned_voice_id !== $clonedVoice->id)
                                            <button type="button" onclick="vcAction('{{ route('dashboard.bots.voiceClone.activate', [$bot, $clonedVoice]) }}', 'POST')" class="px-4 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 shadow-sm transition-colors">
                                                Foloseste aceasta voce
                                            </button>
                                        @else
                                            <button type="button" onclick="vcAction('{{ route('dashboard.bots.voiceClone.deactivate', $bot) }}', 'POST')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Revino la vocea presetata</button>
                                        @endif
                                        <button type="button" onclick="if(confirm('Sigur doriti sa stergeti aceasta voce clonata?')) vcAction('{{ route('dashboard.bots.voiceClone.destroy', [$bot, $clonedVoice]) }}', 'DELETE')" class="p-1.5 text-red-400 hover:text-red-600 transition-colors" title="Sterge vocea clonata">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>

                            @elseif($clonedVoice && $clonedVoice->isPending())
                                {{-- Voice is processing --}}
                                <div class="bg-white rounded-lg border border-yellow-200 p-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span class="text-sm text-yellow-700 font-medium">{{ $clonedVoice->name }} — se proceseaza...</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1" id="clone-poll-msg">Se verifica automat... <span id="clone-poll-cd">5</span>s</p>
                                </div>

                            @elseif($clonedVoice && $clonedVoice->status === 'failed')
                                {{-- Voice failed --}}
                                <div class="bg-white rounded-lg border border-red-200 p-3 mb-3">
                                    <span class="text-sm text-red-700 font-medium">{{ $clonedVoice->name }} — esuat</span>
                                    @if($clonedVoice->error_message)
                                        <p class="text-xs text-red-500 mt-0.5">{{ $clonedVoice->error_message }}</p>
                                    @endif
                                    <button type="button" onclick="vcAction('{{ route('dashboard.bots.voiceClone.destroy', [$bot, $clonedVoice]) }}', 'DELETE')" class="mt-2 text-xs text-red-600 underline hover:no-underline">Sterge si incearca din nou</button>
                                </div>
                            @endif

                            @if(!$clonedVoice || $clonedVoice->status === 'failed')
                            {{-- Recording UI --}}
                            <div id="record-ui" class="mt-3">
                                <p class="text-xs text-slate-500 mb-3">Inregistreaza-ti vocea citind textul de mai jos (minim 60 secunde):</p>

                                <div class="bg-white border border-slate-200 rounded-lg p-3 mb-3 max-h-24 overflow-y-auto">
                                    <p class="text-xs text-slate-600 leading-relaxed">Buna ziua, ma numesc si sunt asistentul dumneavoastra virtual. Sunt aici pentru a va ajuta cu orice intrebare sau solicitare. Compania noastra ofera servicii de inalta calitate, personalizate pentru nevoile fiecarui client. Putem programa intalniri, oferi informatii despre produsele si serviciile noastre, sau va putem pune in legatura cu un consultant specializat. Suntem disponibili pentru dumneavoastra in fiecare zi. Nu ezitati sa ne contactati oricand aveti nevoie de asistenta. Va multumim ca ne-ati ales si va dorim o zi minunata.</p>
                                </div>

                                <div class="flex items-center gap-3 mb-3">
                                    <input type="text" id="vc-name" placeholder="Numele vocii (ex: Vocea lui Andrei)" value="{{ old('name') }}"
                                           class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button" id="vc-btn-record" onclick="vcStart()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg>
                                        Inregistreaza
                                    </button>
                                    <button type="button" id="vc-btn-stop" onclick="vcStop()" class="hidden inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                                        Opreste
                                    </button>
                                    <span id="vc-timer" class="hidden text-sm font-mono text-slate-600">
                                        <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse mr-1"></span>
                                        <span id="vc-timer-val">00:00</span>
                                    </span>
                                </div>

                                <div id="vc-preview" class="hidden mt-3">
                                    <audio id="vc-audio" controls class="w-full h-8"></audio>
                                    <p id="vc-warn" class="hidden text-xs text-yellow-600 mt-1">Sub 60s — calitatea poate fi mai scazuta.</p>
                                </div>

                                <button type="button" id="vc-btn-upload" onclick="vcUpload()" class="hidden mt-3 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Trimite pentru clonare
                                </button>
                                <div id="vc-uploading" class="hidden mt-3 flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span class="text-sm text-slate-600">Se incarca...</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bot->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-slate-300 text-red-800 focus:ring-red-700/20 transition" />
                            <div>
                                <span class="text-sm font-medium text-slate-700">Bot activ</span>
                                <p class="text-xs text-slate-400">Botul va putea primi și efectua apeluri</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- System prompt --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-1">Prompt sistem</h2>
                <p class="text-sm text-slate-500 mb-6">Descrie personalitatea și comportamentul botului.</p>

                <textarea name="system_prompt" id="system_prompt" rows="8"
                          placeholder="Descrie cum trebuie să se comporte botul..."
                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition resize-y">{{ old('system_prompt', $bot->system_prompt) }}</textarea>

                {{-- Greeting message --}}
                <div class="mt-5">
                    <label for="greeting_message" class="block text-sm font-medium text-slate-700 mb-1.5">Mesaj de intampinare</label>
                    <p class="text-xs text-slate-500 mb-2">Textul exact pe care botul il spune cand raspunde. Lasa gol daca vrei sa astepte clientul sa vorbeasca primul.</p>
                    <input type="text" name="greeting_message" id="greeting_message"
                           value="{{ old('greeting_message', $bot->greeting_message) }}"
                           placeholder="ex: Buna ziua, sunt Greg de la Sambla. Cu ce va pot ajuta?"
                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                </div>
            </div>

            {{-- Advanced settings --}}
            @php $settings = $bot->settings ?? []; @endphp
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-1">Configurări avansate</h2>
                <p class="text-sm text-slate-500 mb-6">Parametri tehnici ai botului.</p>

                <div class="space-y-6">
                    {{-- VAD Threshold --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="vad_threshold" class="text-sm font-medium text-slate-700">VAD Threshold</label>
                            <span id="vad_threshold_value" class="text-sm font-mono text-slate-500">{{ old('settings.vad_threshold', $settings['vad_threshold'] ?? 0.5) }}</span>
                        </div>
                        <input type="range" name="settings[vad_threshold]" id="vad_threshold" min="0" max="1" step="0.05"
                               value="{{ old('settings.vad_threshold', $settings['vad_threshold'] ?? 0.5) }}"
                               oninput="document.getElementById('vad_threshold_value').textContent = this.value"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-800" />
                        <p class="mt-1 text-xs text-slate-400">Sensibilitatea detectării vocii (0 = foarte sensibil, 1 = strict)</p>
                    </div>

                    {{-- Silence Duration --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="silence_duration" class="text-sm font-medium text-slate-700">Durată tăcere (ms)</label>
                            <span id="silence_duration_value" class="text-sm font-mono text-slate-500">{{ old('settings.silence_duration_ms', $settings['silence_duration_ms'] ?? 500) }}</span>
                        </div>
                        <input type="range" name="settings[silence_duration_ms]" id="silence_duration" min="200" max="2000" step="50"
                               value="{{ old('settings.silence_duration_ms', $settings['silence_duration_ms'] ?? 500) }}"
                               oninput="document.getElementById('silence_duration_value').textContent = this.value"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-800" />
                        <p class="mt-1 text-xs text-slate-400">Cât timp de tăcere așteaptă botul înainte să considere că vorbitorul a terminat (200-2000ms)</p>
                    </div>

                    {{-- Temperature --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="temperature" class="text-sm font-medium text-slate-700">Temperatură</label>
                            <span id="temperature_value" class="text-sm font-mono text-slate-500">{{ old('settings.temperature', $settings['temperature'] ?? 0.7) }}</span>
                        </div>
                        <input type="range" name="settings[temperature]" id="temperature" min="0" max="1" step="0.05"
                               value="{{ old('settings.temperature', $settings['temperature'] ?? 0.7) }}"
                               oninput="document.getElementById('temperature_value').textContent = this.value"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-800" />
                        <p class="mt-1 text-xs text-slate-400">Creativitatea răspunsurilor (0 = precis, 1 = creativ)</p>
                    </div>

                    {{-- Max Tokens --}}
                    <div>
                        <label for="max_tokens" class="block text-sm font-medium text-slate-700 mb-1.5">Tokeni maximi</label>
                        <input type="number" name="settings[max_tokens]" id="max_tokens" min="64" max="4096"
                               value="{{ old('settings.max_tokens', $settings['max_tokens'] ?? 1024) }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        <p class="mt-1 text-xs text-slate-400">Lungimea maximă a răspunsurilor generate (64-4096)</p>
                    </div>

                    {{-- Knowledge Search Limit --}}
                    <div>
                        <label for="knowledge_search_limit" class="block text-sm font-medium text-slate-700 mb-1.5">Număr maxim rezultate knowledge base</label>
                        <input type="number" name="knowledge_search_limit" id="knowledge_search_limit" min="1" max="20"
                               value="{{ old('knowledge_search_limit', $bot->knowledge_search_limit ?? 5) }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        <p class="mt-1 text-xs text-slate-400">Câte rezultate din knowledge base sunt incluse în context (1-20)</p>
                    </div>

                    {{-- Max Call Duration --}}
                    <div>
                        <label for="max_call_duration_minutes" class="block text-sm font-medium text-slate-700 mb-1.5">Durata maximă apel (minute)</label>
                        <input type="number" name="max_call_duration_minutes" id="max_call_duration_minutes" min="5" max="60"
                               value="{{ old('max_call_duration_minutes', intval(($bot->max_call_duration_seconds ?? 1800) / 60)) }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        <p class="mt-1 text-xs text-slate-400">Durata maximă permisă pentru un apel (5-60 minute)</p>
                    </div>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('dashboard.bots.show', $bot) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Anulează
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Salvează modificările
                </button>
            </div>
        </form>
    </div>

<script>
// Voice clone actions via fetch (to avoid nested forms)
function vcAction(url, method) {
    fetch(url, {
        method: method === 'DELETE' ? 'POST' : 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: method === 'DELETE' ? '_method=DELETE' : '',
        redirect: 'follow',
    }).then(() => window.location.reload())
    .catch(e => alert('Eroare: ' + e.message));
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
    if (!name) { alert('Introduceti un nume pentru voce.'); return; }
    if (!vcBlob) { alert('Inregistrati mai intai vocea.'); return; }
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
        if(msgEl) msgEl.textContent = 'Se verifica...';
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
@endsection
