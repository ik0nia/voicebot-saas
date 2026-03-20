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
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" />
                    </div>

                    {{-- Limbă --}}
                    <div>
                        <label for="language" class="block text-sm font-medium text-slate-700 mb-1.5">Limbă <span class="text-red-500">*</span></label>
                        <select name="language" id="language" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
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
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                            <option value="alloy" {{ old('voice', $bot->voice) === 'alloy' ? 'selected' : '' }}>Alloy (neutru)</option>
                            <option value="echo" {{ old('voice', $bot->voice) === 'echo' ? 'selected' : '' }}>Echo (masculin)</option>
                            <option value="fable" {{ old('voice', $bot->voice) === 'fable' ? 'selected' : '' }}>Fable (expresiv)</option>
                            <option value="onyx" {{ old('voice', $bot->voice) === 'onyx' ? 'selected' : '' }}>Onyx (profund)</option>
                            <option value="nova" {{ old('voice', $bot->voice) === 'nova' ? 'selected' : '' }}>Nova (feminin)</option>
                            <option value="shimmer" {{ old('voice', $bot->voice) === 'shimmer' ? 'selected' : '' }}>Shimmer (cald)</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bot->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20 transition" />
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
                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition resize-y">{{ old('system_prompt', $bot->system_prompt) }}</textarea>
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
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
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
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
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
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                        <p class="mt-1 text-xs text-slate-400">Creativitatea răspunsurilor (0 = precis, 1 = creativ)</p>
                    </div>

                    {{-- Max Tokens --}}
                    <div>
                        <label for="max_tokens" class="block text-sm font-medium text-slate-700 mb-1.5">Tokeni maximi</label>
                        <input type="number" name="settings[max_tokens]" id="max_tokens" min="64" max="4096"
                               value="{{ old('settings.max_tokens', $settings['max_tokens'] ?? 1024) }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" />
                        <p class="mt-1 text-xs text-slate-400">Lungimea maximă a răspunsurilor generate (64-4096)</p>
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
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Salvează modificările
                </button>
            </div>
        </form>
    </div>
@endsection
