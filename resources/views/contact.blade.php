@extends('layouts.app')

@section('title', 'Contact - Sambla')
@section('meta_description', 'Contactează echipa Sambla. Suntem aici să te ajutăm cu orice întrebare despre platforma noastră de agenți vocali AI.')

@section('content')

<section class="relative overflow-hidden bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="contact-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/><rect x="38" y="2" width="4" height="8" fill="#991b1b"/><rect x="38" y="38" width="4" height="8" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#contact-motif)"/></svg>
    </div>
    <div class="absolute bottom-10 -left-20 w-[300px] h-[300px] bg-red-900/15 rounded-full blur-[100px]"></div>
    <div class="container-custom text-center relative z-10">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-6 animate-fade-in">
            <span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">Contactează-ne</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed animate-fade-in">
            Suntem aici să te ajutăm. Răspundem rapid.
        </p>
    </div>
</section>
<x-motif-border />

{{-- Contact Form + Info --}}
<section class="section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-5 gap-12 lg:gap-16">
            {{-- Left: Contact Form --}}
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl p-8 lg:p-10 shadow-sm border border-slate-100">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Trimite-ne un mesaj</h2>

                    {{-- Success Message (hidden by default) --}}
                    <div id="contact-success" class="hidden mb-8 p-6 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-emerald-800 font-medium">Mulțumesc! Mesajul tău a fost trimis. Te vom contacta în curând.</p>
                        </div>
                    </div>

                    <form id="contact-form" action="/contact" method="POST">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-6 mb-6">
                            {{-- Nume --}}
                            <div>
                                <label for="nume" class="block text-sm font-semibold text-slate-700 mb-2">Nume <span class="text-red-500">*</span></label>
                                <input type="text" id="nume" name="nume" required placeholder="Numele tău complet"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" required placeholder="email@companie.ro"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>

                            {{-- Companie --}}
                            <div>
                                <label for="companie" class="block text-sm font-semibold text-slate-700 mb-2">Companie <span class="text-red-500">*</span></label>
                                <input type="text" id="companie" name="companie" required placeholder="Numele companiei"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>

                            {{-- Telefon --}}
                            <div>
                                <label for="telefon" class="block text-sm font-semibold text-slate-700 mb-2">Telefon <span class="text-slate-400 font-normal">(opțional)</span></label>
                                <input type="tel" id="telefon" name="telefon" placeholder="+40 7XX XXX XXX"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>
                        </div>

                        {{-- Subiect --}}
                        <div class="mb-6">
                            <label for="subiect" class="block text-sm font-semibold text-slate-700 mb-2">Subiect <span class="text-red-500">*</span></label>
                            <select id="subiect" name="subiect" required
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all duration-200 text-slate-900 bg-white">
                                <option value="" disabled selected>Selectează un subiect</option>
                                <option value="informatii">Informații generale</option>
                                <option value="demo">Solicitare demo</option>
                                <option value="suport">Suport tehnic</option>
                                <option value="parteneriat">Parteneriat</option>
                                <option value="altele">Altele</option>
                            </select>
                        </div>

                        {{-- Mesaj --}}
                        <div class="mb-8">
                            <label for="mesaj" class="block text-sm font-semibold text-slate-700 mb-2">Mesaj <span class="text-red-500">*</span></label>
                            <textarea id="mesaj" name="mesaj" required rows="5" placeholder="Scrie mesajul tău aici..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400 resize-y"></textarea>
                        </div>

                        {{-- Submit --}}
                        <button type="submit" class="btn-primary text-lg px-8 py-4 w-full sm:w-auto">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            Trimite mesajul
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right: Contact Info --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Contact Details --}}
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
                    <h3 class="text-xl font-bold text-slate-900 mb-6">Informații de contact</h3>
                    <div class="space-y-6">
                        {{-- Email --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Email</p>
                                <a href="mailto:servus@sambla.ro" class="text-sm text-red-700 hover:text-red-800 transition-colors">servus@sambla.ro</a>
                            </div>
                        </div>

                        {{-- Telefon --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Telefon</p>
                                <a href="tel:+40775222333" class="text-sm text-slate-600 hover:text-red-700 transition-colors">0775 222 333</a>
                            </div>
                        </div>

                        {{-- Adresă --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Adresă</p>
                                <p class="text-sm text-slate-600">Bd. Dacia nr. 31,<br>Oradea, România</p>
                            </div>
                        </div>

                        {{-- Program --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Program</p>
                                <p class="text-sm text-slate-600">Luni - Joi, 10:00 - 16:00</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- FAQ Mini-section --}}
<section class="section-padding bg-slate-50">
    <div class="container-custom">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-4">Întrebări frecvente</h2>
        <p class="text-lg text-slate-600 text-center max-w-2xl mx-auto mb-12">Răspunsuri rapide la cele mai comune întrebări.</p>
        <div class="max-w-3xl mx-auto space-y-4">

            {{-- Categorie: Despre platformă --}}
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider mt-2 mb-2">Despre platformă</h3>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Ce este Sambla?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Sambla este o platformă AI românească de comunicare inteligentă. Oferă chatbot text pentru site-ul tău și agent vocal AI pentru apeluri telefonice — totul alimentat de modele AI de ultimă generație (GPT-4o, Claude) și conectat la baza ta de cunoștințe.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Ce poate face chatbot-ul text?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Chatbot-ul răspunde la întrebări bazat pe documentele tale (PDF, DOCX, CSV), caută produse și le afișează cu poze și prețuri, verifică statusul comenzilor cu tracking automat (FanCourier, Cargus, DPD, SameDay), captează lead-uri automat, detectează intenții și frustrare, și poate escalada conversația către un operator uman când e necesar.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Ce poate face agentul vocal?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Agentul vocal poate răspunde și iniția apeluri telefonice cu o voce naturală, folosind OpenAI Realtime API. Poate răspunde la întrebări din baza de cunoștințe, căuta produse, verifica comenzi, colecta date de contact (lead-uri) și transfera la operator. Fiecare apel este transcris automat și analizat pentru sentiment.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Ce este voice cloning?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Poți clona vocea brandului tău folosind o mostră audio. Agentul vocal va vorbi exact cu vocea pe care o alegi, creând o experiență autentică pentru clienți. Tehnologia este pusă la dispoziție prin ElevenLabs și suportă limba română nativ.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Ce modele AI folosiți?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Folosim GPT-4o și GPT-4o-mini de la OpenAI, Claude de la Anthropic, OpenAI Realtime API pentru voce și ElevenLabs pentru sinteză vocală și voice cloning. Platforma rutează automat între modele în funcție de complexitatea întrebării — răspunsuri rapide pentru întrebări simple, analiză profundă pentru cele complexe.</p>
                </div>
            </details>

            {{-- Categorie: Implementare --}}
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider mt-8 mb-2">Implementare</h3>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Cât durează implementarea?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Chatbot-ul text poate fi live pe site-ul tău în 10 minute — o singură linie de cod de adăugat. Configurarea bazei de cunoștințe (documente, scanare site, conectori) durează de obicei câteva ore. Agentul vocal necesită configurare Twilio suplimentară, de obicei 1-2 zile.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Cum instalez chatbot-ul pe site?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Copiezi o singură linie de cod (tag script) și o adaugi în HTML-ul site-ului tău, înainte de &lt;/body&gt;. Funcționează pe orice platformă: WordPress, Shopify, WooCommerce, site-uri custom — orice site care acceptă JavaScript.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Cum învăț chatbot-ul despre afacerea mea?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Ai mai multe opțiuni: încarcă documente (PDF, DOCX, CSV, TXT), adaugă URL-uri pe care le scanăm automat, conectează-ți magazinul WordPress/WooCommerce pentru sincronizare automată de produse, sau scrie direct informațiile ca text. Chatbot-ul folosește tot ce îi dai ca bază de cunoștințe pentru a răspunde clienților.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Pot personaliza aspectul widget-ului?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da, complet. Poți schimba culoarea principală (se potrivește cu brandul tău), mesajul de bun venit, numele chatbot-ului, poziția pe ecran (stânga sau dreapta) și tonul conversației (profesional, prietenos, premium etc.).</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Am nevoie de cunoștințe tehnice?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Nu. Dashboard-ul este intuitiv, iar instalarea chatbot-ului necesită doar copy-paste a unui cod. Pentru integrări avansate (API, webhook-uri), oferim documentație completă și suport tehnic.</p>
                </div>
            </details>

            {{-- Categorie: E-Commerce --}}
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider mt-8 mb-2">E-Commerce</h3>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Funcționează cu magazinul meu WooCommerce?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da, avem conector WooCommerce nativ. Se sincronizează automat cu catalogul de produse (nume, descriere, prețuri, imagini, categorii, stoc) și cu comenzile. Chatbot-ul poate afișa produse cu poze și preț, permite adăugarea în coș și verifică statusul comenzilor.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Poate chatbot-ul să verifice o comandă?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da. Clientul poate întreba „Unde e comanda mea?" și să dea numărul comenzii, email-ul sau telefonul. Chatbot-ul caută comanda, afișează statusul, produsele comandate și link-ul de tracking AWB pentru FanCourier, Cargus, DPD, SameDay, GLS sau Urgent Cargus.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Pot măsura vânzările generate de chatbot?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da. Dashboard-ul Commerce Analytics arată funnel-ul complet: câte produse au fost afișate, câte click-uri, câte adăugări în coș și câte achiziții. Atribuirea vânzărilor funcționează pe 3 moduri: strict (directă), probabil și asistat.</p>
                </div>
            </details>

            {{-- Categorie: Lead-uri și analiză --}}
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider mt-8 mb-2">Lead-uri și analiză</h3>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Cum captează chatbot-ul lead-uri?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Chatbot-ul folosește un sistem de scoring cu peste 30 de semnale (număr mesaje, interacțiuni cu produse, intenție de cumpărare) și cere datele de contact la momentul potrivit — fără a fi agresiv. Lead-urile intră într-un pipeline cu 7 etape pe care îl gestionezi din dashboard.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Ce analize și rapoarte oferă platforma?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Dashboard-ul arată: număr conversații și trend pe 7 zile, cost per mesaj/conversație/bot, analiză de sentiment în timp real, funnel de vânzări, 40+ tipuri de evenimente tracked per conversație, detecție „knowledge gaps" (întrebări la care chatbot-ul nu știe să răspundă) și export CSV/PDF.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Poate chatbot-ul să transfere la un om?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da. Când detectează că clientul cere un operator uman sau când situația depășește ce poate rezolva singur, chatbot-ul creează automat un handoff request cu rezumatul conversației, intențiile detectate și produsele discutate. Echipa ta primește notificarea și preia conversația.</p>
                </div>
            </details>

            {{-- Categorie: Securitate și date --}}
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider mt-8 mb-2">Securitate și date</h3>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Este platforma conformă GDPR?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da. Platforma colectează date personale doar cu consimțământ explicit, hosting-ul este în România, datele fiecărui client sunt izolate complet (arhitectură multi-tenant), și poți șterge contul și toate datele asociate oricând.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Unde sunt stocate datele?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Toate datele sunt stocate pe servere din România. Baza de date PostgreSQL cu extensia pgvector pentru căutare semantică. Comunicarea cu API-urile externe (OpenAI, ElevenLabs, Twilio) se face prin conexiuni securizate (HTTPS/WSS).</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Cine are acces la conversațiile clienților mei?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Doar echipa ta. Platforma are 4 niveluri de acces (Admin, Manager, Viewer și Super Admin) cu permisiuni granulare. Datele fiecărui tenant sunt complet izolate — un client nu poate vedea datele altui client, nici măcar accidental.</p>
                </div>
            </details>

            {{-- Categorie: Prețuri și suport --}}
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider mt-8 mb-2">Prețuri și suport</h3>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Cât costă platforma?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Avem planuri flexibile care pornesc de la un plan gratuit pentru testare. Prețul depinde de volumul de mesaje, minute vocale și funcționalitățile necesare. Vizitează <a href="/preturi" class="text-red-700 hover:text-red-800 font-medium">pagina de prețuri</a> pentru detalii complete sau contactează-ne pentru o ofertă personalizată.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Există perioadă de probă gratuită?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da, poți testa platforma fără card de credit. Creezi un cont, configurezi chatbot-ul și vezi cum funcționează pe site-ul tău înainte de a alege un plan plătit.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Oferiți suport în română?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Da, 100%. Suntem o echipă românească. Suportul, documentația și comunicarea sunt în română nativ. Poți să ne scrii la servus@sambla.ro sau să ne suni la 0775 222 333 (Luni-Joi, 10:00-16:00).</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Putem avea o demonstrație live?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Desigur! Completează formularul de mai sus, scrie-ne la servus@sambla.ro sau sună-ne direct. Programăm o demonstrație personalizată în funcție de industria și nevoile tale.</p>
                </div>
            </details>

            <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 transition-colors">
                    <h4 class="text-lg font-bold text-slate-900">Câte limbi suportă chatbot-ul?</h4>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </summary>
                <div class="px-6 pb-6">
                    <p class="text-slate-600 leading-relaxed">Chatbot-ul suportă 10+ limbi, cu optimizare specială pentru română. Detectează automat limba clientului și răspunde în aceeași limbă. Limbile principale: română, engleză, germană, franceză, spaniolă.</p>
                </div>
            </details>

        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    (function() {
        var form = document.getElementById('contact-form');
        var success = document.getElementById('contact-success');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                form.style.display = 'none';
                success.classList.remove('hidden');
                success.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
    })();
</script>
@endpush
