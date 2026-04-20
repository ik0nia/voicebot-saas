@extends('layouts.new')

@section('title', 'De ce Sambla — diferența față de alte soluții AI')
@section('meta_description', 'De ce aleg afacerile Sambla: nativ românesc, onest când nu știe, construit în România, GDPR by default, fără promisiuni goale. Compară pe bune.')
@section('canonical', url('/new/de-ce-sambla'))

@section('content')

<section class="hero-glow">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ de ce Sambla</div>
        <h1 class="display h-display-xl mb-6">Diferența <em class="italic accent-text">se aude.</em></h1>
        <p class="text-xl max-w-3xl mx-auto leading-relaxed" style="color: var(--muted);">Există destule soluții AI pe piață. Le-am testat pe majoritatea. Iată de ce afacerile românești aleg Sambla — și unde nu suntem încă cea mai bună opțiune (ca să știi din start).</p>
    </div>
</section>

<section class="py-16 md:py-20">
    <div class="max-w-6xl mx-auto px-6 space-y-10">

        @foreach([
            [
                'Nativ românesc, nu tradus',
                'Majoritatea agenților AI de pe piață au fost antrenați în engleză și apoi „localizați". Simți imediat — răspund rigid, ratează idiomuri, pun diacritice la întâmplare. Sambla gândește românește de la prima propoziție. Așa ar vorbi cineva din echipa ta, nu un asistent de call center offshore.',
            ],
            [
                'Nu inventează. Niciodată.',
                'Pentru a economisi costuri, multe soluții lasă AI-ul să „completeze" informațiile lipsă. Tu descoperi abia când un client îți zice „mi-a spus că livrați sâmbăta" — ceea ce nu este adevărat. Sambla este construit cu reguli stricte: dacă informația nu e în documentele tale, agentul spune onest „nu am această informație" și te transferă.',
            ],
            [
                'GDPR by default, nu ca bifă',
                'Nu e suficient să scrii „suntem GDPR compliant" pe site. Trebuie să poți demonstra cum stochezi, cine are acces, cum ștergi. Sambla: DPA gata de semnat, audit trail per conversație, control granular pe retenție. Fără promisiuni de marketing, doar documentație.',
            ],
            [
                'Suport uman, în limba română, de către cineva care scrie cod',
                'Când ai nevoie de ajutor serios, nu vorbești cu un chatbot despre produsul tău de chatbot. Vorbești cu un om din echipa noastră, în română, care înțelege exact ce se întâmplă și poate să rezolve.',
            ],
            [
                'Construit să facă bani, nu să impresioneze',
                'Sambla nu are demo-uri spectaculoase cu agenți care „rezolvă orice". Are agenți care programează, vând, captează lead-uri — și o echipă care măsoară impactul real în clienți noi câștigați și ore libere pentru echipa ta.',
            ],
        ] as $i => $point)
            <div class="fade-up rounded-3xl p-8 md:p-10 bg-paper border border-line grid md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-1">
                    <div class="display text-6xl font-semibold accent-text">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="md:col-span-11">
                    <h2 class="display h-display-m mb-3">{{ $point[0] }}</h2>
                    <p class="text-lg leading-relaxed" style="color: var(--muted);">{{ $point[1] }}</p>
                </div>
            </div>
        @endforeach

        <div class="fade-up rounded-3xl p-8 md:p-10 bg-ink grid md:grid-cols-12 gap-6 items-start">
            <div class="md:col-span-1">
                <div class="display text-6xl font-semibold" style="color: var(--sun);">—</div>
            </div>
            <div class="md:col-span-11">
                <h2 class="display h-display-m mb-3" style="color: var(--cream);">Unde NU suntem încă cea mai bună alegere</h2>
                <p class="text-lg leading-relaxed mb-4" style="color:#D7D3CA;">Dacă ai nevoie astăzi de:</p>
                <ul class="space-y-2 text-lg" style="color:#D7D3CA;">
                    <li class="flex gap-3"><span style="color: var(--sun);">—</span>Suport pentru mai mult de 2 limbi în aceeași conversație (azi: română + engleză)</li>
                    <li class="flex gap-3"><span style="color: var(--sun);">—</span>Integrări out-of-the-box cu CRM-uri enterprise internaționale (încă nu sunt live — putem discuta pe caz)</li>
                    <li class="flex gap-3"><span style="color: var(--sun);">—</span>Volum de peste 100.000 de conversații pe zi (putem discuta un plan enterprise)</li>
                </ul>
                <p class="mt-4" style="color:#A8A29E;">… atunci probabil nu suntem cea mai potrivită alegere chiar acum. Spune-ne ce îți lipsește — poate o construim împreună.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-20 text-center">
    <div class="max-w-3xl mx-auto px-6">
        <h2 class="display h-display-l mb-5">Vezi tu însuți.</h2>
        <p class="text-lg mb-8" style="color: var(--muted);">7 zile gratuit, fără card. Îl pornești în 10 minute.</p>
        <a href="{{ url('/register') }}" class="btn btn-primary">Începe gratuit</a>
    </div>
</section>

@endsection
