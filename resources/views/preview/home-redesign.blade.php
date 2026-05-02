<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Redesign complet — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
        ink:    '#1C1917',
        muted:  '#78716C',
        line:   '#E7E0CE',
        brand:  '#DC2626',
        brandh: '#991B1B',
        brandsoft: '#FEE2E2',
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
    background: #F5F1E8; color: #1C1917;
    -webkit-font-smoothing: antialiased;
  }
  .display { font-family: 'Instrument Sans', sans-serif; letter-spacing: -0.02em; }
  .mono { font-family: 'JetBrains Mono', monospace; }

  .btn-primary {
    background: var(--accent); color: white; border-radius: 999px;
    padding: 14px 24px; font-weight: 600;
    transition: all .2s ease;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); box-shadow: 0 10px 30px rgba(220,38,38,0.25); }
  .btn-ghost { background: #1C1917; color: white; border-radius: 999px; padding: 14px 24px; font-weight: 600; transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
  .btn-ghost:hover { background: #3A3532; transform: translateY(-1px); }
  .btn-outline { border: 1.5px solid #1C1917; color: #1C1917; background: transparent; border-radius: 999px; padding: 14px 24px; font-weight: 600; transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
  .btn-outline:hover { background: #1C1917; color: white; }

  .fade-up { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; }
  .fade-up.in { opacity: 1; transform: translateY(0); }

  .hero-glow {
    background:
      radial-gradient(ellipse 45% 35% at 20% 15%, rgba(220,38,38,0.16) 0%, transparent 60%),
      radial-gradient(ellipse 40% 30% at 85% 20%, rgba(247,213,147,0.35) 0%, transparent 60%),
      radial-gradient(ellipse 50% 40% at 70% 90%, rgba(199,184,232,0.25) 0%, transparent 60%);
  }
  .ticker { animation: tickerScroll 50s linear infinite; }
  @keyframes tickerScroll { 0% { transform: translateX(0) } 100% { transform: translateX(-50%) } }
  .float { animation: floatY 6s ease-in-out infinite; }
  @keyframes floatY { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-8px) } }
  .dots span { animation: dot 1.4s infinite; }
  .dots span:nth-child(2) { animation-delay: .2s }
  .dots span:nth-child(3) { animation-delay: .4s }
  @keyframes dot { 0%,60%,100% { opacity: .3 } 30% { opacity: 1 } }
  .niche-card { transition: transform .25s ease, box-shadow .25s ease; }
  .niche-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px color-mix(in srgb, var(--accent) 25%, transparent); }
  .stat-num { font-feature-settings: "tnum"; }
  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(180deg); }
  .grain::after { content:''; position:absolute; inset:0; pointer-events:none; opacity:.4; background-image: radial-gradient(rgba(28,25,23,0.05) 1px, transparent 1px); background-size: 3px 3px; }
  .accent-text { color: var(--accent); }
  .accent-bg { background: var(--accent); }
  .accent-soft-bg { background: var(--accent-soft); }
  .chip { border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
  .chip-outline { background: white; border: 1px solid #E7E0CE; color: #78716C; }

  /* motif Sambla (Romanian pattern subtle) */
  .motif-bg {
    background-image:
      radial-gradient(circle at 20% 50%, rgba(220,38,38,0.04) 0%, transparent 25%),
      radial-gradient(circle at 80% 50%, rgba(220,38,38,0.04) 0%, transparent 25%);
  }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview · Redesign complet homepage actual · <a href="/preview" class="underline">lista completă</a> · <a href="/" class="underline">vezi varianta live</a>
</div>

<!-- Ticker -->
<div class="bg-sand border-b border-line py-2 overflow-hidden">
  <div class="ticker flex gap-10 whitespace-nowrap text-xs mono text-muted">
    @for($i=0; $i<3; $i++)
      <span>✦ GPT-4o Realtime · voce nativă română</span>
      <span class="text-brand">●</span>
      <span>✦ Hosting fizic în România</span>
      <span class="text-brand">●</span>
      <span>✦ Integrare WooCommerce nativă</span>
      <span class="text-brand">●</span>
      <span>✦ 10 straturi anti-halucinare</span>
      <span class="text-brand">●</span>
      <span>✦ GDPR by default</span>
      <span class="text-brand">●</span>
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
      <a href="#diferit" class="hover:text-ink transition">De ce Sambla</a>
      <a href="#gandire" class="hover:text-ink transition">Cum gândește</a>
      <a href="#capabilitati" class="hover:text-ink transition">Funcționalități</a>
      <a href="#industrii" class="hover:text-ink transition">Industrii</a>
      <a href="#preturi" class="hover:text-ink transition">Prețuri</a>
    </div>
    <div class="flex items-center gap-2 text-sm">
      <a href="/login" class="hidden sm:inline px-4 py-2 text-muted hover:text-ink transition">Autentificare</a>
      <a href="/register" class="btn-primary">Începe gratuit</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero-glow relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-6 pt-16 pb-24 grid lg:grid-cols-12 gap-12 items-start relative">
    <div class="lg:col-span-6 fade-up">
      <div class="chip chip-outline mb-7">
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full rounded-full bg-brand opacity-60 animate-ping"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-brand"></span>
        </span>
        <span class="mono text-[11px] uppercase tracking-wider">platformă activă · răspunde acum</span>
      </div>

      <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-7">
        Angajatul tău AI<br>
        care <span class="italic font-normal accent-text">știe totul</span><br>
        despre afacerea ta.
      </h1>

      <p class="text-xl leading-relaxed text-muted mb-9 max-w-xl">
        Răspunde clienților pe <strong class="text-ink">chat</strong> și <strong class="text-ink">telefon</strong>, 24/7, din documentele, produsele și politicile tale reale.
        <span class="text-ink font-medium">Nu inventează. Nu ghicește. Știe.</span>
      </p>

      <div class="flex flex-wrap gap-3 mb-9">
        <a href="/register" class="btn-primary">
          Începe gratuit
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
        <a href="#demo" class="btn-outline">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
          Vezi în acțiune
        </a>
      </div>

      <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Fără card de credit</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Setup 10 minute</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> GDPR compliant</span>
      </div>
    </div>

    <!-- Animated rotating chat -->
    <div class="lg:col-span-6 fade-up" style="transition-delay: .15s">
      <div class="relative">
        <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" id="heroGlow" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%); transition: background .6s ease;"></div>
        <div id="heroChatCard" data-niche="" class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid #E7E0CE; box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
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

          <div id="heroChat" class="px-5 py-4 h-[420px] overflow-y-auto relative" style="scrollbar-width:none; -ms-overflow-style:none;">
            <div id="heroChatInner" class="space-y-3"></div>
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

          <div class="px-4 py-3 border-t border-line bg-paper flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
              <svg class="w-4 h-4 accent-text shrink-0 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
              <span id="heroFooterText" class="text-xs text-muted font-medium transition-opacity duration-300 truncate">Răspuns din baza de cunoștințe</span>
            </div>
            <div id="heroDotsContainer" class="flex gap-1.5 shrink-0"></div>
          </div>
        </div>

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

<!-- CREDIBILITY STRIP -->
<section class="border-y border-line bg-paper">
  <div class="max-w-7xl mx-auto px-6 py-14">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 max-w-5xl mx-auto">
      @foreach([
        ['&lt;2s','Răspuns end-to-end','RAG complet în mai puțin de 2 secunde'],
        ['10<span class=\"text-brand\">×</span>','Anti-halucinare','Fiecare răspuns verificat de 10 ori'],
        ['100<span class=\"text-brand\">%</span>','Hosting 🇷🇴 RO','Servere fizice, GDPR by default'],
        ['5','Canale','Web · Voce · WhatsApp · FB · IG'],
      ] as $s)
        <div class="text-center fade-up">
          <div class="display text-5xl lg:text-6xl font-medium mb-2 stat-num">{!! $s[0] !!}</div>
          <div class="mono text-[11px] uppercase tracking-wider font-semibold mb-1">{{ $s[1] }}</div>
          <div class="text-xs text-muted">{{ $s[2] }}</div>
        </div>
      @endforeach
    </div>
    <div class="mt-10 text-center max-w-3xl mx-auto fade-up">
      <p class="text-sm text-muted leading-relaxed">Nu suntem un agent AI scriptat. Nu suntem un wrapper de GPT. Suntem un sistem RAG complet cu hybrid search, AI reranker și voce nativă în română — construit în România, hostat în România.</p>
      <a href="/de-ce-sambla" class="inline-flex items-center gap-2 mt-4 text-sm font-semibold accent-text hover:opacity-80 transition">
        Vezi exact cum suntem diferiți
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- DE CE SAMBLA E DIFERIT -->
<section id="diferit" class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-16 text-center mx-auto fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ 3 diferențe cheie</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">De ce Sambla e <em class="italic accent-text">diferit</em>.</h2>
      <p class="text-lg text-muted leading-relaxed">Nu e o altă platformă AI cu răspunsuri generice. E un sistem construit pe trei principii pe care niciun competitor nu le livrează împreună.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      <div class="niche-card rounded-3xl p-8 bg-paper border border-line fade-up">
        <div class="w-14 h-14 rounded-2xl brandsoft-bg flex items-center justify-center mb-6" style="background:#FEE2E2;">
          <svg class="w-7 h-7 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="display text-2xl font-semibold mb-3">Învață din datele <em class="italic">tale</em>.</h3>
        <p class="text-muted leading-relaxed mb-6">Uploadezi documente, scanezi site-ul, conectezi magazinul — și AI-ul răspunde <strong class="text-ink">exclusiv</strong> din informațiile tale reale. Nu inventează. Nu halucinează.</p>
        <div class="flex flex-wrap gap-2">
          <span class="chip chip-outline text-[11px]">📄 PDF & DOCX</span>
          <span class="chip chip-outline text-[11px]">🌐 Website scan</span>
          <span class="chip chip-outline text-[11px]">🛒 WooCommerce</span>
        </div>
      </div>

      <div class="niche-card rounded-3xl p-8 bg-ink text-cream fade-up" style="transition-delay:.1s">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6" style="background:#3A3532;">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:#F2E59A;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="display text-2xl font-semibold mb-3">Se auto-<em class="italic" style="color:#F2E59A;">îmbunătățește</em>.</h3>
        <p class="leading-relaxed mb-6" style="color:#D7D3CA;">Analizează conversațiile, identifică ce NU știe, și sugerează conținut de adăugat. Generează automat draft-uri pe care tu doar le aprobi.</p>
        <div class="flex flex-wrap gap-2">
          <span class="chip text-[11px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">⚠ Gap detection</span>
          <span class="chip text-[11px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">✍ Auto KB builder</span>
          <span class="chip text-[11px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">📊 Health score</span>
        </div>
      </div>

      <div class="niche-card rounded-3xl p-8 bg-paper border border-line fade-up" style="transition-delay:.2s">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6" style="background:#FEE2E2;">
          <svg class="w-7 h-7 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <h3 class="display text-2xl font-semibold mb-3">Voce + Chat, <em class="italic">același creier</em>.</h3>
        <p class="text-muted leading-relaxed mb-6">Clientul sună sau scrie — primește același răspuns expert. Un singur sistem inteligent, toate canalele de comunicare.</p>
        <div class="flex flex-wrap gap-2">
          <span class="chip chip-outline text-[11px]">🎙️ Voce AI</span>
          <span class="chip chip-outline text-[11px]">💬 Web chat</span>
          <span class="chip chip-outline text-[11px]">📱 Multi-canal</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CUM GANDESTE -->
<section id="gandire" class="py-24 bg-paper relative grain">
  <div class="max-w-7xl mx-auto px-6 relative">
    <div class="max-w-3xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ arhitectură inteligentă</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Cum gândește <em class="italic accent-text">AI-ul Sambla</em>.</h2>
      <p class="text-lg text-muted leading-relaxed">Nu e un agent AI simplu. E un pipeline inteligent cu 4 etape care analizează, caută, decide și răspunde — în sub 2 secunde.</p>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-12">
      @foreach([
        ['01','🎯','Query intelligence','Înțelege intenția: tranzacțional, informațional, reclamație, comparativ. Adaptează tot răspunsul.'],
        ['02','🔎','Hybrid RAG','Vectorial + full-text în paralel. AI reranker păstrează 8 din 20 chunks relevante.'],
        ['03','🛡️','Verificare 10×','Confidence scoring · citare sursă · detecție halucinație. Eșuează elegant.'],
        ['04','💬','Răspuns adaptat','Empatie la frustrare. Recomandare la interes. Mereu în tonul brandului tău.'],
      ] as $s)
        <div class="fade-up rounded-3xl p-6 bg-cream border border-line relative">
          <div class="flex items-center justify-between mb-5">
            <div class="text-3xl">{{ $s[1] }}</div>
            <div class="mono text-xs text-muted">{{ $s[0] }}</div>
          </div>
          <h3 class="display text-lg font-semibold mb-2">{{ $s[2] }}</h3>
          <p class="text-sm text-muted leading-relaxed">{{ $s[3] }}</p>
        </div>
      @endforeach
    </div>

    <!-- Intent types -->
    <div class="rounded-3xl p-8 bg-ink text-cream fade-up">
      <div class="grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-4">
          <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color:#F2E59A;">◇ detectare intenție</div>
          <h3 class="display text-2xl font-semibold mb-3">7 tipuri de intenție detectate automat</h3>
          <p class="text-sm leading-relaxed" style="color:#D7D3CA;">AI-ul știe dacă clientul vrea să cumpere, să întrebe, să se plângă sau să compare — și adaptează comportamentul în timp real.</p>
        </div>
        <div class="md:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-2">
          @foreach([
            ['🛒','Tranzacțional'], ['🔍','Explorativ'], ['⚖️','Comparativ'], ['😤','Reclamație'],
            ['❓','Vag'], ['ℹ️','Informațional'], ['👋','Salut'], ['🙏','Mulțumire'],
          ] as $intent)
            <div class="rounded-xl px-3 py-3 flex items-center gap-2" style="background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08);">
              <span class="text-xl">{{ $intent[0] }}</span>
              <span class="text-sm font-medium">{{ $intent[1] }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TOT CE STIE SA FACA -->
<section id="capabilitati" class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ funcționalități cheie</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Tot ce știe să <em class="italic accent-text">facă</em>.</h2>
      <p class="text-lg text-muted leading-relaxed">Șase capabilități care lucrează împreună: bază de cunoștințe, voce, chat, e-commerce, analytics și lead management. Dintr-un singur dashboard.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach([
        ['📚','Bază de cunoștințe inteligentă','PDF, DOCX, CSV, URL-uri — AI-ul procesează tot și organizează automat. FAQ generate cu un click.', '#FEE2E2', '#DC2626'],
        ['🎙️','Voce naturală în română','Conversații telefonice cu voce realistă. Numere românești, transcriere live, analiză de sentiment.', '#DBEAFE', '#2563EB'],
        ['💬','Chat widget premium','Dark mode, carduri produse, link preview, asistență proactivă pe pagini produs. O linie de cod.', '#FEF3C7', '#D97706'],
        ['🛒','E-commerce nativ','Sincronizare produse, căutare semantică, tracking comenzi, add-to-cart, funnel de conversie complet.', '#FFEDD5', '#EA580C'],
        ['📊','Analytics & health score','Dashboard live, scor de sănătate per bot, analiza gap-urilor, recomandări automate de conținut.', '#F3E8FF', '#9333EA'],
        ['🎯','Pipeline de lead-uri','Captare automată, scoring, pipeline CRM complet: nou → contactat → programat → câștigat.', '#D1FAE5', '#059669'],
      ] as $c)
        <div class="niche-card rounded-3xl p-7 bg-paper border border-line fade-up">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl mb-5" style="background: {{ $c[3] }};">{{ $c[0] }}</div>
          <h3 class="display text-xl font-semibold mb-2">{{ $c[1] }}</h3>
          <p class="text-sm text-muted leading-relaxed">{{ $c[2] }}</p>
        </div>
      @endforeach
    </div>

    <div class="mt-8 text-center fade-up">
      <a href="/functionalitati" class="inline-flex items-center gap-2 text-sm font-medium text-ink hover:accent-text transition">
        Vezi lista completă de funcționalități
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- INDUSTRII CU CULORI -->
<section id="industrii" class="py-24 bg-paper">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ identitate adaptivă</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Nu toate afacerile sunt la fel.<br><em class="italic accent-text">Nici Sambla nu e.</em></h2>
      <p class="text-lg text-muted leading-relaxed">Agenții tăi AI se îmbracă în paleta industriei tale — de la widget-ul de pe site la pagina de landing dedicată. Fiecare vertical arată ca <em>el însuși</em>.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach([
        ['medical','Stomatologie · Medical','🦷','Programări, recall, informații despre tratamente și tarife.'],
        ['beauty','Estetică · Saloane beauty','💆','Rezervări instant, consultații inițiale, upsell servicii.'],
        ['auto','Service auto · ITP','🔧','Oferte piese, programări, istoric mașină cu VIN.'],
        ['resto','Restaurante · Pensiuni','🍽️','Rezervări mese, meniu, evenimente, delivery.'],
        ['imob','Imobiliare · Construcții','🏠','Listări filtrate, vizionări, cereri calificate cu scoring.'],
        ['legal','Avocatură · Contabilitate','⚖️','Intake cazuri, programări, FAQ juridic — fără promisiuni.'],
      ] as $n)
        <div class="niche-card rounded-3xl p-6 bg-cream border border-line fade-up" data-niche="{{ $n[0] }}">
          <div class="flex items-start justify-between mb-5">
            <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center text-2xl transition-colors duration-500">{{ $n[2] }}</div>
            <div class="chip text-[10px]" style="padding:4px 10px; background:var(--accent); color:white;">
              <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
              paletă
            </div>
          </div>
          <h3 class="display text-xl font-semibold mb-2">{{ $n[1] }}</h3>
          <p class="text-sm text-muted leading-relaxed mb-5">{{ $n[3] }}</p>

          <div class="rounded-2xl bg-paper p-3 space-y-1.5 border border-line">
            <div class="flex">
              <div class="max-w-[82%] px-3 py-1.5 rounded-xl rounded-br-sm text-xs text-white ml-auto" style="background:var(--accent);">
                Bună, cât costă?
              </div>
            </div>
            <div class="flex">
              <div class="max-w-[82%] px-3 py-1.5 rounded-xl rounded-bl-sm text-xs bg-sand text-ink">
                Tarifele încep de la <strong>180 lei</strong>.
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-10 text-center fade-up">
      <a href="#" class="inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-ink transition">
        Vezi toate cele 30+ verticale
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ONBOARDING -->
<section class="py-24 relative grain">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-xl mb-16 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ onboarding</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">De la <em class="italic accent-text">zero</em><br>la AI live, în 8 minute.</h2>
    </div>

    <div class="space-y-4">
      <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">01</div></div>
        <div class="md:col-span-7">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="display text-2xl font-semibold">Spune-i despre afacerea ta</h3>
            <span class="chip chip-outline mono text-[10px]">~2 min</span>
          </div>
          <p class="text-muted leading-relaxed">Descrie afacerea în 2 propoziții. AI-ul generează automat prompt-ul, personalitatea și setările optime — tu doar ajustezi.</p>
        </div>
        <div class="md:col-span-4">
          <div class="rounded-2xl bg-cream p-4 border border-line">
            <div class="mono text-[10px] uppercase text-muted mb-2">Exemplu</div>
            <p class="text-sm italic">„Clinică stomatologică în București sector 2. Programări online, plătim cu cardul, asigurare parțială."</p>
          </div>
        </div>
      </div>

      <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">02</div></div>
        <div class="md:col-span-7">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="display text-2xl font-semibold">Adaugă informațiile tale</h3>
            <span class="chip chip-outline mono text-[10px]">~5 min</span>
          </div>
          <p class="text-muted leading-relaxed">Uploadează documente, scanează site-ul, conectează magazinul. Agentul AI învață fiecare pagină, politică și produs — cu preț, stoc și imagini.</p>
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
            <h3 class="display text-2xl font-semibold">Activează și monitorizează</h3>
            <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">~1 min</span>
          </div>
          <p class="leading-relaxed" style="color:#D7D3CA;">O linie de cod pe site. Gata. Agentul AI răspunde 24/7, iar tu monitorizezi din dashboard — conversații, leads, health score, gap-uri detectate.</p>
        </div>
        <div class="md:col-span-4">
          <div class="rounded-2xl p-4 mono text-xs leading-relaxed" style="background:#0F0E0C; color:#F2E59A;">
            <div style="color:#78716C;">// în &lt;head&gt;</div>
            <div>&lt;script src=<span style="color:#A7C7F0;">"cdn.sambla.ro/w.js"</span><br>&nbsp;&nbsp;data-bot=<span style="color:#A7C7F0;">"dental-pro"</span>&gt;&lt;/script&gt;</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SE FACE MAI DESTEPT -->
<section class="py-24 bg-paper">
  <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-16 items-center">
    <div class="fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ auto-învățare</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">Se face <em class="italic accent-text">mai deștept</em><br>în fiecare zi.</h2>
      <p class="text-lg text-muted leading-relaxed mb-8">Sambla nu așteaptă să-i spui ce nu știe. Descoperă singur, te anunță, și sugerează soluția.</p>

      <div class="space-y-5">
        <div class="flex gap-4 items-start">
          <div class="w-10 h-10 rounded-xl bg-cream border border-line flex items-center justify-center shrink-0">🔍</div>
          <div>
            <div class="font-semibold mb-1">Detectează întrebările fără răspuns</div>
            <p class="text-sm text-muted leading-relaxed">„12 clienți au întrebat despre retur, dar nu ai conținut. Vrei să generez un draft?"</p>
          </div>
        </div>
        <div class="flex gap-4 items-start">
          <div class="w-10 h-10 rounded-xl bg-cream border border-line flex items-center justify-center shrink-0">✍️</div>
          <div>
            <div class="font-semibold mb-1">Generează conținut automat</div>
            <p class="text-sm text-muted leading-relaxed">AI-ul scrie un draft de politică de retur bazat pe întrebările reale. Tu doar aprobi sau editezi.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start">
          <div class="w-10 h-10 rounded-xl bg-cream border border-line flex items-center justify-center shrink-0">📊</div>
          <div>
            <div class="font-semibold mb-1">Monitorizează calitatea zilnic</div>
            <p class="text-sm text-muted leading-relaxed">Health score, rată de rezolvare, frustrare detectată — totul într-un dashboard clar.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="fade-up" style="transition-delay:.1s;">
      <div class="rounded-3xl p-6 bg-cream border border-line space-y-3">
        <div class="flex items-center justify-between mb-2">
          <div class="font-semibold text-sm">Sugestii de îmbunătățire</div>
          <span class="chip text-[10px]" style="background:#FEF3C7; color:#92400E;">3 pending</span>
        </div>

        <div class="p-4 rounded-2xl bg-paper border border-line">
          <div class="flex items-start justify-between mb-2">
            <span class="mono text-xs font-semibold accent-text">GAP DETECTAT · 12 întrebări</span>
            <span class="text-[11px] text-muted">acum 2h</span>
          </div>
          <p class="text-sm leading-relaxed mb-3">Clienții întreabă despre <strong>politica de retur online</strong>. Am generat un draft bazat pe Legea 449/2003.</p>
          <div class="flex gap-2">
            <button class="text-xs px-3 py-1.5 rounded-full font-medium btn-ghost" style="padding: 6px 14px;">Vezi draft</button>
            <button class="text-xs px-3 py-1.5 rounded-full font-medium btn-outline" style="padding: 6px 14px;">Ignoră</button>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-paper border border-line">
          <div class="flex items-start justify-between mb-2">
            <span class="mono text-xs font-semibold" style="color:#059669;">HEALTH SCORE · 94/100</span>
            <span class="text-[11px] text-muted">actualizat</span>
          </div>
          <p class="text-sm leading-relaxed">Rată rezolvare 91%. Frustrare detectată 3.2% (sub medie). +2 puncte față de săptămâna trecută.</p>
          <div class="mt-3 h-1.5 bg-cream rounded-full overflow-hidden">
            <div class="h-full rounded-full" style="width:94%; background:#059669;"></div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-paper border border-line">
          <div class="flex items-start justify-between mb-2">
            <span class="mono text-xs font-semibold" style="color:#2563EB;">CONTENT DRAFT · READY</span>
            <span class="text-[11px] text-muted">acum 6h</span>
          </div>
          <p class="text-sm leading-relaxed">Draft FAQ „Cum schimb metoda de plată" — generat automat, gata de revizuire.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DEMO -->
<section id="demo" class="py-24 relative overflow-hidden">
  <div class="absolute inset-0 opacity-50 pointer-events-none" style="background: radial-gradient(ellipse 60% 40% at 50% 50%, rgba(220,38,38,0.12) 0%, transparent 60%);"></div>
  <div class="max-w-5xl mx-auto px-6 text-center relative">
    <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4 fade-up">◇ demo live</div>
    <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5 fade-up">Vorbește cu <em class="italic accent-text">Sambla AI</em>.</h2>
    <p class="text-lg text-muted mb-10 max-w-xl mx-auto fade-up">Agent real, conectat live. Întreabă orice sau alege o sugestie de mai jos.</p>

    <div class="max-w-2xl mx-auto rounded-3xl bg-paper border border-line overflow-hidden shadow-xl fade-up">
      <div class="px-5 py-4 flex items-center gap-3 border-b border-line bg-cream">
        <div class="w-10 h-10 rounded-full bg-brand flex items-center justify-center text-white display text-base font-semibold">S</div>
        <div class="flex-1 text-left">
          <div class="font-semibold text-sm">Sambla · Agent general</div>
          <div class="text-xs text-muted flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Online live</div>
        </div>
      </div>
      <div class="p-6 text-left space-y-3 min-h-[180px]">
        <div class="flex">
          <div class="max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-sm bg-sand text-ink">
            Bună! Sunt Sambla — agentul AI oficial al platformei. Întreabă-mă despre funcționalități, prețuri, integrări sau tehnologie.
          </div>
        </div>
      </div>
      <div class="px-4 py-3 border-t border-line bg-cream flex flex-wrap gap-2 justify-center">
        @foreach(['Ce funcționalități are?', 'Cât costă?', 'Cum se integrează cu WooCommerce?', 'Cum garantați că nu inventează?'] as $sug)
          <button class="chip chip-outline hover:border-brand hover:text-brand transition">{{ $sug }}</button>
        @endforeach
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIAL BIG -->
<section class="py-24 grain relative">
  <div class="max-w-6xl mx-auto px-6">
    <div class="rounded-[2.5rem] bg-ink text-cream p-10 md:p-16 overflow-hidden relative grid md:grid-cols-12 gap-10 items-center fade-up">
      <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, rgba(220,38,38,0.3) 0%, transparent 70%);"></div>

      <div class="md:col-span-8 relative">
        <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6" style="color:#F2E59A;">◇ de la clienții noștri</div>
        <p class="display text-3xl md:text-4xl leading-[1.15] font-normal mb-8">
          „Pierdeam jumătate din apelurile după program. Acum
          <span class="italic accent-text">fiecare e preluat de Sambla</span>
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

<!-- PRICING TEASE -->
<section id="preturi" class="py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-xl mx-auto text-center mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ prețuri</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5"><em class="italic accent-text">Simple.</em> În lei.<br>Fără surprize.</h2>
      <p class="text-lg text-muted">7 zile gratuit. Fără card. Anulezi oricând. 30% reducere ONG + școli.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line">
        <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Chat Starter</div>
        <div class="flex items-baseline gap-1 mb-3"><span class="display text-6xl font-medium">29</span><span class="text-muted">lei / lună</span></div>
        <p class="text-sm text-muted mb-6 pb-6 border-b border-line">Ideal pentru afaceri mici cu un agent AI simplu.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>1 agent · 500 conversații/lună</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Widget chat pe 1 site</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Bază cunoștințe nelimitată</li>
          <li class="flex gap-2 items-start text-muted"><span>—</span>WhatsApp / FB / IG</li>
          <li class="flex gap-2 items-start text-muted"><span>—</span>Voce AI</li>
        </ul>
        <a href="/register" class="btn-outline w-full justify-center">Începe gratuit</a>
      </div>

      <div class="fade-up rounded-3xl p-8 bg-ink text-cream relative" style="transition-delay:.1s;">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip bg-brand text-white text-[10px] font-semibold">Recomandat</div>
        <div class="mono text-xs uppercase tracking-wider mb-3" style="color:#F2E59A;">Chat Professional</div>
        <div class="flex items-baseline gap-1 mb-3"><span class="display text-6xl font-medium">79</span><span style="color:#A8A29E;">lei / lună</span></div>
        <p class="text-sm mb-6 pb-6 border-b" style="color:#D7D3CA; border-color: rgba(255,255,255,.1);">Multi-canal + CRM lead pipeline.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>3 agenți · 2.500 conv./lună</li>
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>WooCommerce + WhatsApp</li>
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>Lead scoring + CRM pipeline</li>
          <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>Analiză avansată</li>
          <li class="flex gap-2 items-start" style="color:#A8A29E;"><span>—</span>Voce AI (+49 lei addon)</li>
        </ul>
        <a href="/register" class="btn-primary w-full justify-center" style="background:#F2E59A; color:#1C1917;">Alege Professional →</a>
      </div>

      <div class="fade-up rounded-3xl p-8 bg-paper border border-line" style="transition-delay:.2s;">
        <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Chat Business</div>
        <div class="flex items-baseline gap-1 mb-3"><span class="display text-6xl font-medium">199</span><span class="text-muted">lei / lună</span></div>
        <p class="text-sm text-muted mb-6 pb-6 border-b border-line">Volum mare + toate canalele + voce.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>10 agenți · 10.000 conv./lună</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Toate canalele (FB · IG · WA)</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Voce AI disponibilă</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Suport prioritar</li>
          <li class="flex gap-2 items-start"><span class="accent-text">✓</span>API + webhooks</li>
        </ul>
        <a href="/register" class="btn-outline w-full justify-center">Alege Business</a>
      </div>
    </div>

    <div class="text-center mt-8 fade-up">
      <a href="/preturi" class="text-sm text-muted hover:text-ink underline underline-offset-4">Vezi toate planurile + opțiuni voce →</a>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-24 bg-paper">
  <div class="max-w-3xl mx-auto px-6">
    <div class="text-center mb-12 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ întrebări frecvente</div>
      <h2 class="display text-5xl font-medium">Răspunsuri <em class="italic accent-text">sincere</em>.</h2>
    </div>
    <div class="space-y-3 fade-up">
      @foreach([
        ['Ce este Sambla?', 'Sambla e o platformă AI românească care construiește agenți inteligenți pentru chat și telefon. Aceștia răspund clienților tăi 24/7, folosind documentele, produsele și politicile afacerii tale reale — nu informații generice.'],
        ['Cum garantați că nu inventează?', 'Pipeline cu 10 straturi de verificare, citare sursă obligatorie și confidence scoring. Când AI-ul nu e sigur, răspunde cinstit „nu am această informație" și escaladează către operator — nu improvizează pe politici, prețuri sau promisiuni.'],
        ['Funcționează în română?', 'Da, nativ. Antrenat cu corpus RO, înțelege diacritice, regionalisme, construcții specifice. Voce naturală română prin OpenAI Realtime, fără accent robotic.'],
        ['Pot combina chat + voce?', 'Da. Alegi un plan chat și adaugi un addon de voce (+49 sau +149 lei). Ambele folosesc aceeași bază de cunoștințe și istoric unificat per client.'],
        ['Unde sunt stocate datele?', 'Servere fizice în România. Zero transfer în afara UE. GDPR compliant by default, cu izolare strictă per cont. Audit-uri GDPR direct servite.'],
        ['Funcționează cu WordPress / WooCommerce?', 'Da. Plugin WordPress nativ + sync WooCommerce complet — produse, stocuri, prețuri, comenzi. Plus embed pe orice site cu o singură linie de cod.'],
        ['Oferiți reducere pentru ONG?', 'Da. 30% permanent pentru ONG-uri, școli, universități, muzee. Trimiți documentele organizației și activăm reducerea imediat.'],
      ] as $f)
        <details class="rounded-2xl bg-cream border border-line overflow-hidden">
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

<!-- FINAL CTA -->
<section class="py-28 relative overflow-hidden">
  <div class="absolute inset-0 opacity-60 pointer-events-none" style="background: radial-gradient(ellipse 60% 50% at 50% 50%, rgba(220,38,38,0.18) 0%, transparent 60%);"></div>
  <div class="max-w-4xl mx-auto px-6 text-center relative fade-up">
    <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-6">◇ acum e momentul</div>
    <h2 class="display text-5xl md:text-7xl font-medium leading-[1.02] mb-8">
      Transformă fiecare conversație<br>
      <em class="italic accent-text">într-o oportunitate</em>.
    </h2>
    <p class="text-xl text-muted mb-10 max-w-xl mx-auto leading-relaxed">Configurezi în 10 minute. Primele rezultate din prima zi. Fără card, fără obligații.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="/register" class="btn-primary text-base" style="padding: 16px 28px;">
        Începe gratuit acum
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
      <a href="/contact" class="btn-outline text-base" style="padding: 16px 28px;">Programează un demo</a>
    </div>
    <p class="mt-8 text-xs mono text-muted">Sau scrie direct · servus@sambla.ro · 0775 222 333</p>
  </div>
</section>

<!-- FOOTER -->
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
          <li><a href="#demo" class="hover:text-ink">Demo live</a></li>
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
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

// Hero: rotating scenarios (convenție: CLIENT → dreapta + accent color; AI → stânga + sand neutru)
(function() {
  const scenarios = [
    { niche:'medical', label:'🦷 Cabinet stomatologic', footer:'✓ Programare din baza de cunoștințe', badge:'Programare confirmată', messages:[
      { user:true,  text:'Bună ziua. Aveți loc liber pentru detartraj săptămâna viitoare?' },
      { user:false, text:'Da, am găsit 3 disponibilități. Marți 22, 10:00 · Dr. Ionescu — 180 lei. Vă convine?' },
      { user:true,  text:'Da, pe Maria Popescu.' },
      { user:false, text:'✓ Rezervat. Vă trimit SMS cu detaliile și reamintire cu 24h înainte.' },
    ]},
    { niche:'auto', label:'🛒 Magazin online', footer:'✓ Stoc real sincronizat WooCommerce', badge:'Comandă preluată', messages:[
      { user:true,  text:'Mai aveți vopsea albă 10L pe stoc?' },
      { user:false, text:'Da! 23 bucăți în stoc · 89 lei · livrare mâine 10-14 🚚' },
      { user:true,  text:'Comanda 2 bucăți.' },
      { user:false, text:'Adăugat în coș. Total 178 lei + transport gratuit peste 150 lei. Finalizezi?' },
    ]},
    { niche:'auto', label:'🔧 Service auto', footer:'✓ Istoric VIN verificat automat', badge:'Programare service', messages:[
      { user:true,  text:'Cât costă schimbul de ulei la BMW X3?' },
      { user:false, text:'Ce an? Pentru X3 2020+: schimb ulei + filtru — 420 lei, durează ~1h.' },
      { user:true,  text:'2022. Marți dimineața?' },
      { user:false, text:'Marți 9:00 rezervat ✓ Vă așteptăm. Aveți nevoie să lăsați mașina?' },
    ]},
    { niche:'resto', label:'🏨 Pensiune', footer:'✓ Disponibilitate iCal live', badge:'Rezervare trimisă', messages:[
      { user:true,  text:'Aveți cameră dublă pentru acest weekend?' },
      { user:false, text:'Da! Camera Alba liberă 22-23 apr · 280 lei/noapte, mic-dejun inclus.' },
      { user:true,  text:'Perfect, rezerv 2 nopți.' },
      { user:false, text:'✓ 2 nopți × 280 = 560 lei. Vă trimit link de plată pe email?' },
    ]},
    { niche:'imob', label:'🏠 Agenție imobiliară', footer:'✓ Listări filtrate după criterii', badge:'Vizionare programată', messages:[
      { user:true,  text:'Caut apartament 2 camere în Cluj, până în 95.000 €.' },
      { user:false, text:'Am 4 proprietăți în buget. Cea mai potrivită: 58 mp, Mărăști, etaj 3 — 89.000 €.' },
      { user:true,  text:'Aș vrea să o văd sâmbătă.' },
      { user:false, text:'Sâmbătă 11:00 cu agentul Andrei ✓ Vă trimit adresa pe WhatsApp.' },
    ]},
    { niche:'beauty', label:'💆 Clinică estetică', footer:'✓ Preț din lista de servicii', badge:'Consultație rezervată', messages:[
      { user:true,  text:'Cât costă un tratament cu acid hialuronic?' },
      { user:false, text:'De la 450 lei/ml. Primul pachet include consultație gratuită cu dr. Petrescu.' },
      { user:true,  text:'Când am loc?' },
      { user:false, text:'Joi 17:00 e primul disponibil. Rezerv? Durată ~30 min.' },
    ]},
    { niche:'legal', label:'⚖️ Cabinet avocatură', footer:'✓ Programare în calendar partener', badge:'Consultație rezervată', messages:[
      { user:true,  text:'Am nevoie de consultanță pentru un litigiu comercial.' },
      { user:false, text:'Cabinetul acoperă litigii comerciale, inclusiv recuperări creanțe. Doriți o programare?' },
      { user:true,  text:'Da, cât mai repede.' },
      { user:false, text:'Cel mai apropiat slot: miercuri 14:00 cu av. Popescu. 45 min consultație.' },
    ]},
    { niche:'legal', label:'📊 Contabilitate', footer:'✓ Pachet adaptat tipului de firmă', badge:'Ofertă trimisă', messages:[
      { user:true,  text:'Am un SRL nou, aveți pachet de contabilitate lunară?' },
      { user:false, text:'Felicitări! SRL micro pornește de la 500 lei/lună — include contabilitate + declarații + consultanță.' },
      { user:true,  text:'Ce documente îmi trebuie la început?' },
      { user:false, text:'Certificat înregistrare, act constitutiv și buletinele asociaților. Programăm o întâlnire?' },
    ]},
  ];

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

  let current = 0, timers = [], gen = 0;
  const t = (fn, d) => { const id = setTimeout(fn, d); timers.push(id); return id; };
  const clearAll = () => { timers.forEach(clearTimeout); timers = []; gen++; typing.classList.add('hidden'); typing.classList.remove('flex'); };

  scenarios.forEach((_, i) => {
    const s = document.createElement('button');
    s.className = 'w-1.5 h-1.5 rounded-full transition-all duration-300';
    s.style.background = '#D7D3CA';
    s.addEventListener('click', () => { current = i; play(current); });
    dots.appendChild(s);
  });
  function setDot(i) {
    for (let k = 0; k < dots.children.length; k++) {
      const d = dots.children[k], on = k === i;
      d.style.background = on ? 'var(--accent)' : '#D7D3CA';
      d.style.transform = on ? 'scale(1.6)' : 'scale(1)';
      d.style.width = on ? '18px' : '6px';
      d.style.borderRadius = '999px';
    }
  }

  function addBubble(msg, onDone) {
    const row = document.createElement('div');
    row.className = msg.user ? 'flex justify-end' : 'flex';
    row.style.cssText = 'opacity:0;transform:translateY(6px);transition:opacity .35s ease,transform .35s ease';
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
      typing.classList.remove('hidden'); typing.classList.add('flex');
      chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
      t(() => {
        typing.classList.add('hidden'); typing.classList.remove('flex');
        addBubble(msg, onDone);
      }, 700 + Math.random() * 400);
    } else { addBubble(msg, onDone); }
  }

  function play(index) {
    clearAll();
    const myGen = ++gen;
    const sc = scenarios[index];
    card.setAttribute('data-niche', sc.niche);
    label.style.opacity = '0'; footer.style.opacity = '0'; if (badge) badge.style.opacity = '0';
    t(() => {
      if (myGen !== gen) return;
      label.textContent = sc.label; footer.textContent = sc.footer;
      if (badge) { badge.textContent = sc.badge; badge.style.opacity = '1'; }
      label.style.opacity = '1'; footer.style.opacity = '1';
    }, 220);
    setDot(index);
    inner.innerHTML = '';
    let i = 0;
    const next = () => {
      if (myGen !== gen) return;
      if (i >= sc.messages.length) {
        t(() => {
          if (myGen !== gen) return;
          current = (current + 1) % scenarios.length;
          play(current);
        }, 3200);
        return;
      }
      const m = sc.messages[i];
      const delay = i === 0 ? 500 : (m.user ? 700 : 200);
      t(() => { if (myGen !== gen) return; addMessage(m, () => { i++; next(); }); }, delay);
    };
    next();
  }

  t(() => play(current), 400);

  let startX = 0, startY = 0;
  chatEl.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; startY = e.touches[0].clientY; }, { passive: true });
  chatEl.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - startX, dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      current = (current + (dx < 0 ? 1 : -1) + scenarios.length) % scenarios.length;
      play(current);
    }
  }, { passive: true });
})();
</script>

</body>
</html>
