@extends('layouts.app')

@section('title', 'Prețuri - Sambla')
@section('meta_description', 'Prețuri simple și transparente pentru Sambla. Alege planul potrivit pentru afacerea ta. Fără costuri ascunse.')

@section('content')

    {{-- Section 1: Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-slate-50 to-primary-50 section-padding">
        <x-hero-texture />
        <div class="container-custom text-center animate-fade-in relative">
            <x-hero-ornament />
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-6">
                Prețuri <span class="gradient-text">simple, transparente</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-700 max-w-2xl mx-auto">
                Alege planul potrivit pentru afacerea ta. Fără costuri ascunse.
            </p>
        </div>
    </section>

    <x-motif-border />

    {{-- Section 2: Webchat Packages --}}
    <section class="section-padding bg-slate-50">
        <div class="container-custom">

            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-4 animate-fade-in">
                Pachete <span class="gradient-text">Webchat</span>
            </h2>
            <p class="text-center text-slate-700 mb-12 max-w-2xl mx-auto animate-fade-in">
                Chatbot AI integrat pe site-ul tău. Răspunde automat clienților 24/7.
            </p>

            {{-- Billing Toggle --}}
            <div class="flex items-center justify-center gap-4 mb-12 animate-slide-up">
                <span id="label-monthly" class="text-sm font-bold text-slate-900">Lunar</span>
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

            {{-- Webchat Pricing Cards --}}
            @if($webchatPlans->count())
                <div class="grid grid-cols-1 md:grid-cols-{{ min($webchatPlans->count(), 3) }} gap-8 items-center max-w-6xl mx-auto">
                    @foreach($webchatPlans as $plan)
                        @php
                            $isPopular = $plan->is_popular;
                            $features = $plan->features ?? [];
                            $overage = $plan->overage ?? [];
                            $overageCostPerMessage = $overage['cost_per_message'] ?? null;
                            $overageCostPerBot = $overage['cost_per_extra_bot'] ?? null;
                            $isEnterprise = $plan->price_monthly === null || $plan->price_monthly == 0 && strtolower($plan->slug ?? $plan->name) === 'enterprise';
                        @endphp
                        <div class="animate-slide-up rounded-2xl {{ $isPopular ? 'border-2 border-primary-600 shadow-xl md:scale-105' : 'border border-slate-200 shadow-md hover:shadow-lg' }} bg-white p-8 transition-shadow duration-300 relative">
                            @if($isPopular)
                                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                    <span class="inline-flex items-center rounded-full bg-primary-600 px-4 py-1 text-xs font-bold text-white uppercase tracking-wide shadow-lg">
                                        Cel mai popular
                                    </span>
                                </div>
                            @endif
                            <div class="mb-6 {{ $isPopular ? 'mt-2' : '' }}">
                                <h3 class="text-lg font-bold text-slate-900 uppercase tracking-wide">{{ $plan->name }}</h3>
                                @if($plan->description)
                                    <p class="text-sm text-slate-600 mt-1">{{ $plan->description }}</p>
                                @endif
                            </div>
                            <div class="mb-8">
                                @if($plan->price_monthly !== null && $plan->price_monthly > 0)
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-4xl font-extrabold {{ $isPopular ? 'text-primary-700' : 'text-slate-900' }} pricing-amount" data-monthly="{{ number_format($plan->price_monthly, 0) }}" data-annual="{{ number_format($plan->price_yearly, 0) }}">{{ number_format($plan->price_monthly, 0) }}</span>
                                        <span class="text-lg font-semibold text-slate-700">&euro;</span>
                                        <span class="text-sm text-slate-600">/lună</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 pricing-note hidden">facturat anual</p>
                                @else
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-4xl font-extrabold text-slate-900">Personalizat</span>
                                    </div>
                                @endif
                            </div>
                            <ul class="space-y-3 mb-6">
                                @foreach($features as $feature)
                                    <li class="flex items-start gap-3 text-sm text-slate-800">
                                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                            @if($overageCostPerMessage || $overageCostPerBot)
                                <div class="text-xs text-slate-600 mb-6 border-t border-slate-200 pt-4 space-y-1">
                                    @if($overageCostPerMessage)
                                        <p>Mesaj suplimentar: <span class="font-semibold text-slate-900">&euro;{{ number_format($overageCostPerMessage, 2) }}/mesaj</span></p>
                                    @endif
                                    @if($overageCostPerBot)
                                        <p>Bot suplimentar: <span class="font-semibold text-slate-900">&euro;{{ number_format($overageCostPerBot, 0) }}/lună</span></p>
                                    @endif
                                </div>
                            @endif
                            @if($plan->price_monthly !== null && $plan->price_monthly > 0)
                                <a href="/register" class="{{ $isPopular ? 'btn-primary' : 'btn-secondary' }} block text-center w-full">Începe gratuit</a>
                            @else
                                <a href="/contact" class="btn-secondary block text-center w-full">Contactează-ne</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Fallback when no plans in DB --}}
                <div class="text-center py-12">
                    <p class="text-slate-500 text-lg">Planurile vor fi disponibile în curând. Contactează-ne pentru detalii.</p>
                    <a href="/contact" class="btn-primary inline-block mt-6">Contactează-ne</a>
                </div>
            @endif
        </div>
    </section>

    {{-- Section 3: Voice Addon --}}
    <section class="section-padding bg-gradient-to-b from-slate-900 to-slate-800">
        <div class="container-custom">

            <h2 class="text-3xl md:text-4xl font-bold text-white text-center mb-4 animate-fade-in">
                Adaugă funcționalitate <span class="gradient-text">vocală</span>
            </h2>
            <p class="text-center text-slate-300 mb-12 max-w-2xl mx-auto animate-fade-in">
                Extinde chatbot-ul cu voce AI. Gestionează apeluri telefonice automat prin Twilio + OpenAI.
            </p>

            @if($voicePlans->count())
                <div class="grid grid-cols-1 md:grid-cols-{{ min($voicePlans->count(), 3) }} gap-8 items-center max-w-6xl mx-auto">
                    @foreach($voicePlans as $plan)
                        @php
                            $features = $plan->features ?? [];
                            $overage = $plan->overage ?? [];
                            $overageCostPerMinute = $overage['cost_per_minute'] ?? null;
                            $limits = $plan->limits ?? [];
                            $minutesIncluded = $limits['minutes'] ?? $limits['voice_minutes'] ?? null;
                            $isEnterprise = $plan->price_monthly === null || ($plan->price_monthly == 0 && str_contains(strtolower($plan->name), 'enterprise'));
                        @endphp
                        <div class="animate-slide-up rounded-2xl border border-slate-700 bg-slate-800/80 backdrop-blur p-8 shadow-lg hover:shadow-xl hover:border-primary-500/50 transition-all duration-300 relative">
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-white uppercase tracking-wide">{{ $plan->name }}</h3>
                                @if($plan->description)
                                    <p class="text-sm text-slate-300 mt-1">{{ $plan->description }}</p>
                                @endif
                            </div>
                            <div class="mb-8">
                                @if(!$isEnterprise && $plan->price_monthly > 0)
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-4xl font-extrabold text-white pricing-amount" data-monthly="{{ number_format($plan->price_monthly, 0) }}" data-annual="{{ number_format($plan->price_yearly, 0) }}">{{ number_format($plan->price_monthly, 0) }}</span>
                                        <span class="text-lg font-semibold text-slate-300">&euro;</span>
                                        <span class="text-sm text-slate-400">/lună</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1 pricing-note hidden">facturat anual</p>
                                @else
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-4xl font-extrabold text-white">Personalizat</span>
                                    </div>
                                @endif
                            </div>
                            @if($minutesIncluded)
                                <p class="text-sm text-primary-400 font-semibold mb-4">
                                    {{ $minutesIncluded == -1 ? 'Minute nelimitate' : number_format($minutesIncluded) . ' minute incluse' }}
                                </p>
                            @endif
                            <ul class="space-y-3 mb-6">
                                @foreach($features as $feature)
                                    <li class="flex items-start gap-3 text-sm text-slate-300">
                                        <svg class="w-5 h-5 text-primary-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                            @if($overageCostPerMinute)
                                <p class="text-xs text-slate-400 mb-6 border-t border-slate-600 pt-4">
                                    Credit suplimentar: <span class="font-semibold text-white">&euro;{{ number_format($overageCostPerMinute, 2) }}/minut</span>
                                </p>
                            @endif
                            @if($isEnterprise)
                                <a href="/contact" class="btn-secondary block text-center w-full">Contactează-ne</a>
                            @else
                                <a href="/register" class="btn-primary block text-center w-full">Începe gratuit</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Fallback when no voice plans in DB --}}
                <div class="text-center py-12">
                    <p class="text-slate-400 text-lg">Planurile vocale vor fi disponibile în curând.</p>
                    <a href="/contact" class="btn-primary inline-block mt-6">Contactează-ne</a>
                </div>
            @endif
        </div>
    </section>

    {{-- Section 4: Credit suplimentar explainer --}}
    <section class="section-padding bg-slate-50">
        <div class="container-custom">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-4 animate-fade-in">
                Credit <span class="gradient-text">suplimentar</span>
            </h2>
            <p class="text-center text-slate-700 mb-12 max-w-2xl mx-auto animate-fade-in">
                Depășești limita lunară? Nu-ți face griji. Mesajele și minutele suplimentare se taxează automat, la un cost care scade pe planurile superioare.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto animate-slide-up">

                {{-- Messages overage --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Mesaje suplimentare</h3>
                    </div>
                    @if($webchatPlans->count())
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200">
                                        <th class="py-3 px-4 text-left font-semibold text-slate-700">Plan</th>
                                        <th class="py-3 px-4 text-right font-semibold text-slate-700">Cost/mesaj</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($webchatPlans as $plan)
                                        @php $cpm = $plan->overage['cost_per_message'] ?? null; @endphp
                                        @if($cpm)
                                            <tr>
                                                <td class="py-3 px-4 text-slate-700 font-medium">{{ $plan->name }}</td>
                                                <td class="py-3 px-4 text-right text-slate-900 font-semibold">&euro;{{ number_format($cpm, 2) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Detaliile vor fi disponibile în curând.</p>
                    @endif
                </div>

                {{-- Extra bots --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Chatboți suplimentari</h3>
                    </div>
                    @if($webchatPlans->count())
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200">
                                        <th class="py-3 px-4 text-left font-semibold text-slate-700">Plan</th>
                                        <th class="py-3 px-4 text-right font-semibold text-slate-700">Cost/bot/lună</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($webchatPlans as $plan)
                                        @php $cpb = $plan->overage['cost_per_extra_bot'] ?? null; @endphp
                                        @if($cpb)
                                            <tr>
                                                <td class="py-3 px-4 text-slate-700 font-medium">{{ $plan->name }}</td>
                                                <td class="py-3 px-4 text-right text-slate-900 font-semibold">&euro;{{ number_format($cpb, 0) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Detaliile vor fi disponibile în curând.</p>
                    @endif
                </div>

                {{-- Minutes overage --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Minute voce suplimentare</h3>
                    </div>
                    @if($voicePlans->count())
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200">
                                        <th class="py-3 px-4 text-left font-semibold text-slate-700">Plan voce</th>
                                        <th class="py-3 px-4 text-right font-semibold text-slate-700">Cost/minut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($voicePlans as $plan)
                                        @php $cpm = $plan->overage['cost_per_minute'] ?? null; @endphp
                                        @if($cpm)
                                            <tr>
                                                <td class="py-3 px-4 text-slate-700 font-medium">{{ $plan->name }}</td>
                                                <td class="py-3 px-4 text-right text-slate-900 font-semibold">&euro;{{ number_format($cpm, 2) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Detaliile vor fi disponibile în curând.</p>
                    @endif
                </div>

            </div>
        </div>
    </section>

    {{-- Section 5: FAQ --}}
    <section class="section-padding bg-slate-50">
        <div class="container-custom max-w-3xl">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-12 animate-fade-in">
                Întrebări <span class="gradient-text">frecvente</span>
            </h2>

            <div class="space-y-4 animate-slide-up" id="faq-accordion">

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-100 transition-colors">
                        <span>Ce se întâmplă când depășesc limita?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-700 text-sm leading-relaxed">
                            Se taxează automat la costul suplimentar al planului tău. Nu există întreruperi ale serviciului.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-100 transition-colors">
                        <span>Pot combina webchat + voce?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-700 text-sm leading-relaxed">
                            Da! Alege un plan webchat și adaugă un addon de voce. Ambele funcționează împreună, cu aceeași bază de cunoștințe.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-100 transition-colors">
                        <span>Pot schimba planul oricând?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-700 text-sm leading-relaxed">
                            Da, upgrade sau downgrade instant. Diferența de preț se calculează pro-rata.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-100 transition-colors">
                        <span>Oferiți perioadă de probă gratuită?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-700 text-sm leading-relaxed">
                            Da, toate planurile includ 14 zile de probă gratuită, fără card de credit.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-100 transition-colors">
                        <span>Cum funcționează facturarea?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-700 text-sm leading-relaxed">
                            Facturarea se face lunar sau anual, prin card de credit sau transfer bancar pentru Enterprise.
                        </div>
                    </div>
                </div>

                <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left text-slate-900 font-semibold hover:bg-slate-100 transition-colors">
                        <span>Oferiți discount pentru ONG-uri?</span>
                        <svg class="faq-icon w-5 h-5 text-slate-500 shrink-0 ml-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="px-6 pb-5 text-slate-700 text-sm leading-relaxed">
                            Da, oferim 30% discount pentru organizații non-profit și instituții de învățământ.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Section 6: Contact CTA --}}
    <section class="section-padding bg-gradient-to-b from-primary-100 to-primary-50">
        <div class="container-custom text-center max-w-2xl animate-fade-in">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
                Nu ești sigur ce plan ți se potrivește?
            </h2>
            <p class="text-lg text-slate-700 mb-8">
                Hai să discutăm. Echipa noastră te poate ajuta să alegi soluția optimă.
            </p>
            <a href="/contact" class="btn-primary inline-block">Programează o consultație gratuită</a>
            <p class="text-sm text-slate-600 mt-4">Răspundem în maxim 2 ore</p>
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
