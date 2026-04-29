<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>V5 Sambla warm — Homepage</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter','system-ui','sans-serif'],
        display: ['Instrument Sans','Inter','sans-serif'],
        mono: ['JetBrains Mono','monospace']
      },
      colors: {
        cream:  '#F5F1E8',
        paper:  '#FAF7EF',
        sand:   '#EFE5D0',
        sandy:  '#E5DCC4',
        ink:    '#1C1917',
        muted:  '#78716C',
        line:   '#E7E0CE',
        coral:  '#DC2626',
        coralh: '#991B1B',
        coralsoft: '#FEE2E2',
        peach:  '#FDBA8C',
        sun:    '#F2E59A',
        lilac:  '#C7B8E8',
        sky:    '#A7C7F0',
      }
    }
  }
}
</script>
<style>
  :root { --accent: #DC2626; --accent-soft: #FEE2E2; --accent-dark: #991B1B; }
  [data-niche="medical"]  { --accent: #3B82F6; --accent-soft: #DBEAFE; --accent-dark: #2563EB; }
  [data-niche="beauty"]   { --accent: #F43F5E; --accent-soft: #FFE4E6; --accent-dark: #E11D48; }
  [data-niche="auto"]     { --accent: #F97316; --accent-soft: #FFEDD5; --accent-dark: #EA580C; }
  [data-niche="resto"]    { --accent: #10B981; --accent-soft: #D1FAE5; --accent-dark: #059669; }
  [data-niche="imob"]     { --accent: #F59E0B; --accent-soft: #FEF3C7; --accent-dark: #D97706; }
  [data-niche="legal"]    { --accent: #A855F7; --accent-soft: #F3E8FF; --accent-dark: #9333EA; }

  html { scroll-behavior: smooth; }
  body {
    font-family: 'Inter', system-ui, sans-serif;
    background: #F5F1E8;
    color: #1C1917;
    -webkit-font-smoothing: antialiased;
  }
  .display { font-family: 'Instrument Sans', sans-serif; letter-spacing: -0.02em; }
  .mono { font-family: 'JetBrains Mono', monospace; }

  .btn-primary {
    background: var(--accent);
    color: white;
    border-radius: 999px;
    padding: 14px 24px;
    font-weight: 600;
    transition: all .2s ease;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); box-shadow: 0 10px 30px rgba(220,38,38,0.25); }
  .btn-ghost {
    background: #1C1917; color: white;
    border-radius: 999px; padding: 14px 24px; font-weight: 600;
    transition: all .2s ease;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-ghost:hover { background: #3A3532; transform: translateY(-1px); }
  .btn-outline {
    border: 1.5px solid #1C1917; color: #1C1917; background: transparent;
    border-radius: 999px; padding: 14px 24px; font-weight: 600;
    transition: all .2s ease;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-outline:hover { background: #1C1917; color: white; }

  .fade-up { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; }
  .fade-up.in { opacity: 1; transform: translateY(0); }

  .hero-glow {
    background:
      radial-gradient(ellipse 45% 35% at 20% 15%, rgba(233,74,63,0.18) 0%, transparent 60%),
      radial-gradient(ellipse 40% 30% at 85% 20%, rgba(247,213,147,0.35) 0%, transparent 60%),
      radial-gradient(ellipse 50% 40% at 70% 90%, rgba(199,184,232,0.25) 0%, transparent 60%);
  }

  .ticker { animation: tickerScroll 50s linear infinite; }
  @keyframes tickerScroll { 0% { transform: translateX(0) } 100% { transform: translateX(-50%) } }

  .float { animation: floatY 6s ease-in-out infinite; }
  @keyframes floatY { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-8px) } }

  .msg { opacity: 0; transform: translateY(6px); animation: msgIn .5s ease forwards; }
  @keyframes msgIn { to { opacity: 1; transform: translateY(0); } }
  .msg-1 { animation-delay: .2s }
  .msg-2 { animation-delay: .9s }
  .msg-3 { animation-delay: 1.6s }
  .msg-4 { animation-delay: 2.3s }
  .msg-5 { animation-delay: 3.0s }

  .dots span { animation: dot 1.4s infinite; }
  .dots span:nth-child(2) { animation-delay: .2s }
  .dots span:nth-child(3) { animation-delay: .4s }
  @keyframes dot { 0%,60%,100% { opacity: .3 } 30% { opacity: 1 } }

  .niche-card { transition: transform .25s ease, box-shadow .25s ease; }
  .niche-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px color-mix(in srgb, var(--accent) 25%, transparent); }

  .stat-num { font-feature-settings: "tnum"; }

  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(180deg); }

  .grain::after {
    content:''; position:absolute; inset:0; pointer-events:none; opacity:.4;
    background-image: radial-gradient(rgba(28,25,23,0.05) 1px, transparent 1px);
    background-size: 3px 3px;
  }

  .accent-text { color: var(--accent); }
  .accent-bg { background: var(--accent); }
  .accent-soft-bg { background: var(--accent-soft); }

  .chip { border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
  .chip-outline { background: white; border: 1px solid #E7E0CE; color: #78716C; }
  .chip-filled { background: var(--accent); color: white; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview V5 · Sambla warm · <a href="/preview" class="underline">lista completă</a>
</div>

<!-- Ticker top -->
<div class="bg-sand border-b border-line py-2 overflow-hidden">
  <div class="ticker flex gap-10 whitespace-nowrap text-xs mono text-muted">
    @for($i=0; $i<3; $i++)
      <span>✦ GPT-4o Realtime · voce nativă română</span>
      <span class="text-coral">●</span>
      <span>✦ Hosting fizic în România</span>
      <span class="text-coral">●</span>
      <span>✦ Integrare WooCommerce nativă</span>
      <span class="text-coral">●</span>
      <span>✦ 10 straturi anti-halucinare</span>
      <span class="text-coral">●</span>
      <span>✦ GDPR by default</span>
      <span class="text-coral">●</span>
    @endfor
  </div>
</div>

<!-- Nav -->
<nav class="bg-cream/80 backdrop-blur sticky top-0 z-40 border-b border-line/60">
  <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
    <a href="#" class="flex items-center gap-2 shrink-0">
      <img src="/images/logo-light.svg" alt="Sambla" class="h-10 md:h-11 w-auto">
    </a>
    <div class="hidden lg:flex items-center gap-8 text-sm font-medium text-muted">
      <a href="#colegi" class="hover:text-ink transition">Agenți AI</a>
      <a href="#industrii" class="hover:text-ink transition">Industrii</a>
      <a href="#incredere" class="hover:text-ink transition">Cum funcționează</a>
      <a href="#preturi" class="hover:text-ink transition">Prețuri</a>
      <a href="#" class="hover:text-ink transition">Blog</a>
    </div>
    <div class="flex items-center gap-2 text-sm">
      <a href="/login" class="hidden sm:inline px-4 py-2 text-muted hover:text-ink transition">Autentificare</a>
      <a href="/register" class="btn-primary">Începe gratuit</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero-glow relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-6 pt-20 pb-24 grid lg:grid-cols-12 gap-12 items-start relative">
    <div class="lg:col-span-6 fade-up">
      <div class="chip chip-outline mb-7">
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full rounded-full bg-coral opacity-60 animate-ping"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-coral"></span>
        </span>
        <span class="mono text-[11px] uppercase tracking-wider">live · răspunde acum</span>
      </div>

      <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-7">
        Angajatul tău AI care<br>
        <span class="italic font-normal accent-text">nu iese din tură.</span>
      </h1>

      <p class="text-xl leading-relaxed text-muted mb-9 max-w-xl">
        Răspunde la telefon, pe site și pe WhatsApp — la 3 dimineața, duminica, în concediu. Cu documentele, produsele și tonul afacerii tale.
        <span class="text-ink font-medium">Nu inventează. Nu ghicește. Știe.</span>
      </p>

      <div class="flex flex-wrap gap-3 mb-9">
        <a href="/register" class="btn-primary">
          Începe gratuit
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
        <a href="#demo" class="btn-outline">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
          Ascultă o conversație
        </a>
      </div>

      <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Fără card</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Setup 10 min</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> GDPR nativ</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Hosting 🇷🇴</span>
      </div>
    </div>

    <!-- Chat mockup: animated, scenario rotating, niche-colored -->
    <div class="lg:col-span-6 fade-up" style="transition-delay: .15s">
      <div class="relative">
        <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" id="heroGlow" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%); transition: background .6s ease;"></div>
        <div id="heroChatCard" data-niche="" class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid #E7E0CE; box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
          <!-- header -->
          <div class="px-5 py-4 flex items-center gap-3 border-b border-line accent-soft-bg transition-colors duration-500">
            <div class="relative">
              <div class="w-10 h-10 rounded-full accent-bg flex items-center justify-center transition-colors duration-500">
                <span class="text-white display text-base font-semibold">S</span>
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-paper">
                <span class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-60"></span>
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-sm">Sambla</div>
              <div class="text-xs text-muted flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Online · răspunde instant
              </div>
            </div>
            <span id="heroScenarioLabel" class="chip accent-soft-bg mono text-[10px] transition-all duration-500" style="color: var(--accent-dark);">🦷 Cabinet stomatologic</span>
          </div>

          <!-- messages area -->
          <div id="heroChat" class="px-5 py-4 h-[420px] overflow-y-auto relative" style="scrollbar-width:none; -ms-overflow-style:none;">
            <div id="heroChatInner" class="space-y-3"></div>
            <!-- typing indicator -->
            <div id="heroTyping" class="hidden items-center gap-2 mt-3">
              <div class="bg-sand rounded-2xl rounded-bl-sm px-4 py-2.5">
                <div class="dots flex gap-1.5 h-4 items-center">
                  <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                  <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                  <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- footer -->
          <div class="px-4 py-3 border-t border-line bg-paper flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
              <svg class="w-4 h-4 accent-text shrink-0 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
              <span id="heroFooterText" class="text-xs text-muted font-medium transition-opacity duration-300 truncate">Răspuns din baza de cunoștințe</span>
            </div>
            <div id="heroDotsContainer" class="flex gap-1.5 shrink-0"></div>
          </div>
        </div>

        <!-- floating badge -->
        <div class="absolute -left-4 -bottom-4 bg-white rounded-2xl shadow-xl p-4 pr-5 flex items-center gap-3 border border-line max-w-[260px] float" style="animation-delay:.5s;">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div>
            <div id="heroBadgeTitle" class="text-sm font-semibold leading-tight transition-all duration-500">Programare confirmată</div>
            <div class="text-xs text-muted">automat · fără operator</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Logos / trust strip -->
<section class="border-y border-line/60 bg-paper">
  <div class="max-w-7xl mx-auto px-6 py-10">
    <p class="text-center mono text-[11px] uppercase tracking-[0.2em] text-muted mb-7">Afaceri românești care folosesc deja Sambla</p>
    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4 opacity-70">
      <div class="display text-xl font-semibold tracking-tight">Dental Pro</div>
      <div class="display italic text-xl">boutique</div>
      <div class="mono text-sm font-medium">AUTO · TECH</div>
      <div class="display font-bold text-xl">Imobiliare24</div>
      <div class="display font-semibold tracking-wide text-xl">CASA FRUMOASĂ</div>
      <div class="font-light text-xl">estetică.ro</div>
      <div class="display text-xl">Salonul Ana</div>
    </div>
  </div>
</section>

<!-- Niche colors showcase -->
<section id="industrii-colors" class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ identitate adaptivă</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
        Nu toate afacerile sunt la fel.<br>
        <span class="italic accent-text">Nici Sambla nu e.</span>
      </h2>
      <p class="text-lg text-muted leading-relaxed">Agenții tăi AI se îmbracă în paleta industriei tale — de la widget, la pagina de landing dedicată, până la inbox. Fiecare vertical arată ca <em>el însuși</em>, nu ca un template.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach([
        ['medical','Stomatologie · Medical','🦷','Programări, recall, întrebări frecvente despre tratamente.'],
        ['beauty','Estetică · Saloane','💆','Rezervări instant, consultații inițiale, upsell servicii.'],
        ['auto','Service auto · ITP','🔧','Oferte piese, programări, istoric mașină cu VIN.'],
        ['resto','Restaurante · Cafenele','🍽️','Rezervări mese, meniu, evenimente speciale, delivery.'],
        ['imob','Imobiliare','🏠','Vizionări, filtrare listări, cereri calificate cu scoring.'],
        ['legal','Cabinete avocatură','⚖️','Intake cazuri, programări, FAQ juridic — fără promisiuni.'],
      ] as $n)
        <div class="niche-card rounded-3xl p-6 bg-paper border border-line fade-up" data-niche="{{ $n[0] }}">
          <div class="flex items-start justify-between mb-5">
            <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center text-2xl">{{ $n[2] }}</div>
            <div class="chip chip-filled text-[10px]" style="padding: 4px 10px;">culoare: <span class="w-2 h-2 rounded-full bg-white inline-block"></span></div>
          </div>
          <h3 class="display text-xl font-semibold mb-2">{{ $n[1] }}</h3>
          <p class="text-sm text-muted leading-relaxed mb-5">{{ $n[3] }}</p>

          <!-- Mini chat preview -->
          <div class="rounded-2xl bg-cream p-3 space-y-2">
            <div class="flex">
              <div class="max-w-[82%] px-3 py-1.5 rounded-xl rounded-bl-sm text-xs bg-white border border-line">
                Bună, cât costă?
              </div>
            </div>
            <div class="flex justify-end">
              <div class="max-w-[82%] px-3 py-1.5 rounded-xl rounded-br-sm text-xs accent-bg text-white">
                Tarifele încep de la <strong>180 lei</strong> →
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-10 text-center">
      <a href="#" class="inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-ink transition">
        Vezi toate cele 30+ verticale
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- 3 channels -->
<section id="colegi" class="py-24 bg-paper">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ un singur creier</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
        Trei canale.<br>
        <span class="italic">Aceleași răspunsuri.</span>
      </h2>
      <p class="text-lg text-muted leading-relaxed">Clientul sună sau scrie — primește același răspuns expert. Aceeași bază de cunoștințe, același ton, aceleași politici. Într-un singur dashboard.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      <div class="niche-card rounded-3xl p-8 bg-cream fade-up" style="border:1px solid #E7E0CE;" data-niche="">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center border border-line">
            <svg class="w-6 h-6 text-ink" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
          </div>
          <span class="chip mono text-[10px]" style="background:#D1FAE5; color:#047857;">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            live · beta
          </span>
        </div>
        <h3 class="display text-2xl font-semibold mb-3">Telefon</h3>
        <p class="text-muted leading-relaxed mb-5">Agentul ridică apelul, vorbește natural în română, confirmă programări, escaladează când e nevoie. Voce clonată opțional cu ElevenLabs.</p>
        <ul class="space-y-2 text-sm">
          <li class="flex gap-2"><span class="text-coral">✦</span> Numere românești dedicate (Twilio)</li>
          <li class="flex gap-2"><span class="text-coral">✦</span> Latență sub 800ms, barge-in natural</li>
          <li class="flex gap-2"><span class="text-coral">✦</span> Transcriere live + analiză sentiment</li>
        </ul>
      </div>

      <div class="niche-card rounded-3xl p-8 bg-ink text-cream fade-up" style="transition-delay: .1s;">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:#3A3532;">
            <svg class="w-6 h-6 text-coral" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 4h4m-4 4l-4-4h4a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-7l-2 2z"/></svg>
          </div>
          <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.1); color:#F2E59A;">
            <span class="w-1.5 h-1.5 rounded-full bg-yellow-300"></span>
            cel mai folosit
          </span>
        </div>
        <h3 class="display text-2xl font-semibold mb-3">Chat pe site</h3>
        <p class="leading-relaxed mb-5" style="color:#D7D3CA;">Widget premium. O linie de cod. Răspunde instant, recomandă produse, preia lead-uri, trimite link-uri direct la paginile relevante.</p>
        <ul class="space-y-2 text-sm">
          <li class="flex gap-2"><span class="text-coral">✦</span> Carduri produse, preview link, asistență proactivă</li>
          <li class="flex gap-2"><span class="text-coral">✦</span> Dark mode, branding custom, mobile-first</li>
          <li class="flex gap-2"><span class="text-coral">✦</span> Sandboxed iframe, zero risc XSS</li>
        </ul>
      </div>

      <div class="niche-card rounded-3xl p-8 bg-cream fade-up" style="border:1px solid #E7E0CE; transition-delay: .2s;">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center border border-line">
            <svg class="w-6 h-6 text-ink" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l-2-5-5-2 5-2 2-5 2 5 5 2-5 2-2 5z"/></svg>
          </div>
          <span class="chip chip-outline text-[10px]">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            multi-canal
          </span>
        </div>
        <h3 class="display text-2xl font-semibold mb-3">WhatsApp · FB · IG</h3>
        <p class="text-muted leading-relaxed mb-5">Un singur inbox pentru toate canalele sociale. Sincronizare WooCommerce nativă. Tracking AWB automat (FanCourier, Cargus, DPD).</p>
        <ul class="space-y-2 text-sm">
          <li class="flex gap-2"><span class="text-coral">✦</span> Produse cu stoc real, nu date vechi</li>
          <li class="flex gap-2"><span class="text-coral">✦</span> Handoff către operator când e cazul</li>
          <li class="flex gap-2"><span class="text-coral">✦</span> Istoric unificat per client</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Onboarding 3 steps -->
<section id="onboarding" class="py-24 grain relative">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-xl mb-16 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ onboarding</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
        De la <em class="italic accent-text">pagină goală</em><br>
        la agent live, în 8 minute.
      </h2>
    </div>

    <div class="space-y-4">
      <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">01</div></div>
        <div class="md:col-span-7">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="display text-2xl font-semibold">Spune-i despre afacerea ta</h3>
            <span class="chip chip-outline mono text-[10px]">~2 min</span>
          </div>
          <p class="text-muted leading-relaxed">Descrie în 2 propoziții ce face afacerea ta. AI-ul generează automat prompt-ul, personalitatea și setările inițiale — tu doar ajustezi.</p>
        </div>
        <div class="md:col-span-4">
          <div class="rounded-2xl bg-cream p-4 border border-line">
            <div class="mono text-[10px] uppercase text-muted mb-2">Exemplu input</div>
            <p class="text-sm italic">„Clinică stomatologică în București, sector 2. Acceptăm programări online, plătim cu cardul, asigurare parțială."</p>
          </div>
        </div>
      </div>

      <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">02</div></div>
        <div class="md:col-span-7">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="display text-2xl font-semibold">Hrănește baza de cunoștințe</h3>
            <span class="chip chip-outline mono text-[10px]">~5 min</span>
          </div>
          <p class="text-muted leading-relaxed">Uploadezi PDF-uri, scanezi site-ul, conectezi magazinul WooCommerce. Agentul AI învață fiecare pagină, fiecare politică, fiecare produs — cu preț, stoc și imagini.</p>
        </div>
        <div class="md:col-span-4">
          <div class="rounded-2xl bg-cream p-3 border border-line space-y-1.5">
            <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
              <span class="w-6 h-6 rounded bg-red-50 text-red-600 flex items-center justify-center text-[10px] font-bold">PDF</span>
              <span class="flex-1 truncate">Tarife 2026.pdf</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            </div>
            <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
              <span class="w-6 h-6 rounded bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] font-bold">WEB</span>
              <span class="flex-1 truncate">sambla.ro — 32 pagini</span>
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            </div>
            <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
              <span class="w-6 h-6 rounded bg-violet-50 text-violet-600 flex items-center justify-center text-[10px] font-bold">WOO</span>
              <span class="flex-1 truncate">247 produse · sync live</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="fade-up rounded-3xl p-7 md:p-8 bg-ink text-cream grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-1"><div class="display text-6xl font-semibold" style="color:#F2E59A;">03</div></div>
        <div class="md:col-span-7">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="display text-2xl font-semibold">Pune-l la treabă</h3>
            <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">~1 min</span>
          </div>
          <p class="leading-relaxed" style="color:#D7D3CA;">O linie de cod pe site. Număr de telefon conectat la Twilio. WhatsApp legat prin Meta Business. Și gata — agentul e la muncă.</p>
        </div>
        <div class="md:col-span-4">
          <div class="rounded-2xl p-4 mono text-xs leading-relaxed" style="background:#0F0E0C; color:#F2E59A;">
            <div style="color:#78716C;">// adaugă în &lt;head&gt;</div>
            <div>&lt;script src=<span style="color:#A7C7F0;">"https://cdn.sambla.ro/w.js"</span><br>&nbsp;&nbsp;data-bot=<span style="color:#A7C7F0;">"dental-pro"</span>&gt;&lt;/script&gt;</div>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-10 text-center fade-up">
      <a href="/register" class="btn-primary">
        Încearcă tu în 10 minute
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- Trust - anti-hallucination -->
<section id="incredere" class="py-24 bg-paper">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-3xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ de ce să ai încredere</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
        Dacă nu știe,<br>
        <span class="italic accent-text">spune că nu știe.</span>
      </h2>
      <p class="text-lg text-muted leading-relaxed">Orice sistem AI poate inventa — e natura tehnologiei. De asta am construit 10 straturi de verificare între întrebarea clientului și răspunsul primit. Când AI-ul nu e sigur, recunoaște cinstit și trimite spre operator uman. Fără improvizații pe politici, prețuri sau promisiuni.</p>
    </div>

    <div class="grid md:grid-cols-4 gap-4">
      @foreach([
        ['01','Înțelege intenția','Detectează ce vrea clientul: cumpără, întreabă, reclamă, compară. Adaptează tot răspunsul.','🎯'],
        ['02','Caută în cunoștințele tale','Hybrid search — vectorial + full-text în paralel. AI reranker păstrează 8 din 20 chunks.','🔎'],
        ['03','Verifică 10 straturi','Confidence scoring · citare sursă obligatorie · detecție halucinație.','🛡️'],
        ['04','Răspunde în context','Empatie la frustrare. Recomandare la interes. Scurt dacă vrea rapid. Mereu în tonul tău.','💬'],
      ] as $s)
        <div class="fade-up rounded-3xl p-6 bg-cream border border-line">
          <div class="flex items-center justify-between mb-5">
            <div class="text-3xl">{{ $s[3] }}</div>
            <div class="mono text-xs text-muted">{{ $s[0] }}</div>
          </div>
          <h3 class="display text-lg font-semibold mb-2">{{ $s[1] }}</h3>
          <p class="text-sm text-muted leading-relaxed">{{ $s[2] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Big stats -->
<section class="py-24 bg-cream relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-16 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ cifrele care contează</div>
      <h2 class="display text-4xl md:text-5xl font-medium leading-tight">Construit pentru calitate,<br><span class="italic accent-text">nu pentru demo-uri.</span></h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center">
        <div class="display text-[7rem] md:text-[8rem] stat-num leading-none font-medium accent-text" data-countup="94">94<span class="text-ink">%</span></div>
        <div class="mt-2 text-sm text-muted">rată rezolvare</div>
        <div class="mono text-[10px] text-muted mt-1">fără operator uman</div>
      </div>
      <div class="fade-up rounded-3xl p-8 bg-ink text-cream text-center" style="transition-delay:.1s;">
        <div class="display text-[7rem] md:text-[8rem] stat-num leading-none font-medium" style="color:#F2E59A;">&lt;2s</div>
        <div class="mt-2 text-sm" style="color:#D7D3CA;">latență răspuns</div>
        <div class="mono text-[10px] mt-1" style="color:#A8A29E;">end-to-end, p95</div>
      </div>
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center" style="transition-delay:.2s;">
        <div class="display text-[7rem] md:text-[8rem] stat-num leading-none font-medium accent-text" data-countup="10">10<span class="text-ink">×</span></div>
        <div class="mt-2 text-sm text-muted">straturi verificare</div>
        <div class="mono text-[10px] text-muted mt-1">anti-halucinare</div>
      </div>
      <div class="fade-up rounded-3xl p-8 text-center" style="transition-delay:.3s; background: linear-gradient(135deg, #FCE7E3 0%, #FDBA8C 100%);">
        <div class="display text-[7rem] md:text-[8rem] leading-none font-medium">🇷🇴</div>
        <div class="mt-2 text-sm font-medium">hosting RO</div>
        <div class="mono text-[10px] text-muted mt-1">servere fizice, UE-only</div>
      </div>
    </div>
  </div>
</section>

<!-- Industries grid with niche colors -->
<section id="industrii" class="py-24 bg-paper">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ industrii</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Creat pentru <em class="italic">afacerea ta</em>.</h2>
      <p class="text-lg text-muted">Peste 30 de verticale cu prompt-uri, personalități și integrări adaptate — de la intake juridic la sync WooCommerce.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      @foreach([
        ['🦷','Stomatologie','medical'],
        ['💆','Estetică','beauty'],
        ['🏥','Medical','medical'],
        ['💅','Saloane beauty','beauty'],
        ['🔧','Service auto','auto'],
        ['🚗','Dealeri auto','auto'],
        ['🏠','Imobiliare','imob'],
        ['🏗️','Construcții','imob'],
        ['⚖️','Avocatură','legal'],
        ['📚','Contabilitate','legal'],
        ['🍽️','Restaurante','resto'],
        ['🏨','Pensiuni','resto'],
        ['🛒','E-commerce','auto'],
        ['🎓','Educație','legal'],
        ['💇','Barbershop','beauty'],
        ['🐶','Pet services','imob'],
      ] as $ind)
        <a href="#" data-niche="{{ $ind[2] }}" class="niche-card block rounded-2xl p-5 bg-cream border border-line">
          <div class="flex items-center justify-between mb-4">
            <span class="text-3xl">{{ $ind[0] }}</span>
            <span class="w-2 h-2 rounded-full accent-bg"></span>
          </div>
          <div class="font-semibold text-sm mb-1">{{ $ind[1] }}</div>
          <div class="mono text-[10px] text-muted uppercase tracking-wider">Vezi detalii →</div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<!-- Testimonial big -->
<section class="py-24 grain relative">
  <div class="max-w-6xl mx-auto px-6">
    <div class="rounded-[2.5rem] bg-ink text-cream p-10 md:p-16 overflow-hidden relative grid md:grid-cols-12 gap-10 items-center fade-up">
      <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, rgba(233,74,63,0.3) 0%, transparent 70%);"></div>

      <div class="md:col-span-8 relative">
        <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6" style="color:#F2E59A;">◇ de la clienții noștri</div>
        <p class="display text-3xl md:text-4xl leading-[1.15] font-normal mb-8">
          „Pierdeam jumătate din apelurile după program. Acum
          <span class="italic" style="color:#F2E59A;">fiecare e preluat de Sambla</span>
          — și programările se fac în timp ce dormim. Veniturile au crescut cu 30% în trei luni."
        </p>
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold" style="background:#F2E59A; color:#1C1917;">AM</div>
          <div>
            <div class="font-semibold">Ana Marinescu</div>
            <div class="text-sm" style="color:#A8A29E;">Proprietar clinică estetică · București</div>
          </div>
        </div>
      </div>

      <div class="md:col-span-4 relative">
        <div class="rounded-2xl p-5 space-y-4" style="background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);">
          <div>
            <div class="text-xs mb-1" style="color:#A8A29E;">Apeluri pierdute · săpt.</div>
            <div class="flex items-baseline gap-2">
              <span class="display text-3xl line-through" style="color:#78716C;">42</span>
              <span class="display text-4xl accent-text font-semibold">3</span>
            </div>
          </div>
          <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
          <div>
            <div class="text-xs mb-1" style="color:#A8A29E;">Programări / lună</div>
            <div class="flex items-baseline gap-2">
              <span class="display text-3xl line-through" style="color:#78716C;">120</span>
              <span class="display text-4xl font-semibold" style="color:#F2E59A;">188</span>
            </div>
          </div>
          <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
          <div>
            <div class="text-xs mb-1" style="color:#A8A29E;">Creștere venituri</div>
            <div class="display text-4xl font-semibold accent-text">+30%</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pricing -->
<section id="preturi" class="py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-xl mx-auto text-center mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ prețuri</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5"><em class="italic accent-text">Simple.</em> În lei.<br>Fără surprize.</h2>
      <p class="text-lg text-muted">7 zile gratuit. Fără card. Anulezi oricând. 30% reducere ONG + școli.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line">
        <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Starter</div>
        <div class="flex items-baseline gap-1 mb-3">
          <span class="display text-6xl font-medium">29</span>
          <span class="text-muted">lei / lună</span>
        </div>
        <p class="text-sm text-muted mb-6 pb-6 border-b border-line">Agent AI simplu pe un site.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>1 agent AI · 500 conversații/lună</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Widget chat + 1 site</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Bază cunoștințe nelimitată</li>
          <li class="flex gap-2 items-start text-muted"><span>—</span>WhatsApp / FB / IG</li>
          <li class="flex gap-2 items-start text-muted"><span>—</span>Agent vocal</li>
        </ul>
        <a href="/register" class="btn-outline w-full justify-center">Începe gratuit</a>
      </div>

      <div class="fade-up rounded-3xl p-8 bg-ink text-cream relative" style="transition-delay:.1s;">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip accent-bg text-[10px] font-semibold">Recomandat</div>
        <div class="mono text-xs uppercase tracking-wider mb-3" style="color:#F2E59A;">Professional</div>
        <div class="flex items-baseline gap-1 mb-3">
          <span class="display text-6xl font-medium">79</span>
          <span style="color:#A8A29E;">lei / lună</span>
        </div>
        <p class="text-sm mb-6 pb-6 border-b" style="color:#D7D3CA; border-color: rgba(255,255,255,.1);">Multi-canal + CRM lead pipeline.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>3 agenți AI · 2.500 conversații/lună</li>
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>WooCommerce + WhatsApp</li>
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>Lead scoring + CRM pipeline</li>
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>Analiză avansată</li>
          <li class="flex gap-2 items-start" style="color:#A8A29E;"><span>—</span>Voce AI (addon +49 lei)</li>
        </ul>
        <a href="/register" class="btn-primary w-full justify-center" style="background:#F2E59A; color:#1C1917;">Alege Professional →</a>
      </div>

      <div class="fade-up rounded-3xl p-8 bg-paper border border-line" style="transition-delay:.2s;">
        <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Business</div>
        <div class="flex items-baseline gap-1 mb-3">
          <span class="display text-6xl font-medium">199</span>
          <span class="text-muted">lei / lună</span>
        </div>
        <p class="text-sm text-muted mb-6 pb-6 border-b border-line">Volum mare + toate canalele + voce.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>10 agenți · 10.000 conversații/lună</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Toate canalele (FB · IG · WA)</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Voce AI disponibilă</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Suport prioritar</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>API + webhooks</li>
        </ul>
        <a href="/register" class="btn-outline w-full justify-center">Alege Business</a>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-24 bg-paper">
  <div class="max-w-3xl mx-auto px-6">
    <div class="text-center mb-12 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ întrebări</div>
      <h2 class="display text-5xl font-medium">Răspunsuri <em class="italic accent-text">sincere.</em></h2>
    </div>
    <div class="space-y-3 fade-up">
      @foreach([
        ['Ce se întâmplă când depășesc limita de mesaje?', 'Se taxează automat la costul suplimentar al planului tău — fără întreruperi de serviciu. Vezi tabelul overage la pagina de prețuri.'],
        ['Pot combina chat + voce?', 'Da. Alegi un plan chat și adaugi un addon de voce (+49 sau +149 lei). Ambele folosesc aceeași bază de cunoștințe.'],
        ['Cum garantați că AI-ul nu inventează?', 'Pipeline cu 10 straturi de verificare, citare sursă obligatorie și confidence scoring. Când nu e sigur, răspunde cinstit „nu am această informație" și trimite către operator.'],
        ['Unde sunt stocate datele?', 'Servere fizice în România. Zero transfer în afara UE. GDPR compliant by default, cu izolare strictă per cont.'],
        ['Funcționează cu WordPress și WooCommerce?', 'Avem plugin WordPress și sync WooCommerce nativ — produse, stocuri, prețuri, comenzi. Plus embed pe orice site cu o linie de cod.'],
        ['Oferiți reducere pentru ONG-uri?', 'Da. 30% permanent pentru ONG-uri, școli, universități, muzee. Ne trimiți documentele organizației și activăm reducerea.']
      ] as $f)
        <details class="rounded-2xl bg-cream border border-line overflow-hidden group">
          <summary class="px-6 py-5 flex items-center justify-between cursor-pointer list-none font-medium hover:bg-sand/50 transition">
            <span>{{ $f[0] }}</span>
            <svg class="chev w-4 h-4 text-muted transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
          </summary>
          <div class="px-6 pb-5 text-sm text-muted leading-relaxed">{{ $f[1] }}</div>
        </details>
      @endforeach
    </div>
  </div>
</section>

<!-- Final CTA -->
<section class="py-28 relative overflow-hidden">
  <div class="absolute inset-0 opacity-60 pointer-events-none" style="background: radial-gradient(ellipse 60% 50% at 50% 50%, rgba(233,74,63,0.2) 0%, transparent 60%);"></div>
  <div class="max-w-4xl mx-auto px-6 text-center relative fade-up">
    <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-6">◇ acum e momentul</div>
    <h2 class="display text-5xl md:text-7xl font-medium leading-[1.02] mb-8">
      Să-i dăm voce<br>
      <em class="italic accent-text">afacerii tale.</em>
    </h2>
    <p class="text-xl text-muted mb-10 max-w-xl mx-auto leading-relaxed">Configurezi în 10 minute. Primele rezultate din prima zi. Fără card de credit, fără obligații.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="/register" class="btn-primary text-base" style="padding: 16px 28px;">
        Creează cont gratuit
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
      <a href="/contact" class="btn-outline text-base" style="padding: 16px 28px;">Programează un demo</a>
    </div>
    <p class="mt-8 text-xs mono text-muted">Sau scrie direct · servus@sambla.ro · 0775 222 333</p>
  </div>
</section>

<!-- Footer -->
<footer class="py-14 bg-paper border-t border-line">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid md:grid-cols-5 gap-8 pb-10 border-b border-line">
      <div class="md:col-span-2">
        <img src="/images/logo-light.svg" alt="Sambla" class="h-10 w-auto mb-4">
        <p class="text-sm text-muted leading-relaxed max-w-sm">Angajatul tău AI care știe totul despre afacerea ta. Voce naturală, chat inteligent, auto-îmbunătățire continuă.</p>
      </div>
      <div>
        <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Produs</h4>
        <ul class="space-y-2 text-sm text-muted">
          <li><a href="/functionalitati" class="hover:text-ink">Funcționalități</a></li>
          <li><a href="/preturi" class="hover:text-ink">Prețuri</a></li>
          <li><a href="#" class="hover:text-ink">Demo live</a></li>
          <li><a href="#" class="hover:text-ink">API & docs</a></li>
        </ul>
      </div>
      <div>
        <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Companie</h4>
        <ul class="space-y-2 text-sm text-muted">
          <li><a href="/despre" class="hover:text-ink">Despre</a></li>
          <li><a href="/de-ce-sambla" class="hover:text-ink">De ce Sambla</a></li>
          <li><a href="/blog" class="hover:text-ink">Blog</a></li>
          <li><a href="/contact" class="hover:text-ink">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Legal</h4>
        <ul class="space-y-2 text-sm text-muted">
          <li><a href="/termeni" class="hover:text-ink">Termeni</a></li>
          <li><a href="/confidentialitate" class="hover:text-ink">Confidențialitate</a></li>
          <li><a href="/cookie-uri" class="hover:text-ink">Cookie-uri</a></li>
          <li><a href="#" class="hover:text-ink">GDPR</a></li>
        </ul>
      </div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 pt-6 text-xs mono text-muted">
      <div>© 2026 Sambla · servus@sambla.ro · 0775 222 333</div>
      <div class="flex gap-4">
        <span>🇷🇴 Hosting România</span>
        <span>✓ GDPR compliant</span>
        <span>Făcut cu ❤️ în România</span>
      </div>
    </div>
  </div>
</footer>

<script>
// Fade-up on scroll
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('in');
      io.unobserve(e.target);
    }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

// Stat countup
const cuObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const el = e.target;
      const target = parseInt(el.dataset.countup, 10);
      const suffix = el.querySelector('span')?.outerHTML || '';
      const durationMs = 1200;
      const start = performance.now();
      const tick = (now) => {
        const p = Math.min(1, (now - start) / durationMs);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = Math.round(target * eased);
        el.innerHTML = val + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
      cuObserver.unobserve(el);
    }
  });
}, { threshold: 0.5 });
document.querySelectorAll('[data-countup]').forEach(el => cuObserver.observe(el));

/* =========================================================
   Hero: rotating scenarios
   Convenție (ca pe site-ul actual):
   - Mesaj CLIENT (user:true)  → DREAPTA + bubble accent (niche color)
   - Mesaj SAMBLA (user:false) → STÂNGA + bubble sand neutru
   ========================================================= */
(function() {
  const scenarios = [
    {
      niche: 'medical', label: '🦷 Cabinet stomatologic',
      footer: '✓ Programare din baza de cunoștințe',
      badge: 'Programare confirmată',
      messages: [
        { user: true,  text: 'Bună ziua. Aveți loc liber pentru detartraj săptămâna viitoare?' },
        { user: false, text: 'Da, am găsit 3 disponibilități. Marți 22, 10:00 · Dr. Ionescu — 180 lei. Vă convine?' },
        { user: true,  text: 'Da, pe Maria Popescu.' },
        { user: false, text: '✓ Rezervat. Vă trimit SMS cu detaliile și o reamintire cu 24h înainte.' },
      ]
    },
    {
      niche: 'auto', label: '🛒 Magazin online',
      footer: '✓ Stoc real sincronizat WooCommerce',
      badge: 'Comandă preluată',
      messages: [
        { user: true,  text: 'Mai aveți vopsea albă 10L pe stoc?' },
        { user: false, text: 'Da! 23 bucăți în stoc · 89 lei · livrare mâine 10-14 🚚' },
        { user: true,  text: 'Comanda 2 bucăți.' },
        { user: false, text: 'Adăugat în coș. Total 178 lei + transport gratuit peste 150 lei. Finalizezi?' },
      ]
    },
    {
      niche: 'auto', label: '🔧 Service auto',
      footer: '✓ Istoric VIN verificat automat',
      badge: 'Programare service',
      messages: [
        { user: true,  text: 'Cât costă schimbul de ulei la BMW X3?' },
        { user: false, text: 'Ce an? Pentru X3 2020+: schimb ulei + filtru — 420 lei, durează ~1h.' },
        { user: true,  text: '2022. Marți dimineața?' },
        { user: false, text: 'Marți 9:00 rezervat ✓ Vă așteptăm. Aveți nevoie să lăsați mașina?' },
      ]
    },
    {
      niche: 'resto', label: '🏨 Pensiune',
      footer: '✓ Disponibilitate iCal live',
      badge: 'Rezervare trimisă',
      messages: [
        { user: true,  text: 'Aveți cameră dublă pentru acest weekend?' },
        { user: false, text: 'Da! Camera Alba liberă 22-23 apr · 280 lei/noapte, mic-dejun inclus.' },
        { user: true,  text: 'Perfect, rezerv 2 nopți.' },
        { user: false, text: '✓ 2 nopți × 280 = 560 lei. Vă trimit link de plată pe email?' },
      ]
    },
    {
      niche: 'imob', label: '🏠 Agenție imobiliară',
      footer: '✓ Listări filtrate după criterii',
      badge: 'Vizionare programată',
      messages: [
        { user: true,  text: 'Caut apartament 2 camere în Cluj, până în 95.000 €.' },
        { user: false, text: 'Am 4 proprietăți în buget. Cea mai potrivită: 58 mp, Mărăști, etaj 3 — 89.000 €.' },
        { user: true,  text: 'Aș vrea să o văd sâmbătă.' },
        { user: false, text: 'Sâmbătă 11:00 cu agentul Andrei ✓ Vă trimit adresa exactă pe WhatsApp.' },
      ]
    },
    {
      niche: 'beauty', label: '💆 Clinică estetică',
      footer: '✓ Preț din lista de servicii',
      badge: 'Consultație rezervată',
      messages: [
        { user: true,  text: 'Cât costă un tratament cu acid hialuronic?' },
        { user: false, text: 'De la 450 lei/ml. Primul pachet include consultație gratuită cu dr. Petrescu.' },
        { user: true,  text: 'Când am loc?' },
        { user: false, text: 'Joi 17:00 e primul disponibil. Rezerv? Durată consultație ~30 min.' },
      ]
    },
    {
      niche: 'legal', label: '⚖️ Cabinet avocatură',
      footer: '✓ Programare în calendar partener',
      badge: 'Consultație rezervată',
      messages: [
        { user: true,  text: 'Am nevoie de consultanță pentru un litigiu comercial.' },
        { user: false, text: 'Cabinetul acoperă litigii comerciale, inclusiv recuperări creanțe. Doriți o programare?' },
        { user: true,  text: 'Da, cât mai repede.' },
        { user: false, text: 'Cel mai apropiat slot: miercuri 14:00 cu av. Popescu. 45 min consultație.' },
      ]
    },
    {
      niche: 'legal', label: '📊 Contabilitate',
      footer: '✓ Pachet adaptat tipului de firmă',
      badge: 'Ofertă trimisă',
      messages: [
        { user: true,  text: 'Am un SRL nou, aveți pachet de contabilitate lunară?' },
        { user: false, text: 'Felicitări! Pachetul SRL micro pornește de la 500 lei/lună — include contabilitate + declarații + consultanță.' },
        { user: true,  text: 'Ce documente îmi trebuie la început?' },
        { user: false, text: 'Certificat înregistrare, act constitutiv și buletinele asociaților. Programăm o întâlnire?' },
      ]
    },
  ];

  // shuffle
  for (let i = scenarios.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [scenarios[i], scenarios[j]] = [scenarios[j], scenarios[i]];
  }

  const card = document.getElementById('heroChatCard');
  const chatEl = document.getElementById('heroChat');
  const inner = document.getElementById('heroChatInner');
  const typing = document.getElementById('heroTyping');
  const label = document.getElementById('heroScenarioLabel');
  const footer = document.getElementById('heroFooterText');
  const dots = document.getElementById('heroDotsContainer');
  const badge = document.getElementById('heroBadgeTitle');
  if (!card || !chatEl) return;

  let current = 0;
  let timers = [];
  let gen = 0;

  const t = (fn, d) => { const id = setTimeout(fn, d); timers.push(id); return id; };
  const clearAll = () => { timers.forEach(clearTimeout); timers = []; gen++; typing.classList.add('hidden'); typing.classList.remove('flex'); };

  // build dots
  scenarios.forEach((_, i) => {
    const s = document.createElement('button');
    s.className = 'w-1.5 h-1.5 rounded-full transition-all duration-300';
    s.style.background = '#D7D3CA';
    s.setAttribute('aria-label', `Scenariu ${i+1}`);
    s.addEventListener('click', () => { current = i; play(current); });
    dots.appendChild(s);
  });

  function setDot(i) {
    for (let k = 0; k < dots.children.length; k++) {
      const d = dots.children[k];
      const on = k === i;
      d.style.background = on ? 'var(--accent)' : '#D7D3CA';
      d.style.transform = on ? 'scale(1.6)' : 'scale(1)';
      d.style.width = on ? '18px' : '6px';
      d.style.borderRadius = '999px';
    }
  }

  function addBubble(msg, onDone) {
    // user = client (dreapta, accent red); AI = sambla (stanga, sand neutru)
    const row = document.createElement('div');
    row.className = msg.user ? 'flex justify-end' : 'flex';
    row.style.opacity = '0';
    row.style.transform = 'translateY(6px)';
    row.style.transition = 'opacity .35s ease, transform .35s ease';

    const bubble = document.createElement('div');
    if (msg.user) {
      bubble.className = 'max-w-[85%] px-4 py-2.5 rounded-2xl rounded-br-sm text-[14.5px] leading-relaxed text-white';
      bubble.style.background = 'var(--accent)';
    } else {
      bubble.className = 'max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14.5px] leading-relaxed text-ink';
      bubble.style.background = '#EFE5D0';
    }
    bubble.textContent = msg.text;
    row.appendChild(bubble);
    inner.appendChild(row);

    requestAnimationFrame(() => {
      row.style.opacity = '1';
      row.style.transform = 'translateY(0)';
      chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
    });

    t(onDone, 450);
  }

  function addMessage(msg, onDone) {
    if (!msg.user) {
      // show typing, then bubble
      typing.classList.remove('hidden');
      typing.classList.add('flex');
      chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
      t(() => {
        typing.classList.add('hidden');
        typing.classList.remove('flex');
        addBubble(msg, onDone);
      }, 700 + Math.random() * 400);
    } else {
      addBubble(msg, onDone);
    }
  }

  function play(index) {
    clearAll();
    const myGen = ++gen;
    const sc = scenarios[index];

    // swap niche color
    card.setAttribute('data-niche', sc.niche);

    // label + footer + badge fade-swap
    label.style.opacity = '0';
    footer.style.opacity = '0';
    if (badge) badge.style.opacity = '0';
    t(() => {
      if (myGen !== gen) return;
      label.textContent = sc.label;
      footer.textContent = sc.footer;
      if (badge) { badge.textContent = sc.badge; badge.style.opacity = '1'; }
      label.style.opacity = '1';
      footer.style.opacity = '1';
    }, 220);

    setDot(index);
    inner.innerHTML = '';

    let i = 0;
    const next = () => {
      if (myGen !== gen) return;
      if (i >= sc.messages.length) {
        // scenario ended — pause, then next
        t(() => {
          if (myGen !== gen) return;
          current = (current + 1) % scenarios.length;
          play(current);
        }, 3200);
        return;
      }
      const m = sc.messages[i];
      const delay = i === 0 ? 500 : (m.user ? 700 : 200);
      t(() => {
        if (myGen !== gen) return;
        addMessage(m, () => { i++; next(); });
      }, delay);
    };
    next();
  }

  // start after hero fades in
  t(() => play(current), 400);

  // swipe support (touch)
  let startX = 0, startY = 0;
  chatEl.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; startY = e.touches[0].clientY; }, { passive: true });
  chatEl.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - startX;
    const dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      current = (current + (dx < 0 ? 1 : -1) + scenarios.length) % scenarios.length;
      play(current);
    }
  }, { passive: true });
})();
</script>

</body>
</html>
