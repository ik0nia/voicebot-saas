@extends('layouts.new')

@section('title', 'Ce face Sambla — funcționalități complete pentru agenți AI')
@section('meta_description', 'Agent AI pe site și agent AI vocal — răspunsuri din documentele tale, programări automate în calendar, lead capture, recomandări produse, GDPR nativ. Vezi lista completă.')
@section('canonical', url('/new/functionalitati'))

@section('content')

<section class="hero-glow relative overflow-hidden">
    <div class="max-w-5xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ funcționalități</div>
        <h1 class="display h-display-xl mb-6 fade-up">Tot ce face<br><span class="italic accent-text">agentul tău AI.</span></h1>
        <p class="text-xl leading-relaxed max-w-3xl mx-auto fade-up" style="color: var(--muted); transition-delay:.1s;">Construit pentru afacerile românești. Răspunde clienților 24/7, face programări în calendar, recomandă produse, captează lead-uri — fără să inventeze, fără să promită ce nu poate.</p>
    </div>
</section>

{{-- Pillars: canale, inteligență, operațional, integrări --}}
<section class="py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-6 space-y-16">

        {{-- CANALE --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ canale</div>
                <h2 class="display h-display-l">Un creier.<br><span class="italic">Oriunde vorbești cu clientul.</span></h2>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    ['Chat pe site', 'Widget premium, o linie de cod. Recomandă produse, preia lead-uri, trimite link-uri.', '💬'],
                    ['Telefon', 'Agent vocal pe numere românești dedicate. Voce naturală, clientul poate întrerupe, transcriere în timp real a fiecărui apel.', '📞'],
                    ['WhatsApp', 'Inbound și outbound. Conectare directă WhatsApp Business.', '💚'],
                    ['Messenger · Instagram', 'Facebook Messenger și Instagram DM — inbound din dashboard.', '✨'],
                ] as $c)
                    <div class="fade-up rounded-3xl p-6 bg-paper border border-line">
                        <div class="w-11 h-11 rounded-xl accent-soft-bg flex items-center justify-center mb-4 text-xl">{{ $c[2] }}</div>
                        <h3 class="display text-lg font-semibold mb-2">{{ $c[0] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $c[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- INTELIGENȚĂ --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ inteligență</div>
                <h2 class="display h-display-l">Răspunde <em class="italic accent-text">ce știe</em> — nu ce pare.</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach([
                    ['Antrenat pe conținutul tău', 'PDF-uri, pagini web, catalog de produse, politici interne — fiecare răspuns are o sursă citabilă.'],
                    ['Politici anti-halucinare', 'Reguli stricte de confidence și prudență pe subiecte sensibile. Când nu știe, recunoaște onest.'],
                    ['Învăț automat din conversații', 'Întrebările pe care nu le acoperi în documente devin sugestii pentru baza ta de cunoștințe.'],
                    ['Ton și personalitate ajustabile', 'De la formal la prietenos. Configurabil pe fiecare canal separat.'],
                    ['Detectare frustrare și escaladare', 'Transferă la operator uman când clientul are nevoie, cu tot contextul conversației.'],
                    ['Multilingv: română și engleză', 'Detectează automat limba clientului. Fără accent robotic, fără traduceri stângace.'],
                ] as $f)
                    <div class="fade-up rounded-3xl p-6 bg-cream border border-line">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 rounded-lg accent-soft-bg flex items-center justify-center">
                                <svg class="w-4 h-4 accent-text" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                        <h3 class="display text-base font-semibold mb-2 leading-tight">{{ $f[0] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $f[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- OPERAȚIONAL --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ operațional</div>
                <h2 class="display h-display-l">Transformă conversații<br><span class="italic accent-text">în rezultate.</span></h2>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['Programări în calendar', 'Sincronizare Google Calendar, slot-uri libere în timp real, confirmare SMS/e-mail.'],
                    ['Lead capture automat', 'Nume, telefon, context — extrase din conversație, cu scoring de intenție.'],
                    ['Pipeline CRM integrat', 'Stadii configurabile pentru lead-uri și oportunități, cu notificări pe echipă.'],
                    ['Callback widget', 'Formular de cerere retur apel, cu consimțământ GDPR explicit.'],
                    ['Recomandări produse', 'Carduri de produs din magazin, cu preț și stoc live.'],
                    ['Dashboard analitice', 'Conversații, apeluri, lead-uri, conversii — într-un singur loc.'],
                ] as $f)
                    <div class="fade-up rounded-3xl p-6 bg-paper border border-line">
                        <h3 class="display text-base font-semibold mb-2 leading-tight">{{ $f[0] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $f[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- INTEGRĂRI live --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ integrări</div>
                <h2 class="display h-display-l">Se conectează la<br><span class="italic accent-text">uneltele tale curente.</span></h2>
                <p class="text-lg mt-4" style="color: var(--muted);">Listăm doar integrările live pe care le poți activa astăzi. Dacă ai nevoie de ceva care nu e aici, ne scrii — adăugăm la cerere.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['WooCommerce', 'Plugin oficial. Sincronizare automată produse, stoc și categorii. Recomandări în conversație.'],
                    ['WordPress', 'Instalare widget în 2 click-uri prin plugin. Funcționează cu orice temă.'],
                    ['Google Calendar', 'OAuth per cont. Agentul vede slot-urile libere și programează direct în calendar.'],
                    ['WhatsApp Business', 'Conectare inbound + outbound. Mesajele curg prin același dashboard.'],
                    ['Facebook Messenger', 'Inbound pentru pagina ta — răspunsuri din aceeași bază de cunoștințe.'],
                    ['Instagram DM', 'Inbound pentru contul de business. Conversații centralizate în inbox.'],
                    ['Stripe', 'Facturare abonamente + top-up credite. Facturi automate în contabilitate.'],
                    ['Google Drive', 'Import documente direct dintr-un folder de drive pentru baza de cunoștințe.'],
                    ['API + webhooks', 'Integrări custom prin API REST și event webhooks — pentru CRM-ul tău intern.'],
                ] as $i)
                    <div class="fade-up rounded-2xl p-5 bg-cream border border-line">
                        <h3 class="display text-base font-semibold mb-2 leading-tight">{{ $i[0] }}</h3>
                        <p class="text-xs leading-relaxed" style="color: var(--muted);">{{ $i[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</section>

{{-- CTA --}}
<section class="py-20">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="display h-display-l mb-5">Vezi cum arată pentru <em class="italic accent-text">afacerea ta</em>.</h2>
        <p class="text-lg mb-8" style="color: var(--muted);">Alegi industria, agentul preia instant tonul potrivit.</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ url('/register') }}" class="btn btn-primary">Începe gratuit</a>
            <a href="{{ route('new.home') }}#industrii" class="btn btn-outline">Vezi industriile</a>
        </div>
    </div>
</section>

@endsection
