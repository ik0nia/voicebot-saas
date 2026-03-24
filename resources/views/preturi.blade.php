@extends('layouts.app')

@section('title', 'Prețuri - Sambla')
@section('meta_description', 'Prețuri simple și transparente pentru Sambla. Alege planul potrivit pentru afacerea ta. Fără costuri ascunse.')

@section('content')

    {{-- Section 1: Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-white to-primary-50 section-padding">
        <x-hero-texture />
        <div class="container-custom text-center animate-fade-in relative">
            <x-hero-ornament />
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-6">
                Prețuri <span class="gradient-text">simple, transparente</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto">
                Alege planul potrivit pentru afacerea ta. Fără costuri ascunse.
            </p>
        </div>
    </section>

    <x-motif-border />

    {{-- Section 2: Billing Toggle + Section 3: Pricing Cards --}}
    <section class="section-padding bg-white">
        <div class="container-custom">

            {{-- Billing Toggle --}}
            <div class="flex items-center justify-center gap-4 mb-12 animate-slide-up">
                <span id="label-monthly" class="text-sm font-semibold text-slate-900">Lunar</span>
                <button
                    id="billing-toggle"
                    type="button"
                    class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-300 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    role="switch"
                    aria-checked="false"
                    data-billing="monthly"
                >
                    <span class="pointer-events-none inline-block h-6 w-6 translate-x-0 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
                <span id="label-annual" class="text-sm font-medium text-slate-500">Anual</span>
                <span class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                    Economisești 20%
                </span>
            </div>

            {{-- Pricing Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center max-w-6xl mx-auto">

                {{-- STARTER --}}
                <div class="animate-slide-up rounded-2xl border border-slate-200 bg-white p-8 shadow-sm hover:shadow-lg transition-shadow duration-300">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900 uppercase tracking-wide">Starter</h3>
                        <p class="text-sm text-slate-500 mt-1">Perfect pentru afaceri mici</p>
                    </div>
                    <div class="mb-8">
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-slate-900 pricing-amount" data-monthly="99" data-annual="79">99</span>
                            <span class="text-lg font-semibold text-slate-600">€</span>
                            <span class="text-sm text-slate-500">/lună</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 pricing-note hidden">facturat anual</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            500 minute incluse
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            1 agent AI
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            2 canale (telefon + chatbot web)
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Transcrieri automate
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Suport email
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Rapoarte de bază
                        </li>
                    </ul>
                    <a href="#" class="btn-secondary block text-center w-full">Începe gratuit</a>
                </div>

                {{-- PROFESIONAL (Most Popular) --}}
                <div class="animate-slide-up rounded-2xl border-2 border-primary-600 bg-white p-8 shadow-xl md:scale-105 relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center rounded-full bg-primary-600 px-4 py-1 text-xs font-bold text-white uppercase tracking-wide shadow-lg">
                            Cel mai popular
                        </span>
                    </div>
                    <div class="mb-6 mt-2">
                        <h3 class="text-lg font-bold text-slate-900 uppercase tracking-wide">Profesional</h3>
                        <p class="text-sm text-slate-500 mt-1">Pentru echipe în creștere</p>
                    </div>
                    <div class="mb-8">
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-primary-700 pricing-amount" data-monthly="299" data-annual="239">299</span>
                            <span class="text-lg font-semibold text-slate-600">€</span>
                            <span class="text-sm text-slate-500">/lună</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 pricing-note hidden">facturat anual</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            2.000 minute incluse
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            25 agenți AI
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Toate 5 canalele (telefon, web + WhatsApp, Facebook, Instagram in curând)
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Bază de cunoștințe partajată între canale
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Analiză de sentiment
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Suport prioritar 24/7
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Integrări CRM
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Dashboard avansat
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            API access
                        </li>
                    </ul>
                    <a href="#" class="btn-primary block text-center w-full">Începe gratuit</a>
                </div>

                {{-- ENTERPRISE --}}
                <div class="animate-slide-up rounded-2xl border border-slate-200 bg-white p-8 shadow-sm hover:shadow-lg transition-shadow duration-300">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900 uppercase tracking-wide">Enterprise</h3>
                        <p class="text-sm text-slate-500 mt-1">Pentru organizații mari</p>
                    </div>
                    <div class="mb-8">
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-slate-900">Personalizat</span>
                        </div>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Minute nelimitate
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Agenți AI nelimitați
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            SLA garantat 99.99%
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Manager de cont dedicat
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Onboarding personalizat
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Toate canalele + integrări custom
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Hosting dedicat opțional
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Suport telefonic 24/7
                        </li>
                    </ul>
                    <a href="#" class="btn-secondary block text-center w-full">Contactează-ne</a>
                </div>

            </div>
        </div>
    </section>

    {{-- Section 4: Feature Comparison Table --}}
    <section class="section-padding bg-slate-50">
        <div class="container-custom">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-12 animate-fade-in">
                Comparație <span class="gradient-text">detaliată</span>
            </h2>
            <div class="overflow-x-auto animate-slide-up">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-200">
                            <th class="py-4 px-4 text-left font-semibold text-slate-700 w-1/4">Funcționalitate</th>
                            <th class="py-4 px-4 text-center font-semibold text-slate-700 w-1/4">Starter</th>
                            <th class="py-4 px-4 text-center font-semibold text-primary-700 w-1/4 bg-primary-50 rounded-t-lg">Profesional</th>
                            <th class="py-4 px-4 text-center font-semibold text-slate-700 w-1/4">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Minute incluse</td>
                            <td class="py-3 px-4 text-center text-slate-600">500</td>
                            <td class="py-3 px-4 text-center text-slate-600 bg-primary-50/50">2.000</td>
                            <td class="py-3 px-4 text-center text-slate-600">Nelimitate</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Agenți AI</td>
                            <td class="py-3 px-4 text-center text-slate-600">1</td>
                            <td class="py-3 px-4 text-center text-slate-600 bg-primary-50/50">25</td>
                            <td class="py-3 px-4 text-center text-slate-600">Nelimitați</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Canale</td>
                            <td class="py-3 px-4 text-center text-slate-600">2 (telefon + web)</td>
                            <td class="py-3 px-4 text-center text-slate-600 bg-primary-50/50">Toate 5</td>
                            <td class="py-3 px-4 text-center text-slate-600">Toate 5 + custom</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Transcrieri</td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                            <td class="py-3 px-4 text-center bg-primary-50/50"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Analiză sentiment</td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></td>
                            <td class="py-3 px-4 text-center bg-primary-50/50"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Integrări CRM</td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></td>
                            <td class="py-3 px-4 text-center bg-primary-50/50"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">API Access</td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></td>
                            <td class="py-3 px-4 text-center bg-primary-50/50"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Rapoarte avansate</td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></td>
                            <td class="py-3 px-4 text-center bg-primary-50/50"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">SLA</td>
                            <td class="py-3 px-4 text-center text-slate-400">&mdash;</td>
                            <td class="py-3 px-4 text-center text-slate-600 bg-primary-50/50">99.9%</td>
                            <td class="py-3 px-4 text-center text-slate-600">99.99%</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Suport</td>
                            <td class="py-3 px-4 text-center text-slate-600">Email</td>
                            <td class="py-3 px-4 text-center text-slate-600 bg-primary-50/50">Prioritar 24/7</td>
                            <td class="py-3 px-4 text-center text-slate-600">Dedicat + telefonic</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Onboarding</td>
                            <td class="py-3 px-4 text-center text-slate-600">Documentație</td>
                            <td class="py-3 px-4 text-center text-slate-600 bg-primary-50/50">Ghidat</td>
                            <td class="py-3 px-4 text-center text-slate-600">Personalizat</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-slate-700 font-medium">Manager dedicat</td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></td>
                            <td class="py-3 px-4 text-center bg-primary-50/50"><svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></td>
                            <td class="py-3 px-4 text-center"><svg class="w-5 h-5 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Section 5: FAQ --}}
    <section class="section-padding bg-white">
        <div class="container-custom max-w-3xl">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-12 animate-fade-in">
                Întrebări <span class="gradient-text">frecvente</span>
            </h2>

            <div class="space-y-4 animate-slide-up" id="faq-accordion">

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Pot schimba planul oricând?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Da, poți face upgrade sau downgrade oricând. Diferența de preț se calculează pro-rata.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Ce se întâmplă dacă depășesc minutele incluse?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Minutele suplimentare se facturează la 0.15€/minut pentru Starter și 0.10€/minut pentru Profesional.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Oferiți perioadă de probă gratuită?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Da, toate planurile includ 14 zile de probă gratuită, fără card de credit.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Cum funcționează facturarea?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Facturarea se face lunar sau anual, prin card de credit sau transfer bancar pentru Enterprise.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Pot anula abonamentul?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Da, poți anula oricând. Nu există contracte pe termen lung sau penalități de anulare.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Ce canale de comunicare sunt incluse?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Sambla suportă 5 canale: Telefon (voce AI), Web Chatbot, WhatsApp Business, Facebook Messenger și Instagram DM. Momentan sunt disponibile canalele Telefon și Web Chatbot. WhatsApp, Facebook Messenger și Instagram DM sunt în curs de implementare și vor fi disponibile în curând. Toți agenții partajează aceeași bază de cunoștințe, indiferent de canal.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-50 transition-colors">
                        <span>Oferiți discount pentru ONG-uri?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-600 text-sm leading-relaxed">
                            Da, oferim 30% discount pentru organizații non-profit și instituții de învățământ.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Section 6: Contact CTA --}}
    <section class="section-padding bg-primary-50">
        <div class="container-custom text-center max-w-2xl animate-fade-in">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
                Nu ești sigur ce plan ți se potrivește?
            </h2>
            <p class="text-lg text-slate-600 mb-8">
                Hai să discutăm. Echipa noastră te poate ajuta să alegi soluția optimă.
            </p>
            <a href="#" class="btn-primary inline-block">Programează o consultație gratuită</a>
            <p class="text-sm text-slate-500 mt-4">Răspundem în maxim 2 ore</p>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Billing toggle
        var toggle = document.getElementById('billing-toggle');
        var amounts = document.querySelectorAll('.pricing-amount');
        var notes = document.querySelectorAll('.pricing-note');
        var labelMonthly = document.getElementById('label-monthly');
        var labelAnnual = document.getElementById('label-annual');
        var isAnnual = false;

        toggle.addEventListener('click', function () {
            isAnnual = !isAnnual;
            var knob = toggle.querySelector('span');

            if (isAnnual) {
                toggle.classList.remove('bg-slate-300');
                toggle.classList.add('bg-primary-600');
                knob.classList.remove('translate-x-0');
                knob.classList.add('translate-x-7');
                toggle.setAttribute('aria-checked', 'true');
                toggle.setAttribute('data-billing', 'annual');
                labelMonthly.classList.remove('text-slate-900', 'font-semibold');
                labelMonthly.classList.add('text-slate-500', 'font-medium');
                labelAnnual.classList.remove('text-slate-500', 'font-medium');
                labelAnnual.classList.add('text-slate-900', 'font-semibold');
            } else {
                toggle.classList.remove('bg-primary-600');
                toggle.classList.add('bg-slate-300');
                knob.classList.remove('translate-x-7');
                knob.classList.add('translate-x-0');
                toggle.setAttribute('aria-checked', 'false');
                toggle.setAttribute('data-billing', 'monthly');
                labelMonthly.classList.remove('text-slate-500', 'font-medium');
                labelMonthly.classList.add('text-slate-900', 'font-semibold');
                labelAnnual.classList.remove('text-slate-900', 'font-semibold');
                labelAnnual.classList.add('text-slate-500', 'font-medium');
            }

            amounts.forEach(function (el) {
                var price = isAnnual ? el.getAttribute('data-annual') : el.getAttribute('data-monthly');
                el.textContent = price;
            });

            notes.forEach(function (el) {
                if (isAnnual) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
        });

        // FAQ accordion
        var faqToggles = document.querySelectorAll('.faq-toggle');

        faqToggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.faq-item');
                var content = item.querySelector('.faq-content');
                var icon = item.querySelector('.faq-icon');
                var isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

                // Close all other items
                document.querySelectorAll('.faq-item').forEach(function (otherItem) {
                    if (otherItem !== item) {
                        var otherContent = otherItem.querySelector('.faq-content');
                        var otherIcon = otherItem.querySelector('.faq-icon');
                        otherContent.style.maxHeight = '0px';
                        otherIcon.classList.remove('rotate-45');
                    }
                });

                if (isOpen) {
                    content.style.maxHeight = '0px';
                    icon.classList.remove('rotate-45');
                } else {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    icon.classList.add('rotate-45');
                }
            });
        });

    });
</script>
@endpush
