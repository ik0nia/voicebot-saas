@extends('layouts.app')

@section('title', 'Politica de cookie-uri — Sambla')
@section('meta_description', 'Ce cookies folosește Sambla și cum le poți gestiona.')

@section('content')
<section class="bg-white py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-slate prose-lg">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900">Politica de cookie-uri</h1>
        <p class="text-sm text-slate-500">Ultima actualizare: aprilie 2026</p>

        <h2>Ce sunt cookie-urile?</h2>
        <p>Cookie-urile sunt fișiere text mici stocate de browser pe dispozitivul tău când vizitezi un site. Ele permit site-ului să rețină informații despre vizita ta (preferințe, sesiune autentificată, etc.) pentru a îmbunătăți experiența.</p>

        <h2>Ce cookies folosim</h2>

        <h3>Cookies strict necesare</h3>
        <p>Aceste cookies sunt esențiale pentru funcționarea site-ului și nu pot fi dezactivate.</p>
        <ul>
            <li><strong>voicebot_saas_session</strong> — păstrează sesiunea ta autentificată (durată: 2 ore)</li>
            <li><strong>XSRF-TOKEN</strong> — protecție împotriva atacurilor CSRF (durată: 2 ore)</li>
        </ul>

        <h3>Cookies funcționale</h3>
        <p>Folosite pentru a reține preferințele tale de utilizare.</p>
        <ul>
            <li><strong>theme</strong> — preferința de temă (light/dark)</li>
            <li><strong>locale</strong> — limba preferată</li>
        </ul>

        <h3>Cookies de la terți</h3>
        <p>Anumite servicii integrate pot seta propriile cookies când le folosim:</p>
        <ul>
            <li><strong>Stripe</strong> — la procesarea plăților</li>
        </ul>
        <p>Nu folosim cookies de tracking publicitar sau de profilare comportamentală.</p>

        <h2>Cum gestionezi cookie-urile</h2>
        <p>Poți controla cookies din setările browser-ului tău:</p>
        <ul>
            <li><strong>Chrome</strong>: Setări → Confidențialitate și securitate → Cookies</li>
            <li><strong>Firefox</strong>: Preferințe → Confidențialitate și securitate</li>
            <li><strong>Safari</strong>: Preferințe → Confidențialitate</li>
            <li><strong>Edge</strong>: Setări → Cookies și permisiuni site</li>
        </ul>
        <p>Reține că dezactivarea cookies-urilor strict necesare poate afecta funcționarea contului tău.</p>

        <h2>Modificări</h2>
        <p>Această politică poate fi actualizată. Verifică data ultimei modificări de mai sus.</p>

        <h2>Contact</h2>
        <p>Pentru întrebări: <a href="mailto:servus@sambla.ro">servus@sambla.ro</a></p>
    </div>
</section>
@endsection
