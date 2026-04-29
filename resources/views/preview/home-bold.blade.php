<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>V3 Bold statement — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
      colors: {
        bone: '#FAFAF7',
        ink: '#0E0E0C',
        brand: { DEFAULT: '#DC2626', dark: '#991B1B' },
        sun: '#FCD34D'
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #FAFAF7; color: #0E0E0C; -webkit-font-smoothing: antialiased; }
  .kicker { font-family: 'JetBrains Mono', monospace; letter-spacing: 0.05em; }
  h1, h2, .display { letter-spacing: -0.035em; line-height: 0.95; }
  .motif {
    background-image: repeating-linear-gradient(45deg, rgba(220,38,38,0.04) 0 2px, transparent 2px 16px);
  }
  .scroll-ticker { animation: ticker 40s linear infinite; }
  @keyframes ticker { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(180deg); }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-white text-xs px-4 py-1.5 text-center">
  Preview V3 · Bold statement · <a href="/preview" class="underline">înapoi la lista</a>
</div>

<!-- Nav -->
<nav class="bg-bone border-b-2 border-ink sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-10">
      <a href="#" class="flex items-center gap-2.5">
        <div class="w-8 h-8 bg-brand flex items-center justify-center">
          <span class="text-white font-black text-xl leading-none">S</span>
        </div>
        <span class="font-black text-xl tracking-tight">SAMBLA</span>
      </a>
      <div class="hidden lg:flex items-center gap-7 text-sm font-medium">
        <a href="#produse" class="hover:text-brand transition">Produse</a>
        <a href="#manifesto" class="hover:text-brand transition">Manifesto</a>
        <a href="#pipeline" class="hover:text-brand transition">Cum gândește</a>
        <a href="#industrii" class="hover:text-brand transition">Industrii</a>
        <a href="#preturi" class="hover:text-brand transition">Prețuri</a>
      </div>
    </div>
    <div class="flex items-center gap-2 text-sm">
      <a href="/login" class="hidden sm:inline px-3 py-2 font-medium hover:text-brand">Autentificare</a>
      <a href="/register" class="bg-ink text-white px-4 py-2.5 font-semibold hover:bg-brand transition">Începe gratuit</a>
    </div>
  </div>
</nav>

<!-- Hero statement -->
<section class="relative overflow-hidden">
  <div class="absolute inset-0 motif opacity-70 pointer-events-none"></div>
  <div class="max-w-7xl mx-auto px-6 pt-24 pb-20 relative">
    <div class="kicker text-xs uppercase text-brand mb-6 flex items-center gap-2">
      <span class="w-2 h-2 bg-brand animate-pulse"></span>
      <span>/// GPT-4o Realtime · voce nativă română · live</span>
    </div>

    <h1 class="display text-[clamp(3.5rem,10vw,9rem)] font-black mb-8 text-ink">
      Nu <span class="line-through text-stone-300">inventează.</span><br>
      Nu <span class="line-through text-stone-300">ghicește.</span><br>
      <span class="text-brand">Știe.</span>
    </h1>

    <div class="max-w-2xl mb-12">
      <p class="text-xl md:text-2xl leading-snug font-medium text-stone-800">
        Angajatul AI care răspunde clienților pe chat și telefon, 24/7, din documentele, produsele și politicile tale reale.
        <span class="bg-sun px-1">Construit în România, pentru afaceri românești.</span>
      </p>
    </div>

    <div class="flex flex-wrap gap-3 mb-16">
      <a href="/register" class="bg-ink text-white px-6 py-3.5 font-bold text-base hover:bg-brand transition inline-flex items-center gap-2">
        Începe gratuit
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"/></svg>
      </a>
      <a href="#demo" class="bg-white border-2 border-ink px-6 py-3.5 font-bold text-base hover:bg-ink hover:text-white transition inline-flex items-center gap-2">
        Vezi în acțiune
      </a>
    </div>

    <!-- Credential grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-ink border-2 border-ink">
      <div class="bg-bone p-6"><div class="kicker text-[10px] uppercase text-stone-500 mb-2">setup</div><div class="text-4xl font-black">10min</div></div>
      <div class="bg-bone p-6"><div class="kicker text-[10px] uppercase text-stone-500 mb-2">latență</div><div class="text-4xl font-black">&lt;2s</div></div>
      <div class="bg-bone p-6"><div class="kicker text-[10px] uppercase text-stone-500 mb-2">verificări</div><div class="text-4xl font-black">10<span class="text-brand">×</span></div></div>
      <div class="bg-brand text-white p-6"><div class="kicker text-[10px] uppercase opacity-80 mb-2">disponibil</div><div class="text-4xl font-black">24/7</div></div>
    </div>
  </div>
</section>

<!-- Ticker -->
<section class="bg-ink text-white py-5 overflow-hidden border-y-2 border-ink">
  <div class="scroll-ticker whitespace-nowrap flex gap-12 text-2xl font-black uppercase tracking-tight">
    @for($i=0; $i<3; $i++)
      <span>HOSTING RO</span><span class="text-brand">★</span>
      <span>GDPR NATIV</span><span class="text-brand">★</span>
      <span>VOCE ROMÂNĂ</span><span class="text-brand">★</span>
      <span>ZERO HALUCINAȚII IMPUSE</span><span class="text-brand">★</span>
      <span>ANTI-GENERIC</span><span class="text-brand">★</span>
      <span>WOOCOMMERCE NATIV</span><span class="text-brand">★</span>
    @endfor
  </div>
</section>

<!-- Manifesto -->
<section id="manifesto" class="py-24 border-b-2 border-ink">
  <div class="max-w-6xl mx-auto px-6">
    <div class="kicker text-xs uppercase text-brand mb-6">/// manifesto</div>
    <h2 class="display text-5xl md:text-7xl font-black mb-16 max-w-4xl">Trei convingeri care ne definesc.</h2>

    <div class="grid md:grid-cols-3 gap-px bg-ink border-2 border-ink">
      <div class="bg-white p-8">
        <div class="kicker text-6xl font-black text-brand mb-4">01</div>
        <h3 class="text-2xl font-black mb-3">Orice LLM poate halucina.</h3>
        <p class="text-stone-700 leading-relaxed">E o proprietate a tehnologiei. Oricine promite „zero halucinație" e necinstit. Noi minimizăm prin inginerie și eșuăm elegant când nu putem garanta.</p>
      </div>
      <div class="bg-white p-8">
        <div class="kicker text-6xl font-black text-brand mb-4">02</div>
        <h3 class="text-2xl font-black mb-3">Datele tale rămân ale tale.</h3>
        <p class="text-stone-700 leading-relaxed">Servere fizice în România. Zero transfer în afara UE. GDPR by default, izolare per cont. Nu un addon, un principiu de arhitectură.</p>
      </div>
      <div class="bg-white p-8">
        <div class="kicker text-6xl font-black text-brand mb-4">03</div>
        <h3 class="text-2xl font-black mb-3">Voce + Chat, același creier.</h3>
        <p class="text-stone-700 leading-relaxed">Clientul sună sau scrie — același răspuns, aceleași politici, aceleași produse. Un singur sistem, toate canalele.</p>
      </div>
    </div>
  </div>
</section>

<!-- Products split -->
<section id="produse" class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="mb-14 grid md:grid-cols-2 gap-8 items-end">
      <div>
        <div class="kicker text-xs uppercase text-brand mb-4">/// produse</div>
        <h2 class="display text-5xl md:text-6xl font-black">O platformă.<br>Toate canalele.</h2>
      </div>
      <p class="text-lg text-stone-600 leading-relaxed max-w-lg">Aceeași bază de cunoștințe pe chat, telefon, WhatsApp, Facebook, Instagram, WooCommerce.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-px bg-ink border-2 border-ink">
      <div class="bg-white p-10">
        <div class="flex items-center gap-3 mb-6">
          <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
          <span class="kicker text-xs uppercase">chat · live</span>
        </div>
        <h3 class="text-4xl font-black mb-5">Agent AI pe site</h3>
        <p class="text-stone-700 leading-relaxed mb-8 text-lg">Widget premium. O singură linie de cod. Carduri produse, link preview, asistență proactivă, dark mode. Sandboxed iframe — zero risc XSS.</p>
        <div class="bg-stone-100 border-2 border-ink p-5 rounded-none">
          <div class="kicker text-xs mb-3 text-stone-500 uppercase">Exemplu real · clinică dentară</div>
          <div class="bg-white border border-stone-300 p-4 text-sm">
            <div class="mb-2 text-stone-600">→ „Aveți loc liber marți?"</div>
            <div class="font-medium">← Da, marți 22 la ora 10:00 la Dr. Ionescu. Vă rezerv?</div>
            <div class="mt-2 text-[10px] text-emerald-700 font-mono">sursă: program-doctori.pdf · 1.3s</div>
          </div>
        </div>
      </div>

      <div class="bg-ink text-white p-10 relative overflow-hidden">
        <div class="absolute top-4 right-4 kicker text-[10px] uppercase text-sun">BETA</div>
        <div class="flex items-center gap-3 mb-6">
          <span class="w-3 h-3 rounded-full bg-sun animate-pulse"></span>
          <span class="kicker text-xs uppercase">telefon · live</span>
        </div>
        <h3 class="text-4xl font-black mb-5">Agent AI vocal</h3>
        <p class="text-stone-300 leading-relaxed mb-8 text-lg">Voce naturală română. OpenAI Realtime direct + Twilio. Sub 800ms latență. Barge-in natural. Clonare voce ElevenLabs opțional — sună ca echipa ta, nu ca un robot.</p>
        <div class="bg-stone-900 border-2 border-sun p-5">
          <div class="kicker text-xs mb-3 text-sun uppercase">live · apel entrant</div>
          <div class="flex items-center gap-4 mb-3">
            <div class="w-10 h-10 rounded-full bg-sun text-ink font-black flex items-center justify-center">AI</div>
            <div class="flex-1">
              <div class="h-2 bg-stone-700 overflow-hidden"><div class="h-full bg-sun" style="width:72%"></div></div>
              <div class="text-[11px] mt-1.5 text-stone-400 font-mono">„Bună ziua. Vă ajut cu o programare?"</div>
            </div>
          </div>
          <div class="flex gap-2 text-[10px] font-mono">
            <span class="px-2 py-0.5 bg-emerald-900 text-emerald-300">sentiment · +</span>
            <span class="px-2 py-0.5 bg-blue-900 text-blue-300">intent · rezervare</span>
            <span class="px-2 py-0.5 bg-stone-800 text-stone-300">01:23</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pipeline -->
<section id="pipeline" class="py-24 border-y-2 border-ink bg-bone">
  <div class="max-w-7xl mx-auto px-6">
    <div class="mb-14">
      <div class="kicker text-xs uppercase text-brand mb-4">/// arhitectură</div>
      <h2 class="display text-5xl md:text-6xl font-black max-w-4xl">Nu un chatbot. <span class="text-brand">Un pipeline cu 4 etape.</span></h2>
      <p class="text-lg text-stone-700 max-w-2xl mt-6">Fiecare mesaj trece prin 4 etape — analizează, caută, decide, răspunde. În sub 2 secunde.</p>
    </div>

    <div class="grid md:grid-cols-4 gap-px bg-ink border-2 border-ink">
      <div class="bg-white p-7">
        <div class="kicker text-[10px] text-stone-500 uppercase mb-3">Etapa 01</div>
        <div class="kicker text-sm font-black mb-4 text-brand">QUERY INTELLIGENCE</div>
        <p class="text-stone-700 text-sm leading-relaxed mb-4">Detectează intenția: tranzacțional · informațional · reclamație · comparativ · vag · salut.</p>
        <div class="flex flex-wrap gap-1.5">
          <span class="text-[10px] px-1.5 py-0.5 bg-stone-100 font-mono">🛒 tranz</span>
          <span class="text-[10px] px-1.5 py-0.5 bg-stone-100 font-mono">😤 recl</span>
          <span class="text-[10px] px-1.5 py-0.5 bg-stone-100 font-mono">⚖️ comp</span>
        </div>
      </div>
      <div class="bg-white p-7">
        <div class="kicker text-[10px] text-stone-500 uppercase mb-3">Etapa 02</div>
        <div class="kicker text-sm font-black mb-4 text-brand">HYBRID RAG</div>
        <p class="text-stone-700 text-sm leading-relaxed mb-4">Vectorial + full-text paralel. AI reranker păstrează 8 chunks din 20. pgvector RO.</p>
        <div class="font-mono text-[11px] bg-stone-900 text-emerald-400 px-3 py-2 leading-tight">
          8 / 20 chunks → rerank
        </div>
      </div>
      <div class="bg-white p-7">
        <div class="kicker text-[10px] text-stone-500 uppercase mb-3">Etapa 03</div>
        <div class="kicker text-sm font-black mb-4 text-brand">VERIFICARE 10×</div>
        <p class="text-stone-700 text-sm leading-relaxed mb-4">Confidence scoring · citare sursă · detecție halucinație. Eșuează elegant când nu e sigur.</p>
        <div class="grid grid-cols-5 gap-0.5">
          @for($i=1;$i<=10;$i++)<div class="h-4 bg-brand"></div>@endfor
        </div>
      </div>
      <div class="bg-brand text-white p-7">
        <div class="kicker text-[10px] opacity-70 uppercase mb-3">Etapa 04</div>
        <div class="kicker text-sm font-black mb-4">RĂSPUNS ADAPTAT</div>
        <p class="text-sm leading-relaxed mb-4 opacity-90">Empatie la frustrare. Recomandare la interes. Scurt dacă vrea rapid. Brand voice mereu.</p>
        <div class="font-mono text-[11px] bg-black/30 px-3 py-2">
          tone · confidence · CTA
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats big -->
<section class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="kicker text-xs uppercase text-brand mb-4">/// cifre</div>
    <h2 class="display text-5xl md:text-7xl font-black mb-16 max-w-4xl">Arhitectura e în detalii.</h2>

    <div class="grid md:grid-cols-3 gap-px bg-ink border-2 border-ink">
      <div class="bg-white p-10">
        <div class="display text-7xl font-black mb-2">1536<span class="text-brand">d</span></div>
        <div class="text-lg font-medium">Vector embeddings</div>
        <p class="text-sm text-stone-600 mt-2">pe pgvector hostat în RO</p>
      </div>
      <div class="bg-white p-10">
        <div class="display text-7xl font-black mb-2">25<span class="text-brand">×</span></div>
        <div class="text-lg font-medium">Grupuri sinonime RO</div>
        <p class="text-sm text-stone-600 mt-2">optimizate pentru diacritice</p>
      </div>
      <div class="bg-white p-10">
        <div class="display text-7xl font-black mb-2">8<span class="text-brand">/</span>20</div>
        <div class="text-lg font-medium">Chunks relevante</div>
        <p class="text-sm text-stone-600 mt-2">reranked per query</p>
      </div>
      <div class="bg-ink text-white p-10">
        <div class="display text-7xl font-black mb-2 text-sun">94%</div>
        <div class="text-lg font-medium">Rată rezolvare</div>
        <p class="text-sm text-stone-400 mt-2">fără operator uman</p>
      </div>
      <div class="bg-ink text-white p-10">
        <div class="display text-7xl font-black mb-2 text-sun">&lt;800ms</div>
        <div class="text-lg font-medium">Latență voce</div>
        <p class="text-sm text-stone-400 mt-2">end-to-end apel</p>
      </div>
      <div class="bg-brand text-white p-10">
        <div class="display text-7xl font-black mb-2">100%</div>
        <div class="text-lg font-medium">Românesc</div>
        <p class="text-sm opacity-80 mt-2">hosting, echipă, date</p>
      </div>
    </div>
  </div>
</section>

<!-- Industries -->
<section id="industrii" class="py-24 bg-ink text-white">
  <div class="max-w-7xl mx-auto px-6">
    <div class="mb-14">
      <div class="kicker text-xs uppercase text-sun mb-4">/// industrii</div>
      <h2 class="display text-5xl md:text-6xl font-black max-w-4xl">Creat special <br>pentru <span class="text-brand">afacerea ta</span>.</h2>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-stone-700 border-2 border-stone-700">
      @foreach([
        ['🦷','Stomatologie'],['💆','Estetică'],['🏥','Clinici medicale'],['🛒','E-commerce'],
        ['🏠','Imobiliare'],['🔧','Service auto'],['⚖️','Avocatură'],['📚','Contabilitate'],
        ['🍽️','Restaurante'],['🏨','Pensiuni'],['💇','Saloane'],['🎓','Educație'],
      ] as $ind)
        <a href="#" class="bg-ink hover:bg-brand p-8 transition group">
          <div class="text-4xl mb-3">{{ $ind[0] }}</div>
          <div class="font-bold text-lg">{{ $ind[1] }}</div>
          <div class="kicker text-[10px] uppercase opacity-0 group-hover:opacity-100 transition mt-2">vezi detalii →</div>
        </a>
      @endforeach
    </div>
    <p class="mt-8 text-stone-400 text-sm">Și alte 50+ verticale. <a href="#" class="underline underline-offset-4 text-sun">Vezi toate industriile →</a></p>
  </div>
</section>

<!-- Testimonial bold -->
<section class="py-24 border-b-2 border-ink">
  <div class="max-w-5xl mx-auto px-6">
    <div class="grid md:grid-cols-12 gap-8 items-center">
      <div class="md:col-span-8">
        <p class="display text-4xl md:text-5xl font-black leading-[1.1] mb-6">
          „Acum <span class="text-brand">fiecare apel</span> e preluat. Veniturile au crescut cu <span class="bg-sun px-2">30%</span> în trei luni."
        </p>
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-ink text-white flex items-center justify-center font-black">AM</div>
          <div>
            <div class="font-bold">Ana Marinescu</div>
            <div class="text-sm text-stone-600">Proprietar clinică estetică, București</div>
          </div>
        </div>
      </div>
      <div class="md:col-span-4 bg-bone p-8 border-2 border-ink">
        <div class="kicker text-[10px] uppercase text-stone-500 mb-3">Before / After</div>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-stone-500 mb-1">Apeluri pierdute / săpt.</div>
            <div class="flex items-baseline gap-2"><span class="text-3xl font-black line-through text-stone-400">42</span><span class="text-3xl font-black text-brand">→ 3</span></div>
          </div>
          <div>
            <div class="text-xs text-stone-500 mb-1">Programări noi / lună</div>
            <div class="flex items-baseline gap-2"><span class="text-3xl font-black line-through text-stone-400">120</span><span class="text-3xl font-black text-brand">→ 188</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pricing -->
<section id="preturi" class="py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="mb-14">
      <div class="kicker text-xs uppercase text-brand mb-4">/// prețuri</div>
      <h2 class="display text-5xl md:text-6xl font-black">Simple. În lei.<br>Fără surprize.</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-px bg-ink border-2 border-ink">
      <div class="bg-white p-8">
        <div class="kicker text-xs uppercase mb-2 text-stone-500">Starter</div>
        <div class="flex items-baseline gap-1 mb-2"><span class="display text-6xl font-black">29</span><span class="text-stone-600">lei/lună</span></div>
        <p class="text-sm text-stone-600 mb-6 pb-6 border-b-2 border-ink">Agent AI simplu pe un site.</p>
        <ul class="space-y-2 text-sm mb-6">
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>1 agent · 500 conversații/lună</li>
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>Widget + 1 site</li>
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>KB nelimitată</li>
          <li class="flex gap-2 items-start text-stone-400"><span class="font-black">✕</span>WhatsApp / FB / IG</li>
          <li class="flex gap-2 items-start text-stone-400"><span class="font-black">✕</span>Voce AI</li>
        </ul>
        <a href="/register" class="block text-center py-3 font-bold border-2 border-ink hover:bg-ink hover:text-white transition">Începe gratuit</a>
      </div>

      <div class="bg-ink text-white p-8 relative">
        <div class="absolute top-0 right-0 bg-brand text-white kicker text-[10px] px-2 py-1 uppercase font-bold">Recomandat</div>
        <div class="kicker text-xs uppercase mb-2 text-sun">Professional</div>
        <div class="flex items-baseline gap-1 mb-2"><span class="display text-6xl font-black">79</span><span class="text-stone-400">lei/lună</span></div>
        <p class="text-sm text-stone-300 mb-6 pb-6 border-b-2 border-stone-700">Multi-canal + CRM.</p>
        <ul class="space-y-2 text-sm mb-6">
          <li class="flex gap-2 items-start"><span class="text-sun font-black">✓</span>3 agenți · 2.500 conversații/lună</li>
          <li class="flex gap-2 items-start"><span class="text-sun font-black">✓</span>WooCommerce + WhatsApp</li>
          <li class="flex gap-2 items-start"><span class="text-sun font-black">✓</span>Lead scoring + pipeline CRM</li>
          <li class="flex gap-2 items-start"><span class="text-sun font-black">✓</span>Analiză avansată</li>
          <li class="flex gap-2 items-start text-stone-500"><span class="font-black">✕</span>Voce AI (add-on +49 lei)</li>
        </ul>
        <a href="/register" class="block text-center py-3 font-bold bg-brand hover:bg-white hover:text-ink transition">Alege Professional →</a>
      </div>

      <div class="bg-white p-8">
        <div class="kicker text-xs uppercase mb-2 text-stone-500">Business</div>
        <div class="flex items-baseline gap-1 mb-2"><span class="display text-6xl font-black">199</span><span class="text-stone-600">lei/lună</span></div>
        <p class="text-sm text-stone-600 mb-6 pb-6 border-b-2 border-ink">Volum mare + toate canalele.</p>
        <ul class="space-y-2 text-sm mb-6">
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>10 agenți · 10.000 conversații/lună</li>
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>Toate canalele (FB, IG, WA)</li>
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>Voce AI disponibilă</li>
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>Suport prioritar</li>
          <li class="flex gap-2 items-start"><span class="text-brand font-black">✓</span>API + webhook</li>
        </ul>
        <a href="/register" class="block text-center py-3 font-bold border-2 border-ink hover:bg-ink hover:text-white transition">Alege Business</a>
      </div>
    </div>

    <p class="mt-6 text-sm text-stone-600">Voce AI: <strong>+49 lei/lună</strong> (base) sau <strong>+149 lei</strong> (voce clonată). Overage la 0,04 lei/mesaj · 0,30 lei/min voce. <span class="bg-sun px-1 font-bold">30% reducere ONG & școli.</span></p>
  </div>
</section>

<!-- Trust row -->
<section class="py-16 border-y-2 border-ink bg-bone">
  <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-3 gap-8">
    <div>
      <div class="kicker text-[10px] uppercase text-brand mb-3">🇷🇴 Hosting România</div>
      <h3 class="text-xl font-black mb-2">Datele rămân aici.</h3>
      <p class="text-sm text-stone-700">Servere fizice în RO. Zero transfer UE-extern. Audit GDPR direct servit.</p>
    </div>
    <div>
      <div class="kicker text-[10px] uppercase text-brand mb-3">⚡ GDPR by default</div>
      <h3 class="text-xl font-black mb-2">Nu un addon.</h3>
      <p class="text-sm text-stone-700">Izolare per cont, consimțământ explicit, log pe fiecare acțiune. Arhitectural, nu opțional.</p>
    </div>
    <div>
      <div class="kicker text-[10px] uppercase text-brand mb-3">💬 Suport RO</div>
      <h3 class="text-xl font-black mb-2">Vorbim aceeași limbă.</h3>
      <p class="text-sm text-stone-700">Echipă în București. Răspundem în ore, nu zile. Email, telefon, chat.</p>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-24">
  <div class="max-w-3xl mx-auto px-6">
    <div class="kicker text-xs uppercase text-brand mb-4">/// întrebări</div>
    <h2 class="display text-5xl font-black mb-12">Răspunsuri sincere.</h2>
    <div class="space-y-0 border-2 border-ink">
      @foreach([
        ['Ce se întâmplă când depășesc limita?', 'Se taxează automat la costul suplimentar al planului. Fără întreruperi, fără surprize.'],
        ['Pot combina chat + voce?', 'Da. Alegi un plan chat și adaugi addon vocal (+49 sau +149 lei). Aceeași KB, toate canalele.'],
        ['Cum garantați că AI-ul nu inventează?', 'Pipeline cu 10 straturi: confidence scoring, citare sursă obligatorie, detecție halucinație. Când nu știe, răspunde cinstit „nu am această informație".'],
        ['Unde sunt stocate datele?', 'Servere fizice în România. Zero transfer în afara UE. GDPR conform by default, izolare per cont.'],
        ['Funcționează cu WordPress / WooCommerce?', 'Plugin WordPress nativ + sync WooCommerce (produse, stocuri, comenzi). Plus embed pe orice site cu o linie de cod.'],
        ['Oferiți reducere pentru ONG?', 'Da. 30% permanent pentru ONG-uri, școli, universități, muzee.'],
      ] as $i => $faq)
        <details class="group bg-white {{ $i > 0 ? 'border-t-2 border-ink' : '' }}">
          <summary class="px-6 py-5 flex items-center justify-between cursor-pointer list-none font-bold text-lg hover:bg-bone transition">
            <span>{{ $faq[0] }}</span>
            <svg class="chev w-5 h-5 transition" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
          </summary>
          <div class="px-6 pb-5 text-stone-700 leading-relaxed">{{ $faq[1] }}</div>
        </details>
      @endforeach
    </div>
  </div>
</section>

<!-- Final CTA -->
<section class="bg-brand text-white py-28 border-t-2 border-ink">
  <div class="max-w-5xl mx-auto px-6 text-center">
    <div class="kicker text-xs uppercase opacity-80 mb-6">/// acum e momentul</div>
    <h2 class="display text-6xl md:text-8xl font-black mb-8 leading-[0.95]">
      Dă voce<br>afacerii tale.
    </h2>
    <p class="text-xl opacity-90 mb-10 max-w-xl mx-auto">Configurezi în 10 minute. Primele rezultate din prima zi. Fără card.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="/register" class="bg-ink text-white px-8 py-4 font-bold text-lg hover:bg-white hover:text-ink transition">Începe gratuit acum →</a>
      <a href="/contact" class="border-2 border-white px-8 py-4 font-bold text-lg hover:bg-white hover:text-brand transition">Programează un demo</a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-ink text-stone-300 py-16">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid md:grid-cols-5 gap-8 pb-10 border-b border-stone-800">
      <div class="md:col-span-2">
        <div class="flex items-center gap-2.5 mb-4">
          <div class="w-8 h-8 bg-brand flex items-center justify-center"><span class="text-white font-black text-xl leading-none">S</span></div>
          <span class="font-black text-xl text-white tracking-tight">SAMBLA</span>
        </div>
        <p class="max-w-sm leading-relaxed">Angajatul tău AI care știe totul despre afacerea ta. Voce naturală, chat inteligent, auto-îmbunătățire continuă.</p>
      </div>
      <div><h4 class="font-bold text-white mb-3 text-sm uppercase kicker">Produs</h4><ul class="space-y-2 text-sm"><li>Funcționalități</li><li>Prețuri</li><li>Demo live</li><li>API & docs</li></ul></div>
      <div><h4 class="font-bold text-white mb-3 text-sm uppercase kicker">Companie</h4><ul class="space-y-2 text-sm"><li>Despre</li><li>Contact</li><li>Blog</li><li>Studii de caz</li></ul></div>
      <div><h4 class="font-bold text-white mb-3 text-sm uppercase kicker">Legal</h4><ul class="space-y-2 text-sm"><li>Termeni</li><li>Confidențialitate</li><li>Cookie-uri</li><li>GDPR</li></ul></div>
    </div>
    <div class="flex flex-wrap justify-between gap-3 pt-8 text-xs">
      <div>© 2026 Sambla · servus@sambla.ro · 0775 222 333</div>
      <div class="flex gap-4"><span>🇷🇴 Hosting România</span><span>✓ GDPR</span><span>❤️ Făcut în România</span></div>
    </div>
  </div>
</footer>

</body>
</html>
