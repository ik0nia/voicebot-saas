@extends('layouts.app')

@section('title', 'Sambla — Angajatul tău AI care știe totul despre afacerea ta')
@section('meta_description', 'Platforma AI care răspunde clienților pe chat și telefon, 24/7, din informațiile tale reale. Setup în 10 minute, fără cunoștințe tehnice.')

@section('content')

{{-- ============================================================== --}}
{{-- HERO — Dark cu accent tradițional --}}
{{-- ============================================================== --}}
<section class="relative overflow-hidden bg-slate-950 min-h-[92vh] flex items-center">
    {{-- Romanian motif texture overlay --}}
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="hero-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse">
                    <path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/>
                    <rect x="38" y="2" width="4" height="8" fill="#991b1b"/>
                    <rect x="38" y="38" width="4" height="8" fill="#991b1b"/>
                    <rect x="18" y="22" width="8" height="4" fill="#991b1b"/>
                    <rect x="54" y="22" width="8" height="4" fill="#991b1b"/>
                    <rect x="6" y="58" width="4" height="4" fill="#991b1b"/>
                    <rect x="70" y="58" width="4" height="4" fill="#991b1b"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hero-motif)"/>
        </svg>
    </div>
    {{-- Gradient orbs --}}
    <div class="absolute top-20 -left-40 w-[500px] h-[500px] bg-red-900/20 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-20 -right-40 w-[400px] h-[400px] bg-red-800/15 rounded-full blur-[100px]"></div>

    <div class="container-custom relative z-10 py-24 lg:py-0">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            {{-- Left: Copy --}}
            <div class="animate-fade-in">
                {{-- Live badge --}}
                <div class="inline-flex items-center gap-2.5 bg-white/[0.06] border border-white/[0.08] rounded-full px-5 py-2 mb-8 backdrop-blur-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                    </span>
                    <span class="text-xs font-medium text-slate-300 tracking-wide">Platformă activă — răspunde clienților non-stop</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-[4.25rem] font-extrabold leading-[1.08] tracking-tight text-white mb-7">
                    Angajatul tău AI care<br>
                    <span id="heroRotatingText" class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent inline-block transition-opacity duration-1000 ease-in-out">știe totul despre afacerea ta</span>
                </h1>

                <p class="text-lg text-slate-400 mb-10 max-w-lg leading-relaxed">
                    Răspunde clienților pe <strong class="text-slate-200">chat</strong> și <strong class="text-slate-200">telefon</strong>, 24/7, din documentele, produsele și politicile tale reale. Nu inventează. Nu ghicește. <strong class="text-slate-200">Știe.</strong>
                </p>

                {{-- CTA --}}
                <div class="flex flex-wrap gap-4 mb-12">
                    <a href="/register" class="group inline-flex items-center gap-2.5 px-8 py-4 bg-gradient-to-r from-red-700 to-red-600 text-white font-bold rounded-xl hover:from-red-600 hover:to-red-500 transition-all duration-300 shadow-lg shadow-red-900/40 text-[15px]">
                        Începe gratuit
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#demo" class="inline-flex items-center gap-2 px-8 py-4 text-white/80 font-semibold rounded-xl border border-white/10 hover:bg-white/[0.06] hover:text-white transition-all duration-300 text-[15px]">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                        Vezi în acțiune
                    </a>
                </div>

                {{-- Trust row --}}
                <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Fără card de credit</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Setup 10 minute</span>
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>GDPR compliant</span>
                </div>
            </div>

            {{-- Right: LIVE animated conversation --}}
            <div class="animate-slide-up overflow-hidden">
                <div class="relative mx-2 sm:mx-0">
                    {{-- Multi-layer glow --}}
                    <div class="absolute -inset-6 bg-red-500/[0.07] rounded-[36px] blur-2xl"></div>
                    <div class="absolute -inset-3 bg-white/[0.08] rounded-[30px] blur-lg"></div>

                    <div class="relative bg-white rounded-[20px] overflow-hidden lg:min-w-[400px]" style="box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 4px 20px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.04);">
                        {{-- Header --}}
                        <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-4 relative">
                            <div class="absolute inset-0 opacity-[0.08]">
                                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="hm2" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M10 2 L14 6 L10 10 L6 6 Z" fill="white"/></pattern></defs><rect width="100%" height="100%" fill="url(#hm2)"/></svg>
                            </div>
                            <div class="relative flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full ring-2 ring-white/20 shadow-inner overflow-hidden shrink-0" style="background: linear-gradient(135deg, #fecaca, #fef2f2);">
                                    <svg viewBox="0 0 40 40" fill="none" class="w-full h-full">
                                        {{-- Față --}}
                                        <circle cx="20" cy="18" r="10" fill="#991b1b" opacity="0.9"/>
                                        {{-- Ochi --}}
                                        <circle cx="16" cy="16" r="1.8" fill="white"/>
                                        <circle cx="24" cy="16" r="1.8" fill="white"/>
                                        <circle cx="16.5" cy="16" r="0.8" fill="#1e293b"/>
                                        <circle cx="24.5" cy="16" r="0.8" fill="#1e293b"/>
                                        {{-- Zâmbet --}}
                                        <path d="M16 21 Q20 24 24 21" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                                        {{-- Antenă --}}
                                        <line x1="20" y1="8" x2="20" y2="5" stroke="#991b1b" stroke-width="1.5" stroke-linecap="round"/>
                                        <circle cx="20" cy="4" r="1.5" fill="#fbbf24">
                                            <animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite"/>
                                        </circle>
                                        {{-- Corp --}}
                                        <rect x="14" y="28" width="12" height="6" rx="3" fill="#991b1b" opacity="0.7"/>
                                        {{-- Undă sonoră --}}
                                        <path d="M30 14 Q33 18 30 22" stroke="white" stroke-width="1" fill="none" opacity="0.5" stroke-linecap="round">
                                            <animate attributeName="opacity" values="0.5;0;0.5" dur="1.5s" repeatCount="indefinite"/>
                                        </path>
                                        <path d="M33 12 Q37 18 33 24" stroke="white" stroke-width="0.8" fill="none" opacity="0.3" stroke-linecap="round">
                                            <animate attributeName="opacity" values="0.3;0;0.3" dur="1.5s" begin="0.3s" repeatCount="indefinite"/>
                                        </path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-bold text-base">Sambla AI</p>
                                    <p class="text-white/60 text-xs flex items-center gap-1.5">
                                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span></span>
                                        Online acum
                                    </p>
                                </div>
                                {{-- Scenario tag — OPAC, vizibil --}}
                                <span id="heroScenarioLabel" class="text-[11px] font-bold text-red-700 bg-white px-3.5 py-1.5 rounded-full shadow-sm transition-all duration-500">🛒 Magazin online</span>
                            </div>
                        </div>

                        {{-- Messages area --}}
                        <div id="heroChat" class="px-4 py-4 lg:px-5 lg:py-5 bg-white h-[500px] lg:h-[500px] overflow-y-auto relative" style="scrollbar-width:none;-ms-overflow-style:none;">
                            <div id="heroChatInner" class="space-y-3.5"></div>
                            {{-- Typing indicator --}}
                            <div id="heroTyping" class="hidden flex gap-2 mt-3.5">
                                <div class="bg-slate-100 rounded-2xl rounded-tl-md px-5 py-3">
                                    <div class="flex gap-1.5 h-5 items-center">
                                        <span class="w-2 h-2 bg-slate-300 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                        <span class="w-2 h-2 bg-slate-300 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                        <span class="w-2 h-2 bg-slate-300 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                <span id="heroFooterText" class="text-xs text-slate-500 font-medium transition-opacity duration-300">Răspuns din baza de cunoștințe</span>
                            </div>
                            <div id="heroDotsContainer" class="flex gap-1.5"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom motif transition --}}
    <div class="absolute bottom-0 left-0 right-0">
        <x-motif-border color="red-900" />
    </div>
</section>

{{-- ============================================================== --}}
{{-- AVANTAJE CHEIE — impactful, vizual --}}
{{-- ============================================================== --}}
<section class="bg-white relative">
    <div class="container-custom py-20 lg:py-28">
        <div class="text-center mb-16">
            <x-motif-divider class="mb-8" />
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">De ce Sambla e diferit</h2>
            <p class="text-lg text-slate-500 max-w-2xl mx-auto">Nu e un chatbot generic. E un angajat AI care înțelege afacerea ta, vorbește cu clienții tăi, și devine mai bun în fiecare zi.</p>
        </div>

        {{-- Big 3 advantages --}}
        <div class="grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto mb-20">
            {{-- Advantage 1 --}}
            <div class="group relative bg-gradient-to-br from-red-50 to-white rounded-2xl p-8 border border-red-100 hover:shadow-xl hover:shadow-red-100/50 transition-all duration-500 overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 opacity-[0.04]">
                    <svg viewBox="0 0 80 80" fill="#991b1b"><path d="M40 0 L60 40 L40 80 L20 40 Z"/></svg>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-7 h-7 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Învață din datele TALE</h3>
                <p class="text-slate-600 leading-relaxed mb-5">Uploadezi documente, scanezi site-ul, conectezi magazinul — și AI-ul răspunde EXCLUSIV din informațiile tale reale. Nu inventează. Nu halucinează.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="text-[11px] font-medium text-red-700 bg-red-50 px-2.5 py-1 rounded-lg border border-red-100">PDF & DOCX</span>
                    <span class="text-[11px] font-medium text-red-700 bg-red-50 px-2.5 py-1 rounded-lg border border-red-100">Website Scan</span>
                    <span class="text-[11px] font-medium text-red-700 bg-red-50 px-2.5 py-1 rounded-lg border border-red-100">WooCommerce</span>
                </div>
            </div>

            {{-- Advantage 2 --}}
            <div class="group relative bg-gradient-to-br from-amber-50 to-white rounded-2xl p-8 border border-amber-100 hover:shadow-xl hover:shadow-amber-100/50 transition-all duration-500 overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 opacity-[0.04]">
                    <svg viewBox="0 0 80 80" fill="#92400e"><path d="M40 0 L60 40 L40 80 L20 40 Z"/></svg>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-7 h-7 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Se auto-îmbunătățește</h3>
                <p class="text-slate-600 leading-relaxed mb-5">Analizează conversațiile, identifică ce NU știe, și sugerează conținut de adăugat. Generează automat draft-uri pe care tu doar le aprobi.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="text-[11px] font-medium text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-100">Gap Detection</span>
                    <span class="text-[11px] font-medium text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-100">Auto KB Builder</span>
                    <span class="text-[11px] font-medium text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-100">Health Score</span>
                </div>
            </div>

            {{-- Advantage 3 --}}
            <div class="group relative bg-gradient-to-br from-blue-50 to-white rounded-2xl p-8 border border-blue-100 hover:shadow-xl hover:shadow-blue-100/50 transition-all duration-500 overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 opacity-[0.04]">
                    <svg viewBox="0 0 80 80" fill="#1e40af"><path d="M40 0 L60 40 L40 80 L20 40 Z"/></svg>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-7 h-7 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Voce + Chat, același creier</h3>
                <p class="text-slate-600 leading-relaxed mb-5">Clientul sună sau scrie — primește același răspuns expert. Un singur sistem inteligent, toate canalele de comunicare.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="text-[11px] font-medium text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">Voce AI</span>
                    <span class="text-[11px] font-medium text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">Web Chat</span>
                    <span class="text-[11px] font-medium text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">Multi-canal</span>
                </div>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl mx-auto">
            <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-3xl lg:text-4xl font-extrabold text-slate-900">10<span class="text-red-600 text-2xl">min</span></p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Timp de configurare</p>
            </div>
            <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-3xl lg:text-4xl font-extrabold text-slate-900">24<span class="text-red-600 text-2xl">/7</span></p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Disponibilitate</p>
            </div>
            <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-3xl lg:text-4xl font-extrabold text-slate-900">&lt;2<span class="text-red-600 text-2xl">s</span></p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Timp de răspuns</p>
            </div>
            <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-3xl lg:text-4xl font-extrabold text-slate-900">10<span class="text-red-600 text-2xl">L</span></p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Straturi de verificare AI</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- CUM GÂNDEȘTE AI-UL — roadmap vertical, detaliat --}}
{{-- ============================================================== --}}
<x-motif-border />
<section class="bg-slate-50 overflow-hidden">
    <div class="container-custom section-padding">
        <div class="text-center mb-20">
            <p class="text-sm font-semibold text-red-700 tracking-widest uppercase mb-3">Tehnologia din spate</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Cum gândește AI-ul Sambla</h2>
            <p class="text-lg text-slate-500 max-w-2xl mx-auto">Nu e un chatbot simplu. E un pipeline inteligent cu 4 etape care analizează, caută, decide și răspunde — în sub 2 secunde.</p>
        </div>

        {{-- Roadmap vertical --}}
        <div class="relative max-w-5xl mx-auto">

            {{-- Linia verticală punctată centrală (vizibilă pe desktop) --}}
            <div class="hidden lg:block absolute left-1/2 top-0 bottom-0 w-px" style="background-image: repeating-linear-gradient(to bottom, #cbd5e1 0px, #cbd5e1 6px, transparent 6px, transparent 14px);"></div>

            {{-- STEP 1 — Stânga --}}
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-16 mb-16 lg:mb-24">
                {{-- Dot pe linia centrală --}}
                <div class="hidden lg:flex absolute left-1/2 top-8 -translate-x-1/2 z-10 w-12 h-12 rounded-full bg-red-100 border-4 border-slate-50 items-center justify-center shadow-sm">
                    <span class="text-sm font-extrabold text-red-700">01</span>
                </div>
                {{-- Content stânga --}}
                <div class="lg:pr-16 lg:text-right">
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300">
                        <div class="inline-flex items-center gap-2 bg-red-50 text-red-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                            QUERY INTELLIGENCE
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Înțelege intenția clientului</h3>
                        <p class="text-slate-600 leading-relaxed mb-5">Fiecare mesaj este clasificat automat într-unul din 7 tipuri de intenție. AI-ul știe dacă clientul vrea să cumpere, să întrebe, să se plângă sau să compare — și adaptează TOT comportamentul în funcție de asta.</p>
                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">🛒 Tranzacțional</span>
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">🔍 Explorativ</span>
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">⚖️ Comparativ</span>
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">😤 Reclamație</span>
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">❓ Vag</span>
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">ℹ️ Informațional</span>
                            <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">👋 Salut</span>
                        </div>
                    </div>
                </div>
                {{-- Grafic dreapta --}}
                <div class="hidden lg:flex items-center lg:pl-16">
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm w-full">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Exemplu clasificare</p>
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-36 truncate">"Cât costă livrarea?"</span>
                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-blue-500 rounded-full" style="width:90%"></div></div>
                                <span class="text-[10px] font-bold text-blue-600">ℹ️ Info</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-36 truncate">"Vreau să comand"</span>
                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-red-500 rounded-full" style="width:95%"></div></div>
                                <span class="text-[10px] font-bold text-red-600">🛒 Tranz.</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-36 truncate">"Nu merge produsul!"</span>
                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-amber-500 rounded-full" style="width:88%"></div></div>
                                <span class="text-[10px] font-bold text-amber-600">😤 Reclam.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 2 — Dreapta --}}
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-16 mb-16 lg:mb-24">
                <div class="hidden lg:flex absolute left-1/2 top-8 -translate-x-1/2 z-10 w-12 h-12 rounded-full bg-amber-100 border-4 border-slate-50 items-center justify-center shadow-sm">
                    <span class="text-sm font-extrabold text-amber-700">02</span>
                </div>
                {{-- Grafic stânga --}}
                <div class="hidden lg:flex items-center lg:pr-16">
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm w-full">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Hybrid Search Pipeline</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0"><span class="text-xs">🔢</span></div>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-slate-700">Vector Search</p>
                                    <p class="text-[10px] text-slate-400">Similaritate semantică în 1536 dimensiuni</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0"><span class="text-xs">📝</span></div>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-slate-700">Full-Text Search</p>
                                    <p class="text-[10px] text-slate-400">Cuvinte cheie în română cu stemming</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0"><span class="text-xs">🏆</span></div>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-slate-700">AI Reranking</p>
                                    <p class="text-[10px] text-slate-400">Reordonare cu AI pentru relevanță maximă</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Content dreapta --}}
                <div class="lg:pl-16">
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300">
                        <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                            RAG PIPELINE
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Caută în baza ta de cunoștințe</h3>
                        <p class="text-slate-600 leading-relaxed mb-5">Combină două metode de căutare simultan: vectori semantici (înțelege SENSUL întrebării) și text complet în română (găsește cuvintele exacte). Apoi un AI reordonează rezultatele pentru relevanță maximă.</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center p-3 bg-slate-50 rounded-xl">
                                <p class="text-lg font-extrabold text-slate-900">8</p>
                                <p class="text-[10px] text-slate-500">chunks per query</p>
                            </div>
                            <div class="text-center p-3 bg-slate-50 rounded-xl">
                                <p class="text-lg font-extrabold text-slate-900">25</p>
                                <p class="text-[10px] text-slate-500">grupuri sinonime RO</p>
                            </div>
                            <div class="text-center p-3 bg-slate-50 rounded-xl">
                                <p class="text-lg font-extrabold text-slate-900">20</p>
                                <p class="text-[10px] text-slate-500">candidați reranking</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3 — Stânga --}}
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-16 mb-16 lg:mb-24">
                <div class="hidden lg:flex absolute left-1/2 top-8 -translate-x-1/2 z-10 w-12 h-12 rounded-full bg-blue-100 border-4 border-slate-50 items-center justify-center shadow-sm">
                    <span class="text-sm font-extrabold text-blue-700">03</span>
                </div>
                {{-- Content stânga --}}
                <div class="lg:pr-16 lg:text-right">
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300">
                        <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a7.723 7.723 0 010 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            CONVERSATION STRATEGY
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Adaptează strategia conversației</h3>
                        <p class="text-slate-600 leading-relaxed mb-5">Nu răspunde mecanic. Analizează starea conversației și decide inteligent: când să recomande produse, când să pună o întrebare de clarificare, când să ofere escaladare la un om, și când să ceară date de contact.</p>
                        <div class="space-y-2 lg:space-y-2">
                            <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-lg">✅ Detectează frustrarea → tonul devine empatic</div>
                            <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-lg">🔄 Reclamație → oprește vânzarea, oferă suport</div>
                            <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-xs font-semibold px-3 py-1.5 rounded-lg">📊 Conversație lungă → sugerează acțiune</div>
                        </div>
                    </div>
                </div>
                {{-- Grafic dreapta --}}
                <div class="hidden lg:flex items-center lg:pl-16">
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm w-full">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Strategie per etapă</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-green-400 shrink-0"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-slate-700">Începutul conversației</span>
                                        <span class="text-[10px] text-slate-400">msg 1-3</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">→ Înțelege nevoia, nu vinde</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-slate-700">Mijlocul conversației</span>
                                        <span class="text-[10px] text-slate-400">msg 4-8</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">→ Recomandă, compară, sugerează</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-slate-700">Conversație matură</span>
                                        <span class="text-[10px] text-slate-400">msg 9+</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">→ CTA, lead capture, escaladare</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 4 — Dreapta --}}
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-16">
                <div class="hidden lg:flex absolute left-1/2 top-8 -translate-x-1/2 z-10 w-12 h-12 rounded-full bg-emerald-100 border-4 border-slate-50 items-center justify-center shadow-sm">
                    <span class="text-sm font-extrabold text-emerald-700">04</span>
                </div>
                {{-- Grafic stânga --}}
                <div class="hidden lg:flex items-center lg:pr-16">
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm w-full">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">10 straturi de verificare</p>
                        <div class="space-y-1.5">
                            @foreach(['Base Prompt', 'Politica conversației', 'Context produse + KB', 'Reguli comenzi', 'Stil răspuns', 'Query Intelligence', 'Strategia conversației', 'Nivel de confidență', 'Detector de frustrare', 'Anti-halucinare'] as $i => $layer)
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-mono text-slate-400 w-4 text-right">{{ $i + 1 }}</span>
                                <div class="flex-1 h-1.5 rounded-full {{ $i === 9 ? 'bg-red-200' : 'bg-slate-100' }}">
                                    <div class="h-full rounded-full {{ $i === 9 ? 'bg-red-500' : 'bg-emerald-400' }}" style="width: {{ 100 - $i * 3 }}%"></div>
                                </div>
                                <span class="text-[9px] text-slate-500 w-28 truncate">{{ $layer }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                {{-- Content dreapta --}}
                <div class="lg:pl-16">
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300">
                        <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                            PROMPT BUILDER 10 LAYERS
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Răspunde precis, fără erori</h3>
                        <p class="text-slate-600 leading-relaxed mb-5">Fiecare răspuns trece prin 10 straturi de verificare și formatare. De la tonul brandului tău la reguli de business, de la detectarea frustrării la anti-halucinare — nimic nu scapă neverificat.</p>
                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                            <p class="text-sm font-semibold text-emerald-800 mb-1">🛡️ Garanție anti-halucinare</p>
                            <p class="text-xs text-emerald-600 leading-relaxed">Dacă AI-ul nu are informația în baza ta de cunoștințe, spune clar că nu știe și sugerează o alternativă. Nu inventează niciodată prețuri, termene sau specificații.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- CAPABILITĂȚI — dark section, premium --}}
{{-- ============================================================== --}}
<section class="bg-slate-950 text-white relative overflow-hidden">
    {{-- Subtle motif --}}
    <div class="absolute inset-0 opacity-[0.02]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="cap-motif" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M30 10 L40 20 L30 30 L20 20 Z" fill="white"/></pattern></defs><rect width="100%" height="100%" fill="url(#cap-motif)"/></svg>
    </div>

    <div class="container-custom section-padding relative z-10">
        <div class="text-center mb-16">
            <p class="text-sm font-semibold text-red-400 tracking-widest uppercase mb-3">Capabilități complete</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-5 tracking-tight">Tot ce știe să facă</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-6xl mx-auto">
            @php
            $capabilities = [
                ['title' => 'Bază de cunoștințe inteligentă', 'desc' => 'PDF, DOCX, CSV, URL-uri — AI-ul procesează tot și organizează automat. FAQ generate cu un click.', 'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
                ['title' => 'Voce naturală în română', 'desc' => 'Conversații telefonice cu voce realistă. Numere românești, transcriere live, analiză de sentiment.', 'icon' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m-4-4h8m-4-14a3 3 0 013 3v4a3 3 0 01-6 0V7a3 3 0 013-3z'],
                ['title' => 'Chat widget premium', 'desc' => 'Dark mode, carduri produse, link preview, asistență proactivă pe pagini de produs. O linie de cod.', 'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
                ['title' => 'E-commerce nativ', 'desc' => 'Sincronizare produse, căutare semantică, tracking comenzi, add-to-cart, funnel de conversie complet.', 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z'],
                ['title' => 'Analytics & health score', 'desc' => 'Dashboard live, scor de sănătate per bot, analiza gap-urilor, recomandări automate de conținut.', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                ['title' => 'Pipeline de lead-uri', 'desc' => 'Captare automată, scoring, pipeline CRM complet: nou → contactat → programat → câștigat.', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
            ];
            @endphp

            @foreach($capabilities as $cap)
            <div class="bg-white/[0.04] rounded-2xl p-6 border border-white/[0.06] hover:border-red-500/20 hover:bg-white/[0.06] transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $cap['icon'] }}"/></svg>
                </div>
                <h3 class="text-base font-bold text-white mb-2">{{ $cap['title'] }}</h3>
                <p class="text-sm text-slate-400 leading-relaxed">{{ $cap['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- CAZURI DE UTILIZARE --}}
{{-- ============================================================== --}}
<section class="bg-white">
    <div class="container-custom section-padding">
        <div class="text-center mb-16">
            <x-motif-divider class="mb-8" />
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Pentru orice tip de afacere</h2>
            <p class="text-lg text-slate-500 max-w-2xl mx-auto">E-commerce, servicii, sau ambele — Sambla se adaptează la specificul tău.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            @php
            $cases = [
                ['title' => 'Magazine online', 'color' => 'red', 'features' => ['Căutare produse cu AI', 'Verificare stoc & comandă', 'Add-to-cart din chat', 'Tracking AWB automat', 'Funnel conversie complet']],
                ['title' => 'Firme de servicii', 'color' => 'blue', 'features' => ['Programări & callback-uri', 'Captare lead-uri automat', 'Prezentare servicii & prețuri', 'Pipeline vânzări CRM', 'Escaladare la operator']],
                ['title' => 'Afaceri mixte', 'color' => 'purple', 'features' => ['Produse + servicii în același bot', 'Cross-sell: produs → serviciu', 'Recomandări per proiect', 'Estimări & consultanță AI', 'Toate funcțiile combinate']],
            ];
            @endphp

            @foreach($cases as $case)
            <div class="rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl transition-all duration-300">
                <div class="h-1.5 bg-gradient-to-r from-{{ $case['color'] }}-600 to-{{ $case['color'] }}-400"></div>
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">{{ $case['title'] }}</h3>
                    <ul class="space-y-2.5">
                        @foreach($case['features'] as $f)
                        <li class="flex items-start gap-2 text-sm text-slate-600">
                            <svg class="w-4 h-4 text-{{ $case['color'] }}-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $f }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- 3 PAȘI --}}
{{-- ============================================================== --}}
<section class="bg-slate-50">
    <div class="container-custom section-padding">
        <div class="text-center mb-16">
            <p class="text-sm font-semibold text-red-700 tracking-widest uppercase mb-3">Simplu și rapid</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">De la zero la AI live pe site</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-10 max-w-4xl mx-auto">
            @php
            $onboard = [
                ['num' => '1', 'title' => 'Spune-i despre afacerea ta', 'desc' => 'Descrie afacerea în 2 propoziții. AI-ul generează automat prompt-ul, personalitatea și setările optime.', 'time' => '2 min'],
                ['num' => '2', 'title' => 'Adaugă informațiile tale', 'desc' => 'Uploadează documente, scanează site-ul, conectează magazinul. Bot-ul învață totul în câteva minute.', 'time' => '5 min'],
                ['num' => '3', 'title' => 'Activează și monitorizează', 'desc' => 'O linie de cod pe site. Gata. Bot-ul răspunde 24/7, iar tu monitorizezi din dashboard.', 'time' => '1 min'],
            ];
            @endphp

            @foreach($onboard as $step)
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <span class="text-2xl font-extrabold text-red-700">{{ $step['num'] }}</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm text-slate-500 leading-relaxed mb-3">{{ $step['desc'] }}</p>
                <span class="text-xs font-bold text-red-700 bg-red-50 px-3 py-1 rounded-full">~ {{ $step['time'] }}</span>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-14">
            <a href="/register" class="btn-primary text-base px-10 py-4">Începe gratuit — fără card de credit</a>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- AUTO-LEARNING SECTION --}}
{{-- ============================================================== --}}
<section class="bg-white">
    <div class="container-custom section-padding">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <p class="text-sm font-semibold text-red-700 tracking-widest uppercase mb-3">Inteligență adaptivă</p>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6 tracking-tight">Se face mai deștept<br>în fiecare zi</h2>
                <p class="text-lg text-slate-500 mb-8">Sambla nu așteaptă să-i spui ce nu știe. Descoperă singur, te anunță, și sugerează soluția.</p>

                <div class="space-y-5">
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 mt-0.5"><svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <div>
                            <h4 class="font-bold text-slate-900 mb-1">Detectează întrebările fără răspuns</h4>
                            <p class="text-sm text-slate-500">"12 clienți au întrebat despre retur, dar nu ai conținut. Vrei să generez un draft?"</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center shrink-0 mt-0.5"><svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg></div>
                        <div>
                            <h4 class="font-bold text-slate-900 mb-1">Generează conținut automat</h4>
                            <p class="text-sm text-slate-500">AI-ul scrie un draft de politică de retur bazat pe întrebările reale. Tu doar aprobi.</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 mt-0.5"><svg class="w-5 h-5 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg></div>
                        <div>
                            <h4 class="font-bold text-slate-900 mb-1">Monitorizează calitatea zilnic</h4>
                            <p class="text-sm text-slate-500">Health score, rată de rezolvare, frustrare detectată — totul într-un dashboard clar.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Visual: Knowledge gaps mockup --}}
            <div class="hidden lg:block">
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6 shadow-lg relative overflow-hidden">
                    {{-- Decorative diamond --}}
                    <div class="absolute -top-6 -right-6 w-24 h-24 opacity-[0.04]">
                        <svg viewBox="0 0 80 80" fill="#991b1b"><path d="M40 0 L60 40 L40 80 L20 40 Z"/></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center"><svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <span class="font-semibold text-sm text-slate-800">Ce nu știe bot-ul tău</span>
                        <span class="ml-auto text-xs text-slate-400">Ultima săptămână</span>
                    </div>
                    <div class="space-y-2.5">
                        <div class="bg-white rounded-xl border border-slate-200 p-3.5 flex items-center justify-between hover:border-red-200 transition-colors">
                            <div><p class="text-sm font-medium text-slate-800">🚚 Politica de livrare</p><p class="text-[11px] text-slate-400 mt-0.5">8 întrebări fără răspuns</p></div>
                            <span class="text-[11px] font-bold text-red-700 bg-red-50 px-3 py-1.5 rounded-lg cursor-pointer hover:bg-red-100 transition-colors">Generează cu AI</span>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 p-3.5 flex items-center justify-between hover:border-red-200 transition-colors">
                            <div><p class="text-sm font-medium text-slate-800">🔄 Politica de retur</p><p class="text-[11px] text-slate-400 mt-0.5">5 întrebări fără răspuns</p></div>
                            <span class="text-[11px] font-bold text-red-700 bg-red-50 px-3 py-1.5 rounded-lg cursor-pointer hover:bg-red-100 transition-colors">Generează cu AI</span>
                        </div>
                        <div class="bg-white rounded-xl border border-green-200 p-3.5 flex items-center justify-between">
                            <div><p class="text-sm font-medium text-slate-800">💳 Metode de plată</p><p class="text-[11px] text-slate-400 mt-0.5">Rezolvat acum 2 zile</p></div>
                            <span class="text-[11px] font-bold text-green-700 bg-green-50 px-3 py-1.5 rounded-lg">✓ Adăugat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- DEMO LIVE --}}
{{-- ============================================================== --}}
<x-motif-border />
<section id="demo" class="bg-white">
    <div class="container-custom section-padding">
        <div class="max-w-5xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- Left: Context + sugestii smart --}}
                <div>
                    <p class="text-sm font-semibold text-red-700 tracking-widest uppercase mb-3">Demo live</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 tracking-tight">Vorbește cu Sambla AI</h2>
                    <p class="text-base text-slate-500 mb-8 leading-relaxed">Bot real, conectat live. Întreabă orice sau alege o sugestie de mai jos.</p>

                    <div class="space-y-3">
                        <button onclick="askDemo('Ce funcționalități are Sambla?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-md transition-all duration-300">
                            <span class="text-2xl shrink-0">💡</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900">Ce funcționalități are?</p>
                                <p class="text-xs text-slate-400 mt-0.5">Descoperă tot ce poate face platforma</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 shrink-0 group-hover:text-red-500 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>

                        <button onclick="askDemo('Cât costă platforma?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-md transition-all duration-300">
                            <span class="text-2xl shrink-0">💰</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900">Cât costă?</p>
                                <p class="text-xs text-slate-400 mt-0.5">Planuri și prețuri transparente</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 shrink-0 group-hover:text-red-500 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>

                        <button onclick="askDemo('Cum se integrează cu magazinul meu?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-md transition-all duration-300">
                            <span class="text-2xl shrink-0">🔗</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900">Cum se integrează?</p>
                                <p class="text-xs text-slate-400 mt-0.5">WooCommerce, WordPress, API</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 shrink-0 group-hover:text-red-500 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>

                        <button onclick="askDemo('Funcționează și pe telefon?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-md transition-all duration-300">
                            <span class="text-2xl shrink-0">📞</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900">Merge și pe telefon?</p>
                                <p class="text-xs text-slate-400 mt-0.5">Voce AI naturală în limba română</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 shrink-0 group-hover:text-red-500 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Right: Chat widget --}}
                <div class="relative">
                    <div class="absolute -inset-4 bg-red-100/30 rounded-[28px] blur-2xl"></div>

                    <div class="relative bg-white rounded-[20px] overflow-hidden" style="box-shadow: 0 20px 60px rgba(0,0,0,0.1), 0 4px 20px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.04);">
                        {{-- Header --}}
                        <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-4 relative">
                            <div class="absolute inset-0 opacity-[0.08]">
                                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dm2" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M10 2 L14 6 L10 10 L6 6 Z" fill="white"/></pattern></defs><rect width="100%" height="100%" fill="url(#dm2)"/></svg>
                            </div>
                            <div class="relative flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full ring-2 ring-white/20 overflow-hidden shrink-0" style="background: linear-gradient(135deg, #fecaca, #fef2f2);">
                                    <svg viewBox="0 0 40 40" fill="none" class="w-full h-full">
                                        <circle cx="20" cy="18" r="10" fill="#991b1b" opacity="0.9"/>
                                        <circle cx="16" cy="16" r="1.8" fill="white"/><circle cx="24" cy="16" r="1.8" fill="white"/>
                                        <circle cx="16.5" cy="16" r="0.8" fill="#1e293b"/><circle cx="24.5" cy="16" r="0.8" fill="#1e293b"/>
                                        <path d="M16 21 Q20 24 24 21" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                                        <line x1="20" y1="8" x2="20" y2="5" stroke="#991b1b" stroke-width="1.5" stroke-linecap="round"/>
                                        <circle cx="20" cy="4" r="1.5" fill="#fbbf24"><animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite"/></circle>
                                        <rect x="14" y="28" width="12" height="6" rx="3" fill="#991b1b" opacity="0.7"/>
                                        <path d="M30 14 Q33 18 30 22" stroke="white" stroke-width="1" fill="none" opacity="0.5" stroke-linecap="round"><animate attributeName="opacity" values="0.5;0;0.5" dur="1.5s" repeatCount="indefinite"/></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-white font-bold text-[15px]">Sambla AI</p>
                                    <p class="text-white/60 text-xs flex items-center gap-1.5">
                                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span></span>
                                        Online — răspunde live
                                    </p>
                                </div>
                                <span class="ml-auto text-[10px] font-bold text-white/60 bg-white/10 px-3 py-1 rounded-full">DEMO LIVE</span>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div id="chatMessages" class="h-[380px] overflow-y-auto px-5 py-5 bg-white space-y-3.5">
                            {{-- Greeting --}}
                            <div class="flex gap-2.5">
                                <div class="bg-slate-100 rounded-2xl rounded-tl-md px-5 py-3 max-w-[85%]">
                                    <p class="text-sm text-slate-800 leading-relaxed">Bună! 👋 Sunt Sambla AI. Întreabă-mă orice despre platformă sau alege o sugestie din stânga.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Input --}}
                        <div class="border-t border-slate-100 px-5 py-4 bg-slate-50">
                            <form id="chatForm" class="flex gap-2">
                                <input type="text" id="chatInput" placeholder="Scrie un mesaj..." class="flex-1 rounded-full border border-slate-200 bg-white px-5 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 transition-all" autocomplete="off"/>
                                <button type="submit" class="w-11 h-11 flex items-center justify-center bg-gradient-to-br from-red-600 to-red-700 text-white rounded-full hover:from-red-500 hover:to-red-600 transition-all shadow-md shadow-red-200 hover:shadow-lg hover:shadow-red-300 active:scale-95">
                                    <svg class="w-[18px] h-[18px] -rotate-45" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- INDUSTRII — vizual, colorat, cu iconuri --}}
{{-- ============================================================== --}}
<section class="bg-slate-50 border-y border-slate-100">
    <div class="container-custom py-16 lg:py-20">
        <div class="text-center mb-10">
            <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mb-2">Folosit de afaceri din toate industriile</h3>
            <p class="text-sm text-slate-500">Un singur AI, adaptat pentru orice domeniu de activitate</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 max-w-6xl mx-auto">
            {{-- E-commerce --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-red-200 hover:shadow-lg hover:shadow-red-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">E-commerce</span>
            </div>
            {{-- Servicii --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Servicii</span>
            </div>
            {{-- Sănătate --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Sănătate</span>
            </div>
            {{-- Educație --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-purple-200 hover:shadow-lg hover:shadow-purple-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Educație</span>
            </div>
            {{-- Logistică --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Logistică</span>
            </div>
            {{-- Auto --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-sky-200 hover:shadow-lg hover:shadow-sky-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.1-3.22a.71.71 0 010-1.22l5.1-3.22a.71.71 0 011.08.61v6.44a.71.71 0 01-1.08.61z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Auto</span>
            </div>
            {{-- Retail --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-pink-200 hover:shadow-lg hover:shadow-pink-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Retail</span>
            </div>
            {{-- Construcții --}}
            <div class="group flex flex-col items-center gap-2.5 p-4 rounded-2xl bg-white border border-slate-200 hover:border-orange-200 hover:shadow-lg hover:shadow-orange-50 transition-all duration-300 cursor-default">
                <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 text-center">Construcții</span>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- TESTIMONIALE --}}
{{-- ============================================================== --}}
<section class="bg-slate-50">
    <div class="container-custom section-padding">
        <div class="text-center mb-14">
            <x-motif-divider class="mb-8" />
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 tracking-tight">Ce spun utilizatorii</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl mx-auto">
            @php
            $testimonials = [
                ['q' => 'Am redus timpul de răspuns cu 80%. Clienții primesc răspunsuri instant, chiar și la 3 dimineața.', 'r' => 'CEO, industria tech', 'i' => 'MI', 'c' => 'bg-red-700'],
                ['q' => 'ROI vizibil din prima lună. Echipa de suport se concentrează pe cazurile complexe.', 'r' => 'Director Operațiuni, servicii financiare', 'i' => 'AP', 'c' => 'bg-slate-700'],
                ['q' => 'Calitatea vocii e remarcabilă. Mulți clienți nu realizează că vorbesc cu AI.', 'r' => 'Manager Call Center, sănătate', 'i' => 'ED', 'c' => 'bg-emerald-600'],
                ['q' => 'Ne-a eliberat 40 de ore pe săptămână din suportul manual. Perfect pentru programări și FAQ.', 'r' => 'Fondator, educație online', 'i' => 'AG', 'c' => 'bg-amber-600'],
                ['q' => 'Funcția de detectare a gap-urilor e genială. Primim sugestii săptămânale despre ce conținut să adăugăm.', 'r' => 'E-commerce Manager, retail', 'i' => 'CR', 'c' => 'bg-blue-600'],
                ['q' => 'Analiza sentimentului ne ajută să identificăm clienții nemulțumiți înainte să îi pierdem.', 'r' => 'VP Sales, logistică', 'i' => 'MS', 'c' => 'bg-purple-600'],
            ];
            @endphp

            @foreach($testimonials as $t)
            <div class="bg-white rounded-2xl p-5 border border-slate-200 hover:shadow-lg transition-all duration-300">
                <div class="flex gap-0.5 mb-3">@for($i=0;$i<5;$i++)<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor</div>
                <p class="text-slate-600 text-sm leading-relaxed mb-4">"{{ $t['q'] }}"</p>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full {{ $t['c'] }} flex items-center justify-center"><span class="text-white text-[10px] font-bold">{{ $t['i'] }}</span></div>
                    <p class="text-[11px] text-slate-500">{{ $t['r'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<x-cta-section
    title="Transformă fiecare conversație<br>într-o oportunitate de vânzare"
    subtitle="Configurezi în 10 minute. Primele rezultate din prima zi."
/>

@endsection

@push('scripts')
{{-- Hero rotating headline --}}
<script>
(function() {
    var el = document.getElementById('heroRotatingText');
    if (!el) return;
    var phrases = [
        'știe totul despre afacerea ta',
        'răspunde clienților 24/7',
        'vorbește natural în română',
        'nu uită niciodată un detaliu',
        'vinde în locul tău, non-stop',
        'învață singur din conversații',
        'îți crește vânzările în somn',
        'știe fiecare produs și preț',
        'preia apeluri ca un profesionist',
        'transformă vizitatori în clienți',
    ];
    var idx = 0;
    setInterval(function() {
        el.style.opacity = '0';
        setTimeout(function() {
            idx = (idx + 1) % phrases.length;
            el.textContent = phrases[idx];
            el.style.opacity = '1';
        }, 800);
    }, 8000);
})();
</script>

{{-- Hero conversation animation --}}
<script>
(function() {
    var scenarios = [
        // === CASUAL / FRIENDLY — magazine, retail ===
        {
            label: '🛒 Magazin online',
            footer: '✓ 3 produse găsite + carduri afișate',
            messages: [
                { user: false, text: 'Hei! 👋 Cu ce te pot ajuta?' },
                { user: true, text: 'Caut un laptop pentru birou' },
                { user: false, text: 'Am găsit câteva opțiuni excelente! 💻', cards: [
                    { name: 'Laptop Pro 15"', price: '2.899', oldPrice: '3.299', tag: '-12%', icon: '💻', color: '#eff6ff' },
                    { name: 'Laptop Air 14"', price: '2.199', oldPrice: null, tag: 'Popular', icon: '⚡', color: '#fefce8' },
                    { name: 'Laptop Work 16"', price: '2.649', oldPrice: '2.999', tag: '-11%', icon: '🖥️', color: '#f0fdf4' },
                ]},
                { user: true, text: 'Îl vreau pe primul!' },
                { user: false, text: 'Excelentă alegere! ✅ Te ajut cu comanda — am nevoie de nume și telefon.' },
            ]
        },
        {
            label: '📦 Tracking comandă',
            footer: '✓ Status comandă verificat live',
            messages: [
                { user: false, text: 'Bună! 😊 Cu ce te ajut?' },
                { user: true, text: 'Unde e comanda mea #4521?' },
                { user: false, text: 'Comanda #4521 e în drum spre tine! 📦 FanCourier, AWB: 2150847631 — ajunge mâine 10-14.' },
                { user: true, text: 'Super, mersi!' },
                { user: false, text: 'Cu plăcere! Dacă mai ai nevoie de ceva, sunt aici 😊' },
            ]
        },
        {
            label: '🏷️ Promoții',
            footer: '✓ Oferte active + carduri cu reduceri',
            messages: [
                { user: false, text: 'Hei! 👋 Cu ce te pot ajuta?' },
                { user: true, text: 'Aveți ceva reduceri acum?' },
                { user: false, text: 'Da! 🏷️ Iată ofertele active:', cards: [
                    { name: 'Vopsea Premium 10L', price: '89', oldPrice: '129', tag: '-30%', icon: '🪣', color: '#fef2f2' },
                    { name: 'Gresie 60x60 Bej', price: '39/m²', oldPrice: '49/m²', tag: '-20%', icon: '🏠', color: '#fff7ed' },
                    { name: 'Set Pensule Pro', price: '35', oldPrice: '55', tag: '-36%', icon: '🖌️', color: '#f0fdf4' },
                ]},
                { user: true, text: 'Comand vopseaua!' },
                { user: false, text: 'Excelent! ✅ Adăugată în coș. Vrei și amorsă? E perfectă cu această vopsea.' },
            ]
        },
        {
            label: '🔧 Service auto',
            footer: '✓ Programare service + estimare preț',
            messages: [
                { user: false, text: 'Bună! 🔧 Cu ce te pot ajuta?' },
                { user: true, text: 'Trebuie să schimb uleiul la mașină' },
                { user: false, text: 'Ce model de mașină ai? Pregătim exact uleiul și filtrul potrivit 🚗' },
                { user: true, text: 'SUV diesel, din 2020' },
                { user: false, text: 'SUV diesel 2020 — schimb ulei + filtru: 280 lei ✅ Durează ~45 min. Când vii?' },
            ]
        },
        {
            label: '💡 Recomandări renovare',
            footer: '✓ Recomandări AI + carduri produse',
            messages: [
                { user: false, text: 'Hei! 👋 Cu ce proiect te ajut?' },
                { user: true, text: 'Vreau să renovez baia' },
                { user: false, text: 'Super proiect! 🛁 Iată ce ai nevoie:', cards: [
                    { name: 'Adeziv Gresie Pro', price: '45', oldPrice: null, tag: 'Top', icon: '🧱', color: '#fff7ed' },
                    { name: 'Silicon Sanitar', price: '22', oldPrice: '29', tag: '-24%', icon: '💧', color: '#eff6ff' },
                    { name: 'Chit Rosturi Alb', price: '18', oldPrice: null, tag: 'Stoc ✓', icon: '🎨', color: '#faf5ff' },
                ]},
                { user: true, text: 'Le iau pe toate!' },
                { user: false, text: 'Adăugat în coș! 🛒 Total: 85 lei. Vrei să finalizezi comanda?' },
            ]
        },
        {
            label: '🔄 Retur produs',
            footer: '✓ Răspuns din politica de retur',
            messages: [
                { user: false, text: 'Bună! 😊 Întreabă-mă orice.' },
                { user: true, text: 'Pot returna un produs deschis?' },
                { user: false, text: 'Da, acceptăm retururi în 14 zile ✅ Chiar și desigilate, dacă sunt nefolosite.' },
                { user: true, text: 'Cine plătește transportul?' },
                { user: false, text: 'Produs defect = noi 🙌 Altfel e 15 lei. Vrei să inițiez un retur?' },
            ]
        },
        // === PROFESIONAL — cabinete, clinici ===
        {
            label: '⚖️ Cabinet avocat',
            footer: '✓ Programare consultație juridică',
            messages: [
                { user: false, text: 'Bună ziua. Sunt asistentul virtual al cabinetului de avocatură. Cu ce vă pot ajuta?' },
                { user: true, text: 'Am nevoie de consultanță pentru un litigiu comercial' },
                { user: false, text: 'Înțeleg. Cabinetul nostru acoperă litigii comerciale, inclusiv recuperări creanțe și dispute contractuale. Doriți o programare?' },
                { user: true, text: 'Da, cât mai curând posibil' },
                { user: false, text: 'Cel mai apropiat slot disponibil este miercuri, ora 14:00, cu av. Popescu. Consultația inițială durează 45 minute. Vă rog să indicați numele complet și un număr de telefon.' },
            ]
        },
        {
            label: '🏥 Clinică medicală',
            footer: '✓ Programare medicală confirmată',
            messages: [
                { user: false, text: 'Bună ziua. Bine ați venit la clinica noastră. Cum vă pot ajuta?' },
                { user: true, text: 'Aș dori o programare la dermatologie' },
                { user: false, text: 'Desigur. Avem disponibilitate la dr. Ionescu, medic specialist dermatolog. Preferați dimineața sau după-amiaza?' },
                { user: true, text: 'Dimineața, luni dacă se poate' },
                { user: false, text: 'Am rezervat: luni, ora 10:00, dr. Ionescu — consultație dermatologică. Vă rog să confirmați cu numele complet și numărul de telefon.' },
            ]
        },
        {
            label: '📊 Cabinet contabilitate',
            footer: '✓ Informații servicii contabile',
            messages: [
                { user: false, text: 'Bună ziua. Cu ce vă putem ajuta legat de serviciile noastre contabile?' },
                { user: true, text: 'Am un SRL nou și am nevoie de contabilitate lunară' },
                { user: false, text: 'Felicitări pentru noul SRL. Pachetul nostru pentru micro-întreprinderi include: contabilitate lunară, declarații fiscale și consultanță. Tariful pornește de la 500 lei/lună.' },
                { user: true, text: 'Ce documente aveți nevoie la început?' },
                { user: false, text: 'Vom avea nevoie de: certificatul de înregistrare, actul constitutiv și actele de identitate ale asociaților. Doriți să programăm o întâlnire pentru a discuta detaliile?' },
            ]
        },
        {
            label: '🏢 Agenție imobiliară',
            footer: '✓ Filtrare proprietăți + vizionare',
            messages: [
                { user: false, text: 'Bună ziua. Căutați un imobil de vânzare sau închiriere?' },
                { user: true, text: 'Caut un apartament cu 2 camere de cumpărat în Cluj' },
                { user: false, text: 'Am 12 apartamente disponibile în Cluj-Napoca, zona centrală și Mărăști. Bugetul estimat?' },
                { user: true, text: 'Până în 95.000 euro' },
                { user: false, text: 'Am identificat 4 proprietăți în buget. Cea mai potrivită: 2 camere, 58mp, Mărăști, etaj 3, 89.000€. Doriți să programăm o vizionare?' },
            ]
        },
        {
            label: '🦷 Cabinet stomatologic',
            footer: '✓ Programare + informații tratament',
            messages: [
                { user: false, text: 'Bună ziua. Cum vă putem ajuta la clinica noastră dentară?' },
                { user: true, text: 'Am o urgență, mă doare o măsea' },
                { user: false, text: 'Înțeleg, urgențele dentare au prioritate. Avem un loc disponibil astăzi la ora 16:30 cu dr. Marin. Puteți ajunge?' },
                { user: true, text: 'Da, ajung. Cât costă consultația?' },
                { user: false, text: 'Consultația de urgență este 150 lei, include și radiografia. V-am notat la 16:30. Vă rog să aveți la dumneavoastră buletinul.' },
            ]
        },
        {
            label: '🎓 Centru educațional',
            footer: '✓ Informații cursuri + înscriere',
            messages: [
                { user: false, text: 'Bună ziua! Te interesează un curs din oferta noastră?' },
                { user: true, text: 'Ce cursuri de programare aveți?' },
                { user: false, text: 'Avem 3 cursuri active: Web Development, Python și Data Science — toate de la nivel începător, cu certificare la final.' },
                { user: true, text: 'Cât durează cel de Python?' },
                { user: false, text: '12 săptămâni, 2 sesiuni pe săptămână. Următoarea serie începe pe 15 aprilie. Tarif: 1.800 lei. Doriți să vă înscrieți?' },
            ]
        },
    ];

    // Shuffle — ordine aleatorie la fiecare ciclu
    function shuffle(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
        }
        return arr;
    }
    shuffle(scenarios);

    var container = document.getElementById('heroChatInner');
    var typingEl = document.getElementById('heroTyping');
    var labelEl = document.getElementById('heroScenarioLabel');
    var footerEl = document.getElementById('heroFooterText');
    if (!container) return;

    var current = 0;
    var charSpeed = 25; // ms per character (typing speed)
    var activeTimers = []; // track all timeouts so we can cancel on swipe
    var activeIntervals = []; // track all intervals
    var generation = 0; // incremented on each scenario start to invalidate stale callbacks

    function trackedTimeout(fn, delay) {
        var id = setTimeout(fn, delay);
        activeTimers.push(id);
        return id;
    }

    function trackedInterval(fn, delay) {
        var id = setInterval(fn, delay);
        activeIntervals.push(id);
        return id;
    }

    function cancelAllAnimations() {
        activeTimers.forEach(clearTimeout);
        activeIntervals.forEach(clearInterval);
        activeTimers = [];
        activeIntervals = [];
        generation++;
        typingEl.classList.add('hidden');
        typingEl.classList.remove('flex');
    }

    function typeText(element, text, callback) {
        var i = 0;
        var gen = generation;
        element.textContent = '';
        var timer = trackedInterval(function() {
            if (gen !== generation) { clearInterval(timer); return; }
            element.textContent += text.charAt(i);
            i++;
            if (i >= text.length) {
                clearInterval(timer);
                if (callback) trackedTimeout(callback, 400);
            }
        }, charSpeed);
    }

    function addBubble(msg, callback) {
        // Show typing indicator first for bot messages
        if (!msg.user) {
            typingEl.classList.remove('hidden');
            typingEl.classList.add('flex');
            trackedTimeout(function() {
                typingEl.classList.add('hidden');
                typingEl.classList.remove('flex');
                renderBubble(msg, callback);
            }, 600 + Math.random() * 400);
        } else {
            renderBubble(msg, callback);
        }
    }

    function renderBubble(msg, callback) {
        var wrap = document.createElement('div');
        wrap.className = msg.user ? 'flex gap-2 justify-end' : 'flex gap-2';
        wrap.style.opacity = '0';
        wrap.style.transform = 'translateY(8px)';
        wrap.style.transition = 'opacity 0.3s, transform 0.3s';

        var bubbleCls = msg.user
            ? 'bg-gradient-to-br from-red-600 to-red-700 rounded-2xl rounded-tr-md px-5 py-3 max-w-[85%] shadow-sm'
            : 'bg-slate-100 rounded-2xl rounded-tl-md px-5 py-3 max-w-[85%]';
        var textCls = msg.user ? 'text-white' : 'text-slate-800';

        var bubble = document.createElement('div');
        bubble.className = bubbleCls;

        var p = document.createElement('p');
        p.className = 'text-sm leading-relaxed ' + textCls;
        bubble.appendChild(p);

        // Product cards — 2026 design
        if (msg.cards && msg.cards.length > 0) {
            var cardsWrap = document.createElement('div');
            cardsWrap.style.cssText = 'display:flex;gap:10px;margin-top:14px;overflow-x:auto;padding-bottom:4px;scrollbar-width:none;-ms-overflow-style:none;';

            msg.cards.forEach(function(card) {
                var isDiscount = card.tag && card.tag.startsWith('-');
                var bgColor = card.color || '#f8fafc';

                var c = document.createElement('div');
                c.style.cssText = 'min-width:120px;width:120px;background:#fff;border-radius:16px;flex-shrink:0;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06),0 0 0 1px rgba(0,0,0,0.03);transition:transform 0.2s,box-shadow 0.2s;';
                c.onmouseenter = function(){ c.style.transform='translateY(-2px)';c.style.boxShadow='0 6px 20px rgba(0,0,0,0.1),0 0 0 1px rgba(0,0,0,0.04)'; };
                c.onmouseleave = function(){ c.style.transform='translateY(0)';c.style.boxShadow='0 2px 12px rgba(0,0,0,0.06),0 0 0 1px rgba(0,0,0,0.03)'; };

                c.innerHTML = ''
                    // Icon zona — emoji mare pe fundal pastel
                    + '<div style="background:' + bgColor + ';padding:14px 10px 10px;text-align:center;position:relative;">'
                    +   '<div style="font-size:32px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.1));">' + (card.icon || '📦') + '</div>'
                    +   (isDiscount
                          ? '<span style="position:absolute;top:6px;right:6px;font-size:10px;font-weight:800;color:#fff;background:linear-gradient(135deg,#dc2626,#ef4444);padding:2px 7px;border-radius:8px;box-shadow:0 2px 6px rgba(220,38,38,0.3);">' + card.tag + '</span>'
                          : '<span style="position:absolute;top:6px;right:6px;font-size:10px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 7px;border-radius:8px;">' + card.tag + '</span>')
                    + '</div>'
                    // Info
                    + '<div style="padding:10px 12px 12px;">'
                    +   '<p style="font-size:12px;font-weight:700;color:#0f172a;line-height:1.3;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + card.name + '</p>'
                    +   '<div style="display:flex;align-items:baseline;gap:5px;margin-bottom:8px;">'
                    +     '<span style="font-size:15px;font-weight:800;color:#991b1b;">' + card.price + '</span>'
                    +     '<span style="font-size:10px;font-weight:600;color:#991b1b;">lei</span>'
                    +     (card.oldPrice ? '<span style="font-size:11px;color:#94a3b8;text-decoration:line-through;margin-left:2px;">' + card.oldPrice + '</span>' : '')
                    +   '</div>'
                    // Buton — vizibil, cu SVG icon alb
                    +   '<div style="display:flex;align-items:center;justify-content:center;gap:5px;width:100%;padding:7px 0;background:linear-gradient(135deg,#991b1b,#dc2626);color:#fff;border-radius:10px;font-size:11px;font-weight:700;letter-spacing:0.2px;box-shadow:0 2px 8px rgba(153,27,27,0.25);cursor:pointer;">'
                    +     '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>'
                    +     'Adaugă'
                    +   '</div>'
                    + '</div>';

                cardsWrap.appendChild(c);
            });
            bubble.appendChild(cardsWrap);
        }

        wrap.appendChild(bubble);
        container.appendChild(wrap);

        // Animate in + auto-scroll
        requestAnimationFrame(function() {
            wrap.style.opacity = '1';
            wrap.style.transform = 'translateY(0)';
            var chatEl = document.getElementById('heroChat');
            if (chatEl) chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
        });

        // Type text, then show cards
        typeText(p, msg.text, function() {
            if (msg.cards) {
                // Cards appear after text finishes
                var cardEls = bubble.querySelectorAll('[style*="min-width:120px"]');
                cardEls.forEach(function(el, idx) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(6px)';
                    el.style.transition = 'opacity 0.3s, transform 0.3s';
                    trackedTimeout(function() {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100 + idx * 120);
                });
                trackedTimeout(callback, 300 + msg.cards.length * 150);
            } else if (callback) {
                callback();
            }
        });
    }

    function playScenario(index) {
        cancelAllAnimations();
        var sc = scenarios[index];
        var gen = generation;
        container.innerHTML = '';

        // Update label and footer
        if (labelEl) {
            labelEl.style.opacity = '0';
            trackedTimeout(function() {
                labelEl.textContent = sc.label;
                labelEl.style.opacity = '1';
            }, 200);
        }

        // Update dots dynamically — make them clickable
        var dotsContainer = document.getElementById('heroDotsContainer');
        if (dotsContainer) {
            if (!dotsContainer.children.length) {
                for (var dd = 0; dd < scenarios.length; dd++) {
                    var dotEl = document.createElement('span');
                    dotEl.className = 'inline-block w-2 h-2 rounded-full transition-all duration-300 cursor-pointer';
                    dotEl.setAttribute('data-index', dd);
                    dotEl.addEventListener('click', function() {
                        var idx = parseInt(this.getAttribute('data-index'));
                        goToScenario(idx);
                    });
                    dotsContainer.appendChild(dotEl);
                }
            }
            for (var d = 0; d < dotsContainer.children.length; d++) {
                dotsContainer.children[d].style.backgroundColor = d === index ? '#991b1b' : '#e2e8f0';
                dotsContainer.children[d].style.transform = d === index ? 'scale(1.4)' : 'scale(1)';
            }
        }

        // Play messages sequentially
        var msgIndex = 0;
        function playNext() {
            if (gen !== generation) return; // cancelled by swipe
            if (msgIndex >= sc.messages.length) {
                // Update footer after conversation ends
                if (footerEl) footerEl.textContent = sc.footer;
                // Wait then play next scenario
                trackedTimeout(function() {
                    if (gen !== generation) return;
                    current = current + 1;
                    if (current >= scenarios.length) {
                        current = 0;
                        shuffle(scenarios);
                    }
                    playScenario(current);
                }, 3500);
                return;
            }
            var delay = msgIndex === 0 ? 300 : (sc.messages[msgIndex].user ? 800 : 200);
            trackedTimeout(function() {
                if (gen !== generation) return;
                addBubble(sc.messages[msgIndex], function() {
                    msgIndex++;
                    playNext();
                });
            }, delay);
        }
        playNext();
    }

    // Navigate to a specific scenario (used by swipe + dot click)
    function goToScenario(index) {
        if (index < 0) index = scenarios.length - 1;
        if (index >= scenarios.length) index = 0;
        current = index;
        playScenario(current);
    }

    // Touch swipe support for mobile
    var chatEl = document.getElementById('heroChat');
    if (chatEl) {
        var touchStartX = 0;
        var touchStartY = 0;
        var isSwiping = false;

        chatEl.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            isSwiping = false;
        }, { passive: true });

        chatEl.addEventListener('touchmove', function(e) {
            if (!touchStartX) return;
            var dx = e.touches[0].clientX - touchStartX;
            var dy = e.touches[0].clientY - touchStartY;
            // Only count as swipe if horizontal movement > vertical
            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 20) {
                isSwiping = true;
                e.preventDefault(); // prevent page scroll during horizontal swipe
            }
        }, { passive: false });

        chatEl.addEventListener('touchend', function(e) {
            if (!isSwiping) return;
            var dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 50) {
                if (dx < 0) {
                    // Swipe left → next scenario
                    goToScenario(current + 1);
                } else {
                    // Swipe right → previous scenario
                    goToScenario(current - 1);
                }
            }
            touchStartX = 0;
            touchStartY = 0;
            isSwiping = false;
        }, { passive: true });
    }

    // Start first scenario after page loads
    setTimeout(function() { playScenario(0); }, 1000);
})();
</script>

{{-- Demo chat functionality --}}
<script>
(function() {
    var chatMessages = document.getElementById('chatMessages');
    var chatForm = document.getElementById('chatForm');
    var chatInput = document.getElementById('chatInput');
    var channelId = 2;

    window.askDemo = function(q) { chatInput.value = q; chatForm.dispatchEvent(new Event('submit')); };

    function api(msg, cb) {
        fetch('/api/v1/chatbot/' + channelId + '/message', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ message: msg })
        }).then(function(r) { return r.json(); }).then(function(d) { cb(d.reply || d.response || 'Mulțumesc!'); }).catch(function() { cb('Momentan procesez. Încearcă din nou.'); });
    }

    function add(text, isUser) {
        var w = document.createElement('div');
        w.className = isUser ? 'flex gap-2.5 justify-end' : 'flex gap-2.5';
        w.style.opacity = '0'; w.style.transform = 'translateY(6px)';
        w.style.transition = 'opacity 0.3s ease, transform 0.3s ease';

        if (isUser) {
            w.innerHTML = '<div class="bg-gradient-to-br from-red-600 to-red-700 rounded-2xl rounded-tr-md px-5 py-3 max-w-[85%] shadow-sm"><p class="text-sm text-white leading-relaxed">' + esc(text) + '</p></div>';
        } else {
            w.innerHTML = '<div class="bg-slate-100 rounded-2xl rounded-tl-md px-5 py-3 max-w-[85%]"><p class="text-sm text-slate-800 leading-relaxed">' + text + '</p></div>';
        }

        chatMessages.appendChild(w);
        requestAnimationFrame(function() { w.style.opacity = '1'; w.style.transform = 'translateY(0)'; });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function typing() {
        var w = document.createElement('div'); w.id = 'typing'; w.className = 'flex gap-2.5';
        w.innerHTML = '<div class="bg-slate-100 rounded-2xl rounded-tl-md px-5 py-3"><div class="flex gap-1.5 h-5 items-center"><span class="w-2 h-2 bg-slate-300 rounded-full animate-bounce" style="animation-delay:0ms"></span><span class="w-2 h-2 bg-slate-300 rounded-full animate-bounce" style="animation-delay:150ms"></span><span class="w-2 h-2 bg-slate-300 rounded-full animate-bounce" style="animation-delay:300ms"></span></div></div>';
        chatMessages.appendChild(w); chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function esc(t) { var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault(); var t = chatInput.value.trim(); if (!t) return;
        add(t, true); chatInput.value = ''; chatInput.focus(); typing();
        api(t, function(r) { var el = document.getElementById('typing'); if (el) el.remove(); add(r, false); });
    });
})();
</script>
@endpush
