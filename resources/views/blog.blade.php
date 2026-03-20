@extends('layouts.app')

@section('title', 'Blog - Sambla')
@section('meta_description', 'Citește cele mai noi articole despre inteligență artificială, agenți vocali și comunicare cu clienții pe blogul Sambla.')

@section('content')

{{-- Hero Section --}}
<section class="pt-32 lg:pt-40 pb-16 lg:pb-20 bg-gradient-to-b from-primary-50 to-white">
    <div class="container-custom text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 animate-fade-in">
            <span class="gradient-text">Blog</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed animate-fade-in-delay-1">
            Noutăți, ghiduri și perspective din lumea AI și comunicării cu clienții
        </p>
    </div>
</section>

{{-- Article Grid --}}
<section class="section-padding">
    <div class="container-custom">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">

            {{-- Article 1 --}}
            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-all duration-300 group">
                {{-- Image placeholder --}}
                <div class="h-52 bg-gradient-to-br from-primary-500 to-primary-700 relative overflow-hidden">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white/20" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    {{-- Category badge --}}
                    <div class="absolute top-4 left-4">
                        <span class="inline-block px-3 py-1 bg-white/90 backdrop-blur-sm text-primary-700 text-xs font-bold rounded-full uppercase tracking-wide">Ghid</span>
                    </div>
                </div>
                {{-- Content --}}
                <div class="p-6 lg:p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-primary-600 transition-colors duration-200 leading-snug">
                        Cum să implementezi un agent vocal AI în 5 pași simpli
                    </h2>
                    <p class="text-slate-600 text-sm leading-relaxed mb-5">
                        Descoperă procesul pas cu pas pentru a configura primul tău agent vocal AI, de la alegerea scenariului până la lansare.
                    </p>
                    {{-- Meta --}}
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-5 pt-5 border-t border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                15 Mar 2024
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                5 min citire
                            </span>
                        </div>
                    </div>
                    {{-- Author + Link --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">AI</span>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Alexandru Ionescu</span>
                        </div>
                        <a href="#" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors duration-200">
                            Citește mai mult &rarr;
                        </a>
                    </div>
                </div>
            </article>

            {{-- Article 2 --}}
            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-all duration-300 group">
                {{-- Image placeholder --}}
                <div class="h-52 bg-gradient-to-br from-emerald-500 to-emerald-700 relative overflow-hidden">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white/20" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    {{-- Category badge --}}
                    <div class="absolute top-4 left-4">
                        <span class="inline-block px-3 py-1 bg-white/90 backdrop-blur-sm text-emerald-700 text-xs font-bold rounded-full uppercase tracking-wide">Studiu de caz</span>
                    </div>
                </div>
                {{-- Content --}}
                <div class="p-6 lg:p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-primary-600 transition-colors duration-200 leading-snug">
                        Cum a redus FinanceHub timpul de așteptare cu 75%
                    </h2>
                    <p class="text-slate-600 text-sm leading-relaxed mb-5">
                        Află cum clientul nostru din domeniul financiar a transformat experiența clienților folosind Sambla.
                    </p>
                    {{-- Meta --}}
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-5 pt-5 border-t border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                10 Mar 2024
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                8 min citire
                            </span>
                        </div>
                    </div>
                    {{-- Author + Link --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">DP</span>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Diana Popescu</span>
                        </div>
                        <a href="#" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors duration-200">
                            Citește mai mult &rarr;
                        </a>
                    </div>
                </div>
            </article>

            {{-- Article 3 --}}
            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-all duration-300 group">
                {{-- Image placeholder --}}
                <div class="h-52 bg-gradient-to-br from-amber-500 to-orange-600 relative overflow-hidden">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white/20" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                        </svg>
                    </div>
                    {{-- Category badge --}}
                    <div class="absolute top-4 left-4">
                        <span class="inline-block px-3 py-1 bg-white/90 backdrop-blur-sm text-amber-700 text-xs font-bold rounded-full uppercase tracking-wide">Tehnologie</span>
                    </div>
                </div>
                {{-- Content --}}
                <div class="p-6 lg:p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-primary-600 transition-colors duration-200 leading-snug">
                        Ce este OpenAI Realtime API și cum schimbă comunicarea vocală
                    </h2>
                    <p class="text-slate-600 text-sm leading-relaxed mb-5">
                        O privire în detaliu asupra tehnologiei care face posibilă conversația naturală cu un AI.
                    </p>
                    {{-- Meta --}}
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-5 pt-5 border-t border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                5 Mar 2024
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                6 min citire
                            </span>
                        </div>
                    </div>
                    {{-- Author + Link --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-600 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">RM</span>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Radu Marinescu</span>
                        </div>
                        <a href="#" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors duration-200">
                            Citește mai mult &rarr;
                        </a>
                    </div>
                </div>
            </article>

        </div>
    </div>
</section>

{{-- Newsletter CTA --}}
<section class="section-padding bg-slate-50">
    <div class="container-custom">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Abonează-te la newsletter</h2>
            <p class="text-lg text-slate-600 mb-8">Primești cele mai noi articole direct în inbox. Fără spam.</p>
            <form id="newsletter-form" class="flex flex-col sm:flex-row gap-4 max-w-lg mx-auto">
                <input type="email" required placeholder="adresa@email.com"
                    class="flex-1 px-5 py-3.5 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                <button type="submit" class="btn-primary px-8 py-3.5 whitespace-nowrap">
                    Abonează-te
                </button>
            </form>
            {{-- Success message --}}
            <div id="newsletter-success" class="hidden mt-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                <p class="text-emerald-800 font-medium text-sm">Te-ai abonat cu succes! Vei primi cele mai noi articole pe email.</p>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    (function() {
        var form = document.getElementById('newsletter-form');
        var success = document.getElementById('newsletter-success');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                form.style.display = 'none';
                success.classList.remove('hidden');
            });
        }
    })();
</script>
@endpush
