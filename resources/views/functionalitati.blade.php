@extends('layouts.app')

@section('title', 'Funcționalități - VoiceBot SaaS')
@section('meta_description', 'Descoperă funcționalitățile VoiceBot: telefonie inteligentă, AI conversațional, analiză în timp real, integrări și API pentru dezvoltatori.')

@section('content')

{{-- ==================== HERO ==================== --}}
<section class="relative bg-gradient-to-b from-white to-primary-50 section-padding pt-32 lg:pt-40">
    <div class="container-custom text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 leading-tight">
            Funcționalități puternice pentru <span class="gradient-text">comunicare inteligentă</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Descoperă tot ce poate face VoiceBot pentru afacerea ta
        </p>
    </div>
    {{-- Decorative blobs --}}
    <div class="absolute top-20 left-10 w-64 h-64 bg-primary-200/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-10 w-48 h-48 bg-primary-300/20 rounded-full blur-3xl pointer-events-none"></div>
</section>

{{-- ==================== CATEGORY 1: TELEFONIE (white bg) ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Text --}}
            <div>
                <div class="w-16 h-16 rounded-2xl bg-primary-100 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Telefonie Inteligentă</h2>
                <p class="text-slate-600 mb-8 leading-relaxed">
                    Gestionează apelurile tale cu o infrastructură telefonică modernă, integrată complet cu inteligența artificială. De la rutare inteligentă la transcriere automată, totul funcționează fără efort.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Integrare Twilio completă</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Numere de telefon românești (+40)</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">IVR personalizabil cu AI</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Transfer inteligent către operatori</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Înregistrare și transcriere apeluri</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Suport pentru cozi de așteptare</span>
                    </li>
                </ul>
            </div>

            {{-- Illustration: Phone Interface --}}
            <div class="flex justify-center lg:justify-end">
                <div class="relative w-72 md:w-80">
                    <svg viewBox="0 0 320 480" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full drop-shadow-2xl">
                        {{-- Phone body --}}
                        <rect x="20" y="10" width="280" height="460" rx="36" fill="#f8fafc" stroke="#e2e8f0" stroke-width="2"/>
                        <rect x="36" y="60" width="248" height="370" rx="8" fill="white"/>
                        {{-- Top bar --}}
                        <rect x="120" y="28" width="80" height="6" rx="3" fill="#e2e8f0"/>
                        {{-- Status bar --}}
                        <rect x="48" y="76" width="120" height="8" rx="4" fill="#7c3aed" opacity="0.2"/>
                        <rect x="48" y="100" width="80" height="6" rx="3" fill="#e2e8f0"/>
                        {{-- Call card --}}
                        <rect x="48" y="124" width="224" height="80" rx="12" fill="#7c3aed" opacity="0.08"/>
                        <circle cx="80" cy="164" r="20" fill="#7c3aed" opacity="0.15"/>
                        <circle cx="80" cy="164" r="10" fill="#7c3aed" opacity="0.3"/>
                        <rect x="110" y="150" width="100" height="8" rx="4" fill="#7c3aed" opacity="0.4"/>
                        <rect x="110" y="168" width="60" height="6" rx="3" fill="#7c3aed" opacity="0.2"/>
                        {{-- Waveform --}}
                        <rect x="48" y="224" width="224" height="50" rx="8" fill="#f1f5f9"/>
                        <rect x="64" y="238" width="4" height="22" rx="2" fill="#7c3aed" opacity="0.5"/>
                        <rect x="76" y="232" width="4" height="34" rx="2" fill="#7c3aed" opacity="0.7"/>
                        <rect x="88" y="240" width="4" height="18" rx="2" fill="#7c3aed" opacity="0.4"/>
                        <rect x="100" y="228" width="4" height="40" rx="2" fill="#7c3aed" opacity="0.8"/>
                        <rect x="112" y="236" width="4" height="26" rx="2" fill="#7c3aed" opacity="0.6"/>
                        <rect x="124" y="242" width="4" height="14" rx="2" fill="#7c3aed" opacity="0.3"/>
                        <rect x="136" y="230" width="4" height="38" rx="2" fill="#7c3aed" opacity="0.7"/>
                        <rect x="148" y="238" width="4" height="22" rx="2" fill="#7c3aed" opacity="0.5"/>
                        <rect x="160" y="234" width="4" height="30" rx="2" fill="#7c3aed" opacity="0.6"/>
                        <rect x="172" y="240" width="4" height="18" rx="2" fill="#7c3aed" opacity="0.4"/>
                        <rect x="184" y="228" width="4" height="40" rx="2" fill="#7c3aed" opacity="0.9"/>
                        <rect x="196" y="236" width="4" height="26" rx="2" fill="#7c3aed" opacity="0.5"/>
                        <rect x="208" y="242" width="4" height="14" rx="2" fill="#7c3aed" opacity="0.3"/>
                        <rect x="220" y="232" width="4" height="34" rx="2" fill="#7c3aed" opacity="0.7"/>
                        <rect x="232" y="238" width="4" height="22" rx="2" fill="#7c3aed" opacity="0.5"/>
                        <rect x="244" y="244" width="4" height="10" rx="2" fill="#7c3aed" opacity="0.2"/>
                        <rect x="256" y="236" width="4" height="26" rx="2" fill="#7c3aed" opacity="0.6"/>
                        {{-- Buttons --}}
                        <rect x="48" y="294" width="104" height="40" rx="10" fill="#7c3aed" opacity="0.12"/>
                        <rect x="168" y="294" width="104" height="40" rx="10" fill="#7c3aed" opacity="0.12"/>
                        <rect x="70" y="310" width="60" height="6" rx="3" fill="#7c3aed" opacity="0.4"/>
                        <rect x="190" y="310" width="60" height="6" rx="3" fill="#7c3aed" opacity="0.4"/>
                        {{-- Transcript lines --}}
                        <rect x="48" y="354" width="180" height="6" rx="3" fill="#e2e8f0"/>
                        <rect x="48" y="370" width="140" height="6" rx="3" fill="#e2e8f0"/>
                        <rect x="48" y="386" width="200" height="6" rx="3" fill="#e2e8f0"/>
                        <rect x="48" y="402" width="120" height="6" rx="3" fill="#e2e8f0"/>
                        {{-- Bottom bar --}}
                        <circle cx="160" cy="450" r="12" fill="#e2e8f0"/>
                    </svg>
                    {{-- Floating badge --}}
                    <div class="absolute -top-4 -right-4 bg-primary-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                        Twilio
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ==================== CATEGORY 2: AI CONVERSATIONAL (primary-50 bg) ==================== --}}
<section class="bg-primary-50 section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Illustration: AI Brain / Conversation Flow (LEFT on desktop) --}}
            <div class="flex justify-center lg:justify-start order-2 lg:order-1">
                <div class="relative w-72 md:w-80">
                    <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                        {{-- Central brain circle --}}
                        <circle cx="160" cy="160" r="60" fill="#7c3aed" opacity="0.08" stroke="#7c3aed" stroke-width="2" stroke-opacity="0.2"/>
                        <circle cx="160" cy="160" r="40" fill="#7c3aed" opacity="0.12"/>
                        {{-- Brain paths --}}
                        <path d="M145 148 C148 138, 158 135, 160 140 C162 135, 172 138, 175 148" stroke="#7c3aed" stroke-width="2" fill="none" opacity="0.5"/>
                        <path d="M140 158 C145 152, 155 152, 160 158 C165 152, 175 152, 180 158" stroke="#7c3aed" stroke-width="2" fill="none" opacity="0.5"/>
                        <path d="M145 168 C150 175, 170 175, 175 168" stroke="#7c3aed" stroke-width="2" fill="none" opacity="0.5"/>
                        {{-- AI text --}}
                        <text x="160" y="165" text-anchor="middle" fill="#7c3aed" font-size="14" font-weight="700" opacity="0.7">AI</text>
                        {{-- Conversation nodes --}}
                        {{-- Top node --}}
                        <rect x="120" y="30" width="80" height="32" rx="16" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <rect x="134" y="42" width="52" height="6" rx="3" fill="#7c3aed" opacity="0.3"/>
                        <line x1="160" y1="62" x2="160" y2="100" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2" stroke-dasharray="4 4"/>
                        {{-- Right node --}}
                        <rect x="240" y="115" width="70" height="32" rx="16" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <rect x="254" y="127" width="42" height="6" rx="3" fill="#7c3aed" opacity="0.3"/>
                        <line x1="220" y1="145" x2="240" y2="136" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2" stroke-dasharray="4 4"/>
                        {{-- Bottom-right node --}}
                        <rect x="225" y="210" width="75" height="32" rx="16" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <rect x="239" y="222" width="47" height="6" rx="3" fill="#7c3aed" opacity="0.3"/>
                        <line x1="205" y1="200" x2="230" y2="216" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2" stroke-dasharray="4 4"/>
                        {{-- Bottom node --}}
                        <rect x="105" y="260" width="110" height="32" rx="16" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <rect x="119" y="272" width="82" height="6" rx="3" fill="#7c3aed" opacity="0.3"/>
                        <line x1="160" y1="220" x2="160" y2="260" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2" stroke-dasharray="4 4"/>
                        {{-- Left node --}}
                        <rect x="10" y="180" width="80" height="32" rx="16" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <rect x="24" y="192" width="52" height="6" rx="3" fill="#7c3aed" opacity="0.3"/>
                        <line x1="100" y1="185" x2="90" y2="192" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2" stroke-dasharray="4 4"/>
                        {{-- Top-left node --}}
                        <rect x="20" y="100" width="68" height="32" rx="16" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <rect x="34" y="112" width="40" height="6" rx="3" fill="#7c3aed" opacity="0.3"/>
                        <line x1="100" y1="140" x2="88" y2="126" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2" stroke-dasharray="4 4"/>
                        {{-- Pulse rings --}}
                        <circle cx="160" cy="160" r="80" stroke="#7c3aed" stroke-width="1" stroke-opacity="0.1" fill="none"/>
                        <circle cx="160" cy="160" r="110" stroke="#7c3aed" stroke-width="1" stroke-opacity="0.06" fill="none"/>
                        <circle cx="160" cy="160" r="140" stroke="#7c3aed" stroke-width="1" stroke-opacity="0.03" fill="none"/>
                    </svg>
                    {{-- Floating badge --}}
                    <div class="absolute -bottom-2 -left-2 bg-white text-primary-700 text-xs font-bold px-3 py-1.5 rounded-full shadow-lg border border-primary-100">
                        OpenAI Realtime
                    </div>
                </div>
            </div>

            {{-- Text --}}
            <div class="order-1 lg:order-2">
                <div class="w-16 h-16 rounded-2xl bg-primary-100 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">AI Conversațional Avansat</h2>
                <p class="text-slate-600 mb-8 leading-relaxed">
                    Motor de inteligență artificială de ultimă generație care înțelege, răspunde și se adaptează natural la fiecare conversație, exact ca un operator uman experimentat.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Powered by OpenAI Realtime API</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Barge-in — clientul poate întrerupe botul natural</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Menținere context pe tot parcursul conversației</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Detectare intenție și sentiment</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Răspunsuri personalizate per scenariu</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Suport multilingv (română, engleză, + altele)</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ==================== CATEGORY 3: ANALIZA & RAPOARTE (white bg) ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Text --}}
            <div>
                <div class="w-16 h-16 rounded-2xl bg-primary-100 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Analiză & Rapoarte în Timp Real</h2>
                <p class="text-slate-600 mb-8 leading-relaxed">
                    Vizualizează performanța în timp real, accesează transcrieri detaliate și ia decizii bazate pe date concrete. Totul într-un dashboard intuitiv.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Dashboard live cu metrici cheie</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Transcrieri automate ale fiecărui apel</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Analiză de sentiment în timp real</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Rapoarte de performanță zilnice/săptămânale</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Export date în CSV/PDF</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Alerte automate pentru anomalii</span>
                    </li>
                </ul>
            </div>

            {{-- Illustration: Dashboard with Charts --}}
            <div class="flex justify-center lg:justify-end">
                <div class="relative w-full max-w-sm">
                    <svg viewBox="0 0 380 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full drop-shadow-xl">
                        {{-- Dashboard frame --}}
                        <rect x="0" y="0" width="380" height="280" rx="16" fill="#f8fafc" stroke="#e2e8f0" stroke-width="1.5"/>
                        {{-- Top bar --}}
                        <rect x="0" y="0" width="380" height="36" rx="16" fill="white"/>
                        <rect x="0" y="16" width="380" height="20" fill="white"/>
                        <line x1="0" y1="36" x2="380" y2="36" stroke="#e2e8f0" stroke-width="1"/>
                        <circle cx="20" cy="18" r="5" fill="#ef4444" opacity="0.5"/>
                        <circle cx="36" cy="18" r="5" fill="#eab308" opacity="0.5"/>
                        <circle cx="52" cy="18" r="5" fill="#22c55e" opacity="0.5"/>
                        <rect x="140" y="13" width="100" height="8" rx="4" fill="#e2e8f0"/>
                        {{-- Metric cards --}}
                        <rect x="16" y="48" width="108" height="52" rx="8" fill="white" stroke="#e2e8f0" stroke-width="1"/>
                        <rect x="28" y="58" width="40" height="5" rx="2.5" fill="#94a3b8"/>
                        <rect x="28" y="72" width="60" height="10" rx="4" fill="#7c3aed" opacity="0.7"/>
                        <rect x="28" y="88" width="30" height="4" rx="2" fill="#22c55e" opacity="0.5"/>
                        <rect x="136" y="48" width="108" height="52" rx="8" fill="white" stroke="#e2e8f0" stroke-width="1"/>
                        <rect x="148" y="58" width="44" height="5" rx="2.5" fill="#94a3b8"/>
                        <rect x="148" y="72" width="50" height="10" rx="4" fill="#7c3aed" opacity="0.7"/>
                        <rect x="148" y="88" width="30" height="4" rx="2" fill="#22c55e" opacity="0.5"/>
                        <rect x="256" y="48" width="108" height="52" rx="8" fill="white" stroke="#e2e8f0" stroke-width="1"/>
                        <rect x="268" y="58" width="48" height="5" rx="2.5" fill="#94a3b8"/>
                        <rect x="268" y="72" width="55" height="10" rx="4" fill="#7c3aed" opacity="0.7"/>
                        <rect x="268" y="88" width="30" height="4" rx="2" fill="#ef4444" opacity="0.4"/>
                        {{-- Chart area --}}
                        <rect x="16" y="112" width="228" height="152" rx="8" fill="white" stroke="#e2e8f0" stroke-width="1"/>
                        {{-- Line chart --}}
                        <polyline points="32,230 60,215 88,225 116,195 144,200 172,175 200,165 228,170" stroke="#7c3aed" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="32,240 60,235 88,238 116,228 144,232 172,220 200,215 228,218" stroke="#a855f7" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" opacity="0.5"/>
                        {{-- Chart area fill --}}
                        <polygon points="32,230 60,215 88,225 116,195 144,200 172,175 200,165 228,170 228,248 32,248" fill="#7c3aed" opacity="0.06"/>
                        {{-- Grid lines --}}
                        <line x1="32" y1="180" x2="228" y2="180" stroke="#e2e8f0" stroke-width="0.5"/>
                        <line x1="32" y1="210" x2="228" y2="210" stroke="#e2e8f0" stroke-width="0.5"/>
                        <line x1="32" y1="240" x2="228" y2="240" stroke="#e2e8f0" stroke-width="0.5"/>
                        {{-- Chart label --}}
                        <rect x="28" y="120" width="70" height="6" rx="3" fill="#94a3b8"/>
                        {{-- Donut chart --}}
                        <rect x="256" y="112" width="108" height="152" rx="8" fill="white" stroke="#e2e8f0" stroke-width="1"/>
                        <circle cx="310" cy="184" r="36" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                        <circle cx="310" cy="184" r="36" fill="none" stroke="#7c3aed" stroke-width="10" stroke-dasharray="160 66" stroke-dashoffset="0" opacity="0.8"/>
                        <circle cx="310" cy="184" r="36" fill="none" stroke="#a855f7" stroke-width="10" stroke-dasharray="45 181" stroke-dashoffset="-160" opacity="0.6"/>
                        <rect x="270" y="120" width="50" height="6" rx="3" fill="#94a3b8"/>
                        <rect x="282" y="236" width="56" height="5" rx="2.5" fill="#7c3aed" opacity="0.3"/>
                        <rect x="290" y="248" width="40" height="5" rx="2.5" fill="#a855f7" opacity="0.3"/>
                    </svg>
                    {{-- Floating badge --}}
                    <div class="absolute -bottom-3 left-4 bg-white text-slate-700 text-xs font-bold px-3 py-1.5 rounded-full shadow-lg border border-slate-200">
                        Timp real
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ==================== CATEGORY 4: INTEGRATIONS (primary-50 bg) ==================== --}}
<section class="bg-primary-50 section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Illustration: Connected Nodes (LEFT on desktop) --}}
            <div class="flex justify-center lg:justify-start order-2 lg:order-1">
                <div class="relative w-72 md:w-80">
                    <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                        {{-- Central hub --}}
                        <circle cx="160" cy="160" r="32" fill="#7c3aed" opacity="0.12" stroke="#7c3aed" stroke-width="2" stroke-opacity="0.3"/>
                        <text x="160" y="155" text-anchor="middle" fill="#7c3aed" font-size="10" font-weight="700" opacity="0.7">Voice</text>
                        <text x="160" y="170" text-anchor="middle" fill="#7c3aed" font-size="10" font-weight="700" opacity="0.7">Bot</text>

                        {{-- Connection lines --}}
                        <line x1="160" y1="128" x2="160" y2="60" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="188" y1="140" x2="250" y2="90" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="192" y1="160" x2="268" y2="160" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="188" y1="180" x2="250" y2="230" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="160" y1="192" x2="160" y2="264" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="132" y1="180" x2="70" y2="230" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="128" y1="160" x2="52" y2="160" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>
                        <line x1="132" y1="140" x2="70" y2="90" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.2"/>

                        {{-- Node: CRM (top) --}}
                        <rect x="128" y="28" width="64" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="160" y="49" text-anchor="middle" fill="#7c3aed" font-size="11" font-weight="600" opacity="0.7">CRM</text>

                        {{-- Node: Calendar (top-right) --}}
                        <rect x="236" y="64" width="72" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="272" y="85" text-anchor="middle" fill="#7c3aed" font-size="10" font-weight="600" opacity="0.7">Calendar</text>

                        {{-- Node: API (right) --}}
                        <rect x="256" y="144" width="56" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="284" y="165" text-anchor="middle" fill="#7c3aed" font-size="11" font-weight="600" opacity="0.7">API</text>

                        {{-- Node: WhatsApp (bottom-right) --}}
                        <rect x="230" y="216" width="80" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="270" y="237" text-anchor="middle" fill="#7c3aed" font-size="10" font-weight="600" opacity="0.7">WhatsApp</text>

                        {{-- Node: Webhook (bottom) --}}
                        <rect x="120" y="264" width="80" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="160" y="285" text-anchor="middle" fill="#7c3aed" font-size="10" font-weight="600" opacity="0.7">Webhook</text>

                        {{-- Node: SMS (bottom-left) --}}
                        <rect x="24" y="216" width="56" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="52" y="237" text-anchor="middle" fill="#7c3aed" font-size="11" font-weight="600" opacity="0.7">SMS</text>

                        {{-- Node: SDK (left) --}}
                        <rect x="12" y="144" width="56" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="40" y="165" text-anchor="middle" fill="#7c3aed" font-size="11" font-weight="600" opacity="0.7">SDK</text>

                        {{-- Node: Email (top-left) --}}
                        <rect x="28" y="64" width="64" height="32" rx="8" fill="white" stroke="#7c3aed" stroke-width="1.5" stroke-opacity="0.3"/>
                        <text x="60" y="85" text-anchor="middle" fill="#7c3aed" font-size="11" font-weight="600" opacity="0.7">Email</text>

                        {{-- Animated dots on connections --}}
                        <circle cx="160" cy="94" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="219" cy="115" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="230" cy="160" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="219" cy="205" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="160" cy="228" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="101" cy="205" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="90" cy="160" r="3" fill="#7c3aed" opacity="0.4"/>
                        <circle cx="101" cy="115" r="3" fill="#7c3aed" opacity="0.4"/>
                    </svg>
                </div>
            </div>

            {{-- Text --}}
            <div class="order-1 lg:order-2">
                <div class="w-16 h-16 rounded-2xl bg-primary-100 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                    </svg>
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Integrări & API</h2>
                <p class="text-slate-600 mb-8 leading-relaxed">
                    Conectează VoiceBot cu instrumentele pe care le folosești deja. Integrări native cu cele mai populare platforme și un API complet pentru personalizări avansate.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">CRM: Salesforce, HubSpot, Pipedrive</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Calendar: Google Calendar, Outlook</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Mesagerie: WhatsApp Business, SMS</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">API REST complet documentat</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">Webhook-uri pentru evenimente</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-slate-700 font-medium">SDK-uri Python, Node.js, PHP</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ==================== COMPARISON TABLE ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Cum se compară cu un operator uman?</h2>
            <p class="text-slate-600 max-w-2xl mx-auto">VoiceBot depășește limitele unui call center tradițional pe toate planurile.</p>
        </div>

        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <div class="inline-block min-w-full sm:min-w-0 px-4 sm:px-0">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left py-4 px-4 sm:px-6 text-sm font-semibold text-slate-500 uppercase tracking-wider border-b-2 border-slate-200">Criteriu</th>
                            <th class="text-center py-4 px-4 sm:px-6 text-sm font-semibold text-slate-500 uppercase tracking-wider border-b-2 border-slate-200">Operator Uman</th>
                            <th class="text-center py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 uppercase tracking-wider border-b-2 border-primary-200 bg-primary-50/50 rounded-t-xl">VoiceBot AI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Disponibilitate</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">Program de lucru (8h)</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 text-center bg-primary-50/30">24/7/365</td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Timp de răspuns</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">30-120 secunde</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 text-center bg-primary-50/30">&lt; 1 secundă</td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Cost per apel</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">2-5&euro;</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-emerald-600 text-center bg-primary-50/30">0.10-0.30&euro;</td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Scalabilitate</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">Limitată de personal</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 text-center bg-primary-50/30">Nelimitată</td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Consistență</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">Variabilă</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 text-center bg-primary-50/30">100% consistentă</td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Limbi străine</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">1-2 limbi</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 text-center bg-primary-50/30">10+ limbi</td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Analiză sentiment</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">Subiectivă</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-primary-600 text-center bg-primary-50/30">Obiectivă, în timp real</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 sm:px-6 text-sm font-medium text-slate-700">Oboseală</td>
                            <td class="py-4 px-4 sm:px-6 text-sm text-slate-500 text-center">Da, scade calitatea</td>
                            <td class="py-4 px-4 sm:px-6 text-sm font-semibold text-emerald-600 text-center bg-primary-50/30 rounded-b-xl">Nu, performanță constantă</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ==================== DEVELOPER SECTION ==================== --}}
<section class="bg-slate-900 section-padding">
    <div class="container-custom">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Construit pentru dezvoltatori</h2>
            <p class="text-slate-400 max-w-2xl mx-auto text-lg">
                API REST puternic, documentat complet, cu SDK-uri în limbajele tale preferate
            </p>
        </div>

        {{-- Code block --}}
        <div class="max-w-3xl mx-auto">
            <div class="rounded-2xl overflow-hidden shadow-2xl">
                {{-- Terminal top bar --}}
                <div class="bg-slate-800 px-4 py-3 flex items-center gap-3">
                    <div class="flex gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500/70"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500/70"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500/70"></div>
                    </div>
                    <span class="text-slate-500 text-xs font-mono ml-2">api-example.js</span>
                </div>
                {{-- Code content --}}
                <div class="bg-slate-950 p-6 overflow-x-auto">
                    <pre class="text-sm leading-relaxed font-mono"><code><span class="text-slate-500">// Exemplu: Inițierea unui apel cu VoiceBot API</span>
<span class="text-purple-400">const</span> <span class="text-slate-200">response</span> <span class="text-slate-500">=</span> <span class="text-purple-400">await</span> <span class="text-blue-400">fetch</span><span class="text-slate-400">(</span><span class="text-emerald-400">'https://api.voicebot.ro/v1/calls'</span><span class="text-slate-400">,</span> <span class="text-slate-400">{</span>
  <span class="text-slate-200">method</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'POST'</span><span class="text-slate-400">,</span>
  <span class="text-slate-200">headers</span><span class="text-slate-400">:</span> <span class="text-slate-400">{</span>
    <span class="text-emerald-400">'Authorization'</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'Bearer YOUR_API_KEY'</span><span class="text-slate-400">,</span>
    <span class="text-emerald-400">'Content-Type'</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'application/json'</span>
  <span class="text-slate-400">},</span>
  <span class="text-slate-200">body</span><span class="text-slate-400">:</span> <span class="text-blue-400">JSON</span><span class="text-slate-400">.</span><span class="text-blue-400">stringify</span><span class="text-slate-400">({</span>
    <span class="text-slate-200">to</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'+40721234567'</span><span class="text-slate-400">,</span>
    <span class="text-slate-200">agent_id</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'agent_receptie'</span><span class="text-slate-400">,</span>
    <span class="text-slate-200">scenario</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'programare_consultatie'</span><span class="text-slate-400">,</span>
    <span class="text-slate-200">language</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'ro'</span><span class="text-slate-400">,</span>
    <span class="text-slate-200">webhook_url</span><span class="text-slate-400">:</span> <span class="text-emerald-400">'https://app.example.com/webhook'</span>
  <span class="text-slate-400">})</span>
<span class="text-slate-400">});</span>

<span class="text-purple-400">const</span> <span class="text-slate-200">call</span> <span class="text-slate-500">=</span> <span class="text-purple-400">await</span> <span class="text-slate-200">response</span><span class="text-slate-400">.</span><span class="text-blue-400">json</span><span class="text-slate-400">();</span>
<span class="text-blue-400">console</span><span class="text-slate-400">.</span><span class="text-blue-400">log</span><span class="text-slate-400">(</span><span class="text-emerald-400">`Apel inițiat: <span class="text-yellow-300">${</span><span class="text-slate-200">call.id</span><span class="text-yellow-300">}</span>`</span><span class="text-slate-400">);</span></code></pre>
                </div>
            </div>

            {{-- Badges --}}
            <div class="flex flex-wrap justify-center gap-3 mt-8">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-800 text-primary-400 border border-slate-700">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                    REST API
                </span>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-800 text-primary-400 border border-slate-700">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                    WebSocket
                </span>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-800 text-primary-400 border border-slate-700">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    Webhook-uri
                </span>
            </div>
        </div>
    </div>
</section>

{{-- ==================== CTA SECTION ==================== --}}
<section class="relative overflow-hidden section-padding bg-gradient-to-br from-primary-600 to-primary-800">
    {{-- Decorative elements --}}
    <div class="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/3 translate-y-1/3"></div>

    <div class="container-custom relative text-center">
        <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
            Pregătit să automatizezi comunicarea?
        </h2>
        <p class="text-primary-100 text-lg mb-10 max-w-xl mx-auto">
            Începe gratuit și descoperă cum VoiceBot poate transforma interacțiunile cu clienții tăi.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="/register" class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-primary-700 font-semibold rounded-xl hover:bg-primary-50 transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5 text-lg">
                Începe gratuit
            </a>
        </div>
        <p class="mt-6 text-primary-200 text-sm">
            sau <a href="/contact" class="underline hover:text-white transition-colors duration-200">programează un demo personalizat</a>
        </p>
    </div>
</section>

@endsection
