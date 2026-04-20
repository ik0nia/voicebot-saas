@extends('layouts.new')

@section('title', 'Despre Sambla — construit în România pentru afacerile românești')
@section('meta_description', 'Sambla este o platformă SaaS românească de agenți AI conversaționali, construită cu grijă pentru limba română și pentru ritmul afacerilor din România.')
@section('canonical', url('/new/despre'))

@section('content')

<section class="hero-glow">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ despre noi</div>
        <h1 class="display h-display-xl mb-6">Construim <em class="italic accent-text">în România.</em><br>Pentru afaceri românești.</h1>
        <p class="text-xl leading-relaxed" style="color: var(--muted);">
            Sambla s-a născut dintr-o observație simplă: agenții AI făcuți pentru piața americană nu funcționează la fel de bine când îi pui să răspundă unui client din Cluj, la un cabinet, despre un detartraj. Am construit o platformă care vorbește românește nativ, înțelege contextul local și ține cont de realitatea zilnică a unei afaceri românești.
        </p>
    </div>
</section>

<section class="py-16 md:py-20 bg-paper">
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-10">
        <div class="fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ misiunea</div>
            <h2 class="display h-display-m mb-4">Niciun client pierdut<br>pentru că nu a răspuns nimeni.</h2>
            <p class="leading-relaxed" style="color: var(--muted);">Știm cum arată ora de vârf la un cabinet, la un service, la un salon. Știm ce înseamnă să pierzi un client pentru că recepția era ocupată cu alt pacient. Sambla preia exact partea asta — răspunde, programează, calmează, explică — ca tu să te poți concentra pe munca adevărată.</p>
        </div>
        <div class="fade-up" style="transition-delay:.1s;">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ cum gândim</div>
            <h2 class="display h-display-m mb-4">Calitate, nu trucuri.</h2>
            <p class="leading-relaxed" style="color: var(--muted);">Refuzăm să promitem ce nu putem livra. Un agent AI bun este unul care răspunde doar când știe și care cere ajutor când nu. Pe asta ne punem reputația — nu pe demo-uri care arată bine la vânzare și cedează în producție.</p>
        </div>
    </div>
</section>

<section class="py-16 md:py-20">
    <div class="max-w-5xl mx-auto px-6">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ valori</div>
            <h2 class="display h-display-l">Principii pe care<br><span class="italic">le simți în produs.</span></h2>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach([
                ['Limba română, nativ', 'Nu traducem. Agenții noștri gândesc românește — diacritice, regionalism, politețe. Se simte diferența.'],
                ['Onestitate față de clienții tăi', 'Agentul nu inventează. Când nu știe, spune onest. E singura cale să îți protejeze reputația pe termen lung.'],
                ['Proprietatea datelor tale', 'Datele afacerii tale sunt ale tale. Le poți exporta, șterge, migra oricând. GDPR by default, nu ca adăugire.'],
                ['Investiție în timpul tău', 'Un setup bun durează ore, nu săptămâni. Iar fiecare conversație automată este o oră liberă pentru echipa ta.'],
            ] as $v)
                <div class="fade-up rounded-3xl p-7 bg-paper border border-line">
                    <h3 class="display text-xl font-semibold mb-2">{{ $v[0] }}</h3>
                    <p class="leading-relaxed" style="color: var(--muted);">{{ $v[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-paper">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ echipa</div>
        <h2 class="display h-display-l mb-5">Oameni reali.<br><span class="italic accent-text">La o comandă distanță.</span></h2>
        <p class="text-lg leading-relaxed mb-8" style="color: var(--muted);">
            Suntem o echipă restrânsă din România, cu experiență în produse software și operațiuni digitale. Nu suntem un call-center deghizat în AI. Dacă ai o întrebare tehnică, răspunde cineva care scrie cod, nu un ticket handler.
        </p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ route('new.contact') }}" class="btn btn-primary">Scrie-ne</a>
            <a href="mailto:servus@sambla.ro" class="btn btn-outline">servus@sambla.ro</a>
        </div>
    </div>
</section>

@endsection
