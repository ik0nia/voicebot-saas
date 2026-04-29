@extends('layouts.new')

@section('title', 'Termeni și condiții — Sambla')
@section('meta_description', 'Termenii și condițiile de utilizare a platformei Sambla pentru agenți AI conversaționali.')
@section('canonical', url('/termeni'))

@section('content')

<section class="max-w-3xl mx-auto px-6 pt-14 pb-20 leading-relaxed">
    <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ document legal</div>
    <h1 class="display h-display-l mb-8">Termeni și condiții</h1>
    <p class="text-sm mb-10" style="color: var(--muted);">Ultima actualizare: {{ date('j F Y') }}</p>

    <div class="prose prose-lg" style="color: var(--ink);">
        <h2 class="display text-2xl font-semibold mt-8 mb-3">1. Cine suntem</h2>
        <p>Sambla este o platformă SaaS de agenți AI conversaționali operată de Sambla SRL, cu sediul în România. Prin accesarea sau folosirea serviciului, accepți termenii descriși aici.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">2. Serviciul oferit</h2>
        <p>Sambla oferă acces la o platformă software care permite crearea, configurarea și operarea de agenți AI conversaționali pe canalele proprii ale clientului (site, telefon, mesagerie). Serviciul include hosting, mentenanță, actualizări și suport.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">3. Conturi și acces</h2>
        <p>Ești responsabil pentru confidențialitatea credențialelor contului tău și pentru orice activitate care are loc prin contul tău. Ne anunți imediat dacă suspectezi acces neautorizat.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">4. Plăți și facturare</h2>
        <p>Abonamentele se facturează lunar sau anual, în avans, în lei românești. TVA 19% se adaugă conform legii. Plățile se procesează prin furnizorul nostru de plăți. Neplata rezultă în suspendarea serviciului după 7 zile calendaristice.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">5. Proprietatea datelor</h2>
        <p>Datele pe care le introduci în platformă (documente, conversații, produse, liste clienți) rămân proprietatea ta. Le poți exporta sau șterge oricând. Noi procesăm aceste date strict pentru a-ți furniza serviciul, conform politicii de confidențialitate.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">6. Responsabilitatea conținutului</h2>
        <p>Răspunzi pentru acuratețea și legalitatea conținutului pe care îl încarci. Nu încărca conținut care încalcă drepturi de autor, legi GDPR sau alte reglementări aplicabile.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">7. Limitări ale serviciului</h2>
        <p>Agenții AI sunt instrumente care pot, ocazional, să furnizeze răspunsuri imperfecte. Recomandăm monitorizarea conversațiilor în primele săptămâni și ajustarea configurării. Nu garantăm răspunsuri perfecte în 100% din cazuri — dar garantăm că agentul va recunoaște când nu știe și va transfera la tine.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">8. Anulare</h2>
        <p>Poți anula abonamentul oricând, cu efect de la finalul perioadei curente. Datele rămân accesibile 30 de zile după anulare, apoi se șterg permanent. Poți solicita export anticipat.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">9. Modificări ale termenilor</h2>
        <p>Ne rezervăm dreptul de a actualiza acești termeni. Te vom notifica pe e-mail cu cel puțin 30 de zile înainte de o schimbare materială.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">10. Contact</h2>
        <p>Pentru orice întrebare legală: <a href="mailto:legal@sambla.ro" class="accent-text underline">legal@sambla.ro</a>.</p>
    </div>
</section>

@endsection
