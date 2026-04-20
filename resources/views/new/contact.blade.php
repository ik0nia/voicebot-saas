@extends('layouts.new')

@section('title', 'Contact Sambla — vorbește cu noi despre agenți AI')
@section('meta_description', 'Scrie-ne despre afacerea ta și ce ai vrea să automatizezi. Răspundem în ziua lucrătoare. Pentru urgențe, sună direct.')
@section('canonical', url('/new/contact'))

@section('content')

<section class="hero-glow">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-10 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ contact</div>
        <h1 class="display h-display-xl mb-5">Hai să vorbim <em class="italic accent-text">pe bune</em>.</h1>
        <p class="text-xl" style="color: var(--muted);">Îți răspundem în ziua lucrătoare. Pentru urgențe, sună direct.</p>
    </div>
</section>

<section class="pb-20">
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-12 gap-10">
        {{-- Form --}}
        <div class="md:col-span-7 fade-up">
            <form id="contact-form" method="POST" action="{{ url('/contact') }}" class="rounded-3xl p-7 md:p-8 bg-paper border border-line space-y-5">
                @csrf
                @if(session('success') || session('contact_success'))
                    <div class="rounded-xl px-4 py-3 text-sm" style="background:#D1FAE5; color:#047857;">
                        {{ session('success') ?? session('contact_success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="rounded-xl px-4 py-3 text-sm" style="background: var(--accent-soft); color: var(--accent-dark);">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                {{-- Honeypot for bots — real users leave it empty --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
                <input type="hidden" name="source" value="contact_form">

                <div class="grid md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">Nume</span>
                        <input required name="name" type="text" value="{{ old('name') }}" placeholder="Numele tău"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">E-mail</span>
                        <input required name="email" type="email" value="{{ old('email') }}" placeholder="nume@afacerea-ta.ro"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">Companie <span style="color: var(--muted);" class="font-normal">(opțional)</span></span>
                        <input name="company" type="text" value="{{ old('company') }}" placeholder="Numele afacerii tale"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">Telefon <span style="color: var(--muted);" class="font-normal">(opțional)</span></span>
                        <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="+40 7xx xxx xxx"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-medium mb-1 block">Despre ce vrei să vorbim?</span>
                    <select name="subject"
                            class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2 appearance-none"
                            style="--tw-ring-color: var(--accent); background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23A8A29E%22><path stroke-linecap=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/></svg>'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1rem;">
                        <option value="">Alege un subiect…</option>
                        <option value="demo"          {{ old('subject') === 'demo' ? 'selected' : '' }}>Vreau să văd un demo live</option>
                        <option value="pricing"       {{ old('subject') === 'pricing' ? 'selected' : '' }}>Întrebări despre prețuri / plan</option>
                        <option value="integration"   {{ old('subject') === 'integration' ? 'selected' : '' }}>Integrare cu magazinul / CRM-ul meu</option>
                        <option value="voice"         {{ old('subject') === 'voice' ? 'selected' : '' }}>Agent vocal pe telefon</option>
                        <option value="ong"           {{ old('subject') === 'ong' ? 'selected' : '' }}>Reducere ONG / școală</option>
                        <option value="partnership"   {{ old('subject') === 'partnership' ? 'selected' : '' }}>Parteneriat / revânzător</option>
                        <option value="support"       {{ old('subject') === 'support' ? 'selected' : '' }}>Suport tehnic (sunt client)</option>
                        <option value="other"         {{ old('subject') === 'other' ? 'selected' : '' }}>Altceva</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium mb-1 block">Cum te putem ajuta?</span>
                    <textarea required name="message" rows="5"
                              placeholder="Povestește-ne scurt despre afacere și ce ai vrea să facă agentul AI…"
                              class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                              style="--tw-ring-color: var(--accent);">{{ old('message') }}</textarea>
                </label>

                <label class="flex items-start gap-2 text-xs" style="color: var(--muted);">
                    <input required type="checkbox" name="gdpr_consent" value="1" class="mt-0.5" {{ old('gdpr_consent') ? 'checked' : '' }}>
                    <span>Sunt de acord ca datele mele să fie procesate conform <a href="{{ route('new.legal.confidentialitate') }}" class="underline hover:text-ink">politicii de confidențialitate</a>. Nu le partajăm și nu trimitem newsletter fără acordul tău explicit.</span>
                </label>

                <button type="submit" class="btn btn-primary">
                    Trimite mesajul
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        {{-- Channels --}}
        <div class="md:col-span-5 space-y-4 fade-up" style="transition-delay:.1s;">
            <div class="rounded-3xl p-6 bg-cream border border-line">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--muted);">◇ e-mail</div>
                <a href="mailto:servus@sambla.ro" class="display text-2xl font-semibold hover:text-ink accent-text">servus@sambla.ro</a>
                <p class="text-sm mt-2" style="color: var(--muted);">Răspuns tipic: sub 4 ore în zilele lucrătoare.</p>
            </div>
            <div class="rounded-3xl p-6 bg-cream border border-line">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--muted);">◇ telefon</div>
                <a href="tel:+40775222333" class="display text-2xl font-semibold hover:text-ink accent-text">+40 775 222 333</a>
                <p class="text-sm mt-2" style="color: var(--muted);">Luni – Joi, 10:00 – 16:00 (ora României)</p>
            </div>
            <div class="rounded-3xl p-6 bg-cream border border-line">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--muted);">◇ adresă</div>
                <p class="display text-2xl font-semibold leading-tight">Bd. Dacia nr. 31</p>
                <p class="text-sm mt-1" style="color: var(--muted);">Oradea, România</p>
            </div>
            <div class="rounded-3xl p-6 bg-ink">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--sun);">◇ demo live</div>
                <p class="text-sm leading-relaxed mb-3" style="color:#D7D3CA;">Vrei să vezi agentul în acțiune, cu datele afacerii tale? Scrie-ne — îl configurăm împreună într-un apel de 20 min.</p>
                <a href="mailto:servus@sambla.ro?subject=Demo+Sambla" class="btn btn-primary" style="background: var(--sun); color: var(--ink);">Programează demo</a>
            </div>
        </div>
    </div>
</section>

{{-- FAQ — portat din legacy, reformulat fără nume de furnizori --}}
<section class="py-20 bg-cream border-t border-line">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12 fade-up">
            <div class="chip chip-soft mb-4 mono text-[11px] uppercase tracking-wider inline-flex">◇ întrebări frecvente</div>
            <h2 class="display h-display-lg mb-3">Ce ne întreabă <em class="italic accent-text">oamenii</em> cel mai des.</h2>
            <p class="text-lg" style="color: var(--muted);">Răspunsuri scurte. Dacă nu găsești ce cauți, scrie-ne mai sus.</p>
        </div>

        {{-- Despre platformă --}}
        <h3 class="mono text-[11px] uppercase tracking-wider mt-4 mb-3 accent-text">◇ despre platformă</h3>
        <div class="space-y-3 mb-10">
            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Ce este Sambla?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Sambla e o platformă românească de agenți AI pentru afaceri. Oferim agent pe chat pentru site și agent vocal pentru apeluri telefonice — ambii conectați la datele afacerii tale și disponibili 24/7, în limba română.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Ce poate face agentul AI pe chat?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Răspunde la întrebări bazat pe documentele tale (PDF, DOCX, CSV), caută produse și le afișează cu poze și prețuri, verifică statusul comenzilor cu tracking automat (FanCourier, Cargus, DPD, SameDay), captează lead-uri, detectează intenții și frustrare, și escaladează la operator uman când e nevoie.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Ce poate face agentul vocal?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Primește și inițiază apeluri cu voce naturală în română. Răspunde din baza de cunoștințe, caută produse, verifică comenzi, colectează date de contact și transferă la operator. Fiecare apel e transcris automat și analizat pentru sentiment.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Ce este clonarea vocii?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Poți folosi vocea brandului tău sau a unui coleg — dintr-o mostră audio scurtă. Agentul vocal vorbește exact cu vocea aleasă, creând o experiență consistentă cu identitatea afacerii. Suportă română nativ.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Ce tehnologie e în spate?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Folosim modele LLM de top și infrastructură telefonică de calitate enterprise. Platforma rutează inteligent în funcție de complexitatea întrebării — rapid pentru simple, mai atent pentru cele complexe. Hosting-ul și datele rămân pe servere din România.
                </div>
            </details>
        </div>

        {{-- Implementare --}}
        <h3 class="mono text-[11px] uppercase tracking-wider mt-4 mb-3 accent-text">◇ implementare</h3>
        <div class="space-y-3 mb-10">
            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Cât durează să fie live?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Agentul pe chat poate fi live în 10 minute — o singură linie de cod. Configurarea bazei de cunoștințe (documente, scanare site, conectori magazin) durează de obicei câteva ore. Agentul vocal cu număr telefonic dedicat necesită 1–2 zile (provisioning număr + configurare flux).
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Cum instalez agentul pe site?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Copiezi o linie de script și o adaugi înainte de <code class="mono text-[12px]" style="color: var(--ink);">&lt;/body&gt;</code>. Funcționează pe WordPress, Shopify, WooCommerce, site-uri custom — orice platformă care acceptă JavaScript.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Cum îl învăț despre afacerea mea?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Ai mai multe opțiuni: încarci documente (PDF, DOCX, CSV, TXT), adaugi URL-uri pe care le scanăm automat, conectezi magazinul WordPress/WooCommerce pentru sincronizare produse, sau scrii direct informațiile ca text. Agentul folosește tot ce îi dai ca sursă de adevăr.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Pot personaliza aspectul widget-ului?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da, complet. Schimbi culoarea principală (să se potrivească cu brandul), mesajul de bun venit, numele agentului, poziția pe ecran (stânga/dreapta) și tonul conversației (profesional, prietenos, premium etc.).
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Am nevoie de cunoștințe tehnice?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Nu. Dashboard-ul e intuitiv, iar instalarea agentului pe chat e copy-paste. Pentru integrări avansate (API, webhook-uri), avem documentație și suport tehnic în română.
                </div>
            </details>
        </div>

        {{-- E-Commerce --}}
        <h3 class="mono text-[11px] uppercase tracking-wider mt-4 mb-3 accent-text">◇ e-commerce</h3>
        <div class="space-y-3 mb-10">
            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Funcționează cu magazinul meu WooCommerce?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da, avem conector WooCommerce nativ. Se sincronizează automat cu catalogul (nume, descriere, preț, imagini, categorii, stoc) și cu comenzile. Agentul afișează produse cu poze și preț, permite adăugarea în coș și verifică statusul comenzilor.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Poate verifica o comandă?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da. Clientul întreabă „Unde e comanda mea?" și dă numărul, email-ul sau telefonul. Agentul caută comanda, afișează statusul, produsele comandate și link-ul de tracking AWB pentru FanCourier, Cargus, DPD, SameDay, GLS sau Urgent Cargus.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Pot măsura vânzările generate de agent?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da. Dashboard-ul de Commerce Analytics arată funnel-ul complet: câte produse afișate, câte click-uri, câte adăugări în coș, câte achiziții. Atribuirea funcționează pe 3 moduri: strict (directă), probabil și asistat.
                </div>
            </details>
        </div>

        {{-- Lead-uri --}}
        <h3 class="mono text-[11px] uppercase tracking-wider mt-4 mb-3 accent-text">◇ lead-uri și analiză</h3>
        <div class="space-y-3 mb-10">
            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Cum captează agentul lead-uri?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Folosește scoring cu peste 30 de semnale (număr de mesaje, interacțiuni cu produse, intenție de cumpărare) și cere datele de contact la momentul potrivit — nu agresiv. Lead-urile intră într-un pipeline cu 7 etape pe care îl gestionezi din dashboard.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Ce analize și rapoarte oferă platforma?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Număr conversații și trend pe 7 zile, cost per mesaj/conversație/agent, sentiment în timp real, funnel de vânzări, 40+ tipuri de evenimente per conversație, detecție de „knowledge gaps" (întrebări la care nu știe să răspundă) și export CSV/PDF.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Poate transfera la un om?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da. Când clientul cere operator sau când situația depășește ce poate rezolva singur, agentul creează automat un handoff cu rezumatul conversației, intențiile detectate și produsele discutate. Echipa ta primește notificare și preia conversația.
                </div>
            </details>
        </div>

        {{-- Securitate --}}
        <h3 class="mono text-[11px] uppercase tracking-wider mt-4 mb-3 accent-text">◇ securitate și date</h3>
        <div class="space-y-3 mb-10">
            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Este platforma conformă GDPR?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da. Colectăm date personale doar cu consimțământ explicit, hosting-ul e în România, datele fiecărui client sunt izolate complet (arhitectură multi-tenant) și poți șterge contul și toate datele asociate oricând.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Unde sunt stocate datele?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Toate datele sunt pe servere fizice din România. Comunicarea cu serviciile externe se face exclusiv prin conexiuni securizate (HTTPS/WSS). Nimic nu pleacă din UE fără necesitate tehnică explicită.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Cine are acces la conversațiile clienților mei?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Doar echipa ta. Platforma are 4 niveluri de acces (Admin, Manager, Viewer, Super Admin) cu permisiuni granulare. Datele fiecărui tenant sunt izolate complet — un client nu poate vedea datele altui client, nici măcar accidental.
                </div>
            </details>
        </div>

        {{-- Prețuri și suport --}}
        <h3 class="mono text-[11px] uppercase tracking-wider mt-4 mb-3 accent-text">◇ prețuri și suport</h3>
        <div class="space-y-3">
            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Cât costă platforma?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Planuri flexibile care pornesc de la gratuit pentru testare. Prețul depinde de volumul de mesaje, minute vocale și funcționalitățile necesare. Vezi <a href="{{ route('new.preturi') }}" class="underline accent-text">pagina de prețuri</a> pentru detalii sau scrie-ne pentru ofertă personalizată.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Există perioadă de probă gratuită?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da, poți testa platforma fără card. Creezi cont, configurezi agentul și vezi cum funcționează pe site înainte să alegi un plan plătit.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Oferiți suport în română?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Da, 100%. Echipa e românească. Suportul, documentația și comunicarea sunt în română. Scrie-ne la <a href="mailto:servus@sambla.ro" class="underline accent-text">servus@sambla.ro</a> sau sună la <a href="tel:+40775222333" class="underline accent-text">0775 222 333</a> (Luni–Joi, 10:00–16:00).
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Putem avea o demonstrație live?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Desigur. Completează formularul de mai sus sau scrie-ne la servus@sambla.ro. Programăm o demonstrație personalizată pe industria ta — ne uităm la site-ul tău, configurăm agentul cu datele tale și îți arătăm cum răspunde.
                </div>
            </details>

            <details class="group rounded-2xl bg-paper border border-line overflow-hidden">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-sand/30 transition-colors">
                    <h4 class="font-semibold">Câte limbi suportă agentul?</h4>
                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    10+ limbi, cu optimizare specială pentru română. Detectează automat limba clientului și răspunde în aceeași limbă. Limbile principale: română, engleză, germană, franceză, spaniolă, italiană, maghiară.
                </div>
            </details>
        </div>

    </div>
</section>

@endsection

@push('scripts')
<script>
    (function() {
        var form = document.getElementById('contact-form');
        if (!form) return;
        form.addEventListener('submit', function() {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'form_submit',
                form_name: 'contact',
            });
        });
    })();
</script>
@endpush
