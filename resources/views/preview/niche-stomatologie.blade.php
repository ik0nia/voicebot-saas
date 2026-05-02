<!DOCTYPE html>
<html lang="ro" data-niche="medical">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Agent AI pentru cabinete stomatologice — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans:['Inter','system-ui','sans-serif'], display:['Instrument Sans','Inter','sans-serif'], mono:['JetBrains Mono','monospace'] },
      colors: { cream:'#F5F1E8', paper:'#FAF7EF', sand:'#EFE5D0', ink:'#1C1917', muted:'#78716C', line:'#E7E0CE' }
    }
  }
}
</script>
<style>
  :root { --accent: #3B82F6; --accent-soft: #DBEAFE; --accent-dark: #2563EB; }
  [data-niche="medical"] { --accent: #3B82F6; --accent-soft: #DBEAFE; --accent-dark: #2563EB; }
  [data-niche="beauty"]  { --accent: #F43F5E; --accent-soft: #FFE4E6; --accent-dark: #E11D48; }
  [data-niche="auto"]    { --accent: #F97316; --accent-soft: #FFEDD5; --accent-dark: #EA580C; }
  [data-niche="resto"]   { --accent: #10B981; --accent-soft: #D1FAE5; --accent-dark: #059669; }
  [data-niche="imob"]    { --accent: #F59E0B; --accent-soft: #FEF3C7; --accent-dark: #D97706; }
  [data-niche="legal"]   { --accent: #A855F7; --accent-soft: #F3E8FF; --accent-dark: #9333EA; }

  html { scroll-behavior: smooth; }
  body { font-family: 'Inter', system-ui, sans-serif; background: #F5F1E8; color: #1C1917; -webkit-font-smoothing: antialiased; }
  .display { font-family: 'Instrument Sans', sans-serif; letter-spacing: -0.02em; }
  .mono { font-family: 'JetBrains Mono', monospace; }

  .btn-primary { background: var(--accent); color: white; border-radius: 999px; padding: 14px 24px; font-weight: 600; transition: all .2s ease; display:inline-flex; align-items:center; gap:8px; }
  .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); box-shadow: 0 10px 30px color-mix(in srgb, var(--accent) 30%, transparent); }
  .btn-ghost { background: #1C1917; color: white; border-radius: 999px; padding: 14px 24px; font-weight: 600; transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
  .btn-ghost:hover { background: #3A3532; transform: translateY(-1px); }
  .btn-outline { border: 1.5px solid #1C1917; color: #1C1917; background: transparent; border-radius: 999px; padding: 14px 24px; font-weight: 600; transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
  .btn-outline:hover { background: #1C1917; color: white; }

  .fade-up { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; }
  .fade-up.in { opacity: 1; transform: translateY(0); }
  .float { animation: floatY 6s ease-in-out infinite; }
  @keyframes floatY { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-8px) } }
  .dots span { animation: dot 1.4s infinite; }
  .dots span:nth-child(2) { animation-delay: .2s }
  .dots span:nth-child(3) { animation-delay: .4s }
  @keyframes dot { 0%,60%,100% { opacity: .3 } 30% { opacity: 1 } }
  .ticker { animation: tickerScroll 50s linear infinite; }
  @keyframes tickerScroll { 0% { transform: translateX(0) } 100% { transform: translateX(-50%) } }

  .hero-glow { background:
    radial-gradient(ellipse 45% 35% at 20% 15%, color-mix(in srgb, var(--accent) 18%, transparent) 0%, transparent 60%),
    radial-gradient(ellipse 40% 30% at 85% 20%, rgba(247,213,147,0.35) 0%, transparent 60%),
    radial-gradient(ellipse 50% 40% at 70% 90%, rgba(199,184,232,0.25) 0%, transparent 60%);
    transition: background .8s ease;
  }
  .grain { position: relative; }
  .grain::after { content:''; position:absolute; inset:0; pointer-events:none; opacity:.4; background-image: radial-gradient(rgba(28,25,23,0.05) 1px, transparent 1px); background-size: 3px 3px; }

  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(180deg); }

  .chip { border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
  .chip-outline { background: white; border: 1px solid #E7E0CE; color: #78716C; }

  .accent-text { color: var(--accent); }
  .accent-bg { background: var(--accent); }
  .accent-soft-bg { background: var(--accent-soft); }

  .niche-tab { transition: all .2s ease; border: 1px solid transparent; }
  .niche-tab:hover { background: white; border-color: #E7E0CE; }
  .niche-tab.active { background: white; border-color: transparent; box-shadow: 0 0 0 2px currentColor; }

  .niche-card { transition: transform .25s ease, box-shadow .25s ease; }
  .niche-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px color-mix(in srgb, var(--accent) 25%, transparent); }

  .stat-num { font-feature-settings: "tnum"; }

  .form-input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1px solid #E7E0CE;
    background: #FAF7EF;
    font-size: 14px;
    transition: all .2s ease;
  }
  .form-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
  .form-label { font-size: 12px; font-weight: 600; color: #1C1917; margin-bottom: 6px; display: block; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview · Landing page de nișă · stomatologie · <a href="/preview" class="underline">lista completă</a>
</div>

<!-- TICKER -->
<div class="bg-sand border-b border-line py-2 overflow-hidden">
  <div class="ticker flex gap-10 whitespace-nowrap text-xs mono text-muted">
    @for($i=0; $i<3; $i++)
      <span>✦ Agent AI pentru cabinete stomatologice</span>
      <span class="accent-text">●</span>
      <span>✦ Integrare Google Calendar cabinet</span>
      <span class="accent-text">●</span>
      <span>✦ Triaj urgențe automat</span>
      <span class="accent-text">●</span>
      <span>✦ GDPR date medicale</span>
      <span class="accent-text">●</span>
      <span>✦ Română + engleză pentru expati</span>
      <span class="accent-text">●</span>
    @endfor
  </div>
</div>

<!-- NICHE SWITCHER -->
<div class="bg-paper border-b border-line sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-6 py-2 flex items-center gap-2 overflow-x-auto">
    <span class="mono text-[10px] uppercase tracking-wider text-muted shrink-0 mr-2">◇ schimbă vertical:</span>
    <button class="niche-tab active flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shrink-0" data-switch="medical" style="color:#3B82F6;">
      <span class="w-2 h-2 rounded-full" style="background:#3B82F6;"></span>🦷 Stomatologie
    </button>
    <button class="niche-tab flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shrink-0" data-switch="beauty" style="color:#F43F5E;">
      <span class="w-2 h-2 rounded-full" style="background:#F43F5E;"></span>💆 Estetică
    </button>
    <button class="niche-tab flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shrink-0" data-switch="auto" style="color:#F97316;">
      <span class="w-2 h-2 rounded-full" style="background:#F97316;"></span>🔧 Auto
    </button>
    <button class="niche-tab flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shrink-0" data-switch="resto" style="color:#10B981;">
      <span class="w-2 h-2 rounded-full" style="background:#10B981;"></span>🏨 Pensiuni
    </button>
    <button class="niche-tab flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shrink-0" data-switch="imob" style="color:#F59E0B;">
      <span class="w-2 h-2 rounded-full" style="background:#F59E0B;"></span>🏠 Imobiliare
    </button>
    <button class="niche-tab flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shrink-0" data-switch="legal" style="color:#A855F7;">
      <span class="w-2 h-2 rounded-full" style="background:#A855F7;"></span>⚖️ Avocatură
    </button>
    <span class="text-[10px] text-muted shrink-0 ml-auto hidden md:inline">doar paleta se schimbă — conținutul rămâne stomatologie</span>
  </div>
</div>

<!-- NAV -->
<nav class="bg-cream/80 backdrop-blur border-b border-line/60">
  <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
    <div class="flex items-center gap-4 shrink-0">
      <a href="#"><img src="/images/logo-light.svg" alt="Sambla" class="h-10 md:h-11 w-auto"></a>
      <span class="hidden md:inline-flex items-center gap-1.5 chip accent-soft-bg mono text-[10px]" style="color:var(--accent-dark);">
        <span class="w-1.5 h-1.5 rounded-full accent-bg"></span>
        pentru · cabinete stomatologice
      </span>
    </div>
    <div class="hidden lg:flex items-center gap-8 text-sm font-medium text-muted">
      <a href="#problema" class="hover:text-ink transition">Problema</a>
      <a href="#solutia" class="hover:text-ink transition">Soluția</a>
      <a href="#avantaje" class="hover:text-ink transition">Avantaje</a>
      <a href="#demo" class="hover:text-ink transition">Demo</a>
      <a href="#preturi" class="hover:text-ink transition">Prețuri</a>
      <a href="#faq" class="hover:text-ink transition">FAQ</a>
    </div>
    <div class="flex items-center gap-2 text-sm">
      <a href="/login" class="hidden sm:inline px-4 py-2 text-muted hover:text-ink transition">Autentificare</a>
      <a href="/register" class="btn-primary">Începe gratuit</a>
    </div>
  </div>
</nav>

<!-- Breadcrumbs -->
<div class="max-w-7xl mx-auto px-6 pt-4 text-xs text-muted mono">
  <a href="/" class="hover:text-ink">Sambla</a>
  <span class="mx-1.5">/</span>
  <a href="#" class="hover:text-ink">Pentru afaceri</a>
  <span class="mx-1.5">/</span>
  <span class="text-ink">Cabinete stomatologice</span>
</div>

<!-- HERO -->
<section class="hero-glow relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-6 pt-14 pb-24 grid lg:grid-cols-12 gap-12 items-start relative">
    <div class="lg:col-span-6 fade-up">
      <div class="chip accent-soft-bg mono text-[10px] mb-7" style="color:var(--accent-dark);">
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full rounded-full accent-bg opacity-60 animate-ping"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 accent-bg"></span>
        </span>
        <span class="uppercase tracking-wider">🦷 agent AI pentru cabinete stomatologice</span>
      </div>

      <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-7">
        Recepția care<br>
        <span class="italic font-normal accent-text">nu te oprește din lucru.</span>
      </h1>

      <p class="text-xl leading-relaxed text-muted mb-9 max-w-xl">
        Un agent AI care preia apelurile 24/7, face programări direct în calendarul cabinetului, răspunde la întrebări despre tarife și proceduri, și escaladează urgențele la tine. <strong class="text-ink">Tu te concentrezi pe pacient, nu pe telefon.</strong>
      </p>

      <div class="flex flex-wrap gap-3 mb-9">
        <a href="/register" class="btn-primary">
          Încearcă 7 zile gratuit
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
        <a href="#demo-form" class="btn-outline">Vorbește cu noi</a>
      </div>

      <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> 100% în română</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Setup 10 min</span>
        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> GDPR medical</span>
      </div>
    </div>

    <!-- Hero chat -->
    <div class="lg:col-span-6 fade-up" style="transition-delay: .15s">
      <div class="relative">
        <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%);"></div>
        <div class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid #E7E0CE; box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
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
              <div class="font-semibold text-sm">Clinica Dental Pro</div>
              <div class="text-xs text-muted flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Online · răspunde în sub 2 secunde
              </div>
            </div>
            <span class="chip accent-soft-bg mono text-[10px] transition-all duration-500" style="color: var(--accent-dark);">🦷 cabinet stomatologic</span>
          </div>

          <div id="heroChat" class="px-5 py-4 h-[420px] overflow-y-auto" style="scrollbar-width:none;">
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

          <div class="px-4 py-3 border-t border-line bg-paper flex items-center gap-2">
            <svg class="w-4 h-4 accent-text shrink-0 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
            <span class="text-xs text-muted font-medium truncate">✓ Sincronizat cu calendarul cabinetului · GDPR medical</span>
          </div>
        </div>

        <div class="absolute -left-4 -bottom-4 bg-white rounded-2xl shadow-xl p-4 pr-5 flex items-center gap-3 border border-line max-w-[280px] float" style="animation-delay:.5s;">
          <div class="w-10 h-10 rounded-xl accent-soft-bg accent-text flex items-center justify-center transition-colors duration-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div>
            <div class="text-sm font-semibold leading-tight">Programare rezervată</div>
            <div class="text-xs text-muted">Marți 22 · 10:00 · Dr. Ionescu</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick stats strip under hero -->
  <div class="max-w-7xl mx-auto px-6 pb-16">
    <div class="rounded-3xl bg-paper border border-line p-6 md:p-8 grid grid-cols-2 md:grid-cols-4 gap-6 fade-up">
      @foreach([
        ['&lt;2s','Răspuns mediu'],
        ['24/7','Disponibil non-stop'],
        ['100%','În limba română'],
        ['10 min','Setup complet'],
      ] as $s)
        <div class="text-center md:text-left">
          <div class="display text-4xl md:text-5xl font-medium mb-1 stat-num accent-text transition-colors duration-500">{!! $s[0] !!}</div>
          <div class="text-xs mono uppercase tracking-wider text-muted">{{ $s[1] }}</div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- PROBLEMA -->
<section id="problema" class="py-24 bg-paper border-y border-line relative grain overflow-hidden">
  <div class="max-w-6xl mx-auto px-6 relative grid lg:grid-cols-12 gap-12 items-start">
    <div class="lg:col-span-5 fade-up lg:sticky lg:top-28">
      <div class="mono text-[11px] uppercase tracking-[0.2em] accent-text mb-4 transition-colors duration-500">◇ problema</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">
        Știi exact <em class="italic">cum sună.</em>
      </h2>
      <div class="rounded-3xl p-7 bg-cream border border-line">
        <div class="flex items-start gap-4 mb-4">
          <div class="w-12 h-12 rounded-2xl accent-soft-bg accent-text flex items-center justify-center text-2xl shrink-0 transition-colors duration-500">📊</div>
          <div>
            <div class="text-sm font-semibold">Media unui cabinet din România</div>
            <div class="text-xs text-muted">date interne · 47 clinici monitorizate</div>
          </div>
        </div>
        <div class="space-y-3">
          <div class="flex items-baseline justify-between gap-3 pb-3 border-b border-line">
            <span class="text-sm">Telefoane pierdute / zi</span>
            <span class="display text-2xl font-semibold accent-text transition-colors duration-500">8–14</span>
          </div>
          <div class="flex items-baseline justify-between gap-3 pb-3 border-b border-line">
            <span class="text-sm">Timp pierdut cu telefoane / zi</span>
            <span class="display text-2xl font-semibold accent-text transition-colors duration-500">2–4h</span>
          </div>
          <div class="flex items-baseline justify-between gap-3 pb-3 border-b border-line">
            <span class="text-sm">Pacienți no-show / săpt.</span>
            <span class="display text-2xl font-semibold accent-text transition-colors duration-500">~20%</span>
          </div>
          <div class="flex items-baseline justify-between gap-3">
            <span class="text-sm">Pacienți pierduți după ora 19:00</span>
            <span class="display text-2xl font-semibold accent-text transition-colors duration-500">necunoscut</span>
          </div>
        </div>
      </div>
    </div>

    <div class="lg:col-span-7 fade-up space-y-6" style="transition-delay:.1s;">
      <p class="text-xl leading-relaxed text-ink">
        Ești cu mâinile în gura unui pacient, cu freza pornită, și telefonul de la recepție sună a treia oară în ultimele zece minute. Asistenta lasă aspiratorul, fuge să răspundă, iar tu <em class="italic">pierzi ritmul intervenției</em>.
      </p>

      <p class="text-lg leading-relaxed text-muted">
        Între timp, alți doi pacienți au sunat, n-au primit răspuns și au format deja numărul cabinetului din colț. În medie, un cabinet stomatologic din România pierde între <strong class="text-ink">2 și 4 ore pe zi</strong> doar cu telefoane repetitive: <em>„Cât costă un detartraj?"</em>, <em>„Aveți loc mâine?"</em>, <em>„Cât e o fațetă?"</em>, <em>„Faceți și implanturi?"</em>. Aceleași 15 întrebări, repetate de 30 de ori pe zi.
      </p>

      <p class="text-lg leading-relaxed text-muted">
        Apoi vine seara: <strong class="text-ink">telefonul nu mai e preluat după ora 19:00</strong> și în weekend e închis. Pacientul cu durere de măsea de sâmbătă dimineața nu te așteaptă până luni — sună la următorul cabinet din Google și nu se mai întoarce niciodată.
      </p>

      <p class="text-lg leading-relaxed text-muted">
        La asta se adaugă <strong class="text-ink">no-show-urile</strong>: 1 din 5 pacienți uită pur și simplu de programare, pentru că nimeni nu are timp să sune să confirme cu o zi înainte. Rezultatul? Sloturi goale în agendă, recepție epuizată, medici frustrați și venituri pierdute pe care nici nu le mai numeri.
      </p>

      <div class="rounded-2xl p-5 border-l-4 mt-8" style="background:#FEF3C7; border-color:#F59E0B;">
        <div class="flex items-start gap-3">
          <span class="text-2xl">💡</span>
          <div>
            <div class="font-semibold mb-1">Estimare conservativă</div>
            <p class="text-sm text-muted leading-relaxed">Un cabinet de 3 medici pierde ~<strong class="text-ink">40.000 lei/lună</strong> în programări ratate și pacienți care nu revin. Suma reală e de obicei mai mare.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SOLUTIA -->
<section id="solutia" class="py-24 relative overflow-hidden">
  <div class="max-w-6xl mx-auto px-6 relative grid lg:grid-cols-12 gap-12 items-start">
    <div class="lg:col-span-7 fade-up space-y-6">
      <div class="mono text-[11px] uppercase tracking-[0.2em] accent-text mb-4 transition-colors duration-500">◇ soluția</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">
        Cum funcționează <em class="italic accent-text transition-colors duration-500">Sambla</em><br>
        pentru cabinetul tău.
      </h2>

      <p class="text-lg leading-relaxed text-muted">
        Sambla este un <strong class="text-ink">agent AI vocal + chat</strong> care răspunde la numărul cabinetului tău exact așa cum ar face-o o recepționeră experimentată — doar că nu obosește, nu pleacă în pauză și lucrează 24 din 24.
      </p>

      <p class="text-lg leading-relaxed text-muted">
        Îl antrenăm pe lista ta de servicii și tarife — consultație, detartraj, obturații, endodonție, implanturi, ortodonție, albire — pe orarul medicilor și pe regulile cabinetului. Se conectează direct la <strong class="text-ink">Google Calendar</strong> al fiecărui medic, vede sloturile libere în timp real și face programarea pe loc, în timpul apelului.
      </p>

      <p class="text-lg leading-relaxed text-muted">
        Trimite automat confirmare prin SMS sau WhatsApp și un reminder cu 24 de ore înainte — ceea ce <strong class="text-ink">reduce no-show-urile cu până la 50%</strong>. Răspunde calm și clar la întrebări despre prețuri, despre proceduri și despre ce trebuie să aducă pacientul la prima vizită.
      </p>

      <p class="text-lg leading-relaxed text-muted">
        Dacă cineva sună noaptea cu o <strong class="text-ink">durere acută</strong> sau o traumă dentară, agentul recunoaște urgența, oferă instrucțiuni de prim ajutor și sună imediat medicul de gardă pe care l-ai stabilit. Toate datele stau pe servere din România, conform GDPR pentru date medicale, iar tu vezi fiecare conversație în panoul de control.
      </p>
    </div>

    <div class="lg:col-span-5 fade-up space-y-3" style="transition-delay:.1s;">
      @foreach([
        ['01','📚','Învață din datele tale','Conectezi site-ul, lista de servicii și documentele. Agentul citește tot și răspunde doar din surse reale.'],
        ['02','☎️','Răspunde 24/7','Pe telefon, site, WhatsApp, Messenger, Instagram — într-o română naturală, fără accent robotic.'],
        ['03','🔍','Învață ce nu știe','Îți semnalează automat întrebările la care n-a știut, ca să închizi golurile din baza de cunoștințe.'],
        ['04','🚨','Escaladează la medic','Când detectează urgență dentară sau frustrare, transferă instant la medicul de gardă.'],
      ] as $step)
        <div class="niche-card rounded-2xl p-5 bg-paper border border-line flex items-start gap-4">
          <div class="mono text-2xl font-semibold accent-text w-10 shrink-0 transition-colors duration-500">{{ $step[0] }}</div>
          <div class="text-3xl shrink-0">{{ $step[1] }}</div>
          <div class="flex-1">
            <h4 class="font-semibold mb-1">{{ $step[2] }}</h4>
            <p class="text-sm text-muted leading-relaxed">{{ $step[3] }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- AVANTAJE -->
<section id="avantaje" class="py-24 bg-paper border-y border-line">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ avantaje</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Avantajele Sambla pentru <em class="italic accent-text transition-colors duration-500">cabinetul tău</em>.</h2>
      <p class="text-lg text-muted leading-relaxed">Șase capabilități care se traduc direct în pacienți câștigați, timp eliberat și stres redus pentru echipă.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
      @foreach([
        ['📞','Programări 24/7 fără să sune nimeni la recepție','Pacienții își fac singuri programare la 23:00 sau duminica dimineața, direct pe telefon sau pe WhatsApp. Agentul vede calendarul medicilor în timp real și rezervă slotul pe loc — cu confirmare instant prin SMS.'],
        ['⏰','Reminder automat cu 24h înainte (no-show −50%)','SMS sau apel vocal de confirmare cu o zi înainte de programare. Pacienții uită mai greu, iar tu îți păstrezi agenda plină fără efort din partea echipei. În clinicile noastre pilot, no-show-ul a scăzut de la 22% la 9%.'],
        ['💰','Răspunde la întrebări despre tarife și proceduri','Agentul știe tarifele pentru fiecare procedură (consultație, detartraj, obturație, endodonție, implant, ortodonție, albire) și diferențele între ele. Recepția nu mai repetă aceleași explicații de 30 de ori pe zi — și răspunsurile sunt mereu consistente.'],
        ['🚨','Triere urgențe vs. rutină pre-cabinet','Distinge între o durere acută, o traumă dentară post-accident și o programare de rutină, pe baza unor cuvinte-cheie pre-configurate. Urgențele sunt escaladate imediat la medicul de gardă cu notificare pe WhatsApp, restul intră în agendă normală.'],
        ['💊','Instrucțiuni post-procedură automate','După o extracție sau o intervenție chirurgicală, agentul poate suna pacientul a doua zi să verifice cum se simte și să reamintească regulile — fără clătit 24h, fără fumat, când să ia antibioticul, când să revină pentru control.'],
        ['🌍','Multilingv: română și engleză pentru pacienți expați','Răspunde fluent în română și în engleză, ideal pentru cabinete din București, Cluj sau Timișoara cu pacienți internaționali. Fără accent robotic, fără traduceri stângace — detectează automat limba pacientului.'],
      ] as $b)
        <div class="niche-card rounded-3xl p-7 bg-cream border border-line fade-up">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center text-2xl shrink-0 transition-colors duration-500">{{ $b[0] }}</div>
            <div class="flex-1">
              <h3 class="display text-xl font-semibold mb-2">{{ $b[1] }}</h3>
              <p class="text-sm text-muted leading-relaxed">{{ $b[2] }}</p>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- STATS BIG -->
<section class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ impact măsurabil</div>
      <h2 class="display text-4xl md:text-5xl font-medium leading-tight">Cifrele clinicilor<br>care folosesc <em class="italic accent-text transition-colors duration-500">deja</em> Sambla.</h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center">
        <div class="display text-[6rem] md:text-[7rem] stat-num leading-none font-medium accent-text transition-colors duration-500">94<span class="text-ink">%</span></div>
        <div class="mt-2 text-sm font-semibold">rată preluare apeluri</div>
        <div class="mono text-[10px] text-muted mt-1">vs 58% recepție umană</div>
      </div>
      <div class="fade-up rounded-3xl p-8 bg-ink text-cream text-center" style="transition-delay:.1s;">
        <div class="display text-[6rem] md:text-[7rem] stat-num leading-none font-medium" style="color:#F2E59A;">−50<span class="text-ink">%</span></div>
        <div class="mt-2 text-sm font-semibold">reducere no-show</div>
        <div class="mono text-[10px] mt-1" style="color:#A8A29E;">de la 22% la 9% mediu</div>
      </div>
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center" style="transition-delay:.2s;">
        <div class="display text-[6rem] md:text-[7rem] stat-num leading-none font-medium accent-text transition-colors duration-500">3h</div>
        <div class="mt-2 text-sm font-semibold">eliberate / zi</div>
        <div class="mono text-[10px] text-muted mt-1">recepție eliberată de rutină</div>
      </div>
      <div class="fade-up rounded-3xl p-8 text-center transition-colors duration-500" style="transition-delay:.3s; background: linear-gradient(135deg, var(--accent-soft) 0%, #FEC796 100%);">
        <div class="display text-[6rem] md:text-[7rem] leading-none font-medium">🦷</div>
        <div class="mt-2 text-sm font-semibold">47 clinici</div>
        <div class="mono text-[10px] text-muted mt-1">folosesc deja Sambla</div>
      </div>
    </div>
  </div>
</section>

<!-- DEMO 3 SCENARII -->
<section id="demo" class="py-24 bg-paper border-y border-line">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-2xl mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ conversații reale</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Așa vorbește cu <em class="italic accent-text transition-colors duration-500">pacienții tăi</em>.</h2>
      <p class="text-lg text-muted">Trei scenarii din cabinete stomatologice reale — exact tipul de răspunsuri pe care le dă agentul AI.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      @foreach([
        ['title'=>'Programare nouă','subtitle'=>'Pacient nou caută consultație','icon'=>'📅','msgs'=>[
          ['user'=>true,'text'=>'Bună ziua, aș vrea o programare pentru consultație.'],
          ['user'=>false,'text'=>'Bună ziua! Am loc liber marți 22 la 10:00 cu Dr. Ionescu sau joi 24 la 14:00. Vă convine una?'],
          ['user'=>true,'text'=>'Marți la 10 e perfect. Pe Maria Popescu.'],
          ['user'=>false,'text'=>'✓ Rezervat. SMS cu detaliile și reamintire cu 24h înainte.'],
        ]],
        ['title'=>'Întrebare de preț','subtitle'=>'Răspuns din lista de tarife','icon'=>'💰','msgs'=>[
          ['user'=>true,'text'=>'Cât costă un detartraj?'],
          ['user'=>false,'text'=>'180 lei — include ultrasonic + aeropolishing. Durează ~45 min.'],
          ['user'=>true,'text'=>'Și plombă compozit?'],
          ['user'=>false,'text'=>'De la 250 lei (canin) până la 380 lei (molar). Rezerv consultație pentru estimare exactă?'],
        ]],
        ['title'=>'Urgență dentară','subtitle'=>'Detectează + escaladează','icon'=>'🚨','msgs'=>[
          ['user'=>true,'text'=>'Mă doare rău de tot o măsea, nu mai pot.'],
          ['user'=>false,'text'=>'Înțeleg, urgențele au prioritate. Aveți loc azi la 16:30 cu Dr. Marin. Puteți ajunge?'],
          ['user'=>true,'text'=>'Da, ajung.'],
          ['user'=>false,'text'=>'✓ Notat 16:30. Vă rog să nu mâncați cu 2h înainte. Aduceți buletinul.'],
        ]],
      ] as $scenario)
        <div class="niche-card rounded-3xl overflow-hidden bg-cream border border-line fade-up">
          <div class="p-6 pb-4 border-b border-line accent-soft-bg transition-colors duration-500">
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ $scenario['icon'] }}</span>
              <div class="flex-1">
                <div class="font-semibold text-sm">{{ $scenario['title'] }}</div>
                <div class="text-xs text-muted">{{ $scenario['subtitle'] }}</div>
              </div>
            </div>
          </div>
          <div class="p-5 space-y-2.5 min-h-[260px]">
            @foreach($scenario['msgs'] as $m)
              @if($m['user'])
                <div class="flex justify-end">
                  <div class="max-w-[85%] px-3.5 py-2 rounded-2xl rounded-br-sm text-[13px] leading-relaxed text-white accent-bg transition-colors duration-500">{{ $m['text'] }}</div>
                </div>
              @else
                <div class="flex">
                  <div class="max-w-[85%] px-3.5 py-2 rounded-2xl rounded-bl-sm text-[13px] leading-relaxed bg-sand text-ink">{{ $m['text'] }}</div>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- TESTIMONIAL MONUMENT -->
<section class="py-24 grain relative">
  <div class="max-w-6xl mx-auto px-6">
    <div class="rounded-[2.5rem] bg-ink text-cream p-10 md:p-16 overflow-hidden relative grid md:grid-cols-12 gap-10 items-center fade-up">
      <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl transition-colors duration-500" style="background: radial-gradient(circle, color-mix(in srgb, var(--accent) 35%, transparent) 0%, transparent 70%);"></div>

      <div class="md:col-span-8 relative">
        <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6 transition-colors duration-500" style="color:#F2E59A;">◇ de la clinicile care folosesc sambla</div>
        <p class="display text-3xl md:text-4xl leading-[1.15] font-normal mb-8">
          „Înainte pierdeam 6–7 pacienți pe săptămână după program. Acum
          <span class="italic accent-text transition-colors duration-500">fiecare apel e preluat</span>
          — și reminder-ele au scos no-show-ul de la 22% la 9%. Pe 3 medici, înseamnă ~18.000 lei în plus pe lună."
        </p>
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold transition-colors duration-500" style="background:var(--accent); color:white;">DI</div>
          <div>
            <div class="font-semibold">Dr. Daniela Ionescu</div>
            <div class="text-sm" style="color:#A8A29E;">Proprietar Clinică Dental Pro · Cluj-Napoca</div>
          </div>
        </div>
      </div>

      <div class="md:col-span-4 relative">
        <div class="rounded-2xl p-5 space-y-4" style="background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);">
          <div>
            <div class="text-xs mb-1" style="color:#A8A29E;">No-show / săpt.</div>
            <div class="flex items-baseline gap-2">
              <span class="display text-3xl line-through" style="color:#78716C;">22%</span>
              <span class="display text-4xl accent-text font-semibold transition-colors duration-500">9%</span>
            </div>
          </div>
          <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
          <div>
            <div class="text-xs mb-1" style="color:#A8A29E;">Apeluri preluate</div>
            <div class="flex items-baseline gap-2">
              <span class="display text-3xl line-through" style="color:#78716C;">58%</span>
              <span class="display text-4xl font-semibold" style="color:#F2E59A;">94%</span>
            </div>
          </div>
          <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
          <div>
            <div class="text-xs mb-1" style="color:#A8A29E;">Venit suplimentar / lună</div>
            <div class="display text-4xl font-semibold accent-text transition-colors duration-500">+18k</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- INTEGRARI -->
<section id="integrari" class="py-24 bg-paper border-y border-line">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-2xl mb-12 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ integrări pentru stomatologie</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Se conectează la <em class="italic">tool-urile tale</em>.</h2>
      <p class="text-lg text-muted">Fără export-import manual. Agentul AI citește direct din sistemele pe care deja le folosești zilnic.</p>
    </div>

    <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      @foreach([
        ['📅','Google Calendar','Sync două sensuri · pe medic'],
        ['📆','Calendly','Rezervări automate'],
        ['💬','WhatsApp Business','Confirmări + reamintiri'],
        ['📱','SMS (Twilio/Vonage)','Notificări pacienți'],
        ['💳','Stripe','Avans rezervare'],
        ['🏥','DentalCRM','Date pacienți'],
        ['📧','Mailchimp','Campanii recall'],
        ['⚡','Zapier / API','Orice alt sistem'],
      ] as $int)
        <div class="niche-card rounded-2xl p-5 bg-cream border border-line flex items-center gap-3">
          <div class="text-2xl">{{ $int[0] }}</div>
          <div class="flex-1">
            <div class="font-semibold text-sm">{{ $int[1] }}</div>
            <div class="text-xs text-muted">{{ $int[2] }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- CTA STRIP NICHE-COLORED -->
<section class="py-20 transition-colors duration-500" style="background: var(--accent);">
  <div class="max-w-5xl mx-auto px-6 text-center fade-up" style="color: white;">
    <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6 opacity-80">◇ 7 zile gratuit</div>
    <h2 class="display text-5xl md:text-6xl font-medium leading-[1.02] mb-6">
      Instalează Sambla pe numărul cabinetului.<br>
      <span class="italic opacity-90">Primele programări vin săptămâna asta.</span>
    </h2>
    <div class="flex flex-wrap justify-center gap-3 mt-8">
      <a href="/register" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full font-semibold bg-white text-ink hover:bg-cream transition">
        Începe gratuit
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
      <a href="#demo-form" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full font-semibold border-2 border-white/80 hover:bg-white transition" style="color:white;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='white'">
        Programează un demo
      </a>
    </div>
  </div>
</section>

<!-- PRICING -->
<section id="preturi" class="py-24">
  <div class="max-w-5xl mx-auto px-6">
    <div class="max-w-xl mx-auto text-center mb-14 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ prețuri pentru clinici</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5"><em class="italic accent-text transition-colors duration-500">Un plan simplu.</em><br>Anulezi oricând.</h2>
      <p class="text-muted">O programare în plus pe lună acoperă abonamentul întreg.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <div class="fade-up rounded-3xl p-8 bg-paper border border-line">
        <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Clinic Starter</div>
        <div class="flex items-baseline gap-1 mb-4"><span class="display text-6xl font-medium">29</span><span class="text-muted">lei / lună</span></div>
        <p class="text-sm text-muted mb-5 pb-5 border-b border-line">Chat pe site + preluare întrebări rutină.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>Chat widget pe site</li>
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>500 conversații/lună</li>
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>Google Calendar sync</li>
          <li class="flex gap-2 text-muted"><span>—</span>Agent vocal</li>
          <li class="flex gap-2 text-muted"><span>—</span>WhatsApp Business</li>
        </ul>
        <a href="/register" class="btn-outline w-full justify-center">Începe gratuit</a>
      </div>

      <div class="fade-up rounded-3xl p-8 bg-ink text-cream relative" style="transition-delay:.1s;">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip accent-bg text-[10px] font-semibold text-white transition-colors duration-500">Recomandat pentru clinici</div>
        <div class="mono text-xs uppercase tracking-wider mb-3 transition-colors duration-500" style="color:var(--accent-soft);">Clinic Pro</div>
        <div class="flex items-baseline gap-1 mb-4"><span class="display text-6xl font-medium">128</span><span style="color:#A8A29E;">lei / lună</span></div>
        <p class="text-sm mb-5 pb-5 border-b" style="color:#D7D3CA; border-color: rgba(255,255,255,.1);">Chat + WhatsApp + reamintiri SMS.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2"><span class="transition-colors duration-500" style="color:var(--accent-soft);">✓</span>2 agenți · 2.500 conv./lună</li>
          <li class="flex gap-2"><span class="transition-colors duration-500" style="color:var(--accent-soft);">✓</span>WhatsApp Business integrat</li>
          <li class="flex gap-2"><span class="transition-colors duration-500" style="color:var(--accent-soft);">✓</span>SMS reamintiri (200/lună incluse)</li>
          <li class="flex gap-2"><span class="transition-colors duration-500" style="color:var(--accent-soft);">✓</span>Triaj urgențe automat</li>
          <li class="flex gap-2 text-sm" style="color:#A8A29E;"><span>—</span>Agent vocal (+49 lei)</li>
        </ul>
        <a href="/register" class="btn-primary w-full justify-center">Alege Pro →</a>
      </div>

      <div class="fade-up rounded-3xl p-8 bg-paper border border-line" style="transition-delay:.2s;">
        <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Clinic Voice</div>
        <div class="flex items-baseline gap-1 mb-4"><span class="display text-6xl font-medium">248</span><span class="text-muted">lei / lună</span></div>
        <p class="text-sm text-muted mb-5 pb-5 border-b border-line">Totul din Pro + agent vocal 24/7.</p>
        <ul class="space-y-2.5 text-sm mb-8">
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>Tot din Clinic Pro</li>
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>Agent vocal 24/7</li>
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>Număr românesc dedicat</li>
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>200 min voce incluse</li>
          <li class="flex gap-2"><span class="accent-text transition-colors duration-500">✓</span>Suport prioritar</li>
        </ul>
        <a href="/register" class="btn-outline w-full justify-center">Alege Voice</a>
      </div>
    </div>

    <p class="text-center mt-6 text-sm text-muted">7 zile trial pe toate planurile · Anulezi oricând · 30% reducere clinici non-profit</p>
  </div>
</section>

<!-- HAI SA VORBIM (demo form) -->
<section id="demo-form" class="py-24 bg-paper border-y border-line">
  <div class="max-w-5xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-start">
    <div class="fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ hai să vorbim</div>
      <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">Vrei un demo <em class="italic accent-text transition-colors duration-500">personalizat</em> pentru cabinetul tău?</h2>
      <p class="text-lg text-muted leading-relaxed mb-8">Trimite-ne link-ul site-ului clinicii sau al paginii de Facebook — primești un demo antrenat pe cabinetul tău, nu un demo generic.</p>

      <div class="space-y-4">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl accent-soft-bg accent-text flex items-center justify-center shrink-0 transition-colors duration-500">1</div>
          <div>
            <div class="font-semibold text-sm">Trimitem demo în 24h</div>
            <div class="text-sm text-muted">Antrenat pe serviciile și tarifele tale reale.</div>
          </div>
        </div>
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl accent-soft-bg accent-text flex items-center justify-center shrink-0 transition-colors duration-500">2</div>
          <div>
            <div class="font-semibold text-sm">15 minute video-call</div>
            <div class="text-sm text-muted">Discutăm integrare cu calendar și reguli urgență.</div>
          </div>
        </div>
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl accent-soft-bg accent-text flex items-center justify-center shrink-0 transition-colors duration-500">3</div>
          <div>
            <div class="font-semibold text-sm">Test live 7 zile</div>
            <div class="text-sm text-muted">Pe numărul tău, cu pacienții tăi. Anulezi dacă nu-ți place.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="fade-up" style="transition-delay:.1s;">
      <form class="rounded-3xl bg-cream border border-line p-8 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Nume <span class="accent-text transition-colors duration-500">*</span></label>
            <input type="text" class="form-input" placeholder="Dr. Maria Popescu" required>
          </div>
          <div>
            <label class="form-label">Telefon <span class="accent-text transition-colors duration-500">*</span></label>
            <input type="tel" class="form-input" placeholder="07xx xxx xxx" required>
          </div>
        </div>

        <div>
          <label class="form-label">Email <span class="accent-text transition-colors duration-500">*</span></label>
          <input type="email" class="form-input" placeholder="cabinet@clinica.ro" required>
        </div>

        <div>
          <label class="form-label">Site sau pagina Facebook a cabinetului</label>
          <input type="url" class="form-input" placeholder="https://clinica-mea.ro">
        </div>

        <div>
          <label class="form-label">Mesaj (opțional)</label>
          <textarea rows="3" class="form-input resize-none" placeholder="Câteva cuvinte despre cabinet — număr medici, oraș, ce vrei să rezolvi în primul rând..."></textarea>
        </div>

        <button type="submit" class="btn-primary w-full justify-center text-base" style="padding: 14px 24px;">
          Vreau demo personalizat
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>

        <p class="text-xs text-muted text-center">Prin trimitere ești de acord cu <a href="/confidentialitate" class="underline accent-text transition-colors duration-500">politica de confidențialitate</a>. Nu primești spam. Promitem.</p>
      </form>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-24">
  <div class="max-w-3xl mx-auto px-6">
    <div class="text-center mb-12 fade-up">
      <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ întrebări frecvente</div>
      <h2 class="display text-5xl font-medium"><em class="italic accent-text transition-colors duration-500">Întrebări</em> pe care ni le-au pus dentiștii.</h2>
    </div>
    <div class="space-y-3 fade-up">
      @foreach([
        ['Înlocuiește agentul AI recepția?','Nu, o ajută. Recepționera ta rămâne pentru pacienții din cabinet, pentru cazurile sensibile și pentru relația umană directă. Sambla preia volumul mare de apeluri repetitive — programări, întrebări de tarif, confirmări — astfel încât echipa ta să aibă timp să se ocupe de oamenii care sunt deja acolo.'],
        ['Cum funcționează cu programările pe Google Calendar?','Conectăm agentul la calendarele Google ale medicilor din cabinet. El vede în timp real ce sloturi sunt libere, ține cont de durata fiecărei proceduri (o consultație nu durează cât o endodonție) și creează evenimentul direct în calendar, cu numele și telefonul pacientului. Modificările și anulările se sincronizează automat.'],
        ['Răspunde și pacienților vorbitori de engleză?','Da. Agentul detectează automat limba în care vorbește pacientul și răspunde fluent în română sau engleză, fără accent robotic. Este ideal pentru cabinetele din marile orașe, unde aveți pacienți expați sau turiști care caută servicii dentare.'],
        ['Cum se ocupă de urgențele dentare?','Agentul este antrenat să recunoască semnele unei urgențe — durere acută severă, traumă dentară, hemoragie, abces. În aceste cazuri oferă instrucțiuni de prim ajutor, nu programează la rând și sună imediat medicul de gardă pe care l-ai configurat. Tu decizi cine este de gardă și în ce intervale.'],
        ['Este conform GDPR pentru date medicale?','Da. Toate conversațiile, înregistrările și datele pacienților sunt stocate pe servere din România, criptate, cu acces restricționat. Avem contract de prelucrare a datelor (DPA) gata de semnat, iar pacienții sunt informați la începutul apelului că discuția poate fi procesată de un asistent AI.'],
        ['Cât durează setup-ul pentru cabinetul meu?','În medie, între 3 și 5 zile lucrătoare. Ne trimiți lista ta de servicii și tarife, ne dai acces la Google Calendar al medicilor, stabilim regulile pentru urgențe și mesajul de întâmpinare. Facem împreună câteva apeluri de test, ajustăm tonul și expresiile, iar apoi îl pornim pe numărul cabinetului.'],
      ] as $f)
        <details class="rounded-2xl bg-paper border border-line overflow-hidden">
          <summary class="px-6 py-5 flex items-center justify-between cursor-pointer list-none font-medium hover:bg-cream transition">
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
  <div class="absolute inset-0 opacity-50 pointer-events-none transition-colors duration-500" style="background: radial-gradient(ellipse 60% 50% at 50% 50%, color-mix(in srgb, var(--accent) 18%, transparent) 0%, transparent 60%);"></div>
  <div class="max-w-4xl mx-auto px-6 text-center relative fade-up">
    <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-6">◇ gata să automatizezi cabinetul?</div>
    <h2 class="display text-5xl md:text-7xl font-medium leading-[1.02] mb-8">
      Nu mai pierde pacienți<br>
      <em class="italic accent-text transition-colors duration-500">după program.</em>
    </h2>
    <p class="text-xl text-muted mb-10 max-w-xl mx-auto leading-relaxed">Configurezi în 10 minute. Primele conversații reale din prima zi. Fără card, fără obligații.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="/register" class="btn-primary text-base" style="padding: 16px 28px;">
        Începe gratuit acum
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
      <a href="#demo-form" class="btn-outline text-base" style="padding: 16px 28px;">Vreau demo personalizat</a>
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
        </ul>
      </div>
      <div>
        <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Companie</h4>
        <ul class="space-y-2 text-sm text-muted">
          <li><a href="/despre" class="hover:text-ink">Despre</a></li>
          <li><a href="/de-ce-sambla" class="hover:text-ink">De ce Sambla</a></li>
          <li><a href="/contact" class="hover:text-ink">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Legal</h4>
        <ul class="space-y-2 text-sm text-muted">
          <li><a href="/termeni" class="hover:text-ink">Termeni</a></li>
          <li><a href="/confidentialitate" class="hover:text-ink">Confidențialitate</a></li>
          <li><a href="/cookie-uri" class="hover:text-ink">Cookie-uri</a></li>
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
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
}, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

// Niche switcher — changes paleta only
document.querySelectorAll('.niche-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    const n = btn.dataset.switch;
    document.documentElement.setAttribute('data-niche', n);
    document.querySelectorAll('.niche-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  });
});

// Hero chat: dentistry scenario
(function() {
  const messages = [
    { user: true,  text: 'Bună ziua. Aveți loc pentru detartraj săptămâna viitoare?' },
    { user: false, text: 'Bună ziua! Am 3 disponibilități: marți 22, 10:00 · miercuri 23, 16:30 · joi 24, 14:00. Vă convine una?' },
    { user: true,  text: 'Marți la 10 e perfect. Pe Maria Popescu.' },
    { user: false, text: 'Am notat ✓ Detartraj + consultație: 180 lei. Vă trimit SMS cu detaliile și reamintire cu 24h înainte.' },
    { user: true,  text: 'Pot plăti cu cardul la clinică?' },
    { user: false, text: 'Da, acceptăm card. Dacă doriți, putem confirma rezervarea cu avans 50 lei — se scade din factura finală.' },
  ];
  const chatEl = document.getElementById('heroChat');
  const inner = document.getElementById('heroChatInner');
  const typing = document.getElementById('heroTyping');
  if (!chatEl) return;

  let timers = [];
  const t = (fn, d) => { const id = setTimeout(fn, d); timers.push(id); return id; };

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
      t(() => { typing.classList.add('hidden'); typing.classList.remove('flex'); addBubble(msg, onDone); }, 800 + Math.random() * 400);
    } else addBubble(msg, onDone);
  }
  function play() {
    timers.forEach(clearTimeout); timers = [];
    inner.innerHTML = '';
    let i = 0;
    const next = () => {
      if (i >= messages.length) { t(play, 5000); return; }
      const m = messages[i];
      t(() => addMessage(m, () => { i++; next(); }), i === 0 ? 500 : (m.user ? 700 : 200));
    };
    next();
  }
  t(play, 400);
})();
</script>

</body>
</html>
