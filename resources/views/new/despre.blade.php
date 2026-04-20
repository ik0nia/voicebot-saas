@extends('layouts.new')

@section('title', 'Despre Sambla — platformă AI românească pentru agent AI și voce')
@section('meta_description', 'Sambla este o platformă AI românească construită de o echipă din România pentru afaceri mici și mijlocii. Numele vine din graiul ardelenesc — „a sâmbla" = a semăna, a suna a poveste.')
@section('canonical', url('/new/despre'))

@section('jsonld')
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"AboutPage","@id":"https://sambla.ro/new/despre#webpage","url":"https://sambla.ro/new/despre","name":"Despre Sambla","description":"Sambla este o platformă AI românească construită de o echipă din România pentru afaceri mici și mijlocii. Numele Sambla vine din graiul ardelenesc.","isPartOf":{"@id":"https://sambla.ro/#website"},"mainEntity":{"@id":"https://sambla.ro/#organization"},"inLanguage":"ro-RO"}
</script>
@endsection

@section('content')

{{-- HERO --}}
<section class="hero-glow relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-outline mono text-[11px] uppercase tracking-wider inline-flex mb-7">◇ despre noi</div>
        <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-6">
            Despre <em class="italic accent-text font-normal">Sambla</em>.
        </h1>
        <p class="text-xl md:text-2xl leading-relaxed text-muted max-w-3xl mx-auto">
            Platformă AI construită în România, pentru afaceri care vor comunicare inteligentă cu clienții lor.
        </p>
    </div>
</section>

{{-- CINE SUNTEM + diferențiatori --}}
<section class="py-20 md:py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-start">
            <div class="fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] accent-text mb-4">◇ cine suntem</div>
                <h2 class="display text-4xl md:text-5xl font-medium leading-[1.05] mb-6">
                    O echipă mică<br>cu <em class="italic accent-text">ambiții mari</em>.
                </h2>
                <div class="space-y-5 text-lg leading-relaxed text-muted">
                    <p>Sambla a pornit dintr-o convingere simplă: companiile din România merită acces la aceeași tehnologie AI pe care o folosesc giganții tech din Silicon Valley, dar <strong class="text-ink">adaptată limbii și culturii noastre</strong>.</p>
                    <p>Construim o platformă completă de comunicare inteligentă — de la agent AI text pe site, la agenți vocali care sună ca oamenii reali. Totul <strong class="text-ink">hostat în România, conform GDPR, în limba română</strong>.</p>
                    <p>Nu suntem încă un brand mare. Suntem o echipă care construiește zi de zi, release după release, o platformă de care suntem mândri. Fiecare funcționalitate pe care o vezi a fost gândită, testată și rafinată cu grijă.</p>
                </div>
            </div>

            <div class="space-y-4 fade-up" style="transition-delay:.1s;">
                <div class="niche-card rounded-2xl p-6 bg-paper border border-line flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl accent-soft-bg accent-text flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">100% românesc</h3>
                        <p class="text-sm text-muted leading-relaxed">Hosting în România, optimizat pentru limba română, suport local. Nu e un produs tradus din engleză — e construit de la zero pentru piața noastră.</p>
                    </div>
                </div>

                <div class="niche-card rounded-2xl p-6 bg-paper border border-line flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background:#D1FAE5; color:#047857;">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Cele mai avansate modele AI disponibile</h3>
                        <p class="text-sm text-muted leading-relaxed">Folosim cele mai bune modele de limbaj și voce disponibile pe piață, și comutăm automat între ele pentru cel mai bun rezultat în fiecare conversație.</p>
                    </div>
                </div>

                <div class="niche-card rounded-2xl p-6 bg-paper border border-line flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background:#FEF3C7; color:#92400E;">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.648-3.014A2.066 2.066 0 004 14.456V17.5a2.068 2.068 0 001.772 2.3l5.648 1.013a2.062 2.062 0 002.159-.823l.196-.282a2.066 2.066 0 00-.164-2.554L11.42 15.17z" /></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Platformă completă, nu doar agent AI</h3>
                        <p class="text-sm text-muted leading-relaxed">Agent AI text, agent vocal, bază de cunoștințe, lead management, analytics, e-commerce integration, API — totul într-un singur loc.</p>
                    </div>
                </div>

                <div class="niche-card rounded-2xl p-6 bg-paper border border-line flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background:#DBEAFE; color:#1D4ED8;">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286z" /></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">GDPR nativ</h3>
                        <p class="text-sm text-muted leading-relaxed">Consimțământ explicit, izolare datelor per client, hosting România. Nu e un addon — e integrat în arhitectura platformei.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- VALORI --}}
<section class="py-20 md:py-24 bg-ink relative overflow-hidden">
    <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, rgba(220,38,38,0.3) 0%, transparent 70%);"></div>
    <div class="max-w-7xl mx-auto px-6 relative">
        <div class="text-center mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color:#F2E59A;">◇ valorile noastre</div>
            <h2 class="display text-4xl md:text-5xl font-medium text-cream">Principii pe care le <em class="italic" style="color:#F2E59A;">simți</em> în produs.</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            @foreach([
                ['⚡','Transparență','Nu promitem ce nu putem livra. Prețuri clare, funcționalități reale.'],
                ['✨','Calitate','Fiecare feature e testat și rafinat. Preferăm mai puțin, dar bine.'],
                ['🤝','Parteneriat','Lucrăm cu tine, nu doar pentru tine. Feedback-ul tău modelează produsul.'],
                ['🛡️','Securitate','GDPR, izolare datelor, hosting RO. Securitatea nu e opțională.'],
            ] as $v)
                <div class="text-center fade-up">
                    <div class="text-4xl mb-4">{{ $v[0] }}</div>
                    <div class="display text-xl font-semibold text-cream mb-2">{{ $v[1] }}</div>
                    <p class="text-sm leading-relaxed" style="color:#A8A29E;">{{ $v[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- POVESTEA NUMELUI --}}
<section class="py-20 md:py-24 bg-paper grain">
    <div class="max-w-6xl mx-auto px-6 relative">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="flex justify-center order-2 lg:order-1 fade-up">
                <svg class="w-full max-w-sm" viewBox="0 0 400 460" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="200" cy="220" r="180" fill="#FEE2E2" opacity="0.5"/>
                    <circle cx="200" cy="220" r="140" fill="#FECACA" opacity="0.4"/>
                    <path d="M200 100 L260 220 L200 340 L140 220 Z" fill="#FEF2F2" stroke="#991B1B" stroke-width="2"/>
                    <path d="M200 140 L240 220 L200 300 L160 220 Z" fill="#FEE2E2" stroke="#B91C1C" stroke-width="1.5"/>
                    <path d="M200 180 L220 220 L200 260 L180 220 Z" fill="#FECACA" stroke="#991B1B" stroke-width="1"/>
                    <circle cx="200" cy="220" r="20" fill="#991B1B"/>
                    <path d="M192 215 C192 215 196 210 200 210 C204 210 208 215 208 215 L208 225 C208 225 204 230 200 230 C196 230 192 225 192 225 Z" fill="white" opacity="0.9"/>
                    <path d="M212 212 Q218 220 212 228" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <path d="M216 208 Q224 220 216 232" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.7"/>
                    <text x="200" y="420" text-anchor="middle" fill="#78716C" font-size="11" font-weight="500" letter-spacing="4">VOCE · POVESTE · AI</text>
                </svg>
            </div>

            <div class="order-1 lg:order-2 fade-up" style="transition-delay:.1s;">
                <div class="mono text-[11px] uppercase tracking-[0.2em] accent-text mb-4">◇ povestea numelui</div>
                <h2 class="display text-4xl md:text-5xl font-medium leading-[1.05] mb-6">
                    Ce înseamnă <em class="italic accent-text">Sambla</em>?
                </h2>
                <p class="text-lg text-muted leading-relaxed mb-6">
                    <strong class="text-ink">„A sâmbla"</strong> este un verb vechi din graiul ardelenesc, folosit în Transilvania și Banat de sute de ani. Sensul lui primar este <em>a semăna</em> — dar la scriitorii transilvăneni precum <strong class="text-ink">Ioan Slavici</strong> și <strong class="text-ink">Ion Agârbiceanu</strong>, cuvântul capătă o profunzime aparte: <em>a suna a ceva</em>, <em>a lăsa să se înțeleagă</em>.
                </p>

                <div class="rounded-2xl p-6 bg-cream border border-line mb-4 relative">
                    <div class="absolute -top-3 left-6 w-6 h-6 rounded-full flex items-center justify-center" style="background:#FEE2E2;">
                        <span class="text-xs accent-text">❝</span>
                    </div>
                    <blockquote class="italic leading-relaxed text-ink">
                        „Nu-i sâmbla a bine ce auzea de prin sat."
                    </blockquote>
                    <cite class="block mt-2 text-sm text-muted not-italic">— <span class="font-semibold text-ink">Ioan Slavici</span>, Mara</cite>
                </div>

                <div class="rounded-2xl p-6 bg-cream border border-line mb-6 relative">
                    <div class="absolute -top-3 left-6 w-6 h-6 rounded-full flex items-center justify-center" style="background:#FEE2E2;">
                        <span class="text-xs accent-text">❝</span>
                    </div>
                    <blockquote class="italic leading-relaxed text-ink">
                        „Vorba lor sâmbla a minciună, dar oamenii tot o ascultau."
                    </blockquote>
                    <cite class="block mt-2 text-sm text-muted not-italic">— <span class="font-semibold text-ink">Ion Agârbiceanu</span>, Arhanghelii</cite>
                </div>

                <p class="text-lg text-muted leading-relaxed">
                    Am ales acest nume pentru că <strong class="text-ink">Sambla</strong> face exact asta: dă voce, creează o poveste în fiecare conversație. <em>A semăna</em> + <em>a suna a poveste</em> + <em>a vorbi</em> — trei sensuri într-un singur cuvânt, trei dimensiuni ale comunicării în era AI.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- VIZIUNE --}}
<section class="py-20 md:py-24">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ viziunea noastră</div>
            <h2 class="display text-4xl md:text-5xl font-medium mb-6">Unde mergem.</h2>
            <p class="text-muted max-w-3xl mx-auto text-lg leading-relaxed">
                Construim platforma pe care ne-am fi dorit-o noi înșine: un singur loc unde orice afacere din România poate avea un agent AI care vorbește, scrie și înțelege clienții la fel de bine ca cel mai bun angajat.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl mx-auto">
            <div class="fade-up rounded-2xl p-6 bg-paper border border-line">
                <div class="w-10 h-10 rounded-lg accent-soft-bg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 accent-text" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h3 class="font-semibold mb-2">Agent AI text</h3>
                <p class="text-muted text-sm leading-relaxed mb-3">Widget pe site cu cunoștințe din documentele tale, e-commerce, lead generation și detecție intenții. Live și funcțional.</p>
                <span class="chip mono text-[10px]" style="background:#D1FAE5; color:#047857;">Live</span>
            </div>

            <div class="fade-up rounded-2xl p-6 bg-paper border border-line" style="transition-delay:.1s;">
                <div class="w-10 h-10 rounded-lg accent-soft-bg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 accent-text" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                </div>
                <h3 class="font-semibold mb-2">Agent vocal AI</h3>
                <p class="text-muted text-sm leading-relaxed mb-3">Apeluri vocale cu voce naturală românească, voice cloning premium, transcriere și analiză sentiment în timp real.</p>
                <span class="chip mono text-[10px]" style="background:#FEF3C7; color:#92400E;">Beta</span>
            </div>

            <div class="fade-up rounded-2xl p-6 bg-paper border border-line" style="transition-delay:.2s;">
                <div class="w-10 h-10 rounded-lg accent-soft-bg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 accent-text" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                </div>
                <h3 class="font-semibold mb-2">API & integrări</h3>
                <p class="text-muted text-sm leading-relaxed mb-3">REST API complet, webhook-uri, WebSocket. WordPress, WooCommerce, Google Calendar, Stripe.</p>
                <span class="chip mono text-[10px]" style="background:#D1FAE5; color:#047857;">Live</span>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-24">
    <div class="max-w-4xl mx-auto px-6">
        <div class="rounded-[2.5rem] bg-ink p-10 md:p-16 text-center relative overflow-hidden fade-up">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, rgba(220,38,38,0.35) 0%, transparent 70%);"></div>
            <div class="relative">
                <h2 class="display text-4xl md:text-5xl font-medium leading-[1.05] mb-4 text-cream">
                    Vrei să faci parte din <em class="italic accent-text">poveste</em>?
                </h2>
                <p class="text-lg mb-8" style="color:#D7D3CA;">Hai să dăm voce afacerii tale. Configurare în 10 minute, fără card de credit.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ url('/register') }}" class="btn-primary">
                        Creează cont gratuit
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ route('new.contact') }}" class="btn-outline" style="border-color: rgba(255,255,255,.4); color: #F5F1E8;">Vorbește cu noi</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
