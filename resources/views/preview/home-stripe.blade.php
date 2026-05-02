<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>V1 Stripe minimal — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
      colors: {
        ink: '#0A0A0A',
        paper: '#FAFAFA',
        brand: { DEFAULT: '#DC2626', hover: '#B91C1C', soft: '#FEF2F2' }
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #FAFAFA; color: #0A0A0A; -webkit-font-smoothing: antialiased; }
  .hero-grad {
    background: radial-gradient(ellipse 80% 60% at 20% 0%, rgba(220,38,38,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 90% 10%, rgba(251,191,36,0.05) 0%, transparent 60%);
  }
  .shimmer-line { background: linear-gradient(90deg, transparent, rgba(0,0,0,0.08), transparent); }
  .dot-grid { background-image: radial-gradient(circle, rgba(0,0,0,0.08) 1px, transparent 1px); background-size: 20px 20px; }
  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(180deg); }
</style>
</head>
<body class="antialiased">

<!-- preview banner -->
<div class="bg-ink text-white text-xs px-4 py-1.5 text-center">
  Preview V1 · Stripe minimal · <a href="/preview" class="underline">înapoi la lista</a>
</div>

<!-- Nav -->
<nav class="bg-white/80 backdrop-blur border-b border-stone-200 sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-8">
      <a href="#" class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-md bg-brand flex items-center justify-center text-white font-bold text-sm">S</div>
        <span class="font-semibold tracking-tight text-base">Sambla</span>
      </a>
      <div class="hidden lg:flex items-center gap-6 text-sm text-stone-700">
        <a href="#produse" class="hover:text-ink">Produse</a>
        <a href="#solutii" class="hover:text-ink">Soluții</a>
        <a href="#preturi" class="hover:text-ink">Prețuri</a>
        <a href="#clienti" class="hover:text-ink">Clienți</a>
        <a href="#" class="hover:text-ink">Docs</a>
      </div>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <a href="/login" class="hidden sm:inline text-stone-700 hover:text-ink">Autentificare</a>
      <a href="/register" class="bg-ink text-white px-4 py-2 rounded-lg hover:bg-stone-800 font-medium">Începe gratuit →</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero-grad">
  <div class="max-w-7xl mx-auto px-6 pt-20 pb-24 grid lg:grid-cols-12 gap-12 items-start">
    <div class="lg:col-span-6">
      <div class="inline-flex items-center gap-2 text-xs font-medium bg-brand-soft text-brand px-3 py-1.5 rounded-full mb-6">
        <span class="w-1.5 h-1.5 rounded-full bg-brand animate-pulse"></span>
        Voce nativă română · GPT-4o Realtime · live
      </div>
      <h1 class="text-5xl md:text-6xl font-semibold tracking-tight leading-[1.05] mb-6">
        Angajatul tău AI<br>
        care <span class="text-brand">știe totul</span><br>
        despre afacerea ta.
      </h1>
      <p class="text-lg text-stone-600 leading-relaxed max-w-xl mb-8">
        Răspunde clienților pe chat și telefon, 24/7, din documentele, produsele și politicile tale reale. Nu inventează. Nu ghicește. <strong class="text-ink">Știe.</strong>
      </p>
      <div class="flex flex-wrap gap-3 mb-10">
        <a href="/register" class="bg-brand text-white px-5 py-3 rounded-lg font-medium hover:bg-brand-hover transition">Începe gratuit</a>
        <a href="#demo" class="bg-white border border-stone-300 text-ink px-5 py-3 rounded-lg font-medium hover:bg-stone-50 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
          Vezi în acțiune
        </a>
      </div>
      <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-stone-600">
        <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Fără card de credit</span>
        <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Setup 10 minute</span>
        <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Hosting 🇷🇴 România</span>
      </div>
    </div>

    <!-- Product mock: chat + call split -->
    <div class="lg:col-span-6">
      <div class="relative">
        <div class="absolute -inset-4 bg-gradient-to-tr from-brand/10 via-transparent to-amber-100/40 rounded-3xl blur-2xl"></div>
        <div class="relative bg-white rounded-2xl border border-stone-200 shadow-xl overflow-hidden">
          <div class="h-10 bg-stone-50 border-b border-stone-200 flex items-center px-4 gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-stone-300"></span>
            <span class="w-2.5 h-2.5 rounded-full bg-stone-300"></span>
            <span class="w-2.5 h-2.5 rounded-full bg-stone-300"></span>
            <span class="text-xs text-stone-500 ml-3">Sambla · Dashboard</span>
          </div>
          <div class="p-5 space-y-4">
            <!-- Live call card -->
            <div class="border border-stone-200 rounded-xl p-4 bg-gradient-to-br from-stone-50 to-white">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  <span class="text-xs font-medium text-stone-700">Apel în desfășurare · 01:23</span>
                </div>
                <span class="text-xs text-stone-500">+40 731 ···</span>
              </div>
              <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-full bg-brand text-white flex items-center justify-center text-xs font-semibold">AI</div>
                <div class="flex-1">
                  <div class="h-2 bg-stone-200 rounded-full overflow-hidden">
                    <div class="h-full bg-brand rounded-full" style="width: 62%"></div>
                  </div>
                  <p class="text-xs text-stone-500 mt-1.5">„Bună ziua, numele meu este Sambla. Cu ce vă pot ajuta?"</p>
                </div>
              </div>
              <div class="flex gap-2 mt-3 text-[10px]">
                <span class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">sentiment · pozitiv</span>
                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">intent · programare</span>
              </div>
            </div>
            <!-- Chat card -->
            <div class="border border-stone-200 rounded-xl p-4 bg-white">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-stone-200"></div>
                  <span class="text-xs font-medium">Andrei · web chat</span>
                </div>
                <span class="text-xs text-stone-400">acum</span>
              </div>
              <div class="space-y-2">
                <div class="bg-stone-100 rounded-lg rounded-tl-sm px-3 py-2 text-sm max-w-[80%]">Aveți retur pentru produsele online?</div>
                <div class="bg-brand text-white rounded-lg rounded-tr-sm px-3 py-2 text-sm max-w-[85%] ml-auto">Da. 14 zile pentru retur conform politicii. Îți trimit linkul cu formularul → <u>sambla.ro/retur</u></div>
              </div>
              <div class="mt-3 pt-3 border-t border-stone-100 flex items-center justify-between text-[10px] text-stone-500">
                <span>📎 sursă: <u>Politică retur.pdf</u></span>
                <span class="flex items-center gap-1"><span class="w-1 h-1 rounded-full bg-emerald-500"></span>răspuns în 1.4s</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Social proof strip -->
<section class="border-y border-stone-200 bg-white py-10">
  <div class="max-w-7xl mx-auto px-6">
    <p class="text-center text-xs font-medium uppercase tracking-wider text-stone-500 mb-6">Afaceri românești care folosesc Sambla</p>
    <div class="flex items-center justify-center gap-8 md:gap-16 flex-wrap opacity-60">
      <div class="font-bold text-lg tracking-tight text-stone-700">Dental Pro</div>
      <div class="font-serif italic text-lg text-stone-700">boutique</div>
      <div class="font-mono text-sm text-stone-700">AUTO·TECH</div>
      <div class="font-semibold text-lg text-stone-700">Imobiliare24</div>
      <div class="font-bold text-lg text-stone-700">CASA FRUMOASĂ</div>
      <div class="text-lg text-stone-700">estetică.ro</div>
    </div>
  </div>
</section>

<!-- 3 products -->
<section id="produse" class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14">
      <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3">Produse</p>
      <h2 class="text-4xl font-semibold tracking-tight mb-4">Un singur creier. Toate canalele.</h2>
      <p class="text-lg text-stone-600">Clientul sună sau scrie — primește același răspuns expert. Aceeași bază de cunoștințe, același context, același rezultat.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <div class="rounded-2xl border border-stone-200 bg-white p-7 hover:shadow-lg hover:-translate-y-0.5 transition">
        <div class="w-10 h-10 rounded-lg bg-brand-soft text-brand flex items-center justify-center mb-5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 4h4m-4 4l-4-4h4a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-7l-2 2z"/></svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">Agent AI chat</h3>
        <p class="text-stone-600 mb-4 text-sm leading-relaxed">Widget pe site-ul tău. Răspunde instant, recomandă produse, captează lead-uri, programează întâlniri. O singură linie de cod.</p>
        <ul class="space-y-1.5 text-sm text-stone-700">
          <li class="flex gap-2"><span class="text-brand">→</span>Dark mode, carduri produse, link preview</li>
          <li class="flex gap-2"><span class="text-brand">→</span>Asistență proactivă pe pagini produs</li>
          <li class="flex gap-2"><span class="text-brand">→</span>Sandboxed iframe, zero risc XSS</li>
        </ul>
      </div>

      <div class="rounded-2xl border border-stone-200 bg-white p-7 hover:shadow-lg hover:-translate-y-0.5 transition ring-1 ring-brand/10">
        <div class="w-10 h-10 rounded-lg bg-brand-soft text-brand flex items-center justify-center mb-5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">Agent AI voce
          <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold align-middle">BETA</span>
        </h3>
        <p class="text-stone-600 mb-4 text-sm leading-relaxed">Apeluri cu voce naturală în română. OpenAI Realtime + Twilio. Analiză sentiment live, transcriere, escaladare la operator.</p>
        <ul class="space-y-1.5 text-sm text-stone-700">
          <li class="flex gap-2"><span class="text-brand">→</span>Voce clonată (ElevenLabs) opțional</li>
          <li class="flex gap-2"><span class="text-brand">→</span>Numere românești dedicate</li>
          <li class="flex gap-2"><span class="text-brand">→</span>Latență sub 800ms, barge-in natural</li>
        </ul>
      </div>

      <div class="rounded-2xl border border-stone-200 bg-white p-7 hover:shadow-lg hover:-translate-y-0.5 transition">
        <div class="w-10 h-10 rounded-lg bg-brand-soft text-brand flex items-center justify-center mb-5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l-2-5-5-2 5-2 2-5 2 5 5 2-5 2-2 5z"/></svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">Multi-canal</h3>
        <p class="text-stone-600 mb-4 text-sm leading-relaxed">WhatsApp, Facebook Messenger, Instagram, WooCommerce. Aceleași date, aceleași răspunsuri, toate conversațiile într-un singur inbox.</p>
        <ul class="space-y-1.5 text-sm text-stone-700">
          <li class="flex gap-2"><span class="text-brand">→</span>Sync produse WooCommerce nativ</li>
          <li class="flex gap-2"><span class="text-brand">→</span>Tracking AWB (FanCourier, Cargus, DPD)</li>
          <li class="flex gap-2"><span class="text-brand">→</span>Handoff către operator când e nevoie</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Feature showcase with mock screenshot -->
<section class="py-24 bg-white border-y border-stone-200">
  <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-12 gap-16 items-center">
    <div class="lg:col-span-5">
      <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3">Baza de cunoștințe</p>
      <h2 class="text-4xl font-semibold tracking-tight mb-4">Învață din datele tale. Nu dintr-o bază generică.</h2>
      <p class="text-lg text-stone-600 mb-6 leading-relaxed">Uploadezi documente, scanezi site-ul, conectezi magazinul — și agentul AI răspunde <strong class="text-ink">exclusiv</strong> din informațiile tale reale. Zero halucinații pe informații critice.</p>
      <div class="space-y-3">
        <div class="flex gap-3">
          <div class="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center text-stone-700 font-semibold text-sm shrink-0">1</div>
          <div><div class="font-semibold text-sm">Upload fișiere</div><div class="text-sm text-stone-600">PDF, DOCX, CSV, TXT — procesate automat.</div></div>
        </div>
        <div class="flex gap-3">
          <div class="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center text-stone-700 font-semibold text-sm shrink-0">2</div>
          <div><div class="font-semibold text-sm">Scanare site</div><div class="text-sm text-stone-600">Crawler indexează toate paginile din sitemap.</div></div>
        </div>
        <div class="flex gap-3">
          <div class="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center text-stone-700 font-semibold text-sm shrink-0">3</div>
          <div><div class="font-semibold text-sm">Sync WooCommerce</div><div class="text-sm text-stone-600">Produse, stocuri, prețuri — în timp real.</div></div>
        </div>
        <div class="flex gap-3">
          <div class="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center text-stone-700 font-semibold text-sm shrink-0">4</div>
          <div><div class="font-semibold text-sm">Auto-detectare gap-uri</div><div class="text-sm text-stone-600">„12 clienți au întrebat de retur — vrei draft răspuns?"</div></div>
        </div>
      </div>
    </div>

    <div class="lg:col-span-7">
      <div class="rounded-xl border border-stone-200 shadow-lg overflow-hidden bg-stone-50">
        <div class="h-9 bg-white border-b border-stone-200 flex items-center px-3 gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-stone-300"></span>
          <span class="w-2.5 h-2.5 rounded-full bg-stone-300"></span>
          <span class="w-2.5 h-2.5 rounded-full bg-stone-300"></span>
          <div class="ml-4 bg-stone-100 rounded px-3 py-0.5 text-[11px] text-stone-600 flex-1 max-w-xs">app.sambla.ro/bots/dental-pro/knowledge</div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-white border border-stone-200 rounded-lg p-3">
              <div class="text-xs text-stone-500 mb-1">Documente indexate</div>
              <div class="text-2xl font-semibold">47</div>
              <div class="text-xs text-emerald-600 mt-1">+3 această săptămână</div>
            </div>
            <div class="bg-white border border-stone-200 rounded-lg p-3">
              <div class="text-xs text-stone-500 mb-1">Vector chunks</div>
              <div class="text-2xl font-semibold">1,842</div>
              <div class="text-xs text-stone-500 mt-1">1536d · pgvector RO</div>
            </div>
          </div>
          <div class="bg-white border border-stone-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between">
              <span class="font-semibold text-sm">Fișiere recente</span>
              <span class="text-xs text-stone-500">auto-reindex activ</span>
            </div>
            <div class="divide-y divide-stone-100">
              <div class="px-4 py-2.5 flex items-center gap-3">
                <span class="w-8 h-8 rounded bg-red-50 text-red-600 flex items-center justify-center text-xs font-bold">PDF</span>
                <div class="flex-1"><div class="text-sm font-medium">Politică retur.pdf</div><div class="text-xs text-stone-500">indexat acum 4 minute</div></div>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              </div>
              <div class="px-4 py-2.5 flex items-center gap-3">
                <span class="w-8 h-8 rounded bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">DOC</span>
                <div class="flex-1"><div class="text-sm font-medium">Tarife servicii 2026.docx</div><div class="text-xs text-stone-500">indexat acum 18 minute</div></div>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              </div>
              <div class="px-4 py-2.5 flex items-center gap-3">
                <span class="w-8 h-8 rounded bg-amber-50 text-amber-600 flex items-center justify-center text-xs font-bold">WEB</span>
                <div class="flex-1"><div class="text-sm font-medium">sambla.ro/blog — 32 pagini</div><div class="text-xs text-stone-500">scanare în progres...</div></div>
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pipeline -->
<section id="solutii" class="py-24">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-14">
      <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3">Cum gândește</p>
      <h2 class="text-4xl font-semibold tracking-tight mb-4">Nu e un agent AI simplu. E un pipeline cu 4 etape.</h2>
      <p class="text-lg text-stone-600">Fiecare mesaj trece printr-un proces care analizează, caută, decide și răspunde — în sub 2 secunde.</p>
    </div>

    <div class="grid md:grid-cols-4 gap-4">
      <div class="rounded-xl border border-stone-200 bg-white p-6 relative">
        <div class="absolute top-4 right-4 text-xs font-mono text-stone-400">01</div>
        <div class="text-2xl mb-3">🎯</div>
        <h3 class="font-semibold mb-2">Query intelligence</h3>
        <p class="text-sm text-stone-600">Detectează intenția: tranzacțional, informațional, reclamație, comparativ. Adaptează tot răspunsul.</p>
      </div>
      <div class="rounded-xl border border-stone-200 bg-white p-6 relative">
        <div class="absolute top-4 right-4 text-xs font-mono text-stone-400">02</div>
        <div class="text-2xl mb-3">🔎</div>
        <h3 class="font-semibold mb-2">Hybrid RAG search</h3>
        <p class="text-sm text-stone-600">Vectorial + full-text paralel. AI reranker păstrează top 8 chunks din 20. Surse RO.</p>
      </div>
      <div class="rounded-xl border border-stone-200 bg-white p-6 relative">
        <div class="absolute top-4 right-4 text-xs font-mono text-stone-400">03</div>
        <div class="text-2xl mb-3">🧠</div>
        <h3 class="font-semibold mb-2">Verificare 10 straturi</h3>
        <p class="text-sm text-stone-600">Confidence scoring, citare sursă, detectare halucinație. Eșuează elegant când nu e sigur.</p>
      </div>
      <div class="rounded-xl border border-stone-200 bg-white p-6 relative">
        <div class="absolute top-4 right-4 text-xs font-mono text-stone-400">04</div>
        <div class="text-2xl mb-3">💬</div>
        <h3 class="font-semibold mb-2">Răspuns adaptat</h3>
        <p class="text-sm text-stone-600">Personalitate, verbozitate și CTA potrivite contextului. Frustrare detectată → empatie, nu vânzare.</p>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="bg-ink text-white py-20">
  <div class="max-w-7xl mx-auto px-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400 mb-3 text-center">Cifrele care contează</p>
    <h2 class="text-3xl font-semibold tracking-tight text-center mb-14">Arhitectură construită pentru calitate, nu pentru demo-uri.</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
      <div class="text-center"><div class="text-5xl font-semibold tracking-tight mb-2">&lt;2s</div><div class="text-sm text-stone-400">latență end-to-end</div></div>
      <div class="text-center"><div class="text-5xl font-semibold tracking-tight mb-2">10</div><div class="text-sm text-stone-400">straturi anti-halucinare</div></div>
      <div class="text-center"><div class="text-5xl font-semibold tracking-tight mb-2">1536d</div><div class="text-sm text-stone-400">vector embeddings</div></div>
      <div class="text-center"><div class="text-5xl font-semibold tracking-tight mb-2">24/7</div><div class="text-sm text-stone-400">disponibilitate</div></div>
    </div>
  </div>
</section>

<!-- Integrations -->
<section class="py-20 border-b border-stone-200">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mb-10">
      <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3">Integrări</p>
      <h2 class="text-3xl font-semibold tracking-tight">Se conectează la instrumentele tale.</h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
      @foreach([
        ['WordPress','🔵'],['WooCommerce','🛒'],['Shopify','🛍️'],['Google Calendar','📅'],
        ['Calendly','📆'],['Stripe','💳'],['FanCourier','📦'],['Cargus','🚚'],
        ['HubSpot','📊'],['Mailchimp','📧'],['Zapier','⚡'],['REST API','🔗']
      ] as $int)
        <div class="rounded-lg border border-stone-200 bg-white px-4 py-3 flex items-center gap-3 text-sm hover:border-stone-300 transition">
          <span class="text-lg">{{ $int[1] }}</span>
          <span class="font-medium">{{ $int[0] }}</span>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Pricing tease -->
<section id="preturi" class="py-24 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <div class="max-w-2xl mx-auto text-center mb-14">
      <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3">Prețuri</p>
      <h2 class="text-4xl font-semibold tracking-tight mb-4">Simple. Transparente. În lei.</h2>
      <p class="text-lg text-stone-600">Upgrade, downgrade, anulezi oricând. 30% reducere pentru ONG-uri și școli.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-5 max-w-5xl mx-auto">
      <div class="rounded-2xl border border-stone-200 p-7 bg-white">
        <div class="text-sm font-semibold text-stone-500 mb-2">Chat Starter</div>
        <div class="flex items-baseline gap-1 mb-4"><span class="text-4xl font-semibold tracking-tight">29</span><span class="text-stone-500">lei/lună</span></div>
        <p class="text-sm text-stone-600 mb-5">Ideal pentru afaceri mici care vor un agent AI simplu pe site.</p>
        <ul class="space-y-2 text-sm mb-6">
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>1 agent AI activ</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>500 conversații/lună</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Widget + 1 site</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Bază de cunoștințe nelimitată</li>
        </ul>
        <a href="/register" class="block text-center border border-stone-300 text-ink font-medium py-2.5 rounded-lg hover:bg-stone-50">Începe gratuit</a>
      </div>

      <div class="rounded-2xl border-2 border-brand p-7 bg-white relative shadow-lg">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand text-white text-xs font-semibold px-3 py-1 rounded-full">Recomandat</div>
        <div class="text-sm font-semibold text-brand mb-2">Chat Professional</div>
        <div class="flex items-baseline gap-1 mb-4"><span class="text-4xl font-semibold tracking-tight">79</span><span class="text-stone-500">lei/lună</span></div>
        <p class="text-sm text-stone-600 mb-5">Pentru echipe care au nevoie de mai mulți agenți AI și integrări.</p>
        <ul class="space-y-2 text-sm mb-6">
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>3 agenți AI activi</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>2.500 conversații/lună</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>WooCommerce + WhatsApp</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Lead scoring + CRM pipeline</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Analiză avansată</li>
        </ul>
        <a href="/register" class="block text-center bg-brand text-white font-medium py-2.5 rounded-lg hover:bg-brand-hover">Alege Professional →</a>
      </div>

      <div class="rounded-2xl border border-stone-200 p-7 bg-white">
        <div class="text-sm font-semibold text-stone-500 mb-2">Chat Business</div>
        <div class="flex items-baseline gap-1 mb-4"><span class="text-4xl font-semibold tracking-tight">199</span><span class="text-stone-500">lei/lună</span></div>
        <p class="text-sm text-stone-600 mb-5">Soluția completă pentru afaceri cu volum mare + add-on voce.</p>
        <ul class="space-y-2 text-sm mb-6">
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>10 agenți AI activi</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>10.000 conversații/lună</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Toate canalele (FB, IG, WA)</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Voce AI disponibilă (+49 lei)</li>
          <li class="flex gap-2"><span class="text-emerald-600">✓</span>Suport prioritar</li>
        </ul>
        <a href="/register" class="block text-center border border-stone-300 text-ink font-medium py-2.5 rounded-lg hover:bg-stone-50">Alege Business</a>
      </div>
    </div>

    <div class="text-center mt-8"><a href="/preturi" class="text-sm text-stone-600 hover:text-ink underline underline-offset-4">Vezi toate planurile + opțiuni voce →</a></div>
  </div>
</section>

<!-- Testimonials -->
<section id="clienti" class="py-24 border-t border-stone-200">
  <div class="max-w-5xl mx-auto px-6">
    <div class="max-w-2xl mb-12">
      <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3">Clienți</p>
      <h2 class="text-4xl font-semibold tracking-tight">Ce spun afacerile care folosesc deja Sambla.</h2>
    </div>
    <div class="grid md:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-stone-200 p-8 bg-white">
        <p class="text-lg leading-relaxed mb-6">„Înainte pierdeam jumătate din apelurile după program. Acum agentul AI preia tot — programările se fac și când dormim. Veniturile au crescut cu 30% în trei luni."</p>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-stone-200 flex items-center justify-center font-semibold">AM</div>
          <div><div class="font-semibold text-sm">Ana Marinescu</div><div class="text-xs text-stone-500">Proprietar clinică estetică, București</div></div>
        </div>
      </div>
      <div class="rounded-2xl border border-stone-200 p-8 bg-white">
        <p class="text-lg leading-relaxed mb-6">„Peste 200 de mesaje WhatsApp pe zi gestionate automat — rezervări, întrebări despre meniu. Echipa mea se ocupă acum de bucătărie în loc de telefon."</p>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-stone-200 flex items-center justify-center font-semibold">MT</div>
          <div><div class="font-semibold text-sm">Mihai Tudor</div><div class="text-xs text-stone-500">Manager restaurant, Cluj</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-24 bg-white border-t border-stone-200">
  <div class="max-w-3xl mx-auto px-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-brand mb-3 text-center">Întrebări frecvente</p>
    <h2 class="text-4xl font-semibold tracking-tight text-center mb-12">Ai întrebări? Răspundem.</h2>
    <div class="space-y-3">
      @foreach([
        ['Ce se întâmplă când depășesc limita de mesaje?', 'Se taxează automat la costul suplimentar al planului tău. Nu există întreruperi ale serviciului.'],
        ['Pot combina chat + voce?', 'Da. Alegi un plan chat și adaugi un addon vocal. Ambele folosesc aceeași bază de cunoștințe.'],
        ['Funcționează cu site-ul meu WordPress?', 'Da. Avem plugin WordPress și WooCommerce nativ, plus embed pe orice site cu o singură linie de cod.'],
        ['Cum se asigură că AI-ul nu inventează?', 'Pipeline cu 10 straturi de verificare, citare obligatorie a sursei, și confidence scoring. Când nu știe, răspunde cinstit „nu am această informație" în loc să improvizeze.'],
        ['Unde sunt stocate datele?', 'Servere fizice în România. Zero transfer în afara UE. GDPR conform by default, izolare per cont.'],
        ['Oferiți reducere pentru ONG-uri?', 'Da, 30% permanent pentru ONG-uri, școli, universități și muzee. Contactează-ne cu documentele organizației.']
      ] as $faq)
        <details class="group border border-stone-200 rounded-lg bg-white">
          <summary class="px-5 py-4 flex items-center justify-between cursor-pointer list-none font-medium">
            <span>{{ $faq[0] }}</span>
            <svg class="chev w-4 h-4 text-stone-400 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          </summary>
          <div class="px-5 pb-4 text-sm text-stone-600 leading-relaxed">{{ $faq[1] }}</div>
        </details>
      @endforeach
    </div>
  </div>
</section>

<!-- Final CTA -->
<section class="py-24">
  <div class="max-w-5xl mx-auto px-6">
    <div class="rounded-3xl bg-ink text-white p-12 md:p-16 text-center relative overflow-hidden">
      <div class="absolute inset-0 dot-grid opacity-20"></div>
      <div class="relative">
        <h2 class="text-4xl md:text-5xl font-semibold tracking-tight mb-4">Transformă fiecare conversație<br>într-o oportunitate de vânzare.</h2>
        <p class="text-lg text-stone-300 mb-8 max-w-xl mx-auto">Configurezi în 10 minute. Primele rezultate din prima zi. Fără card de credit.</p>
        <div class="flex flex-wrap justify-center gap-3">
          <a href="/register" class="bg-brand hover:bg-brand-hover px-6 py-3 rounded-lg font-medium">Începe gratuit acum</a>
          <a href="/contact" class="bg-white/10 border border-white/20 hover:bg-white/20 px-6 py-3 rounded-lg font-medium backdrop-blur">Programează un demo</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-white border-t border-stone-200 py-12">
  <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-4 gap-8 text-sm">
    <div class="md:col-span-1">
      <div class="flex items-center gap-2 mb-3">
        <div class="w-7 h-7 rounded-md bg-brand flex items-center justify-center text-white font-bold text-sm">S</div>
        <span class="font-semibold">Sambla</span>
      </div>
      <p class="text-stone-600 leading-relaxed">Angajatul tău AI care știe totul despre afacerea ta. Voce naturală, chat inteligent, auto-îmbunătățire continuă.</p>
    </div>
    <div>
      <h4 class="font-semibold mb-3">Produs</h4>
      <ul class="space-y-2 text-stone-600"><li>Funcționalități</li><li>Prețuri</li><li>Demo live</li><li>API & docs</li></ul>
    </div>
    <div>
      <h4 class="font-semibold mb-3">Companie</h4>
      <ul class="space-y-2 text-stone-600"><li>Despre noi</li><li>Contact</li><li>Blog</li><li>Studii de caz</li></ul>
    </div>
    <div>
      <h4 class="font-semibold mb-3">Legal</h4>
      <ul class="space-y-2 text-stone-600"><li>Termeni</li><li>Confidențialitate</li><li>Cookie-uri</li><li>GDPR</li></ul>
    </div>
  </div>
  <div class="max-w-7xl mx-auto px-6 mt-10 pt-6 border-t border-stone-100 flex flex-wrap items-center justify-between gap-3 text-xs text-stone-500">
    <div>© 2026 Sambla · servus@sambla.ro · 0775 222 333</div>
    <div class="flex gap-3"><span>🇷🇴 Hosting România</span><span>✓ GDPR</span><span>❤️ Făcut în România</span></div>
  </div>
</footer>

</body>
</html>
