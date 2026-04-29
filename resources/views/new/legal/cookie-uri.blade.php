@extends('layouts.new')

@section('title', 'Politica de cookie-uri — Sambla')
@section('meta_description', 'Ce cookie-uri folosim pe sambla.ro, de ce și cum le poți controla.')
@section('canonical', url('/cookie-uri'))

@section('content')

<section class="max-w-3xl mx-auto px-6 pt-14 pb-20 leading-relaxed">
    <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ document legal</div>
    <h1 class="display h-display-l mb-8">Politica de cookie-uri</h1>
    <p class="text-sm mb-10" style="color: var(--muted);">Ultima actualizare: {{ date('j F Y') }}</p>

    <div class="prose prose-lg">
        <h2 class="display text-2xl font-semibold mt-8 mb-3">Ce sunt cookie-urile</h2>
        <p>Cookie-urile sunt fișiere mici de text pe care site-ul le salvează în browserul tău pentru a reține preferințe, statistici și sesiunea de utilizare.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Ce tipuri folosim</h2>
        <ul class="list-disc ml-6 space-y-3">
            <li><strong>Strict necesare</strong> — sesiune, autentificare, protecție CSRF, preferință consimțământ. Nu pot fi dezactivate fără a afecta funcționarea site-ului.</li>
            <li><strong>Analitice</strong> — măsurăm cum e folosit site-ul ca să îl îmbunătățim. Se activează doar cu consimțământ.</li>
            <li><strong>Marketing</strong> — optimizare reclame și remarketing. Se activează doar cu consimțământ.</li>
            <li><strong>Funcționalitate</strong> — reținerea preferințelor (temă, limbă). Se activează doar cu consimțământ.</li>
        </ul>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Cum controlezi cookie-urile</h2>
        <p>Când vizitezi prima dată site-ul, apare un banner unde poți accepta, refuza sau personaliza. Îți poți schimba preferințele oricând — click pe butonul de consimțământ din footer.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Cookie-uri terțe</h2>
        <p>Folosim servicii analitice operate de parteneri (de ex. Google Analytics în mod anonimizat). Aceste servicii pot seta propriile cookie-uri. Le poți dezactiva individual din panoul de consimțământ.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Dezactivare la nivel de browser</h2>
        <p>Majoritatea browserelor permit blocarea cookie-urilor din setări. Atenție: blocarea tuturor cookie-urilor poate afecta funcționarea site-ului (autentificare, formulare).</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Contact</h2>
        <p>Pentru întrebări: <a href="mailto:dpo@sambla.ro" class="accent-text underline">dpo@sambla.ro</a>.</p>
    </div>
</section>

@endsection
