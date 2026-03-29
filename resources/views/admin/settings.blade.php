@extends('layouts.admin')

@section('title', 'Setări Platformă')
@section('breadcrumb')<span class="text-slate-900 font-medium">Setări Platformă</span>@endsection

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Setări Platformă</h1>
            <p class="text-sm text-slate-500">Configurare globală a platformei Sambla</p>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-x-1 overflow-x-auto" aria-label="Tabs">
            @php
                $tabs = [
                    'general' => ['label' => 'General', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                    'openai' => ['label' => 'OpenAI', 'icon' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 001.5 2.25M14.25 3.104c.251.023.501.05.75.082M19.5 14.5l-4.09-4.09a2.25 2.25 0 01-.66-1.591V3.186'],
                    'twilio' => ['label' => 'Twilio', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                    'stripe' => ['label' => 'Stripe', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    'email' => ['label' => 'Email', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                    'facebook' => ['label' => 'Facebook', 'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z'],
                    'instagram' => ['label' => 'Instagram', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    'elevenlabs' => ['label' => 'ElevenLabs', 'icon' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z'],
                    'securitate' => ['label' => 'Securitate', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                    'tenanti' => ['label' => 'Tenanți', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    'planuri' => ['label' => 'Planuri', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                    'mentenanta' => ['label' => 'Mentenanță', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0'],
                ];
            @endphp
            @foreach($tabs as $key => $info)
                <a href="{{ url('/admin/setari?tab=' . $key) }}"
                   class="whitespace-nowrap flex items-center gap-1.5 border-b-2 px-4 py-3 text-sm font-medium transition-colors
                          {{ $tab === $key
                              ? 'border-red-600 text-red-600'
                              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $info['icon'] }}" />
                    </svg>
                    {{ $info['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- ============================================================ --}}
    {{-- TAB: General --}}
    {{-- ============================================================ --}}
    @if($tab === 'general')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Setări Generale</h2>
            <p class="mt-1 text-sm text-slate-500">Configurarea de bază a platformei.</p>

            <form method="POST" action="{{ url('/admin/setari/general') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Nume Platformă --}}
                    <div>
                        <label for="platform_name" class="block text-sm font-medium text-slate-700">Nume Platformă</label>
                        <input type="text" name="platform_name" id="platform_name"
                               value="{{ old('platform_name', $settings['general']['platform_name'] ?? 'Sambla') }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- URL Platformă --}}
                    <div>
                        <label for="platform_url" class="block text-sm font-medium text-slate-700">URL Platformă</label>
                        <input type="url" name="platform_url" id="platform_url"
                               value="{{ old('platform_url', $settings['general']['platform_url'] ?? '') }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Email Suport --}}
                    <div>
                        <label for="support_email" class="block text-sm font-medium text-slate-700">Email Suport</label>
                        <input type="email" name="support_email" id="support_email"
                               value="{{ old('support_email', $settings['general']['support_email'] ?? '') }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Fus orar implicit --}}
                    <div>
                        <label for="default_timezone" class="block text-sm font-medium text-slate-700">Fus Orar Implicit</label>
                        <select name="default_timezone" id="default_timezone"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php
                                $tzOptions = ['Europe/Bucharest', 'Europe/London', 'Europe/Berlin', 'Europe/Paris', 'America/New_York', 'America/Los_Angeles', 'UTC'];
                                $currentTz = old('default_timezone', $settings['general']['default_timezone'] ?? 'Europe/Bucharest');
                            @endphp
                            @foreach($tzOptions as $tz)
                                <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Limba implicită --}}
                    <div>
                        <label for="default_language" class="block text-sm font-medium text-slate-700">Limba Implicită</label>
                        <select name="default_language" id="default_language"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentLang = old('default_language', $settings['general']['default_language'] ?? 'ro'); @endphp
                            <option value="ro" {{ $currentLang === 'ro' ? 'selected' : '' }}>Română</option>
                            <option value="en" {{ $currentLang === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>
                </div>

                {{-- Toggle switches --}}
                <div class="space-y-4 pt-2">
                    <label class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-slate-900">Înregistrare publică</span>
                            <p class="text-sm text-slate-500 mt-0.5">Permite utilizatorilor noi să se înregistreze.</p>
                        </div>
                        <input type="checkbox" name="registration_enabled" value="1"
                               {{ ($settings['general']['registration_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500/20">
                    </label>

                    <label class="flex items-center justify-between p-4 rounded-lg border border-amber-200 bg-amber-50 hover:bg-amber-100 transition-colors cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-amber-900">Mod Mentenanță</span>
                            <p class="text-sm text-amber-700 mt-0.5">Activează modul de mentenanță — utilizatorii vor vedea o pagină de indisponibilitate.</p>
                        </div>
                        <input type="checkbox" name="maintenance_mode" value="1"
                               {{ ($settings['general']['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500/20">
                    </label>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: OpenAI --}}
    {{-- ============================================================ --}}
    @if($tab === 'openai')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare OpenAI</h2>
            <p class="mt-1 text-sm text-slate-500">Cheile API și parametrii pentru OpenAI Realtime API.</p>

            <form method="POST" action="{{ url('/admin/setari/openai') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- API Key --}}
                    <div class="sm:col-span-2">
                        <label for="openai_api_key" class="block text-sm font-medium text-slate-700">API Key</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="openai_api_key" id="openai_api_key"
                                   value="{{ old('openai_api_key', $settings['openai']['openai_api_key'] ?? '') }}"
                                   placeholder="sk-..."
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                                   required>
                            <button type="button" onclick="togglePassword('openai_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Cheia ta de API de la OpenAI. Începe cu "sk-".</p>
                    </div>

                    {{-- Organization --}}
                    <div>
                        <label for="openai_organization" class="block text-sm font-medium text-slate-700">Organizație (opțional)</label>
                        <input type="text" name="openai_organization" id="openai_organization"
                               value="{{ old('openai_organization', $settings['openai']['openai_organization'] ?? '') }}"
                               placeholder="org-..."
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                    </div>

                    {{-- Model --}}
                    <div>
                        <label for="openai_realtime_model" class="block text-sm font-medium text-slate-700">Model Realtime</label>
                        <select name="openai_realtime_model" id="openai_realtime_model"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentModel = old('openai_realtime_model', $settings['openai']['openai_realtime_model'] ?? 'gpt-4o-realtime-preview'); @endphp
                            <option value="gpt-4o-realtime-preview" {{ $currentModel === 'gpt-4o-realtime-preview' ? 'selected' : '' }}>gpt-4o-realtime-preview</option>
                            <option value="gpt-4o-realtime-preview-2024-12-17" {{ $currentModel === 'gpt-4o-realtime-preview-2024-12-17' ? 'selected' : '' }}>gpt-4o-realtime-preview-2024-12-17</option>
                            <option value="gpt-4o-mini-realtime-preview" {{ $currentModel === 'gpt-4o-mini-realtime-preview' ? 'selected' : '' }}>gpt-4o-mini-realtime-preview</option>
                        </select>
                    </div>

                    {{-- Max Tokens --}}
                    <div>
                        <label for="openai_max_tokens" class="block text-sm font-medium text-slate-700">Max Tokens</label>
                        <input type="number" name="openai_max_tokens" id="openai_max_tokens"
                               value="{{ old('openai_max_tokens', $settings['openai']['openai_max_tokens'] ?? '4096') }}"
                               min="256" max="32768"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Temperature --}}
                    <div>
                        <label for="openai_temperature" class="block text-sm font-medium text-slate-700">Temperature</label>
                        <input type="number" name="openai_temperature" id="openai_temperature"
                               value="{{ old('openai_temperature', $settings['openai']['openai_temperature'] ?? '0.7') }}"
                               min="0" max="2" step="0.1"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                        <p class="mt-1 text-xs text-slate-500">0 = deterministic, 2 = creativ maxim</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările OpenAI
                    </button>
                </div>
            </form>
        </div>

        {{-- Anthropic (Claude) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 mt-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare Anthropic (Claude)</h2>
            <p class="mt-1 text-sm text-slate-500">API key pentru Claude — folosit ca smart tier și fallback. Opțional dar recomandat.</p>

            <form method="POST" action="{{ url('/admin/setari/anthropic') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="anthropic_api_key" class="block text-sm font-medium text-slate-700">Anthropic API Key</label>
                    <div class="relative mt-1.5">
                        <input type="password" name="anthropic_api_key" id="anthropic_api_key"
                               value="{{ old('anthropic_api_key', $settings['anthropic']['anthropic_api_key'] ?? '') }}"
                               placeholder="sk-ant-..."
                               class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                        <button type="button" onclick="togglePassword('anthropic_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">De la <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-blue-600 hover:underline">console.anthropic.com</a>. Începe cu "sk-ant-".</p>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors">
                        Salvează Anthropic
                    </button>
                </div>
            </form>
        </div>

        {{-- Sentry --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 mt-6">
            <h2 class="text-lg font-semibold text-slate-900">Sentry — Error Tracking</h2>
            <p class="mt-1 text-sm text-slate-500">DSN pentru monitorizare erori în producție. Opțional.</p>

            <form method="POST" action="{{ url('/admin/setari/sentry') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="sentry_dsn" class="block text-sm font-medium text-slate-700">Sentry DSN</label>
                    <input type="text" name="sentry_dsn" id="sentry_dsn"
                           value="{{ old('sentry_dsn', $settings['sentry']['sentry_dsn'] ?? '') }}"
                           placeholder="https://xxx@xxx.ingest.sentry.io/xxx"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors">
                        Salvează Sentry
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Twilio --}}
    {{-- ============================================================ --}}
    @if($tab === 'twilio')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare Twilio</h2>
            <p class="mt-1 text-sm text-slate-500">Credențiale și configurări pentru serviciul de telefonie Twilio.</p>

            <form method="POST" action="{{ url('/admin/setari/twilio') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Account SID --}}
                    <div>
                        <label for="twilio_sid" class="block text-sm font-medium text-slate-700">Account SID</label>
                        <input type="text" name="twilio_sid" id="twilio_sid"
                               value="{{ old('twilio_sid', $settings['twilio']['twilio_sid'] ?? '') }}"
                               placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors font-mono text-xs"
                               required>
                    </div>

                    {{-- Auth Token --}}
                    <div>
                        <label for="twilio_auth_token" class="block text-sm font-medium text-slate-700">Auth Token</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="twilio_auth_token" id="twilio_auth_token"
                                   value="{{ old('twilio_auth_token', $settings['twilio']['twilio_auth_token'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors font-mono text-xs"
                                   required>
                            <button type="button" onclick="togglePassword('twilio_auth_token')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Phone Number --}}
                    <div>
                        <label for="twilio_phone_number" class="block text-sm font-medium text-slate-700">Număr Telefon Principal</label>
                        <input type="text" name="twilio_phone_number" id="twilio_phone_number"
                               value="{{ old('twilio_phone_number', $settings['twilio']['twilio_phone_number'] ?? '') }}"
                               placeholder="+40XXXXXXXXX"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Webhook URL --}}
                    <div>
                        <label for="twilio_webhook_url" class="block text-sm font-medium text-slate-700">Webhook URL</label>
                        <input type="url" name="twilio_webhook_url" id="twilio_webhook_url"
                               value="{{ old('twilio_webhook_url', $settings['twilio']['twilio_webhook_url'] ?? '') }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările Twilio
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: ElevenLabs --}}
    {{-- ============================================================ --}}
    @if($tab === 'elevenlabs')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare ElevenLabs</h2>
            <p class="mt-1 text-sm text-slate-500">Setari pentru clonarea vocii si sinteza vocala cu ElevenLabs.</p>

            <form method="POST" action="{{ url('/admin/setari/elevenlabs') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- API Key --}}
                    <div class="sm:col-span-2">
                        <label for="elevenlabs_api_key" class="block text-sm font-medium text-slate-700">API Key</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="elevenlabs_api_key" id="elevenlabs_api_key"
                                   value="{{ old('elevenlabs_api_key', $settings['elevenlabs']['elevenlabs_api_key'] ?? '') }}"
                                   placeholder="xi-..."
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                                   required>
                            <button type="button" onclick="togglePassword('elevenlabs_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Cheia API de la ElevenLabs. O gasesti in Settings > API Keys.</p>
                    </div>

                    {{-- Model --}}
                    <div>
                        <label for="elevenlabs_model_id" class="block text-sm font-medium text-slate-700">Model TTS</label>
                        <select name="elevenlabs_model_id" id="elevenlabs_model_id"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentModel = old('elevenlabs_model_id', $settings['elevenlabs']['elevenlabs_model_id'] ?? 'eleven_multilingual_v2'); @endphp
                            <option value="eleven_multilingual_v2" {{ $currentModel === 'eleven_multilingual_v2' ? 'selected' : '' }}>Multilingual v2 (recomandat)</option>
                            <option value="eleven_turbo_v2_5" {{ $currentModel === 'eleven_turbo_v2_5' ? 'selected' : '' }}>Turbo v2.5 (latenta mica)</option>
                            <option value="eleven_monolingual_v1" {{ $currentModel === 'eleven_monolingual_v1' ? 'selected' : '' }}>Monolingual v1</option>
                        </select>
                    </div>

                    {{-- Stability --}}
                    <div>
                        <label for="elevenlabs_stability" class="block text-sm font-medium text-slate-700">Stabilitate</label>
                        <input type="number" name="elevenlabs_stability" id="elevenlabs_stability"
                               value="{{ old('elevenlabs_stability', $settings['elevenlabs']['elevenlabs_stability'] ?? '0.5') }}"
                               min="0" max="1" step="0.05"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                        <p class="mt-1 text-xs text-slate-500">0 = mai variata, 1 = mai consistenta</p>
                    </div>

                    {{-- Similarity Boost --}}
                    <div>
                        <label for="elevenlabs_similarity_boost" class="block text-sm font-medium text-slate-700">Similarity Boost</label>
                        <input type="number" name="elevenlabs_similarity_boost" id="elevenlabs_similarity_boost"
                               value="{{ old('elevenlabs_similarity_boost', $settings['elevenlabs']['elevenlabs_similarity_boost'] ?? '0.75') }}"
                               min="0" max="1" step="0.05"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                        <p class="mt-1 text-xs text-slate-500">0 = mai generic, 1 = mai asemanator cu vocea clonata</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salveaza setarile ElevenLabs
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Stripe --}}
    {{-- ============================================================ --}}
    @if($tab === 'stripe')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare Stripe</h2>
            <p class="mt-1 text-sm text-slate-500">Chei API și configurări pentru plățile Stripe.</p>

            <form method="POST" action="{{ url('/admin/setari/stripe') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Public Key --}}
                    <div class="sm:col-span-2">
                        <label for="stripe_public_key" class="block text-sm font-medium text-slate-700">Cheie Publică (Publishable Key)</label>
                        <input type="text" name="stripe_public_key" id="stripe_public_key"
                               value="{{ old('stripe_public_key', $settings['stripe']['stripe_public_key'] ?? '') }}"
                               placeholder="pk_live_..."
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors font-mono text-xs"
                               required>
                    </div>

                    {{-- Secret Key --}}
                    <div class="sm:col-span-2">
                        <label for="stripe_secret_key" class="block text-sm font-medium text-slate-700">Cheie Secretă (Secret Key)</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                                   value="{{ old('stripe_secret_key', $settings['stripe']['stripe_secret_key'] ?? '') }}"
                                   placeholder="sk_live_..."
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors font-mono text-xs"
                                   required>
                            <button type="button" onclick="togglePassword('stripe_secret_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Webhook Secret --}}
                    <div>
                        <label for="stripe_webhook_secret" class="block text-sm font-medium text-slate-700">Webhook Secret</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="stripe_webhook_secret" id="stripe_webhook_secret"
                                   value="{{ old('stripe_webhook_secret', $settings['stripe']['stripe_webhook_secret'] ?? '') }}"
                                   placeholder="whsec_..."
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors font-mono text-xs"
                                   required>
                            <button type="button" onclick="togglePassword('stripe_webhook_secret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label for="stripe_currency" class="block text-sm font-medium text-slate-700">Monedă</label>
                        <select name="stripe_currency" id="stripe_currency"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentCurrency = old('stripe_currency', $settings['stripe']['stripe_currency'] ?? 'eur'); @endphp
                            <option value="eur" {{ $currentCurrency === 'eur' ? 'selected' : '' }}>EUR (Euro)</option>
                            <option value="usd" {{ $currentCurrency === 'usd' ? 'selected' : '' }}>USD (Dollar)</option>
                            <option value="ron" {{ $currentCurrency === 'ron' ? 'selected' : '' }}>RON (Leu)</option>
                            <option value="gbp" {{ $currentCurrency === 'gbp' ? 'selected' : '' }}>GBP (Liră)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările Stripe
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Email --}}
    {{-- ============================================================ --}}
    @if($tab === 'email')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare Email / SMTP</h2>
            <p class="mt-1 text-sm text-slate-500">Setări pentru trimiterea emailurilor din platformă.</p>

            <form method="POST" action="{{ url('/admin/setari/email') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Mailer --}}
                    <div>
                        <label for="mail_mailer" class="block text-sm font-medium text-slate-700">Mailer</label>
                        <select name="mail_mailer" id="mail_mailer"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentMailer = old('mail_mailer', $settings['email']['mail_mailer'] ?? 'smtp'); @endphp
                            @foreach(['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'mailgun' => 'Mailgun', 'ses' => 'Amazon SES', 'postmark' => 'Postmark', 'resend' => 'Resend', 'log' => 'Log (test)'] as $val => $lbl)
                                <option value="{{ $val }}" {{ $currentMailer === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Encryption --}}
                    <div>
                        <label for="mail_encryption" class="block text-sm font-medium text-slate-700">Criptare</label>
                        <select name="mail_encryption" id="mail_encryption"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentEnc = old('mail_encryption', $settings['email']['mail_encryption'] ?? 'tls'); @endphp
                            <option value="tls" {{ $currentEnc === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ $currentEnc === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="none" {{ $currentEnc === 'none' ? 'selected' : '' }}>Fără criptare</option>
                        </select>
                    </div>

                    {{-- Host --}}
                    <div>
                        <label for="mail_host" class="block text-sm font-medium text-slate-700">SMTP Host</label>
                        <input type="text" name="mail_host" id="mail_host"
                               value="{{ old('mail_host', $settings['email']['mail_host'] ?? '') }}"
                               placeholder="smtp.gmail.com"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                    </div>

                    {{-- Port --}}
                    <div>
                        <label for="mail_port" class="block text-sm font-medium text-slate-700">Port</label>
                        <input type="number" name="mail_port" id="mail_port"
                               value="{{ old('mail_port', $settings['email']['mail_port'] ?? '587') }}"
                               min="1" max="65535"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Username --}}
                    <div>
                        <label for="mail_username" class="block text-sm font-medium text-slate-700">Username</label>
                        <input type="text" name="mail_username" id="mail_username"
                               value="{{ old('mail_username', $settings['email']['mail_username'] ?? '') }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="mail_password" class="block text-sm font-medium text-slate-700">Password</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="mail_password" id="mail_password"
                                   value="{{ old('mail_password', $settings['email']['mail_password'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            <button type="button" onclick="togglePassword('mail_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- From Address --}}
                    <div>
                        <label for="mail_from_address" class="block text-sm font-medium text-slate-700">Adresă expeditor (From)</label>
                        <input type="email" name="mail_from_address" id="mail_from_address"
                               value="{{ old('mail_from_address', $settings['email']['mail_from_address'] ?? '') }}"
                               placeholder="noreply@sambla.ro"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- From Name --}}
                    <div>
                        <label for="mail_from_name" class="block text-sm font-medium text-slate-700">Nume expeditor</label>
                        <input type="text" name="mail_from_name" id="mail_from_name"
                               value="{{ old('mail_from_name', $settings['email']['mail_from_name'] ?? '') }}"
                               placeholder="Sambla"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările Email
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: WhatsApp --}}
    {{-- ============================================================ --}}
    @if($tab === 'whatsapp')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare WhatsApp</h2>
            <p class="mt-1 text-sm text-slate-500">Setări pentru integrarea cu WhatsApp Business API.</p>

            <form method="POST" action="{{ url('/admin/setari/whatsapp') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Provider --}}
                    <div>
                        <label for="whatsapp_provider" class="block text-sm font-medium text-slate-700">Provider</label>
                        <select name="whatsapp_provider" id="whatsapp_provider"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            @php $currentProvider = old('whatsapp_provider', $settings['whatsapp']['whatsapp_provider'] ?? 'meta_cloud_api'); @endphp
                            <option value="twilio_whatsapp" {{ $currentProvider === 'twilio_whatsapp' ? 'selected' : '' }}>Twilio WhatsApp</option>
                            <option value="meta_cloud_api" {{ $currentProvider === 'meta_cloud_api' ? 'selected' : '' }}>Meta Cloud API</option>
                        </select>
                    </div>

                    {{-- API Key --}}
                    <div>
                        <label for="whatsapp_api_key" class="block text-sm font-medium text-slate-700">API Key</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="whatsapp_api_key" id="whatsapp_api_key"
                                   value="{{ old('whatsapp_api_key', $settings['whatsapp']['whatsapp_api_key'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            <button type="button" onclick="togglePassword('whatsapp_api_key')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Phone Number ID --}}
                    <div>
                        <label for="whatsapp_phone_number_id" class="block text-sm font-medium text-slate-700">Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_number_id" id="whatsapp_phone_number_id"
                               value="{{ old('whatsapp_phone_number_id', $settings['whatsapp']['whatsapp_phone_number_id'] ?? '') }}"
                               placeholder="123456789012345"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                    </div>

                    {{-- Business Account ID --}}
                    <div>
                        <label for="whatsapp_business_account_id" class="block text-sm font-medium text-slate-700">Business Account ID</label>
                        <input type="text" name="whatsapp_business_account_id" id="whatsapp_business_account_id"
                               value="{{ old('whatsapp_business_account_id', $settings['whatsapp']['whatsapp_business_account_id'] ?? '') }}"
                               placeholder="123456789012345"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                    </div>

                    {{-- Verify Token --}}
                    <div>
                        <label for="whatsapp_verify_token" class="block text-sm font-medium text-slate-700">Verify Token</label>
                        <input type="text" name="whatsapp_verify_token" id="whatsapp_verify_token"
                               value="{{ old('whatsapp_verify_token', $settings['whatsapp']['whatsapp_verify_token'] ?? '') }}"
                               placeholder="Token pentru verificare webhook"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">Token folosit pentru verificarea webhook-ului de către Meta/Twilio.</p>
                    </div>

                    {{-- Webhook URL (readonly) --}}
                    <div>
                        <label for="whatsapp_webhook_url" class="block text-sm font-medium text-slate-700">Webhook URL</label>
                        <input type="text" id="whatsapp_webhook_url"
                               value="https://sambla.ro/webhook/whatsapp"
                               readonly
                               class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-500 shadow-sm cursor-not-allowed focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">URL-ul webhook-ului (auto-generat, nu poate fi modificat).</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările WhatsApp
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Facebook --}}
    {{-- ============================================================ --}}
    @if($tab === 'facebook')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare Facebook</h2>
            <p class="mt-1 text-sm text-slate-500">Setări pentru integrarea cu Facebook Messenger.</p>

            <form method="POST" action="{{ url('/admin/setari/facebook') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- App ID --}}
                    <div>
                        <label for="facebook_app_id" class="block text-sm font-medium text-slate-700">App ID</label>
                        <input type="text" name="facebook_app_id" id="facebook_app_id"
                               value="{{ old('facebook_app_id', $settings['facebook']['facebook_app_id'] ?? '') }}"
                               placeholder="123456789012345"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                    </div>

                    {{-- App Secret --}}
                    <div>
                        <label for="facebook_app_secret" class="block text-sm font-medium text-slate-700">App Secret</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="facebook_app_secret" id="facebook_app_secret"
                                   value="{{ old('facebook_app_secret', $settings['facebook']['facebook_app_secret'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            <button type="button" onclick="togglePassword('facebook_app_secret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Page Access Token --}}
                    <div class="sm:col-span-2">
                        <label for="facebook_page_access_token" class="block text-sm font-medium text-slate-700">Page Access Token</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="facebook_page_access_token" id="facebook_page_access_token"
                                   value="{{ old('facebook_page_access_token', $settings['facebook']['facebook_page_access_token'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            <button type="button" onclick="togglePassword('facebook_page_access_token')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Token-ul de acces pentru pagina Facebook conectată.</p>
                    </div>

                    {{-- Verify Token --}}
                    <div>
                        <label for="facebook_verify_token" class="block text-sm font-medium text-slate-700">Verify Token</label>
                        <input type="text" name="facebook_verify_token" id="facebook_verify_token"
                               value="{{ old('facebook_verify_token', $settings['facebook']['facebook_verify_token'] ?? '') }}"
                               placeholder="Token pentru verificare webhook"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">Token folosit pentru verificarea webhook-ului de către Facebook.</p>
                    </div>

                    {{-- Webhook URL (readonly) --}}
                    <div>
                        <label for="facebook_webhook_url" class="block text-sm font-medium text-slate-700">Webhook URL</label>
                        <input type="text" id="facebook_webhook_url"
                               value="https://sambla.ro/webhook/facebook"
                               readonly
                               class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-500 shadow-sm cursor-not-allowed focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">URL-ul webhook-ului (auto-generat, nu poate fi modificat).</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările Facebook
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Instagram --}}
    {{-- ============================================================ --}}
    @if($tab === 'instagram')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurare Instagram</h2>
            <p class="mt-1 text-sm text-slate-500">Setări pentru integrarea cu Instagram Messaging API.</p>

            <form method="POST" action="{{ url('/admin/setari/instagram') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- App ID --}}
                    <div>
                        <label for="instagram_app_id" class="block text-sm font-medium text-slate-700">App ID</label>
                        <input type="text" name="instagram_app_id" id="instagram_app_id"
                               value="{{ old('instagram_app_id', $settings['instagram']['instagram_app_id'] ?? '') }}"
                               placeholder="123456789012345"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">De obicei același App ID ca la Facebook.</p>
                    </div>

                    {{-- App Secret --}}
                    <div>
                        <label for="instagram_app_secret" class="block text-sm font-medium text-slate-700">App Secret</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="instagram_app_secret" id="instagram_app_secret"
                                   value="{{ old('instagram_app_secret', $settings['instagram']['instagram_app_secret'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            <button type="button" onclick="togglePassword('instagram_app_secret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Access Token --}}
                    <div class="sm:col-span-2">
                        <label for="instagram_access_token" class="block text-sm font-medium text-slate-700">Access Token</label>
                        <div class="relative mt-1.5">
                            <input type="password" name="instagram_access_token" id="instagram_access_token"
                                   value="{{ old('instagram_access_token', $settings['instagram']['instagram_access_token'] ?? '') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                            <button type="button" onclick="togglePassword('instagram_access_token')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Token-ul de acces pentru Instagram API.</p>
                    </div>

                    {{-- Verify Token --}}
                    <div>
                        <label for="instagram_verify_token" class="block text-sm font-medium text-slate-700">Verify Token</label>
                        <input type="text" name="instagram_verify_token" id="instagram_verify_token"
                               value="{{ old('instagram_verify_token', $settings['instagram']['instagram_verify_token'] ?? '') }}"
                               placeholder="Token pentru verificare webhook"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">Token folosit pentru verificarea webhook-ului de către Instagram.</p>
                    </div>

                    {{-- Webhook URL (readonly) --}}
                    <div>
                        <label for="instagram_webhook_url" class="block text-sm font-medium text-slate-700">Webhook URL</label>
                        <input type="text" id="instagram_webhook_url"
                               value="https://sambla.ro/webhook/instagram"
                               readonly
                               class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-500 shadow-sm cursor-not-allowed focus:outline-none transition-colors">
                        <p class="mt-1 text-xs text-slate-500">URL-ul webhook-ului (auto-generat, nu poate fi modificat).</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările Instagram
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Securitate --}}
    {{-- ============================================================ --}}
    @if($tab === 'securitate')
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Setări Securitate</h2>
            <p class="mt-1 text-sm text-slate-500">Parametri de securitate și autentificare.</p>

            <form method="POST" action="{{ url('/admin/setari/securitate') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Bcrypt Rounds --}}
                    <div>
                        <label for="bcrypt_rounds" class="block text-sm font-medium text-slate-700">Bcrypt Rounds</label>
                        <input type="number" name="bcrypt_rounds" id="bcrypt_rounds"
                               value="{{ old('bcrypt_rounds', $settings['security']['bcrypt_rounds'] ?? '12') }}"
                               min="4" max="31"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                        <p class="mt-1 text-xs text-slate-500">Recomandat: 10-12</p>
                    </div>

                    {{-- Session Lifetime --}}
                    <div>
                        <label for="session_lifetime" class="block text-sm font-medium text-slate-700">Durata Sesiune (minute)</label>
                        <input type="number" name="session_lifetime" id="session_lifetime"
                               value="{{ old('session_lifetime', $settings['security']['session_lifetime'] ?? '120') }}"
                               min="5" max="1440"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- API Rate Limit --}}
                    <div>
                        <label for="api_rate_limit" class="block text-sm font-medium text-slate-700">Limită API (req/min)</label>
                        <input type="number" name="api_rate_limit" id="api_rate_limit"
                               value="{{ old('api_rate_limit', $settings['security']['api_rate_limit'] ?? '60') }}"
                               min="10" max="1000"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Max Login Attempts --}}
                    <div>
                        <label for="max_login_attempts" class="block text-sm font-medium text-slate-700">Max încercări login</label>
                        <input type="number" name="max_login_attempts" id="max_login_attempts"
                               value="{{ old('max_login_attempts', $settings['security']['max_login_attempts'] ?? '5') }}"
                               min="3" max="20"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>

                    {{-- Password Min Length --}}
                    <div>
                        <label for="password_min_length" class="block text-sm font-medium text-slate-700">Lungime min. parolă</label>
                        <input type="number" name="password_min_length" id="password_min_length"
                               value="{{ old('password_min_length', $settings['security']['password_min_length'] ?? '8') }}"
                               min="6" max="32"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                        Salvează setările de securitate
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Tenanți --}}
    {{-- ============================================================ --}}
    @if($tab === 'tenanti')
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Gestionare Tenanți</h2>
                <p class="mt-1 text-sm text-slate-500">Toate organizațiile înregistrate pe platformă.</p>
            </div>

            @if(isset($extra['tenants']) && $extra['tenants']->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Organizație</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Utilizatori</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Boți</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Apeluri</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($extra['tenants'] as $t)
                                @php
                                    $isSuspended = ($t->settings['suspended'] ?? false);
                                    $planColors = [
                                        'starter' => 'bg-slate-100 text-slate-700',
                                        'professional' => 'bg-red-100 text-red-800',
                                        'enterprise' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <tr class="{{ $isSuspended ? 'bg-red-50/50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $t->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $t->slug }} &middot; Creat {{ $t->created_at->format('d.m.Y') }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" action="{{ url('/admin/setari/tenanti/' . $t->id) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $t->name }}">
                                            <select name="plan" onchange="this.form.submit()"
                                                    class="rounded-md border-slate-300 text-xs py-1 pr-7 focus:border-red-500 focus:ring-red-500/20">
                                                <option value="starter" {{ $t->plan === 'starter' ? 'selected' : '' }}>Starter</option>
                                                <option value="professional" {{ $t->plan === 'professional' ? 'selected' : '' }}>Pro</option>
                                                <option value="enterprise" {{ $t->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-slate-700">{{ $t->users_count }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-slate-700">{{ $t->bots_count }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-slate-700">{{ $t->calls_count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if($isSuspended)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Suspendat</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Activ</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ url('/admin/setari/tenanti/' . $t->id . '/toggle') }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors
                                                           {{ $isSuspended
                                                               ? 'border-emerald-200 text-emerald-600 hover:bg-emerald-50'
                                                               : 'border-red-200 text-red-600 hover:bg-red-50' }}"
                                                    onclick="return confirm('{{ $isSuspended ? 'Reactivezi' : 'Suspendezi' }} tenantul {{ $t->name }}?')">
                                                {{ $isSuspended ? 'Reactivează' : 'Suspendează' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-slate-500">Nu există tenanți înregistrați.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Planuri --}}
    {{-- ============================================================ --}}
    @if($tab === 'planuri')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach(($extra['plans'] ?? []) as $planKey => $plan)
                @php
                    $borderColors = ['starter' => 'border-slate-200', 'professional' => 'border-red-200', 'enterprise' => 'border-red-200'];
                    $headerColors = ['starter' => 'from-slate-50 to-slate-100', 'professional' => 'from-red-50 to-red-100', 'enterprise' => 'from-red-50 to-red-100'];
                    $textColors = ['starter' => 'text-slate-700', 'professional' => 'text-red-800', 'enterprise' => 'text-red-800'];
                @endphp
                <div class="bg-white rounded-xl border-2 {{ $borderColors[$planKey] ?? 'border-slate-200' }} overflow-hidden">
                    <div class="bg-gradient-to-br {{ $headerColors[$planKey] ?? '' }} px-6 py-5">
                        <h3 class="text-lg font-bold {{ $textColors[$planKey] ?? '' }}">{{ $plan['name'] }}</h3>
                        <div class="mt-2">
                            @if($plan['price_monthly'] > 0)
                                <span class="text-3xl font-bold {{ $textColors[$planKey] ?? '' }}">{{ $plan['price_monthly'] }}€</span>
                                <span class="text-sm text-slate-500">/lună</span>
                            @else
                                <span class="text-3xl font-bold {{ $textColors[$planKey] ?? '' }}">Custom</span>
                            @endif
                        </div>
                    </div>
                    <div class="px-6 py-5 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Minute/lună:</span>
                            <span class="font-medium text-slate-900">{{ $plan['minutes'] >= 999999 ? 'Nelimitate' : number_format($plan['minutes']) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Boți:</span>
                            <span class="font-medium text-slate-900">{{ $plan['bots'] >= 999 ? 'Nelimitați' : $plan['bots'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Canale:</span>
                            <span class="font-medium text-slate-900">{{ $plan['channels'] >= 999 ? 'Nelimitate' : $plan['channels'] }}</span>
                        </div>
                        @if($plan['overage_per_minute'] > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Cost depășire/min:</span>
                            <span class="font-medium text-slate-900">{{ $plan['overage_per_minute'] }}€</span>
                        </div>
                        @endif
                        @if($plan['price_yearly'] > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Preț anual/lună:</span>
                            <span class="font-medium text-emerald-600">{{ $plan['price_yearly'] }}€</span>
                        </div>
                        @endif

                        <div class="border-t border-slate-100 pt-3">
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Funcționalități</p>
                            <ul class="space-y-1.5">
                                @foreach($plan['features'] as $feature)
                                    <li class="flex items-center gap-2 text-sm text-slate-600">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-600">
                Planurile sunt configurate în <code class="text-xs bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono">config/plans.php</code>.
                Pentru a modifica planurile, editează fișierul de configurare și redeploy.
            </p>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Mentenanță --}}
    {{-- ============================================================ --}}
    @if($tab === 'mentenanta')
        {{-- System Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Informații Sistem</h2>
            <p class="mt-1 text-sm text-slate-500">Starea curentă a serverului și aplicației.</p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach(($extra['systemInfo'] ?? []) as $label => $value)
                    @php
                        $labelMap = [
                            'php_version' => 'PHP',
                            'laravel_version' => 'Laravel',
                            'server_os' => 'Sistem Operare',
                            'database' => 'Bază de Date',
                            'cache_driver' => 'Cache Driver',
                            'queue_driver' => 'Queue Driver',
                            'session_driver' => 'Session Driver',
                            'disk_free' => 'Spațiu Liber Disk',
                            'disk_total' => 'Spațiu Total Disk',
                            'memory_usage' => 'Memorie Utilizată',
                            'uptime' => 'Uptime Server',
                            'total_tenants' => 'Total Tenanți',
                            'total_users' => 'Total Utilizatori',
                            'total_bots' => 'Total Boți',
                            'total_calls' => 'Total Apeluri',
                        ];
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <span class="text-sm text-slate-500">{{ $labelMap[$label] ?? $label }}</span>
                        <span class="text-sm font-medium text-slate-900">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Acțiuni Mentenanță</h2>
            <p class="mt-1 text-sm text-slate-500">Operațiuni de întreținere a platformei.</p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Clear Cache --}}
                <form method="POST" action="{{ url('/admin/setari/clear-cache') }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Ștergi tot cache-ul aplicației?')"
                            class="w-full flex items-center gap-4 p-4 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-left">
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-red-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Șterge Cache</p>
                            <p class="text-xs text-slate-500">Șterge cache-ul aplicației, config, views și routes.</p>
                        </div>
                    </button>
                </form>

                {{-- App Info --}}
                <div class="flex items-center gap-4 p-4 rounded-lg border border-slate-200">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900">Status Aplicație</p>
                        <p class="text-xs text-emerald-600 font-medium">Funcțională</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
@endsection
