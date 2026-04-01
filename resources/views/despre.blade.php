@extends('layouts.app')

@section('title', 'Despre noi - Sambla')
@section('meta_description', 'Descoperă povestea Sambla — platformă AI românească pentru chatbot și voce. Un nume din graiul ardelenesc, o tehnologie care privește spre viitor.')

@section('content')

{{-- Hero Section --}}
<section class="relative overflow-hidden bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="about-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/><rect x="38" y="2" width="4" height="8" fill="#991b1b"/><rect x="38" y="38" width="4" height="8" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#about-motif)"/></svg>
    </div>
    <div class="absolute top-20 -left-40 w-[400px] h-[400px] bg-red-900/20 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-10 -right-40 w-[300px] h-[300px] bg-red-800/10 rounded-full blur-[100px]"></div>
    <div class="container-custom text-center relative z-10">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-6 animate-fade-in">
            Despre <span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">Sambla</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed animate-fade-in">
            Platformă AI construită în România, pentru afaceri care vor comunicare inteligentă cu clienții lor.
        </p>
    </div>
</section>
<x-motif-border />

{{-- Cine suntem --}}
<section class="section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div>
                <span class="inline-flex items-center gap-2 text-sm font-semibold text-red-700 mb-4">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    CINE SUNTEM
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-6">
                    O echipă mică cu <span class="bg-gradient-to-r from-red-600 to-red-400 bg-clip-text text-transparent">ambiții mari</span>
                </h2>
                <p class="text-lg text-slate-600 leading-relaxed mb-6">
                    Sambla a pornit dintr-o convingere simplă: companiile din România merită acces la aceeași tehnologie AI pe care o folosesc giganții tech din Silicon Valley, dar adaptată limbii și culturii noastre.
                </p>
                <p class="text-lg text-slate-600 leading-relaxed mb-6">
                    Construim o platformă completă de comunicare inteligentă — de la chatbot text pe site, la agenți vocali care sună ca oamenii reali. Totul hosted în România, totul conform GDPR, totul în limba română.
                </p>
                <p class="text-lg text-slate-600 leading-relaxed">
                    Nu suntem încă un brand mare. Suntem o echipă care construiește zi de zi, release după release, o platformă de care suntem mândri. Fiecare funcționalitate pe care o vezi a fost gândită, testată și rafinată cu grijă.
                </p>
            </div>

            {{-- Ce ne diferențiază --}}
            <div class="space-y-5">
                <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-red-200 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 mb-1">100% românesc</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Hosting în România, optimizat pentru limba română, suport local. Nu e un produs tradus din engleză — e construit de la zero pentru piața noastră.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-red-200 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 mb-1">Cele mai bune modele AI</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">GPT-4o, Claude, OpenAI Realtime API, ElevenLabs. Folosim cele mai avansate modele disponibile și comutăm automat între ele pentru cel mai bun rezultat.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-red-200 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.648-3.014A2.066 2.066 0 004 14.456V17.5a2.068 2.068 0 001.772 2.3l5.648 1.013a2.062 2.062 0 002.159-.823l.196-.282a2.066 2.066 0 00-.164-2.554L11.42 15.17zm0 0l5.082-5.083a2.073 2.073 0 00.546-1.022l.63-3.462a2.068 2.068 0 00-2.378-2.378l-3.463.63a2.073 2.073 0 00-1.021.546L5.734 9.404" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 mb-1">Platformă completă, nu doar chatbot</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Chatbot text, agent vocal, bază de cunoștințe, lead management, analytics, e-commerce integration, API — totul într-un singur loc.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-red-200 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 mb-1">GDPR nativ</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Consimțământ explicit, izolare datelor per client, hosting România. Nu e un addon — e integrat în arhitectura platformei.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Valori --}}
<section class="py-16 lg:py-20 bg-red-700 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="motif-vals" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M20 8 L24 12 L20 16 L16 12 Z" fill="white"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#motif-vals)"/>
        </svg>
    </div>

    <div class="container-custom relative">
        <h2 class="text-3xl font-bold text-white text-center mb-12">Valorile noastre</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <div class="text-center">
                <div class="text-4xl mb-3 text-white">
                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                </div>
                <div class="text-xl font-bold text-white mb-1">Transparență</div>
                <p class="text-red-200 text-sm">Nu promitem ce nu putem livra. Prețuri clare, funcționalități reale.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-3 text-white">
                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.648-3.014A2.066 2.066 0 004 14.456V17.5a2.068 2.068 0 001.772 2.3l5.648 1.013a2.062 2.062 0 002.159-.823l.196-.282a2.066 2.066 0 00-.164-2.554L11.42 15.17zm0 0l5.082-5.083a2.073 2.073 0 00.546-1.022l.63-3.462a2.068 2.068 0 00-2.378-2.378l-3.463.63a2.073 2.073 0 00-1.021.546L5.734 9.404" /></svg>
                </div>
                <div class="text-xl font-bold text-white mb-1">Calitate</div>
                <p class="text-red-200 text-sm">Fiecare feature e testat și rafinat. Preferăm mai puțin, dar bine.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-3 text-white">
                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                </div>
                <div class="text-xl font-bold text-white mb-1">Parteneriat</div>
                <p class="text-red-200 text-sm">Lucrăm cu tine, nu doar pentru tine. Feedback-ul tău modelează produsul.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-3 text-white">
                    <svg class="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg>
                </div>
                <div class="text-xl font-bold text-white mb-1">Securitate</div>
                <p class="text-red-200 text-sm">GDPR, izolare datelor, hosting RO. Securitatea nu e opțională.</p>
            </div>
        </div>
    </div>
</section>

{{-- Romanian motif divider --}}
<x-motif-divider class="my-0" />

{{-- Povestea numelui --}}
<section class="section-padding bg-slate-50">
    <div class="container-custom">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            {{-- Decorative SVG --}}
            <div class="flex justify-center order-2 lg:order-1">
                <svg class="w-full max-w-sm" viewBox="0 0 400 460" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="200" cy="220" r="180" fill="#fef2f2" opacity="0.5"/>
                    <circle cx="200" cy="220" r="140" fill="#fee2e2" opacity="0.4"/>
                    <path d="M200 100 L260 220 L200 340 L140 220 Z" fill="#fef2f2" stroke="#991b1b" stroke-width="2"/>
                    <path d="M200 140 L240 220 L200 300 L160 220 Z" fill="#fee2e2" stroke="#b91c1c" stroke-width="1.5"/>
                    <path d="M200 180 L220 220 L200 260 L180 220 Z" fill="#fecaca" stroke="#991b1b" stroke-width="1"/>
                    <circle cx="200" cy="220" r="20" fill="#991b1b"/>
                    <path d="M192 215 C192 215 196 210 200 210 C204 210 208 215 208 215 L208 225 C208 225 204 230 200 230 C196 230 192 225 192 225 Z" fill="white" opacity="0.9"/>
                    <path d="M212 212 Q218 220 212 228" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <path d="M216 208 Q224 220 216 232" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.7"/>
                    <rect x="196" y="60" width="8" height="8" fill="#991b1b" opacity="0.3"/>
                    <rect x="188" y="68" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="204" y="68" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="196" y="380" width="8" height="8" fill="#991b1b" opacity="0.3"/>
                    <rect x="188" y="372" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <rect x="204" y="372" width="8" height="8" fill="#991b1b" opacity="0.2"/>
                    <path d="M100 100 L108 108 L100 116 L92 108 Z" fill="#b91c1c" opacity="0.2"/>
                    <path d="M300 100 L308 108 L300 116 L292 108 Z" fill="#b91c1c" opacity="0.2"/>
                    <path d="M100 340 L108 348 L100 356 L92 348 Z" fill="#b91c1c" opacity="0.2"/>
                    <path d="M300 340 L308 348 L300 356 L292 348 Z" fill="#b91c1c" opacity="0.2"/>
                    <text x="200" y="420" text-anchor="middle" class="fill-slate-400" font-size="11" font-weight="500" letter-spacing="4">VOCE &middot; POVESTE &middot; AI</text>
                </svg>
            </div>

            {{-- Text --}}
            <div class="order-1 lg:order-2">
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
                    <strong class="text-slate-900">„A sâmbla"</strong> este un verb vechi din graiul ardelenesc, folosit în Transilvania și Banat de sute de ani. Sensul lui primar este <em>a semăna</em> — dar la scriitorii transilvăneni precum <strong class="text-slate-900">Ioan Slavici</strong> și <strong class="text-slate-900">Ion Agârbiceanu</strong>, cuvântul capătă o profunzime aparte: <em>a suna a ceva</em>, <em>a lăsa să se înțeleagă</em>.
                </p>

                {{-- Slavici quote --}}
                <div class="relative bg-white rounded-2xl p-6 border border-slate-200 shadow-sm mb-6">
                    <div class="absolute -top-3 left-6">
                        <svg width="24" height="24" viewBox="0 0 24 24" class="text-red-300" fill="currentColor">
                            <path d="M12 2 L16 8 L12 14 L8 8 Z" opacity="0.5"/>
                            <path d="M12 5 L14 8 L12 11 L10 8 Z"/>
                        </svg>
                    </div>
                    <blockquote class="text-slate-700 italic leading-relaxed">
                        „Nu-i sâmbla a bine ce auzea de prin sat."
                    </blockquote>
                    <cite class="block mt-2 text-sm text-slate-500 not-italic">— <span class="font-semibold text-slate-700">Ioan Slavici</span>, Mara</cite>
                </div>

                {{-- Agârbiceanu quote --}}
                <div class="relative bg-white rounded-2xl p-6 border border-slate-200 shadow-sm mb-6">
                    <div class="absolute -top-3 left-6">
                        <svg width="24" height="24" viewBox="0 0 24 24" class="text-red-300" fill="currentColor">
                            <path d="M12 2 L16 8 L12 14 L8 8 Z" opacity="0.5"/>
                            <path d="M12 5 L14 8 L12 11 L10 8 Z"/>
                        </svg>
                    </div>
                    <blockquote class="text-slate-700 italic leading-relaxed">
                        „Vorba lor sâmbla a minciună, dar oamenii tot o ascultau."
                    </blockquote>
                    <cite class="block mt-2 text-sm text-slate-500 not-italic">— <span class="font-semibold text-slate-700">Ion Agârbiceanu</span>, Arhanghelii</cite>
                </div>

                <p class="text-lg text-slate-600 leading-relaxed">
                    Am ales acest nume pentru că <strong class="text-slate-900">Sambla</strong> face exact asta: dă voce, creează o poveste în fiecare conversație. <em>A semăna</em> + <em>a suna a poveste</em> + <em>a vorbi</em> — trei sensuri într-un singur cuvânt, trei dimensiuni ale comunicării în era AI.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Viziune --}}
<section class="section-padding bg-slate-950 relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.03]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="vis-grid" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M60 0 L0 0 L0 60" fill="none" stroke="#991b1b" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#vis-grid)"/></svg>
    </div>
    <div class="container-custom relative z-10">
        <div class="text-center mb-14">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-900/30 text-red-400 text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Viziunea noastră
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Unde mergem</h2>
            <p class="text-slate-400 max-w-3xl mx-auto text-lg leading-relaxed">
                Construim platforma pe care ne-am fi dorit-o noi înșine: un singur loc unde orice afacere din România poate avea un agent AI care vorbește, scrie și înțelege clienții la fel de bine ca cel mai bun angajat.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-red-500/40 transition-colors duration-300">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Chatbot text</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Widget pe site cu RAG, e-commerce, lead generation și detecție intenții. Live și funcțional.</p>
                <span class="inline-flex items-center mt-3 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Live</span>
            </div>

            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-red-500/40 transition-colors duration-300">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Agent vocal AI</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Apeluri vocale cu OpenAI Realtime, voice cloning ElevenLabs, transcriere și analiză sentiment.</p>
                <span class="inline-flex items-center mt-3 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Beta</span>
            </div>

            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-red-500/40 transition-colors duration-300">
                <div class="w-10 h-10 rounded-lg bg-red-700/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                </div>
                <h3 class="text-white font-semibold mb-2">API & integrări</h3>
                <p class="text-slate-400 text-sm leading-relaxed">REST API complet, webhook-uri, WebSocket. WordPress, WooCommerce și CRM-uri.</p>
                <span class="inline-flex items-center mt-3 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Live</span>
            </div>
        </div>
    </div>
</section>

{{-- Tehnologii --}}
<section class="section-padding">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Stiva tehnologică</h2>
            <p class="text-slate-600 max-w-2xl mx-auto text-lg">Folosim cele mai avansate tehnologii disponibile, integrate într-o platformă coerentă.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {{-- Tech cards --}}
            <div class="p-4 rounded-xl bg-white border border-slate-200 text-center hover:shadow-md hover:border-red-200 transition-all duration-300">
                <div class="text-2xl mb-2 font-bold text-slate-900">GPT-4o</div>
                <p class="text-xs text-slate-500">AI conversațional</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 text-center hover:shadow-md hover:border-red-200 transition-all duration-300">
                <div class="text-2xl mb-2 font-bold text-slate-900">Claude</div>
                <p class="text-xs text-slate-500">AI multi-model</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 text-center hover:shadow-md hover:border-red-200 transition-all duration-300">
                <div class="text-2xl mb-2 font-bold text-slate-900">Twilio</div>
                <p class="text-xs text-slate-500">Telefonie & SMS</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 text-center hover:shadow-md hover:border-red-200 transition-all duration-300">
                <div class="text-2xl mb-2 font-bold text-slate-900">11Labs</div>
                <p class="text-xs text-slate-500">Voice cloning</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 text-center hover:shadow-md hover:border-red-200 transition-all duration-300">
                <div class="text-2xl mb-2 font-bold text-slate-900">Laravel</div>
                <p class="text-xs text-slate-500">Backend robust</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 text-center hover:shadow-md hover:border-red-200 transition-all duration-300">
                <div class="text-2xl mb-2 font-bold text-slate-900">pgvector</div>
                <p class="text-xs text-slate-500">Căutare vectorială</p>
            </div>
        </div>
    </div>
</section>

<x-cta-section
    title="Vrei să faci parte din poveste?"
    subtitle="Hai să dăm voce afacerii tale. Configurare în 10 minute, fără card de credit."
/>

@endsection
