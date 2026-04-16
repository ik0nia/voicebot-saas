@extends('layouts.app')

@section('title', 'Termeni și condiții — Sambla')
@section('meta_description', 'Termenii și condițiile de utilizare a platformei Sambla.')

@section('content')
<section class="bg-white py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-slate prose-lg">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900">Termeni și condiții</h1>
        <p class="text-sm text-slate-500">Ultima actualizare: aprilie 2026</p>

        <h2>1. Acceptarea termenilor</h2>
        <p>Prin accesarea și utilizarea platformei Sambla (sambla.ro), confirmi că ai citit, ai înțeles și ești de acord cu acești termeni. Dacă nu ești de acord, te rugăm să nu folosești serviciul.</p>

        <h2>2. Descrierea serviciului</h2>
        <p>Sambla este o platformă SaaS care oferă agenți AI conversaționali (agent AI și agent AI vocal) pentru afaceri. Serviciul include unelte de configurare, hosting, integrare cu canale de comunicare și analize.</p>

        <h2>3. Cont și securitate</h2>
        <p>Ești responsabil pentru păstrarea în siguranță a credențialelor contului tău. Orice activitate desfășurată prin contul tău este responsabilitatea ta. Anunță-ne imediat la <a href="mailto:servus@sambla.ro">servus@sambla.ro</a> dacă suspectezi acces neautorizat.</p>

        <h2>4. Utilizare acceptabilă</h2>
        <p>Te angajezi să nu folosești Sambla pentru:</p>
        <ul>
            <li>Activități ilegale sau care încalcă drepturile altora</li>
            <li>Trimiterea de spam sau conținut abuziv</li>
            <li>Reverse engineering, scraping nepermis sau atacuri asupra infrastructurii</li>
            <li>Conținut care încalcă proprietatea intelectuală</li>
        </ul>

        <h2>5. Plată și abonamente</h2>
        <p>Planurile plătite se reînnoiesc automat la sfârșitul perioadei de facturare. Poți anula oricând din contul tău. Plățile sunt procesate prin furnizori terți (ex: Stripe).</p>

        <h2>6. Conținutul tău</h2>
        <p>Păstrezi toate drepturile asupra conținutului pe care îl încarci în platformă (documente, prompt-uri, date). Sambla îl folosește exclusiv pentru a-ți livra serviciul și nu îl partajează cu terți.</p>

        <h2>7. Disponibilitatea serviciului</h2>
        <p>Ne străduim să menținem serviciul disponibil 24/7, dar nu garantăm 100% uptime. Putem face mentenanță programată anunțată în prealabil.</p>

        <h2>8. Limitarea răspunderii</h2>
        <p>Sambla este oferit „așa cum este". În măsura permisă de lege, nu suntem răspunzători pentru pagube indirecte rezultate din utilizarea platformei.</p>

        <h2>9. Modificarea termenilor</h2>
        <p>Putem actualiza acești termeni periodic. Modificările intră în vigoare la publicarea pe această pagină. Folosirea continuă a serviciului înseamnă acceptarea termenilor actualizați.</p>

        <h2>10. Lege aplicabilă</h2>
        <p>Acești termeni sunt guvernați de legislația română. Orice litigiu va fi soluționat de instanțele competente din România.</p>

        <h2>11. Contact</h2>
        <p>Pentru orice întrebare despre termeni, scrie-ne la <a href="mailto:servus@sambla.ro">servus@sambla.ro</a>.</p>
    </div>
</section>
@endsection
