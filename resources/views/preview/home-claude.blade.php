<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>V2 Claude warm — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], display: ['Fraunces', 'serif'] },
      colors: {
        cream: '#F4F0E7',
        paper: '#FFFBF2',
        ink:   '#2D2A24',
        muted: '#6B5E3F',
        rule:  '#E5DFD0',
        amber: '#D97757',
        wine:  '#C1272D',
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #F4F0E7; color: #2D2A24; -webkit-font-smoothing: antialiased; }
  .serif { font-family: 'Fraunces', serif; font-optical-sizing: auto; }
  .grain { position: relative; }
  .grain::before {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background-image: radial-gradient(rgba(45,42,36,0.04) 1px, transparent 1px);
    background-size: 3px 3px; opacity: 0.6;
  }
  .hero-glow {
    background:
      radial-gradient(ellipse 50% 40% at 30% 20%, rgba(217,119,87,0.18) 0%, transparent 60%),
      radial-gradient(ellipse 40% 30% at 80% 0%, rgba(193,39,45,0.10) 0%, transparent 60%);
  }
  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(180deg); }
  .divider { height: 1px; background: linear-gradient(90deg, transparent, #E5DFD0, transparent); }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview V2 · Claude warm · <a href="/preview" class="underline">înapoi la lista</a>
</div>

<!-- Nav -->
<nav class="bg-cream/80 backdrop-blur border-b border-rule sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="#" class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background:#2D2A24;">
        <span class="serif text-paper font-semibold text-lg leading-none">S</span>
      </div>
      <span class="serif font-semibold text-xl tracking-tight">Sambla</span>
    </a>
    <div class="hidden md:flex items-center gap-8 text-sm text-muted">
      <a href="#produse" class="hover:text-ink transition">Produse</a>
      <a href="#gandire" class="hover:text-ink transition">Cum gândește</a>
      <a href="#industrii" class="hover:text-ink transition">Industrii</a>
      <a href="#preturi" class="hover:text-ink transition">Prețuri</a>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <a href="/login" class="hidden sm:inline text-muted hover:text-ink">Autentificare</a>
      <a href="/register" class="text-paper px-4 py-2 rounded-full font-medium" style="background:#2D2A24;">Începe gratuit</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero-glow grain">
  <div class="max-w-5xl mx-auto px-6 pt-24 pb-20 text-center relative">
    <div class="inline-flex items-center gap-2 text-xs font-medium mb-10 px-3 py-1.5 rounded-full" style="background:#FFFBF2; border:1px solid #E5DFD0; color:#6B5E3F;">
      <span class="w-1.5 h-1.5 rounded-full" style="background:#D97757;"></span>
      GPT-4o Realtime · voce nativă română · live acum
    </div>
    <h1 class="serif text-6xl md:text-[5.5rem] leading-[0.95] tracking-tight mb-8 font-normal">
      Un angajat AI care<br>
      <span class="italic" style="color:#C1272D;">știe</span> afacerea ta<br>
      <span style="color:#6B5E3F;">pe de rost.</span>
    </h1>
    <p class="text-xl leading-relaxed max-w-2xl mx-auto mb-10 text-muted">
      Răspunde pe chat și telefon, 24/7, <em class="serif italic text-ink">din documentele, produsele și politicile tale reale</em>. Nu inventează. Nu ghicește.
    </p>
    <div class="flex flex-wrap justify-center gap-3 mb-12">
      <a href="/register" class="text-paper px-6 py-3 rounded-full font-medium text-base hover:opacity-90 transition" style="background:#2D2A24;">Încearcă gratuit →</a>
      <a href="#demo" class="px-6 py-3 rounded-full font-medium text-base transition hover:bg-paper" style="border:1px solid #2D2A24; color:#2D2A24;">Vezi o conversație reală</a>
    </div>
    <div class="text-sm text-muted">Fără card · Setup 10 minute · Anulezi oricând</div>
  </div>

  <!-- Big chat mockup -->
  <div class="max-w-3xl mx-auto px-6 pb-24">
    <div class="relative">
      <div class="absolute -inset-6 rounded-[2rem] blur-2xl opacity-60" style="background:linear-gradient(135deg, rgba(217,119,87,0.3), rgba(193,39,45,0.15));"></div>
      <div class="relative bg-paper rounded-3xl overflow-hidden shadow-2xl" style="border:1px solid #E5DFD0;">
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid #E5DFD0; background:#FAF6EB;">
          <div class="flex items-center gap-3">
            <div class="relative">
              <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#2D2A24;"><span class="serif text-paper text-sm font-semibold">S</span></div>
              <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-paper" style="background:#4ADE80;"></span>
            </div>
            <div>
              <div class="font-semibold text-sm">Sambla · Clinica Dental Pro</div>
              <div class="text-xs text-muted">Online · răspunde de obicei în 2 secunde</div>
            </div>
          </div>
          <span class="text-xs px-2 py-1 rounded-full" style="background:#EFE6D0; color:#6B5E3F;">demo live</span>
        </div>

        <div class="p-6 space-y-4 min-h-[380px]">
          <div class="flex gap-3 items-end">
            <div class="max-w-[78%] px-4 py-2.5 rounded-2xl rounded-bl-md text-[15px] leading-relaxed" style="background:#EFE6D0; color:#2D2A24;">
              Bună ziua! Aveți disponibilitate pentru o detartraj săptămâna viitoare?
            </div>
          </div>

          <div class="flex gap-3 justify-end">
            <div class="max-w-[82%] px-4 py-3 rounded-2xl rounded-br-md text-[15px] leading-relaxed" style="background:#2D2A24; color:#F4F0E7;">
              Bună ziua! Da, avem. În săptămâna 21–27 aprilie mai sunt 4 locuri disponibile:<br><br>
              📅 Marți 22, ora 10:00 – Dr. Ionescu<br>
              📅 Miercuri 23, ora 16:30 – Dr. Popescu<br>
              📅 Joi 24, ora 14:00 – Dr. Ionescu<br>
              📅 Vineri 25, ora 09:30 – Dr. Popescu<br><br>
              Vă potrivește unul dintre ele? 🦷
            </div>
          </div>

          <div class="flex gap-3 items-end">
            <div class="max-w-[70%] px-4 py-2.5 rounded-2xl rounded-bl-md text-[15px] leading-relaxed" style="background:#EFE6D0;">
              Marți 22 la 10 e perfect. Cât costă?
            </div>
          </div>

          <div class="flex gap-3 justify-end">
            <div class="max-w-[82%] px-4 py-3 rounded-2xl rounded-br-md text-[15px] leading-relaxed" style="background:#2D2A24; color:#F4F0E7;">
              Detartrajul costă <strong>180 lei</strong> (ultrasonic + aeropolishing). Am rezervat provizoriu slot-ul. Îmi puteți da numele complet și un telefon pentru confirmare?
            </div>
          </div>

          <div class="flex gap-3 justify-end items-center pt-1">
            <div class="px-3 py-1.5 rounded-full text-[11px] font-medium flex items-center gap-1.5" style="background:#FFFBF2; border:1px solid #E5DFD0; color:#6B5E3F;">
              <span class="w-1.5 h-1.5 rounded-full" style="background:#4ADE80;"></span>
              sursă: Tarife 2026.pdf · confidence 94%
            </div>
          </div>
        </div>

        <div class="px-5 py-3 flex items-center gap-3" style="border-top:1px solid #E5DFD0; background:#FAF6EB;">
          <div class="flex-1 rounded-full px-4 py-2 text-sm text-muted" style="background:#FFFBF2; border:1px solid #E5DFD0;">Scrie un mesaj...</div>
          <button class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#2D2A24;">
            <svg class="w-4 h-4 text-paper" fill="currentColor" viewBox="0 0 20 20"><path d="M3.4 2.3a.75.75 0 00-.95.95l2 6a.75.75 0 00.55.5l5.5 1.25-5.5 1.25a.75.75 0 00-.55.5l-2 6a.75.75 0 00.95.95L18 11a.75.75 0 000-1.3L3.4 2.3z"/></svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- Manifesto -->
<section class="py-24 grain">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <p class="text-xs uppercase tracking-[0.2em] text-muted mb-6">Ce credem</p>
    <p class="serif text-3xl md:text-4xl leading-[1.25] font-normal max-w-3xl mx-auto">
      „<em class="italic">Orice LLM poate halucina.</em> E o proprietate fundamentală a tehnologiei.<br>
      Oricine îți spune că produsul lui are <span class="underline decoration-wavy decoration-[#D97757] underline-offset-[6px]">zero halucinație</span> e necinstit.<br><br>
      Ce facem noi: <strong class="font-semibold">minimizăm halucinarea prin inginerie și eșuăm elegant</strong> când nu putem garanta un răspuns."
    </p>
    <p class="mt-8 text-sm text-muted">— Sambla, principiul fondator</p>
  </div>
</section>

<div class="divider"></div>

<!-- Products -->
<section id="produse" class="py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-2xl mb-16">
      <p class="text-xs uppercase tracking-[0.2em] text-muted mb-4">Trei canale, un creier</p>
      <h2 class="serif text-5xl leading-[1.05] tracking-tight mb-5 font-normal">O platformă. <em class="italic" style="color:#C1272D;">O bază de cunoștințe.</em> Toate conversațiile.</h2>
      <p class="text-lg text-muted leading-relaxed">Clientul sună sau scrie — primește același răspuns expert. Aceleași politici, aceleași produse, același ton.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      <div class="rounded-3xl p-8 relative overflow-hidden" style="background:#FFFBF2; border:1px solid #E5DFD0;">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-6" style="background:#EFE6D0; color:#C1272D;">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 4h4m-4 4l-4-4h4a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-7l-2 2z"/></svg>
        </div>
        <h3 class="serif text-2xl mb-3 font-semibold">Chat pe site</h3>
        <p class="text-muted leading-relaxed mb-4">Widget premium cu dark mode, carduri produse, preview link-uri. O linie de cod și e live.</p>
        <div class="text-sm pt-4 flex items-center gap-2" style="color:#C1272D; border-top:1px solid #E5DFD0;">
          <span>Vezi detalii</span>
          <span class="transition-transform group-hover:translate-x-1">→</span>
        </div>
      </div>

      <div class="rounded-3xl p-8 relative overflow-hidden" style="background:#2D2A24; color:#F4F0E7;">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-6" style="background:#3D3A30; color:#D97757;">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
        </div>
        <div class="flex items-center gap-2 mb-3">
          <h3 class="serif text-2xl font-semibold">Apeluri vocale</h3>
          <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold" style="background:#D97757; color:#2D2A24;">BETA</span>
        </div>
        <p class="leading-relaxed mb-4" style="color:#C6BDA6;">Voce naturală română, latență sub 800ms. Clonează vocea echipei cu ElevenLabs pentru continuitate de brand.</p>
        <div class="text-sm pt-4 flex items-center gap-2" style="color:#D97757; border-top:1px solid #3D3A30;">
          <span>Ascultă un apel demo</span>
          <span>→</span>
        </div>
      </div>

      <div class="rounded-3xl p-8 relative overflow-hidden" style="background:#FFFBF2; border:1px solid #E5DFD0;">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-6" style="background:#EFE6D0; color:#C1272D;">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l-2-5-5-2 5-2 2-5 2 5 5 2-5 2-2 5z"/></svg>
        </div>
        <h3 class="serif text-2xl mb-3 font-semibold">WhatsApp, FB, IG</h3>
        <p class="text-muted leading-relaxed mb-4">Un singur inbox pentru toate mesajele. Sincronizare WooCommerce nativă, tracking AWB automat.</p>
        <div class="text-sm pt-4 flex items-center gap-2" style="color:#C1272D; border-top:1px solid #E5DFD0;">
          <span>Canale disponibile</span>
          <span>→</span>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- How it thinks -->
<section id="gandire" class="py-24" style="background:#EFE6D0;">
  <div class="max-w-5xl mx-auto px-6">
    <div class="max-w-2xl mb-16">
      <p class="text-xs uppercase tracking-[0.2em] text-muted mb-4">Cum gândește</p>
      <h2 class="serif text-5xl leading-[1.05] tracking-tight mb-5 font-normal">Nu un agent AI, <em class="italic">un pipeline inteligent</em> cu patru etape.</h2>
      <p class="text-lg text-muted leading-relaxed">Fiecare mesaj trece prin patru etape — analizează, caută, decide, răspunde — în sub două secunde.</p>
    </div>

    <div class="space-y-4">
      @foreach([
        ['01', 'Înțelege intenția', 'Query intelligence detectează ce vrea clientul: să cumpere, să întrebe, să se plângă, să compare. Apoi adaptează tot răspunsul.', '🎯'],
        ['02', 'Caută în cunoștințele tale', 'Hybrid search combină vectorial + full-text. AI reranker alege top 8 chunks din 20. Surse explicite, citate.', '🔎'],
        ['03', 'Verifică 10 straturi', 'Confidence scoring. Anti-halucinare. Cite source. Când nu e sigur, recunoaște cinstit că nu știe.', '🧠'],
        ['04', 'Răspunde în contextul potrivit', 'Empatie dacă detectează frustrare. Recomandare dacă e interesat. Scurt dacă vrea răspuns rapid. Mereu în stilul brandului.', '💬'],
      ] as $step)
        <div class="grid md:grid-cols-12 gap-6 items-center rounded-2xl p-7 hover:shadow-lg transition" style="background:#FFFBF2; border:1px solid #E5DFD0;">
          <div class="md:col-span-1 serif text-5xl font-light" style="color:#D97757;">{{ $step[0] }}</div>
          <div class="md:col-span-1 text-4xl">{{ $step[3] }}</div>
          <div class="md:col-span-10">
            <h3 class="serif text-2xl font-semibold mb-1.5">{{ $step[1] }}</h3>
            <p class="text-muted leading-relaxed">{{ $step[2] }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Auto-learning -->
<section class="py-24 grain">
  <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-16 items-center">
    <div>
      <p class="text-xs uppercase tracking-[0.2em] text-muted mb-4">Auto-învățare</p>
      <h2 class="serif text-4xl md:text-5xl leading-[1.05] tracking-tight mb-6 font-normal">Se face <em class="italic" style="color:#C1272D;">mai deștept</em> în fiecare zi.</h2>
      <p class="text-lg text-muted leading-relaxed mb-8">Sambla nu așteaptă să-i spui ce nu știe. Descoperă singur, te anunță, și sugerează soluția.</p>

      <div class="space-y-5">
        <div>
          <div class="font-semibold mb-1">Detectează întrebările fără răspuns</div>
          <p class="text-sm text-muted">„12 clienți au întrebat despre retur, dar nu ai conținut. Vrei să generez un draft?"</p>
        </div>
        <div>
          <div class="font-semibold mb-1">Generează conținut automat</div>
          <p class="text-sm text-muted">AI-ul scrie un draft de politică bazat pe întrebările reale. Tu doar aprobi.</p>
        </div>
        <div>
          <div class="font-semibold mb-1">Monitorizează calitatea zilnic</div>
          <p class="text-sm text-muted">Health score, rată de rezolvare, frustrare detectată — toate într-un dashboard clar.</p>
        </div>
      </div>
    </div>

    <div class="rounded-2xl p-7" style="background:#FFFBF2; border:1px solid #E5DFD0;">
      <div class="flex items-center justify-between mb-4">
        <div class="font-semibold text-sm">Suggestii de îmbunătățire</div>
        <span class="text-xs px-2 py-0.5 rounded-full" style="background:#FEF3C7; color:#92400E;">3 pending</span>
      </div>
      <div class="space-y-3">
        <div class="p-4 rounded-xl" style="background:#F4F0E7; border:1px solid #E5DFD0;">
          <div class="flex items-start justify-between mb-2">
            <span class="text-xs font-semibold" style="color:#D97757;">GAP DETECTAT · 12 întrebări</span>
            <span class="text-[11px] text-muted">acum 2h</span>
          </div>
          <p class="text-sm leading-relaxed">Clienții întreabă despre <strong>politica de retur online</strong>. Am generat un draft bazat pe legea RO 449/2003.</p>
          <div class="flex gap-2 mt-3">
            <button class="text-xs px-3 py-1.5 rounded-full font-medium text-paper" style="background:#2D2A24;">Vezi draft</button>
            <button class="text-xs px-3 py-1.5 rounded-full font-medium" style="border:1px solid #E5DFD0;">Ignoră</button>
          </div>
        </div>
        <div class="p-4 rounded-xl" style="background:#F4F0E7; border:1px solid #E5DFD0;">
          <div class="flex items-start justify-between mb-2">
            <span class="text-xs font-semibold" style="color:#059669;">HEALTH SCORE · 94/100</span>
            <span class="text-[11px] text-muted">actualizat</span>
          </div>
          <p class="text-sm leading-relaxed">Rată rezolvare 91%. Frustrare detectată 3.2% (sub medie). +2 puncte față de săptămâna trecută.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- Industries -->
<section id="industrii" class="py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-2xl mb-12 text-center mx-auto">
      <p class="text-xs uppercase tracking-[0.2em] text-muted mb-4">Industrii</p>
      <h2 class="serif text-5xl leading-[1.05] tracking-tight mb-5 font-normal">Creat special pentru <em class="italic">afacerea ta.</em></h2>
      <p class="text-lg text-muted">Personalități, prompt-uri și integrări adaptate la fiecare verticală.</p>
    </div>

    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
      @foreach([
        ['🦷','Stomatologie','Programări, tarife, recall-uri de control.'],
        ['💆','Beauty & Estetică','Rezervări, consultații, upselling servicii.'],
        ['🏥','Clinici medicale','Triaj, programări, reamintiri, întrebări frecvente.'],
        ['🛒','E-commerce','Stoc real, recomandări, tracking AWB, retururi.'],
        ['🏠','Imobiliare','Listări, vizionări, cereri calificate cu scoring.'],
        ['🔧','Service auto','Programări ITP, oferte piese, istoric mașină.'],
        ['⚖️','Cabinete avocatură','Intake cazuri, programări, FAQ juridic.'],
        ['🍽️','Restaurante','Rezervări, meniu, evenimente, delivery.'],
        ['🏨','Pensiuni & hoteluri','Disponibilitate iCal, concierge, upsell servicii.'],
      ] as $ind)
        <div class="rounded-2xl p-6 hover:-translate-y-0.5 transition" style="background:#FFFBF2; border:1px solid #E5DFD0;">
          <div class="text-3xl mb-3">{{ $ind[0] }}</div>
          <div class="serif text-xl font-semibold mb-1">{{ $ind[1] }}</div>
          <p class="text-sm text-muted leading-relaxed">{{ $ind[2] }}</p>
        </div>
      @endforeach
    </div>
    <div class="text-center mt-10"><a href="#" class="text-sm underline underline-offset-4" style="color:#C1272D;">Vezi toate industriile →</a></div>
  </div>
</section>

<div class="divider"></div>

<!-- Testimonial big -->
<section class="py-24" style="background:#2D2A24; color:#F4F0E7;">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <p class="text-xs uppercase tracking-[0.2em] mb-8" style="color:#D97757;">De la clienții noștri</p>
    <p class="serif text-4xl md:text-5xl leading-[1.15] mb-10 font-light">
      „Pierdeam jumătate din apelurile de după program.<br>
      Acum <em class="italic" style="color:#D97757;">fiecare e preluat de Sambla.</em><br>
      Veniturile au crescut cu 30% în trei luni."
    </p>
    <div class="flex items-center justify-center gap-3">
      <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-lg" style="background:#D97757; color:#2D2A24;">AM</div>
      <div class="text-left">
        <div class="font-semibold">Ana Marinescu</div>
        <div class="text-sm" style="color:#C6BDA6;">Proprietar clinică estetică, București</div>
      </div>
    </div>
  </div>
</section>

<!-- Pricing -->
<section id="preturi" class="py-24">
  <div class="max-w-5xl mx-auto px-6">
    <div class="text-center mb-16">
      <p class="text-xs uppercase tracking-[0.2em] text-muted mb-4">Prețuri</p>
      <h2 class="serif text-5xl leading-[1.05] tracking-tight mb-4 font-normal"><em class="italic">Simple.</em> Transparente. În lei.</h2>
      <p class="text-lg text-muted">7 zile gratuit. Fără card. Anulezi oricând.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      <div class="rounded-3xl p-8" style="background:#FFFBF2; border:1px solid #E5DFD0;">
        <div class="serif text-xl mb-1 font-semibold">Starter</div>
        <div class="text-sm text-muted mb-6">Afaceri mici, un agent AI simplu.</div>
        <div class="flex items-baseline gap-1 mb-6">
          <span class="serif text-5xl font-semibold">29</span>
          <span class="text-muted">lei / lună</span>
        </div>
        <ul class="space-y-2.5 text-sm mb-8">
          <li>✓ 1 agent AI activ</li>
          <li>✓ 500 conversații / lună</li>
          <li>✓ Widget + 1 site</li>
          <li>✓ Bază de cunoștințe nelimitată</li>
        </ul>
        <a href="/register" class="block text-center py-2.5 rounded-full font-medium transition hover:bg-cream" style="border:1px solid #2D2A24; color:#2D2A24;">Începe gratuit</a>
      </div>

      <div class="rounded-3xl p-8 relative" style="background:#2D2A24; color:#F4F0E7;">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 text-xs font-semibold px-3 py-1 rounded-full" style="background:#D97757; color:#2D2A24;">Cel mai ales</div>
        <div class="serif text-xl mb-1 font-semibold">Professional</div>
        <div class="text-sm mb-6" style="color:#C6BDA6;">Echipe în creștere, multi-canal.</div>
        <div class="flex items-baseline gap-1 mb-6">
          <span class="serif text-5xl font-semibold">79</span>
          <span style="color:#C6BDA6;">lei / lună</span>
        </div>
        <ul class="space-y-2.5 text-sm mb-8">
          <li>✓ 3 agenți AI activi</li>
          <li>✓ 2.500 conversații / lună</li>
          <li>✓ WooCommerce + WhatsApp</li>
          <li>✓ Lead scoring + CRM pipeline</li>
          <li>✓ Analiză avansată</li>
        </ul>
        <a href="/register" class="block text-center py-2.5 rounded-full font-medium text-paper hover:opacity-90" style="background:#D97757; color:#2D2A24;">Alege Professional →</a>
      </div>

      <div class="rounded-3xl p-8" style="background:#FFFBF2; border:1px solid #E5DFD0;">
        <div class="serif text-xl mb-1 font-semibold">Business</div>
        <div class="text-sm text-muted mb-6">Volum mare + add-on voce.</div>
        <div class="flex items-baseline gap-1 mb-6">
          <span class="serif text-5xl font-semibold">199</span>
          <span class="text-muted">lei / lună</span>
        </div>
        <ul class="space-y-2.5 text-sm mb-8">
          <li>✓ 10 agenți AI activi</li>
          <li>✓ 10.000 conversații / lună</li>
          <li>✓ Toate canalele (FB, IG, WA)</li>
          <li>✓ Voce AI disponibilă (+49 lei)</li>
          <li>✓ Suport prioritar</li>
        </ul>
        <a href="/register" class="block text-center py-2.5 rounded-full font-medium transition hover:bg-cream" style="border:1px solid #2D2A24; color:#2D2A24;">Alege Business</a>
      </div>
    </div>
    <p class="text-center mt-8 text-sm text-muted">30% reducere pentru ONG-uri, școli, muzee. <a href="#" class="underline underline-offset-4">Vezi toate planurile →</a></p>
  </div>
</section>

<!-- Final CTA -->
<section class="py-24 grain">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <h2 class="serif text-5xl md:text-6xl leading-[1.05] tracking-tight mb-6 font-normal">
      Să-i <em class="italic" style="color:#C1272D;">dăm voce</em> afacerii tale.
    </h2>
    <p class="text-xl text-muted mb-10 max-w-xl mx-auto">Configurezi în 10 minute. Primele rezultate din prima zi.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="/register" class="text-paper px-7 py-3.5 rounded-full font-medium text-base" style="background:#2D2A24;">Creează cont gratuit</a>
      <a href="/contact" class="px-7 py-3.5 rounded-full font-medium text-base" style="border:1px solid #2D2A24; color:#2D2A24;">Programează un demo</a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="py-12" style="background:#EFE6D0; border-top:1px solid #E5DFD0;">
  <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-4 gap-8 text-sm">
    <div>
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background:#2D2A24;"><span class="serif text-paper font-semibold">S</span></div>
        <span class="serif font-semibold text-lg">Sambla</span>
      </div>
      <p class="text-muted leading-relaxed">Un angajat AI care știe afacerea ta pe de rost.</p>
    </div>
    <div>
      <h4 class="font-semibold mb-3">Produs</h4>
      <ul class="space-y-2 text-muted"><li>Funcționalități</li><li>Prețuri</li><li>Demo live</li><li>API</li></ul>
    </div>
    <div>
      <h4 class="font-semibold mb-3">Companie</h4>
      <ul class="space-y-2 text-muted"><li>Despre noi</li><li>Povestea numelui</li><li>Contact</li><li>Blog</li></ul>
    </div>
    <div>
      <h4 class="font-semibold mb-3">Legal</h4>
      <ul class="space-y-2 text-muted"><li>Termeni</li><li>Confidențialitate</li><li>Cookie-uri</li></ul>
    </div>
  </div>
  <div class="max-w-6xl mx-auto px-6 mt-10 pt-6 flex flex-wrap justify-between gap-3 text-xs text-muted" style="border-top:1px solid #E5DFD0;">
    <div>© 2026 Sambla · servus@sambla.ro</div>
    <div class="flex gap-4"><span>🇷🇴 Hosting România</span><span>GDPR compliant</span><span>Făcut cu ❤️ în România</span></div>
  </div>
</footer>

</body>
</html>
