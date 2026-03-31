@extends('layouts.app')

@section('title', 'Despre noi - Sambla')
@section('meta_description', 'Descoperă povestea din spatele numelui Sambla — un cuvânt din graiul ardelenesc care înseamnă a semăna, a suna a poveste. Tehnologie vocală AI cu suflet românesc.')

@section('content')

{{-- Hero Section --}}
<section class="relative overflow-hidden bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="about-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/><rect x="38" y="2" width="4" height="8" fill="#991b1b"/><rect x="38" y="38" width="4" height="8" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#about-motif)"/></svg>
    </div>
    <div class="absolute top-20 -left-40 w-[400px] h-[400px] bg-red-900/20 rounded-full blur-[120px]"></div>
    <div class="container-custom text-center relative z-10">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-6 animate-fade-in">
            Despre <span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">noi</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed animate-fade-in">
            Un nume cu rădăcini adânci în graiul românesc, o tehnologie care privește spre viitor.
        </p>
    </div>
</section>
<x-motif-border />

{{-- De unde vine numele Section --}}
<section class="section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            {{-- Text --}}
            <div>
                <span class="inline-flex items-center gap-2 text-sm font-semibold text-red-700 mb-4">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 1 L11 5 L8 9 L5 5 Z" opacity="0.6"/>
                        <path d="M8 7 L11 11 L8 15 L5 11 Z" opacity="0.6"/>
                        <path d="M8 4 L11 8 L8 12 L5 8 Z"/>
                    </svg>
                    POVESTEA NUMELUI
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-6">
                    Ce înseamnă <span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">Sambla</span>?
                </h2>
                <p class="text-lg text-slate-600 leading-relaxed mb-6">
                    <strong class="text-slate-900">„A sâmbla"</strong> este un verb vechi din graiul ardelenesc, folosit în Ardeal și Banat de sute de ani. Sensul lui primar este <em>a semăna</em> — dar în literatura clasică, cuvântul capătă o profunzime aparte.
                </p>
                <p class="text-lg text-slate-600 leading-relaxed mb-6">
                    La scriitorii transilvăneni ca <strong class="text-slate-900">Ioan Slavici</strong> și <strong class="text-slate-900">Ion Agârbiceanu</strong>, „a sâmbla" devine <em>a suna a ceva</em>, <em>a părea</em>, <em>a lăsa să se înțeleagă</em>. Când cineva spunea că o întâmplare <strong class="text-slate-900">„sâmblă a poveste"</strong>, însemna că are aura unei narațiuni — a ceva ce merită spus și ascultat.
                </p>

                {{-- Quote block with Romanian ornament --}}
                <div class="relative bg-red-50/50 rounded-2xl p-6 border border-red-100 mb-6">
                    <div class="absolute -top-3 left-6">
                        <svg width="24" height="24" viewBox="0 0 24 24" class="text-red-300" fill="currentColor">
                            <path d="M12 2 L16 8 L12 14 L8 8 Z" opacity="0.5"/>
                            <path d="M12 5 L14 8 L12 11 L10 8 Z"/>
                        </svg>
                    </div>
                    <blockquote class="text-slate-700 italic leading-relaxed">
                        „Nu-i sâmbla a bine ce auzea de prin sat."
                    </blockquote>
                    <cite class="block mt-2 text-sm text-slate-500 not-italic">— Ioan Slavici, <span class="font-medium">Mara</span></cite>
                </div>

                <p class="text-lg text-slate-600 leading-relaxed">
                    Am ales acest nume pentru că <strong class="text-slate-900">Sambla</strong> face exact asta: dă voce, creează o poveste în fiecare apel. Legătura dintre <em>a sâmbla</em> și <em>a vorbi, a povesti</em> este legătura dintre tradiție și inovație — exact ca platforma noastră.
                </p>
            </div>

            {{-- Decorative SVG with Romanian motifs --}}
            <div class="flex justify-center">
                <svg class="w-full max-w-sm" viewBox="0 0 400 460" fill="none" xmlns="http://www.w3.org/2000/svg">
                    {{-- Background circle with traditional pattern --}}
                    <circle cx="200" cy="220" r="180" fill="#fef2f2" opacity="0.5"/>
                    <circle cx="200" cy="220" r="140" fill="#fee2e2" opacity="0.4"/>

                    {{-- Central Romanian diamond pattern --}}
                    <path d="M200 100 L260 220 L200 340 L140 220 Z" fill="#fef2f2" stroke="#991b1b" stroke-width="2"/>
                    <path d="M200 140 L240 220 L200 300 L160 220 Z" fill="#fee2e2" stroke="#b91c1c" stroke-width="1.5"/>
                    <path d="M200 180 L220 220 L200 260 L180 220 Z" fill="#fecaca" stroke="#991b1b" stroke-width="1"/>

                    {{-- Central speaking icon --}}
                    <circle cx="200" cy="220" r="20" fill="#991b1b"/>
                    <path d="M192 215 C192 215 196 210 200 210 C204 210 208 215 208 215 L208 225 C208 225 204 230 200 230 C196 230 192 225 192 225 Z" fill="white" opacity="0.9"/>
                    <path d="M212 212 Q218 220 212 228" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <path d="M216 208 Q224 220 216 232" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.7"/>

                    {{-- Cross-stitch border decorations --}}
                    {{-- Top --}}
                    <rect x="196" y="60" width="8" height="8" fill="#991b1b" opacity="0.3"/>
                    <rect x="188" y="68" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="204" y="68" width="8" height="8" fill="#991b1b" opacity="0.2"/>

                    {{-- Bottom --}}
                    <rect x="196" y="380" width="8" height="8" fill="#991b1b" opacity="0.3"/>
                    <rect x="188" y="372" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="204" y="372" width="8" height="8" fill="#991b1b" opacity="0.2"/>

                    {{-- Left --}}
                    <rect x="72" y="216" width="8" height="8" fill="#991b1b" opacity="0.3"/>
                    <rect x="80" y="208" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="80" y="224" width="8" height="8" fill="#991b1b" opacity="0.2"/>

                    {{-- Right --}}
                    <rect x="320" y="216" width="8" height="8" fill="#991b1b" opacity="0.3"/>
                    <rect x="312" y="208" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="312" y="224" width="8" height="8" fill="#991b1b" opacity="0.2"/>

                    {{-- Corner diamonds --}}
                    <path d="M100 100 L108 108 L100 116 L92 108 Z" fill="#b91c1c" opacity="0.2"/>
                    <path d="M300 100 L308 108 L300 116 L292 108 Z" fill="#b91c1c" opacity="0.2"/>
                    <path d="M100 340 L108 348 L100 356 L92 348 Z" fill="#b91c1c" opacity="0.2"/>
                    <path d="M300 340 L308 348 L300 356 L292 348 Z" fill="#b91c1c" opacity="0.2"/>

                    {{-- Decorative text arc --}}
                    <text x="200" y="420" text-anchor="middle" class="fill-slate-400" font-size="11" font-weight="500" letter-spacing="4">VOCE · POVESTE · AI</text>
                </svg>
            </div>
        </div>
    </div>
</section>

{{-- Romanian motif divider --}}
<x-motif-divider class="my-0" />

{{-- Mission Section --}}
<section class="section-padding bg-slate-50">
    <div class="container-custom">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-6">Misiunea noastră</h2>
        <p class="text-lg text-slate-600 text-center max-w-3xl mx-auto mb-16 leading-relaxed">
            Să democratizăm accesul la tehnologia vocală AI pentru companiile din România și din regiune. Credem că fiecare afacere merită să ofere clienților o experiență de comunicare excepțională, disponibilă 24/7.
        </p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Tradiție --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-red-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 opacity-5">
                    <svg viewBox="0 0 40 40" fill="#991b1b"><path d="M20 0 L30 10 L20 20 L10 10 Z"/><path d="M20 20 L30 30 L20 40 L10 30 Z"/></svg>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-red-700" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2 L16 8 L12 14 L8 8 Z" opacity="0.5"/>
                        <path d="M12 10 L16 16 L12 22 L8 16 Z" opacity="0.5"/>
                        <path d="M12 6 L16 12 L12 18 L8 12 Z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Rădăcini românești</h3>
                <p class="text-slate-600 leading-relaxed">Inspirați de bogăția limbii și culturii românești, construim tehnologie cu suflet. Numele nostru poartă în el secole de tradiție transilvăneană.</p>
            </div>

            {{-- Inovație --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-red-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 opacity-5">
                    <svg viewBox="0 0 40 40" fill="#991b1b"><path d="M20 0 L30 10 L20 20 L10 10 Z"/><path d="M20 20 L30 30 L20 40 L10 30 Z"/></svg>
                </div>
                <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Tehnologie de vârf</h3>
                <p class="text-slate-600 leading-relaxed">Folosim cele mai avansate modele de inteligență artificială pentru a crea agenți vocali care înțeleg și vorbesc natural în limba română.</p>
            </div>

            {{-- Parteneriat --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md hover:border-red-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 opacity-5">
                    <svg viewBox="0 0 40 40" fill="#991b1b"><path d="M20 0 L30 10 L20 20 L10 10 Z"/><path d="M20 20 L30 30 L20 40 L10 30 Z"/></svg>
                </div>
                <div class="w-14 h-14 rounded-xl bg-amber-100 flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Parteneriat real</h3>
                <p class="text-slate-600 leading-relaxed">Nu suntem un simplu furnizor. Suntem partenerul tău în transformarea digitală a comunicării cu clienții.</p>
            </div>
        </div>
    </div>
</section>

{{-- Etymology deep dive --}}
<section class="section-padding">
    <div class="container-custom max-w-4xl">
        <div class="text-center mb-12">
            <x-motif-divider class="mb-8" />
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Din graiul Ardealului, în era AI</h2>
        </div>

        <div class="space-y-8">
            {{-- Slavici quote --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 relative">
                <div class="absolute top-6 left-6 text-red-200">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" opacity="0.4">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>
                </div>
                <div class="pl-12">
                    <blockquote class="text-xl text-slate-700 italic leading-relaxed mb-4">
                        „Mara sâmbla tăt cu bărbatu-său la chip, dar la minte era deosebită."
                    </blockquote>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-red-800">IS</span>
                        </div>
                        <div>
                            <cite class="text-sm font-semibold text-slate-900 not-italic">Ioan Slavici</cite>
                            <p class="text-xs text-slate-500">Maestrul regionalismelor ardelenești</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Agârbiceanu quote --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 relative">
                <div class="absolute top-6 left-6 text-red-200">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" opacity="0.4">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>
                </div>
                <div class="pl-12">
                    <blockquote class="text-xl text-slate-700 italic leading-relaxed mb-4">
                        „Vorba lor sâmbla a minciună, dar oamenii tot o ascultau."
                    </blockquote>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-red-800">IA</span>
                        </div>
                        <div>
                            <cite class="text-sm font-semibold text-slate-900 not-italic">Ion Agârbiceanu</cite>
                            <p class="text-xs text-slate-500">Viața satului transilvănean, <em>Arhanghelii</em></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Connection explanation --}}
        <div class="mt-12 bg-gradient-to-br from-red-50 to-white rounded-2xl p-8 lg:p-10 border border-red-100">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-12 h-12 rounded-xl bg-red-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2 L16 8 L12 14 L8 8 Z" opacity="0.6"/>
                        <path d="M12 10 L16 16 L12 22 L8 16 Z" opacity="0.6"/>
                        <path d="M12 6 L16 12 L12 18 L8 12 Z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">De la grai la tehnologie</h3>
                    <p class="text-slate-600 leading-relaxed mb-4">
                        Când o întâmplare <strong class="text-slate-900">„sâmblă a poveste"</strong>, înseamnă că are aura unei narațiuni — a ceva ce se transmite prin viu grai. Exact asta face platforma noastră: transformă fiecare apel telefonic într-o poveste bine spusă, cu ajutorul inteligenței artificiale.
                    </p>
                    <p class="text-slate-600 leading-relaxed">
                        <strong class="text-slate-900">Sambla</strong> = <em>a semăna</em> + <em>a suna a poveste</em> + <em>a vorbi</em>. Trei sensuri într-un singur cuvânt — trei dimensiuni ale comunicării pe care le aducem în era digitală.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Numbers Section --}}
<section class="py-16 lg:py-20 bg-red-700 relative overflow-hidden">
    {{-- Subtle motif overlay --}}
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="motif-stats" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M20 8 L24 12 L20 16 L16 12 Z" fill="white"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#motif-stats)"/>
        </svg>
    </div>

    <div class="container-custom relative">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">150+</div>
                <div class="text-red-200 font-medium">Clienți activi</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">2M+</div>
                <div class="text-red-200 font-medium">Apeluri procesate</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">99.9%</div>
                <div class="text-red-200 font-medium">Uptime</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">4.8/5</div>
                <div class="text-red-200 font-medium">Rating clienți</div>
            </div>
        </div>
    </div>
</section>

<x-cta-section
    title="Vrei să faci parte din poveste?"
    subtitle="Hai să dăm voce afacerii tale. Cu rădăcini în tradiție și privirea spre viitor."
/>

@endsection
