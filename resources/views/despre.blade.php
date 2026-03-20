@extends('layouts.app')

@section('title', 'Despre VoiceBot - Echipa și Misiunea Noastră')
@section('meta_description', 'Află povestea VoiceBot, misiunea noastră de a democratiza tehnologia vocală AI și echipa din spatele platformei.')

@section('content')

{{-- Hero Section --}}
<section class="pt-32 lg:pt-40 pb-16 lg:pb-20 bg-gradient-to-b from-primary-50 to-white">
    <div class="container-custom text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 animate-fade-in">
            Despre <span class="gradient-text">VoiceBot</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed animate-fade-in-delay-1">
            Construim viitorul comunicării cu clienții prin inteligență artificială
        </p>
    </div>
</section>

{{-- Mission Section --}}
<section class="section-padding">
    <div class="container-custom">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-16">Misiunea noastră</h2>
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Text --}}
            <div>
                <p class="text-lg text-slate-600 leading-relaxed mb-6">
                    Ne-am propus să democratizăm accesul la tehnologia vocală AI pentru companiile din România și din regiune. Credem că fiecare afacere, indiferent de dimensiune, merită să ofere clienților o experiență de comunicare excepțională, disponibilă 24/7.
                </p>
                <p class="text-lg text-slate-600 leading-relaxed">
                    Viziunea noastră este un viitor în care barierele lingvistice și limitările de program nu mai sunt un obstacol în relația cu clienții. Construim instrumente care permit oricărei companii să ofere suport vocal inteligent, personalizat și scalabil, transformând fiecare apel într-o experiență memorabilă.
                </p>
            </div>
            {{-- Abstract SVG Illustration --}}
            <div class="flex justify-center">
                <svg class="w-full max-w-md" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="50" y="40" width="300" height="220" rx="20" fill="#f5f3ff" stroke="#c4b5fd" stroke-width="2"/>
                    <circle cx="200" cy="130" r="60" fill="#ede9fe" stroke="#a78bfa" stroke-width="2"/>
                    <circle cx="200" cy="130" r="35" fill="#ddd6fe" stroke="#8b5cf6" stroke-width="2"/>
                    <circle cx="200" cy="130" r="12" fill="#7c3aed"/>
                    {{-- Sound waves --}}
                    <path d="M280 100 Q300 130 280 160" stroke="#a78bfa" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M295 85 Q325 130 295 175" stroke="#c4b5fd" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <path d="M310 70 Q350 130 310 190" stroke="#ddd6fe" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <path d="M120 100 Q100 130 120 160" stroke="#a78bfa" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M105 85 Q75 130 105 175" stroke="#c4b5fd" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <path d="M90 70 Q50 130 90 190" stroke="#ddd6fe" stroke-width="2" fill="none" stroke-linecap="round"/>
                    {{-- Decorative dots --}}
                    <circle cx="140" cy="60" r="4" fill="#c4b5fd"/>
                    <circle cx="260" cy="220" r="4" fill="#c4b5fd"/>
                    <circle cx="300" cy="60" r="3" fill="#ddd6fe"/>
                    <circle cx="100" cy="220" r="3" fill="#ddd6fe"/>
                    {{-- Connection lines --}}
                    <line x1="160" y1="200" x2="180" y2="220" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="240" y1="200" x2="220" y2="220" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="180" y1="220" x2="220" y2="220" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="200" cy="240" r="8" fill="#8b5cf6" opacity="0.6"/>
                </svg>
            </div>
        </div>
    </div>
</section>

{{-- Values Section --}}
<section class="section-padding bg-slate-50">
    <div class="container-custom">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-4">Valorile noastre</h2>
        <p class="text-lg text-slate-600 text-center max-w-2xl mx-auto mb-16">Principiile care ne ghidează în tot ceea ce facem.</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            {{-- Inovație --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-primary-200 transition-all duration-300">
                <div class="w-14 h-14 rounded-xl bg-primary-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Inovație</h3>
                <p class="text-slate-600 leading-relaxed">Îmbrățișăm cele mai noi tehnologii pentru a oferi soluții de vârf.</p>
            </div>

            {{-- Transparență --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-primary-200 transition-all duration-300">
                <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Transparență</h3>
                <p class="text-slate-600 leading-relaxed">Prețuri clare, comunicare deschisă, fără surprize.</p>
            </div>

            {{-- Orientare către client --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-primary-200 transition-all duration-300">
                <div class="w-14 h-14 rounded-xl bg-amber-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Orientare către client</h3>
                <p class="text-slate-600 leading-relaxed">Succesul tău este succesul nostru. Suntem parteneri, nu furnizori.</p>
            </div>

            {{-- Calitate --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-primary-200 transition-all duration-300">
                <div class="w-14 h-14 rounded-xl bg-rose-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Calitate</h3>
                <p class="text-slate-600 leading-relaxed">Fiecare linie de cod, fiecare interacțiune — la cele mai înalte standarde.</p>
            </div>
        </div>
    </div>
</section>

{{-- Team Section --}}
<section class="section-padding">
    <div class="container-custom">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-4">Echipa</h2>
        <p class="text-lg text-slate-600 text-center max-w-2xl mx-auto mb-16">Oamenii pasionați din spatele VoiceBot.</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            {{-- Alexandru Ionescu --}}
            <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300">
                <div class="w-20 h-20 rounded-full bg-primary-600 flex items-center justify-center mx-auto mb-5">
                    <span class="text-2xl font-bold text-white">AI</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Alexandru Ionescu</h3>
                <p class="text-sm font-semibold text-primary-600 mb-4">CEO & Co-Fondator</p>
                <p class="text-sm text-slate-600 leading-relaxed">Fost VP Engineering la UiPath. 15 ani experiență în AI și automatizare.</p>
            </div>

            {{-- Diana Popescu --}}
            <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300">
                <div class="w-20 h-20 rounded-full bg-emerald-600 flex items-center justify-center mx-auto mb-5">
                    <span class="text-2xl font-bold text-white">DP</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Diana Popescu</h3>
                <p class="text-sm font-semibold text-primary-600 mb-4">CTO & Co-Fondator</p>
                <p class="text-sm text-slate-600 leading-relaxed">PhD în NLP la Politehnica București. Expert în procesare de limbaj natural.</p>
            </div>

            {{-- Radu Marinescu --}}
            <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300">
                <div class="w-20 h-20 rounded-full bg-amber-600 flex items-center justify-center mx-auto mb-5">
                    <span class="text-2xl font-bold text-white">RM</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Radu Marinescu</h3>
                <p class="text-sm font-semibold text-primary-600 mb-4">Head of Product</p>
                <p class="text-sm text-slate-600 leading-relaxed">10 ani în product management la companii SaaS internaționale.</p>
            </div>

            {{-- Ioana Dumitrescu --}}
            <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300">
                <div class="w-20 h-20 rounded-full bg-rose-600 flex items-center justify-center mx-auto mb-5">
                    <span class="text-2xl font-bold text-white">ID</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Ioana Dumitrescu</h3>
                <p class="text-sm font-semibold text-primary-600 mb-4">Head of Customer Success</p>
                <p class="text-sm text-slate-600 leading-relaxed">Experiență vastă în relații cu clienții și implementări enterprise.</p>
            </div>
        </div>
    </div>
</section>

{{-- Numbers Section --}}
<section class="py-16 lg:py-20 bg-primary-600">
    <div class="container-custom">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">150+</div>
                <div class="text-primary-200 font-medium">Clienți activi</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">2M+</div>
                <div class="text-primary-200 font-medium">Apeluri procesate</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">99.9%</div>
                <div class="text-primary-200 font-medium">Uptime</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">4.8/5</div>
                <div class="text-primary-200 font-medium">Rating clienți</div>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="section-padding">
    <div class="container-custom text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-6">Vrei să faci parte din poveste?</h2>
        <p class="text-lg text-slate-600 max-w-xl mx-auto mb-10">Suntem mereu în căutare de oameni talentați care vor să schimbe modul în care companiile comunică.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="/contact" class="btn-primary text-lg px-8 py-4">Vezi pozițiile deschise</a>
            <a href="/contact" class="text-primary-600 hover:text-primary-700 font-semibold transition-colors duration-200">Sau contactează-ne &rarr;</a>
        </div>
    </div>
</section>

@endsection
