@extends('layouts.app')

@section('title', 'Sambla — Agenți AI Multi-Canal pentru Afacerea Ta')
@section('meta_description', 'Automatizează comunicarea pe telefon, WhatsApp, Facebook, Instagram și chatbot web cu inteligență artificială. O singură bază de cunoștințe, toate canalele.')

@section('content')

{{-- ============================================================== --}}
{{-- SECTION 1: HERO --}}
{{-- ============================================================== --}}
<section class="relative overflow-hidden bg-gradient-to-b from-white to-primary-50">
    <x-hero-texture />
    <div class="container-custom section-padding relative">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            {{-- Left column --}}
            <div class="animate-fade-in">
                <x-hero-ornament />
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight text-slate-900 mb-6">
                    Agenți AI care <span class="gradient-text">Transformă Fiecare Conversație</span> în Oportunitate
                </h1>
                <p class="text-lg sm:text-xl text-slate-600 mb-8 max-w-xl">
                    Automatizează comunicarea pe telefon, WhatsApp, Facebook, Instagram și chatbot web. O singură bază de cunoștințe, toate canalele.
                </p>
                <div class="flex flex-wrap gap-4 mb-10">
                    <a href="/register" class="btn-primary">Începe gratuit</a>
                    <a href="#demo" class="btn-secondary">Vezi demo</a>
                </div>
                {{-- Stat badges --}}
                <div class="flex flex-wrap gap-4">
                    <div class="inline-flex items-center gap-2 bg-white rounded-full px-4 py-2 shadow-sm border border-slate-100 text-sm font-medium text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        99.9% Uptime
                    </div>
                    <div class="inline-flex items-center gap-2 bg-white rounded-full px-4 py-2 shadow-sm border border-slate-100 text-sm font-medium text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                        &lt;200ms Latență
                    </div>
                    <div class="inline-flex items-center gap-2 bg-white rounded-full px-4 py-2 shadow-sm border border-slate-100 text-sm font-medium text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-red-700"></span>
                        24/7 Disponibil
                    </div>
                    <div class="inline-flex items-center gap-2 bg-white rounded-full px-4 py-2 shadow-sm border border-slate-100 text-sm font-medium text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        5 Canale
                    </div>
                </div>
            </div>

            {{-- Right column: SVG Illustration --}}
            <div class="animate-slide-up hidden lg:flex justify-center items-center">
                <svg viewBox="0 0 500 500" class="w-full max-w-md" xmlns="http://www.w3.org/2000/svg">
                    {{-- Background circles --}}
                    <circle cx="250" cy="250" r="200" fill="none" stroke="currentColor" stroke-width="1" class="text-primary-200" opacity="0.5"/>
                    <circle cx="250" cy="250" r="160" fill="none" stroke="currentColor" stroke-width="1" class="text-primary-300" opacity="0.4"/>
                    <circle cx="250" cy="250" r="120" fill="none" stroke="currentColor" stroke-width="1.5" class="text-primary-400" opacity="0.3"/>

                    {{-- Pulsing center circle --}}
                    <circle cx="250" cy="250" r="80" class="fill-primary-100"/>
                    <circle cx="250" cy="250" r="60" class="fill-primary-200"/>

                    {{-- Phone icon --}}
                    <rect x="220" y="210" width="60" height="80" rx="12" class="fill-primary-600"/>
                    <rect x="228" y="218" width="44" height="56" rx="4" fill="white" opacity="0.9"/>
                    <circle cx="250" cy="282" r="5" fill="white" opacity="0.7"/>

                    {{-- Sound waves (right side) --}}
                    <path d="M295 235 C310 235, 310 265, 295 265" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-primary-500"/>
                    <path d="M305 225 C325 225, 325 275, 305 275" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-primary-400"/>
                    <path d="M315 215 C340 215, 340 285, 315 285" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-primary-300"/>

                    {{-- AI sparkle dots --}}
                    <circle cx="170" cy="180" r="6" class="fill-primary-400" opacity="0.7"/>
                    <circle cx="340" cy="170" r="4" class="fill-primary-500" opacity="0.6"/>
                    <circle cx="150" cy="310" r="5" class="fill-primary-300" opacity="0.8"/>
                    <circle cx="360" cy="320" r="7" class="fill-primary-400" opacity="0.5"/>
                    <circle cx="190" cy="140" r="3" class="fill-primary-600" opacity="0.4"/>
                    <circle cx="330" cy="370" r="4" class="fill-primary-500" opacity="0.6"/>

                    {{-- Orbital dots --}}
                    <circle cx="250" cy="50" r="8" class="fill-primary-500" opacity="0.6"/>
                    <circle cx="450" cy="250" r="6" class="fill-primary-400" opacity="0.5"/>
                    <circle cx="250" cy="450" r="7" class="fill-primary-500" opacity="0.4"/>
                    <circle cx="50" cy="250" r="5" class="fill-primary-300" opacity="0.6"/>

                    {{-- Connecting lines --}}
                    <line x1="250" y1="58" x2="250" y2="170" stroke="currentColor" stroke-width="1" class="text-primary-200" stroke-dasharray="4 4"/>
                    <line x1="442" y1="250" x2="330" y2="250" stroke="currentColor" stroke-width="1" class="text-primary-200" stroke-dasharray="4 4"/>
                </svg>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 2: CLIENT LOGOS --}}
{{-- ============================================================== --}}
<x-motif-border />
<section class="bg-white border-b border-slate-100">
    <div class="container-custom py-12 lg:py-16">
        <p class="text-center text-sm font-semibold text-slate-400 uppercase tracking-wider mb-8">
            Folosit de companii inovatoare din România
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 items-center">
            @foreach(['TechRo', 'MediaPro', 'FinServ', 'AutoNet', 'SănătateDigitală', 'EduSmart'] as $logo)
                <div class="flex items-center justify-center px-6 py-4 rounded-lg border border-slate-100 bg-slate-50 grayscale opacity-60 hover:opacity-100 hover:grayscale-0 transition-all duration-300">
                    <span class="text-lg font-bold text-slate-500">{{ $logo }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 3: HOW IT WORKS --}}
{{-- ============================================================== --}}
<section class="bg-white">
    <div class="container-custom section-padding">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Cum funcționează?</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Trei pași simpli pentru a automatiza comunicarea pe toate canalele</p>
        </div>

        <div class="grid md:grid-cols-5 gap-8 items-start max-w-5xl mx-auto">
            {{-- Step 1 --}}
            <div class="md:col-span-1 text-center animate-fade-in-delay-1">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-primary-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Configurezi botul</h3>
                <p class="text-sm text-slate-600">Definești scenariile de conversație, tonul vocii și răspunsurile la întrebări frecvente.</p>
            </div>

            {{-- Arrow 1 --}}
            <div class="hidden md:flex md:col-span-1 items-center justify-center pt-8">
                <svg class="w-10 h-10 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </div>

            {{-- Step 2 --}}
            <div class="md:col-span-1 text-center animate-fade-in-delay-2">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-primary-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Îl conectezi la canale</h3>
                <p class="text-sm text-slate-600">Conectează-l la telefon, WhatsApp, Facebook, Instagram sau chatbot web — toate dintr-un singur loc.</p>
            </div>

            {{-- Arrow 2 --}}
            <div class="hidden md:flex md:col-span-1 items-center justify-center pt-8">
                <svg class="w-10 h-10 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </div>

            {{-- Step 3 --}}
            <div class="md:col-span-1 text-center animate-fade-in-delay-3">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-primary-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Agentul răspunde pe toate canalele</h3>
                <p class="text-sm text-slate-600">Agentul AI preia conversațiile pe orice canal, răspunde natural și transferă când e necesar.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 4: FEATURES --}}
{{-- ============================================================== --}}
<section class="bg-slate-50">
    <div class="container-custom section-padding">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Tot ce ai nevoie pentru comunicare inteligentă</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Funcționalități puternice, gata de producție</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Feature 1: Voce Naturală AI --}}
            <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Voce Naturală AI</h3>
                <p class="text-slate-600">Conversații fluente în limba română, cu intonație naturală și înțelegere contextuală avansată.</p>
            </div>

            {{-- Feature 2: Integrare Telefonie --}}
            <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Integrare Telefonie</h3>
                <p class="text-slate-600">Compatibil cu Twilio, numere românești și centrale PBX existente.</p>
            </div>

            {{-- Feature 3: Analiză în Timp Real --}}
            <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Analiză în Timp Real</h3>
                <p class="text-slate-600">Dashboard cu transcrieri live, analiză de sentiment și metrici de performanță.</p>
            </div>

            {{-- Feature 4: Multi-canal --}}
            <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Multi-canal (5 canale)</h3>
                <p class="text-slate-600">Telefon, WhatsApp, Facebook Messenger, Instagram DM și Web Chatbot — toate într-un singur loc, o singură bază de cunoștințe.</p>
            </div>

            {{-- Feature 5: Scalabil --}}
            <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Scalabil</h3>
                <p class="text-slate-600">De la 10 la 10.000 de apeluri simultane. Infrastructura crește cu afacerea ta.</p>
            </div>

            {{-- Feature 6: Securitate Enterprise --}}
            <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Securitate Enterprise</h3>
                <p class="text-slate-600">Date criptate, GDPR compliant, hosting în România disponibil.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 4B: CANALE SUPORTATE --}}
{{-- ============================================================== --}}
<section class="bg-white">
    <div class="container-custom section-padding">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Canale Suportate</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Un singur agent AI, o singură bază de cunoștințe, 5 canale de comunicare</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-6">
            {{-- Canal 1: Telefon --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-primary-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Telefon</h3>
                <p class="text-sm text-slate-600">Apeluri telefonice cu voce AI naturală, numere locale românești</p>
            </div>

            {{-- Canal 2: WhatsApp --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-green-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">WhatsApp</h3>
                <p class="text-sm text-slate-600">Răspunsuri instant pe WhatsApp Business, disponibil 24/7</p>
            </div>

            {{-- Canal 3: Facebook Messenger --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.145 2 11.243c0 2.906 1.453 5.497 3.727 7.193V22l3.454-1.896c.92.256 1.9.396 2.819.396 5.523 0 10-4.145 10-9.257C22 6.145 17.523 2 12 2zm1.002 12.463l-2.542-2.713-4.96 2.713 5.46-5.794 2.604 2.713 4.898-2.713-5.46 5.794z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Facebook Messenger</h3>
                <p class="text-sm text-slate-600">Conectează pagina ta Facebook și răspunde automat clienților</p>
            </div>

            {{-- Canal 4: Instagram DM --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-pink-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Instagram DM</h3>
                <p class="text-sm text-slate-600">Gestionează mesajele private Instagram cu AI</p>
            </div>

            {{-- Canal 5: Web Chatbot --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-primary-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Web Chatbot</h3>
                <p class="text-sm text-slate-600">Widget de chat pe site-ul tău, instalare în 2 minute</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 5: INTERACTIVE DEMO --}}
{{-- ============================================================== --}}
<section id="demo" class="bg-white">
    <div class="container-custom section-padding">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Testează Sambla în acțiune</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Vezi cum răspunde agentul nostru AI la întrebări reale</p>
        </div>

        <div class="max-w-lg mx-auto">
            {{-- Chat widget --}}
            <div class="rounded-2xl border border-slate-200 shadow-lg overflow-hidden bg-white">
                {{-- Chat header --}}
                <div class="bg-primary-600 px-6 py-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm">Sambla Asistent</p>
                        <p class="text-white/70 text-xs flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                            Online
                        </p>
                    </div>
                </div>

                {{-- Chat messages --}}
                <div id="chatMessages" class="h-80 overflow-y-auto p-4 space-y-4 bg-slate-50">
                    {{-- Bot greeting --}}
                    <div class="flex items-start gap-2">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex-shrink-0 flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <div class="bg-white rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%] shadow-sm border border-slate-100">
                            <p class="text-sm text-slate-700">Bună ziua! Sunt asistentul virtual Sambla. Cu ce vă pot ajuta?</p>
                        </div>
                    </div>
                </div>

                {{-- Chat input --}}
                <div class="border-t border-slate-200 p-4 bg-white">
                    <form id="chatForm" class="flex gap-2">
                        <input
                            type="text"
                            id="chatInput"
                            placeholder="Scrie un mesaj..."
                            class="flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            autocomplete="off"
                        />
                        <button
                            type="submit"
                            class="bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 transition-colors duration-200 flex items-center justify-center"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-4">
                Încearcă: „Cât costă?", „Ce funcționalități are?", „Cum se integrează?"
            </p>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 6: TESTIMONIALS --}}
{{-- ============================================================== --}}
<section class="bg-slate-50">
    <div class="container-custom section-padding">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Ce spun clienții noștri</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Feedback real de la companii care folosesc Sambla zilnic</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @php
                $testimonials = [
                    [
                        'name' => 'Maria Ionescu',
                        'role' => 'CEO @ TechSolutions',
                        'initials' => 'MI',
                        'color' => 'bg-primary-500',
                        'stars' => 5,
                        'quote' => 'Am redus timpul de răspuns cu 80%. Clienții sunt impresionați că primesc răspunsuri instant, chiar și la 3 dimineața.',
                    ],
                    [
                        'name' => 'Andrei Popescu',
                        'role' => 'Director Operațiuni @ FinanceHub',
                        'initials' => 'AP',
                        'color' => 'bg-red-700',
                        'stars' => 5,
                        'quote' => 'ROI-ul a fost vizibil din prima lună. Echipa de suport se concentrează acum pe cazurile complexe.',
                    ],
                    [
                        'name' => 'Elena Dumitrescu',
                        'role' => 'Manager Call Center @ MediCare',
                        'initials' => 'ED',
                        'color' => 'bg-emerald-500',
                        'stars' => 4,
                        'quote' => 'Integrarea cu sistemul nostru existent a durat doar 2 zile. Suportul tehnic a fost excepțional.',
                    ],
                    [
                        'name' => 'Cristian Radu',
                        'role' => 'CTO @ AutoServ',
                        'initials' => 'CR',
                        'color' => 'bg-amber-500',
                        'stars' => 5,
                        'quote' => 'Calitatea vocii este remarcabilă. Mulți clienți nu realizează că vorbesc cu un AI.',
                    ],
                    [
                        'name' => 'Ana Gheorghe',
                        'role' => 'Fondator @ EduOnline',
                        'initials' => 'AG',
                        'color' => 'bg-rose-500',
                        'stars' => 5,
                        'quote' => 'Perfect pentru programări și întrebări frecvente. Ne-a eliberat 40 de ore pe săptămână.',
                    ],
                    [
                        'name' => 'Mihai Stanescu',
                        'role' => 'VP Sales @ LogiTrans',
                        'initials' => 'MS',
                        'color' => 'bg-red-800',
                        'stars' => 4,
                        'quote' => 'Analiza sentimentului ne ajută să identificăm clienții nemulțumiți înainte să îi pierdem.',
                    ],
                ];
            @endphp

            @foreach($testimonials as $testimonial)
                <div class="bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg transition-shadow duration-300">
                    {{-- Stars --}}
                    <div class="flex gap-1 mb-4">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= $testimonial['stars'] ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    {{-- Quote --}}
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">"{{ $testimonial['quote'] }}"</p>
                    {{-- Author --}}
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full {{ $testimonial['color'] }} flex items-center justify-center">
                            <span class="text-white text-sm font-bold">{{ $testimonial['initials'] }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $testimonial['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $testimonial['role'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- SECTION 7: FINAL CTA --}}
{{-- ============================================================== --}}
<section class="bg-gradient-to-r from-primary-600 to-primary-800">
    <div class="container-custom section-padding text-center">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6">Gata să transformi comunicarea cu clienții?</h2>
        <p class="text-lg sm:text-xl text-white/80 mb-10 max-w-2xl mx-auto">Începe gratuit, fără card de credit. Configurare în 5 minute.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/register" class="inline-flex items-center justify-center px-8 py-3.5 rounded-xl bg-white text-primary-700 font-semibold hover:bg-primary-50 transition-colors duration-200 shadow-lg">
                Începe acum — gratuit
            </a>
            <a href="/contact" class="inline-flex items-center justify-center px-8 py-3.5 rounded-xl border-2 border-white text-white font-semibold hover:bg-white/10 transition-colors duration-200">
                Programează un demo
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function() {
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');

    function getResponse(message) {
        const msg = message.toLowerCase();

        if (msg.includes('preț') || msg.includes('cost') || msg.includes('pret')) {
            return 'Avem trei planuri: Starter (99\u20AC/lun\u0103), Profesional (299\u20AC/lun\u0103) \u0219i Enterprise (pre\u021B personalizat). Dori\u021Bi detalii despre un plan anume?';
        }
        if (msg.includes('demo') || msg.includes('test')) {
            return 'Desigur! Pute\u021Bi programa o demonstra\u021Bie personalizat\u0103 accesând pagina noastr\u0103 de contact sau sunând la +40 21 XXX XXXX.';
        }
        if (msg.includes('func\u021Bion') || msg.includes('function') || msg.includes('face')) {
            return 'Sambla poate gestiona apeluri inbound \u0219i outbound, poate r\u0103spunde la \u00EEntreb\u0103ri frecvente, programa \u00EEntâlniri \u0219i transfera apeluri c\u0103tre operatori umani când e necesar.';
        }
        if (msg.includes('integr')) {
            return 'Ne integr\u0103m cu Twilio, centralele PBX, CRM-uri populare (Salesforce, HubSpot), Google Calendar \u0219i oferim API REST complet documentat.';
        }
        if (msg.includes('român') || msg.includes('limba')) {
            return 'Da, Sambla func\u021Bioneaz\u0103 nativ \u00EEn limba român\u0103, cu \u00EEn\u021Belegere complet\u0103 a diacriticelor \u0219i expresiilor locale.';
        }

        return 'Mul\u021Bumesc pentru \u00EEntrebare! Un coleg din echipa noastr\u0103 v\u0103 va contacta \u00EEn curând cu mai multe detalii. \u00CEntre timp, pute\u021Bi explora func\u021Bionalit\u0103\u021Bile noastre sau pagina de pre\u021Buri.';
    }

    function addMessage(text, isUser) {
        const wrapper = document.createElement('div');
        wrapper.className = isUser ? 'flex items-start gap-2 justify-end' : 'flex items-start gap-2';

        if (isUser) {
            wrapper.innerHTML =
                '<div class="bg-primary-600 text-white rounded-2xl rounded-tr-md px-4 py-3 max-w-[80%] shadow-sm">' +
                    '<p class="text-sm">' + escapeHtml(text) + '</p>' +
                '</div>';
        } else {
            wrapper.innerHTML =
                '<div class="w-8 h-8 rounded-full bg-primary-100 flex-shrink-0 flex items-center justify-center">' +
                    '<svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>' +
                    '</svg>' +
                '</div>' +
                '<div class="bg-white rounded-2xl rounded-tl-md px-4 py-3 max-w-[80%] shadow-sm border border-slate-100">' +
                    '<p class="text-sm text-slate-700">' + text + '</p>' +
                '</div>';
        }

        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingIndicator() {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-start gap-2';
        wrapper.id = 'typingIndicator';
        wrapper.innerHTML =
            '<div class="w-8 h-8 rounded-full bg-primary-100 flex-shrink-0 flex items-center justify-center">' +
                '<svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>' +
                '</svg>' +
            '</div>' +
            '<div class="bg-white rounded-2xl rounded-tl-md px-4 py-3 shadow-sm border border-slate-100">' +
                '<div class="flex gap-1 items-center h-5">' +
                    '<span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>' +
                    '<span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>' +
                    '<span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>' +
                '</div>' +
            '</div>';
        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function removeTypingIndicator() {
        var el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var text = chatInput.value.trim();
        if (!text) return;

        addMessage(text, true);
        chatInput.value = '';

        showTypingIndicator();

        var response = getResponse(text);
        setTimeout(function() {
            removeTypingIndicator();
            addMessage(response, false);
        }, 500);
    });
})();
</script>
@endpush
