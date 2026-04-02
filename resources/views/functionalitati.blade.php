@extends('layouts.app')

@section('title', 'Funcționalități - Sambla')
@section('meta_description', 'Descoperă funcționalitățile Sambla: chatbot AI inteligent, apeluri vocale cu AI, bază de cunoștințe RAG, analiză în timp real și API pentru dezvoltatori.')

@section('content')

{{-- ==================== HERO ==================== --}}
<section class="relative overflow-hidden bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="feat-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/><rect x="38" y="2" width="4" height="8" fill="#991b1b"/><rect x="38" y="38" width="4" height="8" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#feat-motif)"/></svg>
    </div>
    <div class="absolute bottom-20 -right-40 w-[400px] h-[400px] bg-red-900/15 rounded-full blur-[120px]"></div>
    <div class="absolute top-20 -left-40 w-[300px] h-[300px] bg-red-800/10 rounded-full blur-[100px]"></div>
    <div class="container-custom text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-900/20 border border-red-700/30 mb-8 animate-fade-in">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-red-300 text-sm font-medium">Toate sistemele funcționale</span>
        </div>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-6 animate-fade-in leading-tight">
            Tot ce ai nevoie pentru<br><span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">comunicare inteligentă cu AI</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed animate-fade-in">
            De la chatbot text pe site-ul tău la apeluri vocale cu inteligență artificială. O singură platformă, o singură bază de cunoștințe, răspunsuri instantanee 24/7.
        </p>
    </div>
</section>
<x-motif-border />

{{-- ==================== CHATBOT AI - SECȚIUNEA PRINCIPALĂ ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center mb-14 lg:mb-20">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-100 text-red-700 text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Chatbot Text AI
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Chatbot-ul care chiar înțelege afacerea ta</h2>
            <p class="text-slate-600 max-w-3xl mx-auto text-lg leading-relaxed">
                Nu e un simplu chatbot cu răspunsuri pre-definite. Sambla folosește modele AI avansate (GPT-4o, Claude) combinate cu baza ta de cunoștințe pentru a oferi răspunsuri precise, personalizate și contextuale.
            </p>
        </div>

        {{-- Grid cu funcționalități chatbot --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">

            {{-- 1. Bază de cunoștințe RAG --}}
            <div class="group p-8 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-xl transition-all duration-300">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                    <svg class="w-7 h-7 text-red-700 group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Sistem RAG cu bază vectorială</h3>
                <p class="text-slate-600 mb-4 leading-relaxed">Căutare hibridă (vectorială + full-text) pe PostgreSQL pgvector, hostat pe servere proprii în România. AI-ul răspunde strict din datele tale.</p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Upload PDF, DOCX, CSV, TXT + scanare URL
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Conector WordPress & WooCommerce
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        pgvector pe servere proprii în România
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Detecție automată knowledge gaps
                    </li>
                </ul>
            </div>

            {{-- 2. Modele AI multiple --}}
            <div class="group p-8 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-xl transition-all duration-300">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                    <svg class="w-7 h-7 text-red-700 group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">AI multi-model inteligent</h3>
                <p class="text-slate-600 mb-4 leading-relaxed">Rutare automată între modele AI (GPT-4o, GPT-4o-mini, Claude) în funcție de complexitatea întrebării. Răspunsuri rapide pentru întrebări simple, analiză profundă pentru cele complexe.</p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Rutare inteligentă între modele
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Fallback automat între provideri
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Cache inteligent pentru eficiență
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Protecție anti-halucinare
                    </li>
                </ul>
            </div>

            {{-- 3. Detecție intenții --}}
            <div class="group p-8 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-xl transition-all duration-300">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                    <svg class="w-7 h-7 text-red-700 group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Detecție avansată de intenții</h3>
                <p class="text-slate-600 mb-4 leading-relaxed">AI-ul detectează automat ce vrea clientul: caută un produs, are o reclamație, vrea să plaseze o comandă sau are nevoie de un operator uman.</p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Scoring multi-intent per mesaj
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Detecție frustrare și sentiment
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Clasificare complexitate query
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Escalare automată către om
                    </li>
                </ul>
            </div>

            {{-- 4. E-Commerce --}}
            <div class="group p-8 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-xl transition-all duration-300">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                    <svg class="w-7 h-7 text-red-700 group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Integrare e-commerce nativă</h3>
                <p class="text-slate-600 mb-4 leading-relaxed">Chatbot-ul afișează produse cu poze și prețuri, permite adăugarea în coș, caută comenzi și oferă tracking automat cu FanCourier, Cargus, DPD, SameDay.</p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Carduri produse cu imagini și prețuri
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Adaugă în coș direct din chat
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Tracking automat AWB (6 curieri)
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Sincronizare WooCommerce completă
                    </li>
                </ul>
            </div>

            {{-- 5. Lead generation --}}
            <div class="group p-8 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-xl transition-all duration-300">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                    <svg class="w-7 h-7 text-red-700 group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Generare lead-uri automat</h3>
                <p class="text-slate-600 mb-4 leading-relaxed">Scoring inteligent bazat pe engagement: număr mesaje, interacțiuni cu produse, intenție de cumpărare. Capturează datele de contact la momentul potrivit, fără a fi agresiv.</p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Lead scoring automat (30+ semnale)
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Pipeline lead cu 7 etape
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Programare callback automat
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Consimțământ GDPR integrat
                    </li>
                </ul>
            </div>

            {{-- 6. Widget personalizabil --}}
            <div class="group p-8 rounded-2xl bg-white border border-slate-200 hover:border-red-300 hover:shadow-xl transition-all duration-300">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mb-6 group-hover:bg-red-700 group-hover:text-white transition-colors duration-300">
                    <svg class="w-7 h-7 text-red-700 group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Widget 100% personalizabil</h3>
                <p class="text-slate-600 mb-4 leading-relaxed">Instalare în 2 minute cu o singură linie de cod. Personalizează culorile, mesajul de bun venit, poziția și comportamentul. Optimizat pentru mobil și desktop.</p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        O linie de cod pentru instalare
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Culori, logo, poziție, greeting
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Sandbox securizat (iframe izolat)
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        WordPress, Shopify, orice site
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ==================== CONVERSAȚIE INTELIGENTĂ - DARK SECTION ==================== --}}
<section class="bg-slate-950 section-padding relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.03]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="conv-grid" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M60 0 L0 0 L0 60" fill="none" stroke="#991b1b" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#conv-grid)"/></svg>
    </div>
    <div class="container-custom relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            {{-- Text --}}
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-900/30 text-red-400 text-sm font-semibold mb-6">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    Personalizare avansată
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Conversații care se adaptează contextului</h2>
                <p class="text-slate-400 text-lg leading-relaxed mb-8">
                    Chatbot-ul Sambla nu e rigid. Își ajustează tonul, nivelul de detaliu și strategia în funcție de ce detectează: un client frustrat primește empatie, un client interesat de produse primește recomandări, iar un client care vrea ajutor rapid primește răspunsuri concise.
                </p>

                <div class="space-y-4">
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-800/50 border border-slate-700/50">
                        <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" /></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-1">Multi-limbă nativ</h4>
                            <p class="text-slate-400 text-sm">Vorbește româna, engleza și alte 8+ limbi. Detectează automat limba clientului și răspunde în aceeași limbă.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-800/50 border border-slate-700/50">
                        <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-1">6 personalități configurabile</h4>
                            <p class="text-slate-400 text-sm">Profesional, cald, premium, prietenos, consultativ sau tehnic. Alege tonul perfect pentru brandul tău. Reglează verbozitatea și agresivitatea CTA-urilor.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-800/50 border border-slate-700/50">
                        <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-1">Detecție frustrare în timp real</h4>
                            <p class="text-slate-400 text-sm">Monitorizează CAPS LOCK, punctuație excesivă (!!!), repetiții și cuvinte cheie de frustrare. Oprește automat vânzarea și trece pe empatie.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-800/50 border border-slate-700/50">
                        <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-1">Reguli de business personalizate</h4>
                            <p class="text-slate-400 text-sm">Definește fraze interzise, vocabular de brand obligatoriu, politici de preț și reguli specifice industriei tale.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Illustration: Chat mockup --}}
            <div class="flex justify-center lg:justify-end">
                <div class="w-full max-w-sm">
                    <div class="rounded-2xl overflow-hidden shadow-2xl border border-slate-700/50">
                        {{-- Chat header --}}
                        <div class="bg-red-800 px-5 py-4 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                            </div>
                            <div>
                                <p class="text-white font-semibold text-sm">Sambla AI</p>
                                <p class="text-red-200 text-xs">Online acum</p>
                            </div>
                        </div>
                        {{-- Chat messages --}}
                        <div class="bg-slate-900 p-4 space-y-3">
                            {{-- Bot message --}}
                            <div class="flex gap-2">
                                <div class="w-6 h-6 rounded-full bg-red-700/30 flex-shrink-0 flex items-center justify-center mt-1">
                                    <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                </div>
                                <div class="bg-slate-800 rounded-xl rounded-tl-sm px-4 py-2.5 max-w-[85%]">
                                    <p class="text-slate-300 text-sm">Bună! Cu ce te pot ajuta azi? Pot căuta produse, verifica o comandă sau răspunde la întrebări.</p>
                                </div>
                            </div>
                            {{-- User message --}}
                            <div class="flex justify-end">
                                <div class="bg-red-800 rounded-xl rounded-tr-sm px-4 py-2.5 max-w-[85%]">
                                    <p class="text-white text-sm">Unde e comanda mea #4521?</p>
                                </div>
                            </div>
                            {{-- Bot message with order --}}
                            <div class="flex gap-2">
                                <div class="w-6 h-6 rounded-full bg-red-700/30 flex-shrink-0 flex items-center justify-center mt-1">
                                    <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                </div>
                                <div class="bg-slate-800 rounded-xl rounded-tl-sm px-4 py-2.5 max-w-[85%]">
                                    <p class="text-slate-300 text-sm mb-2">Comanda #4521 a fost expediată via FanCourier.</p>
                                    <div class="bg-slate-700/50 rounded-lg px-3 py-2 text-xs">
                                        <p class="text-red-400 font-semibold">AWB: 6284719305</p>
                                        <p class="text-slate-400">Status: In tranzit</p>
                                        <p class="text-slate-400">Livrare estimată: Mâine</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Input --}}
                        <div class="bg-slate-800 px-4 py-3 flex items-center gap-2 border-t border-slate-700/50">
                            <div class="flex-1 bg-slate-700/50 rounded-lg px-3 py-2">
                                <p class="text-slate-500 text-sm">Scrie un mesaj...</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-red-700 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<x-motif-border />

{{-- ==================== APELURI VOCALE AI ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center mb-14 lg:mb-20">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-100 text-red-700 text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                Apeluri Vocale AI
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Agent vocal care sună ca un om real</h2>
            <p class="text-slate-600 max-w-3xl mx-auto text-lg leading-relaxed">
                Tehnologie de ultimă generație: OpenAI Realtime API pentru conversații vocale naturale în timp real, cu voice cloning prin ElevenLabs pentru a clona vocea brandului tău.
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
            {{-- Coloana stânga: Funcționalități voice --}}
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Conversații vocale în timp real</h3>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-3">Bazat pe OpenAI Realtime API cu procesare audio nativă. Agentul vocal înțelege contextul, menține firul conversației și răspunde natural.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">Barge-in (întrerupere)</span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">VAD semantic</span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">&lt;1s latență</span>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Voice Cloning cu ElevenLabs</h3>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-3">Clonează vocea brandului tău din doar o mostră audio. Agentul vocal va vorbi exact cu vocea pe care o alegi tu, creând o experiență autentică și de încredere.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">WAV, MP3, WebM, OGG</span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">Multilingual v2</span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">Reglaje fine</span>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Telefonie Telnyx integrată</h3>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-3">Numere de telefon românești (+40), apeluri inbound și outbound, înregistrare completă cu transcriere automată și analiză de sentiment post-apel.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">Numere +40</span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">Inbound & Outbound</span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-medium">Înregistrare</span>
                    </div>
                </div>
            </div>

            {{-- Coloana dreaptă: Ce poate face agentul vocal --}}
            <div>
                <h3 class="text-xl font-bold text-slate-900 mb-6">Ce poate face agentul vocal în timpul unui apel</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-1">Răspunde la întrebări din baza de cunoștințe</h4>
                            <p class="text-slate-500 text-sm">Acces la aceleași documente ca și chatbot-ul text. O singură bază de cunoștințe pentru ambele canale.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-1">Caută produse și verifică comenzi</h4>
                            <p class="text-slate-500 text-sm">Clientul spune ce caută, agentul găsește produsul. Poate verifica statusul unei comenzi prin număr, email sau telefon.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-1">Captează lead-uri automat</h4>
                            <p class="text-slate-500 text-sm">Colectează nume, telefon și interval preferat de callback. Confirmă datele cu clientul înainte de a salva.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-1">Transcriere și analiză automată</h4>
                            <p class="text-slate-500 text-sm">Fiecare apel e transcris automat, analizat pentru sentiment (-1.0 la 1.0) și primește un sumar generat de AI.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-1">Salut personalizat și filtru anti-halucinare</h4>
                            <p class="text-slate-500 text-sm">Salută în funcție de ora zilei. Whisper hallucination filter elimină transcrierile false din zgomot de fond.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ==================== ANALIZĂ ȘI RAPOARTE ==================== --}}
<section class="bg-slate-50 section-padding">
    <div class="container-custom">
        <div class="text-center mb-14 lg:mb-20">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-100 text-red-700 text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                Dashboard & Analytics
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Totul măsurabil, totul vizibil</h2>
            <p class="text-slate-600 max-w-3xl mx-auto text-lg leading-relaxed">
                Dashboard complet cu metrici în timp real. Știi exact ce face chatbot-ul tău, câți clienți ajută, ce produse recomandă și cât costă fiecare conversație.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Metric card 1 --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Conversații</h3>
                <p class="text-slate-500 text-sm">Total, active, completate. Trend zilnic pe 7 zile.</p>
            </div>
            {{-- Metric card 2 --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 8.25H9m6 3H9m3 6l-3-3h1.5a3 3 0 100-6M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Costuri</h3>
                <p class="text-slate-500 text-sm">Cost per mesaj, per conversație, per bot. AI + voice separat.</p>
            </div>
            {{-- Metric card 3 --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Vânzări</h3>
                <p class="text-slate-500 text-sm">Funnel complet: impresii, click-uri, add to cart, achiziții.</p>
            </div>
            {{-- Metric card 4 --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Sentiment</h3>
                <p class="text-slate-500 text-sm">Analiză sentiment în timp real. Export CSV/PDF.</p>
            </div>
        </div>

        {{-- Sub-features --}}
        <div class="mt-12 grid md:grid-cols-3 gap-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <p class="text-slate-700 text-sm"><span class="font-semibold">40+ evenimente</span> tracked per conversație (impresii, click-uri, add to cart, abandon, etc.)</p>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <p class="text-slate-700 text-sm"><span class="font-semibold">Atribuire vânzări</span> pe 3 moduri: strict, probabil și asistat</p>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <p class="text-slate-700 text-sm"><span class="font-semibold">Knowledge gap detection</span> - vezi ce întreabă clienții și nu știi să răspunzi</p>
            </div>
        </div>
    </div>
</section>

{{-- ==================== TABEL COMPARATIV ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Sambla AI vs. Operator uman</h2>
            <p class="text-slate-600 max-w-2xl mx-auto text-lg">De ce companiile aleg automatizarea cu AI</p>
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="py-4 px-6 text-left text-sm font-bold text-slate-700">Criteriu</th>
                            <th class="py-4 px-6 text-center text-sm font-bold text-slate-500">Operator uman</th>
                            <th class="py-4 px-6 text-center text-sm font-bold text-red-700 bg-red-50/50">Sambla AI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Disponibilitate</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">8h/zi, L-V</td>
                            <td class="py-4 px-6 text-sm font-semibold text-red-700 text-center bg-red-50/30">24/7/365</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Timp de răspuns</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">2-10 minute</td>
                            <td class="py-4 px-6 text-sm font-semibold text-red-700 text-center bg-red-50/30">&lt;1 secundă</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Cost per interacțiune</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">2-5&euro;</td>
                            <td class="py-4 px-6 text-sm font-semibold text-emerald-600 text-center bg-red-50/30">0.10-0.30&euro;</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Scalabilitate</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">Limitată de personal</td>
                            <td class="py-4 px-6 text-sm font-semibold text-red-700 text-center bg-red-50/30">Nelimitată</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Consistență</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">Variabilă</td>
                            <td class="py-4 px-6 text-sm font-semibold text-red-700 text-center bg-red-50/30">100% consistentă</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Limbi străine</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">1-2 limbi</td>
                            <td class="py-4 px-6 text-sm font-semibold text-red-700 text-center bg-red-50/30">10+ limbi</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Analiză sentiment</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">Subiectivă</td>
                            <td class="py-4 px-6 text-sm font-semibold text-red-700 text-center bg-red-50/30">Obiectivă, în timp real</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-4 px-6 text-sm font-medium text-slate-700">Oboseală</td>
                            <td class="py-4 px-6 text-sm text-slate-500 text-center">Da, scade calitatea</td>
                            <td class="py-4 px-6 text-sm font-semibold text-emerald-600 text-center bg-red-50/30">Nu, performanță constantă</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ==================== SECURITATE ȘI MULTI-TENANT ==================== --}}
<section class="bg-slate-950 section-padding relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.03]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="sec-dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1.5" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#sec-dots)"/></svg>
    </div>
    <div class="container-custom relative z-10">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-900/30 text-red-400 text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg>
                Securitate & Fiabilitate
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Construit pentru siguranță și scalabilitate</h2>
            <p class="text-slate-400 max-w-2xl mx-auto text-lg">Fiecare aspect al platformei e gândit pentru protecția datelor și fiabilitate enterprise.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Izolare multi-tenant</h3>
                <p class="text-slate-400 text-sm">Datele fiecărui client sunt complet izolate. Filtrare automată per tenant la nivel de query.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Conformitate GDPR</h3>
                <p class="text-slate-400 text-sm">Consimțământ explicit pentru colectarea datelor, hosting în România, ștergere la cerere.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Rate limiting & circuit breaker</h3>
                <p class="text-slate-400 text-sm">Protecție la nivel de API, IP și canal. Circuit breaker automat pe provideri AI.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Control acces pe roluri</h3>
                <p class="text-slate-400 text-sm">4 niveluri de acces: Super Admin, Admin, Manager, Viewer. Permisiuni granulare.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Verificare webhook signatures</h3>
                <p class="text-slate-400 text-sm">HMAC-SHA256 pentru sesiuni chat, verificare semnătură Telnyx pe toate webhook-urile.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Protecție SSRF & verificare domeniu</h3>
                <p class="text-slate-400 text-sm">Validare URL-uri externe, verificare domenii pentru embedding widget, protecție SSRF.</p>
            </div>
        </div>
    </div>
</section>

{{-- ==================== DEVELOPER SECTION ==================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center mb-12 lg:mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-100 text-red-700 text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                Pentru Dezvoltatori
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Construit pentru dezvoltatori</h2>
            <p class="text-slate-600 max-w-2xl mx-auto text-lg">
                API REST puternic, documentat complet, cu autentificare Sanctum și webhook-uri pentru orice eveniment
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
                    <pre class="text-sm leading-relaxed font-mono"><code><span class="text-slate-500">// Exemplu: Inițierea unui apel cu Sambla API</span>
<span class="text-red-400">const</span> <span class="text-slate-200">response</span> <span class="text-slate-500">=</span> <span class="text-red-400">await</span> <span class="text-blue-400">fetch</span><span class="text-slate-400">(</span><span class="text-emerald-400">'https://api.sambla.ro/v1/calls'</span><span class="text-slate-400">,</span> <span class="text-slate-400">{</span>
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

<span class="text-red-400">const</span> <span class="text-slate-200">call</span> <span class="text-slate-500">=</span> <span class="text-red-400">await</span> <span class="text-slate-200">response</span><span class="text-slate-400">.</span><span class="text-blue-400">json</span><span class="text-slate-400">();</span>
<span class="text-blue-400">console</span><span class="text-slate-400">.</span><span class="text-blue-400">log</span><span class="text-slate-400">(</span><span class="text-emerald-400">`Apel inițiat: <span class="text-yellow-300">${</span><span class="text-slate-200">call.id</span><span class="text-yellow-300">}</span>`</span><span class="text-slate-400">);</span></code></pre>
                </div>
            </div>

            {{-- Badges --}}
            <div class="flex flex-wrap justify-center gap-3 mt-8">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-red-700 border border-slate-200">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                    REST API
                </span>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-red-700 border border-slate-200">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                    WebSocket
                </span>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-red-700 border border-slate-200">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    Webhook-uri
                </span>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-red-700 border border-slate-200">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                    Sanctum Auth
                </span>
            </div>
        </div>
    </div>
</section>

<x-cta-section
    title="Pregătit să automatizezi comunicarea?"
    subtitle="Configurare în 10 minute. Fără card de credit. Chatbot-ul tău AI poate fi live azi."
/>

@endsection
