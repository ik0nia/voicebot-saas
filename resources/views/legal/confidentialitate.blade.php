@extends('layouts.app')

@section('title', 'Politica de confidențialitate — Sambla')
@section('meta_description', 'Cum colectează, folosește și protejează Sambla datele tale personale.')

@section('content')
<section class="bg-white py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-slate prose-lg">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900">Politica de confidențialitate</h1>
        <p class="text-sm text-slate-500">Ultima actualizare: aprilie 2026</p>

        <p>Sambla respectă confidențialitatea datelor tale. Această politică explică ce date colectăm, de ce și cum le protejăm, în conformitate cu Regulamentul General privind Protecția Datelor (GDPR).</p>

        <h2>1. Operatorul de date</h2>
        <p><strong>Sambla</strong>, cu sediul în România, este operatorul datelor cu caracter personal colectate prin sambla.ro. Pentru orice cerere legată de date, contactează-ne la <a href="mailto:servus@sambla.ro">servus@sambla.ro</a>.</p>

        <h2>2. Ce date colectăm</h2>
        <ul>
            <li><strong>Date de cont</strong>: nume, email, parolă (criptată), număr de telefon</li>
            <li><strong>Date de utilizare</strong>: log-uri de acces, IP, browser, pagini vizitate</li>
            <li><strong>Date de plată</strong>: procesate prin Stripe; nu stocăm cardul</li>
            <li><strong>Conținutul tău</strong>: documente, prompt-uri, conversații generate de bot</li>
            <li><strong>Cookies</strong>: vezi politica de cookies</li>
        </ul>

        <h2>3. De ce le folosim</h2>
        <ul>
            <li>Pentru a-ți livra serviciul (configurare bot, hosting, conversații)</li>
            <li>Pentru facturare și suport</li>
            <li>Pentru îmbunătățirea platformei (analize anonime de utilizare)</li>
            <li>Pentru notificări importante (contractuale și de siguranță)</li>
            <li>Pentru a respecta obligațiile legale</li>
        </ul>

        <h2>4. Unde stocăm datele</h2>
        <p>Datele tale sunt stocate pe servere localizate <strong>în România</strong>. Nu transferăm date în afara Uniunii Europene. Backup-urile sunt criptate și păstrate pentru maxim 30 de zile.</p>

        <h2>5. Cu cine partajăm datele</h2>
        <p>Nu vindem și nu închiriem datele tale. Le împărtășim doar cu procesatori de date strict necesari:</p>
        <ul>
            <li><strong>Stripe</strong> — procesare plăți</li>
            <li><strong>OpenAI / Google Vertex AI</strong> — procesare conversații AI (datele nu sunt folosite pentru antrenare)</li>
            <li><strong>Telnyx</strong> — apeluri telefonice</li>
            <li><strong>Sentry</strong> — diagnosticare erori (date anonimizate)</li>
        </ul>

        <h2>6. Drepturile tale GDPR</h2>
        <p>Ai dreptul să:</p>
        <ul>
            <li>Acceses datele pe care le avem despre tine</li>
            <li>Corectezi date inexacte</li>
            <li>Soliciți ștergerea contului și a datelor („dreptul de a fi uitat")</li>
            <li>Exporți datele tale într-un format portabil</li>
            <li>Te opui anumitor procesări</li>
            <li>Depui plângere la Autoritatea Națională de Supraveghere a Prelucrării Datelor cu Caracter Personal (ANSPDCP)</li>
        </ul>
        <p>Pentru a-ți exercita oricare dintre aceste drepturi, scrie-ne la <a href="mailto:servus@sambla.ro">servus@sambla.ro</a>.</p>

        <h2>7. Securitate</h2>
        <p>Folosim măsuri tehnice și organizatorice pentru a-ți proteja datele: criptare în tranzit (TLS), criptare la repaus pe baza de date, izolare per cont, log-uri de acces, audit periodic.</p>

        <h2>8. Retenție</h2>
        <p>Păstrăm datele atât timp cât contul e activ. La închiderea contului, datele sunt șterse în maxim 30 de zile, cu excepția celor cerute de lege (ex: facturi — 10 ani).</p>

        <h2>9. Modificări</h2>
        <p>Vom actualiza această politică când apar modificări semnificative. Te vom notifica prin email pentru schimbări majore.</p>

        <h2>10. Contact</h2>
        <p>Pentru orice întrebare: <a href="mailto:servus@sambla.ro">servus@sambla.ro</a></p>
    </div>
</section>
@endsection
