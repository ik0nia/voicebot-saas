@extends('layouts.app')

@section('title', 'Politica de confidențialitate — Sambla')
@section('meta_description', 'Cum colectează, folosește și protejează Sambla datele tale personale (GDPR).')

@section('content')
<section class="bg-white py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-slate prose-lg">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900">Politica de confidențialitate</h1>
        <p class="text-sm text-slate-500">Ultima actualizare: {{ date('d F Y') }}</p>

        <p>Sambla respectă confidențialitatea datelor tale. Acest document explică ce date colectăm, de ce, cu cine le împărtășim, cât timp le ținem și ce drepturi ai, în conformitate cu <strong>Regulamentul General privind Protecția Datelor (GDPR — 2016/679)</strong> și Directiva ePrivacy (2002/58/CE).</p>

        <h2>1. Operatorul de date</h2>
        <p><strong>Sambla</strong>, cu sediul în România, este operatorul datelor cu caracter personal colectate prin sambla.ro și prin agenții AI operați de noi. Pentru orice cerere legată de date: <a href="mailto:servus@sambla.ro">servus@sambla.ro</a>.</p>

        <h2>2. Ce date colectăm</h2>
        <h3>2.1 Dacă ești vizitator pe sambla.ro</h3>
        <ul>
            <li><strong>Tehnice</strong>: adresă IP (anonimizată la 24 ore — ultima grupă zeroată), browser, referrer, pagini vizitate</li>
            <li><strong>Marketing attribution</strong>: parametri UTM (utm_source, utm_medium, utm_campaign, utm_term, utm_content) + click IDs (gclid, fbclid, msclkid, ttclid, li_fat_id) atunci când sosești prin reclame</li>
            <li><strong>Evenimente</strong>: click pe linkuri tel:/mailto:/social, scroll, timp de engagement, deschidere widget</li>
        </ul>
        <h3>2.2 Dacă ai cont Sambla</h3>
        <ul>
            <li><strong>Cont</strong>: nume, email, parolă (hash), număr de telefon, rol, tenant</li>
            <li><strong>Plată</strong>: procesate exclusiv prin Stripe; noi NU stocăm cardul</li>
            <li><strong>Conținut</strong>: documente, prompt-uri, conversații, apeluri, recomandări produse</li>
        </ul>
        <h3>2.3 Dacă trimiți formularul de contact sau completezi un landing</h3>
        <ul>
            <li>Nume, email, telefon, companie, mesaj</li>
            <li>Pagina de pe care ai trimis (source_url) + atribuire campanie</li>
        </ul>
        <h3>2.4 Dacă vorbești cu un agent AI (chat sau voce)</h3>
        <ul>
            <li>Mesajele schimbate cu agentul (text sau transcript audio)</li>
            <li>Pentru apeluri vocale: durata, număr de telefon (anonimizat la 30 zile), înregistrare audio (ștearsă la 30 zile)</li>
            <li>Metadate: bot-ul folosit, canalul, datele colectate explicit de tine (dacă lași lead)</li>
        </ul>

        <h2>3. Temeiul legal</h2>
        <ul>
            <li><strong>Executarea contractului</strong> (art. 6(1)(b)) — pentru furnizarea serviciului</li>
            <li><strong>Interes legitim</strong> (art. 6(1)(f)) — logare tehnică, securitate, prevenirea fraudei, atribuirea de marketing pentru lead-urile noastre</li>
            <li><strong>Obligații legale</strong> (art. 6(1)(c)) — facturare (10 ani), contabilitate</li>
            <li><strong>Consimțământ</strong> (art. 6(1)(a)) — pentru cookies non-necesare (analitice / marketing / personalizare), gestionate prin bannerul de cookie cu Consent Mode v2</li>
        </ul>

        <h2>4. Cât timp păstrăm datele</h2>
        <table>
            <thead><tr><th>Categorie</th><th>Retenție</th></tr></thead>
            <tbody>
                <tr><td>IP vizitator (logs, atribuire, consent)</td><td>Anonimizat la 24 ore</td></tr>
                <tr><td>Conversații chat</td><td>90 zile (apoi șterse)</td></tr>
                <tr><td>Înregistrări apeluri voce</td><td>30 zile</td></tr>
                <tr><td>Număr telefon apelant</td><td>Anonimizat la 30 zile</td></tr>
                <tr><td>Lead-uri contact + landing</td><td>3 ani pentru follow-up comercial (interes legitim), apoi anonimizate</td></tr>
                <tr><td>Cont utilizator (după dezactivare)</td><td>30 zile</td></tr>
                <tr><td>Facturi + date contabile</td><td>10 ani (cerință legală)</td></tr>
                <tr><td>Log-uri de audit consent</td><td>3 ani</td></tr>
                <tr><td>Backup-uri</td><td>Maxim 30 zile, criptate</td></tr>
            </tbody>
        </table>

        <h2>5. Cu cine partajăm datele (procesatori / sub-procesatori)</h2>
        <p>Nu vindem datele tale. Le trimitem către procesatori strict necesari, pe baza clauzelor contractuale standard (SCC) unde transferul părăsește UE:</p>
        <table>
            <thead><tr><th>Furnizor</th><th>Scop</th><th>Locație</th><th>Garanție</th></tr></thead>
            <tbody>
                <tr><td><strong>OpenAI</strong></td><td>Generare răspunsuri AI (chat + voce)</td><td>SUA</td><td>SCC + DPA (datele nu sunt folosite pentru antrenare model)</td></tr>
                <tr><td><strong>Twilio</strong></td><td>Telefonie — apeluri voce</td><td>SUA + UE</td><td>SCC + DPA</td></tr>
                <tr><td><strong>Google (GA4, Tag Manager, Ads)</strong></td><td>Analiză trafic + campanii</td><td>SUA (EU-US DPF)</td><td>EU-US Data Privacy Framework + SCC</td></tr>
                <tr><td><strong>Meta (Pixel + CAPI)</strong></td><td>Remarketing + măsurare conversii</td><td>SUA</td><td>SCC + DPA</td></tr>
                <tr><td><strong>Stripe</strong></td><td>Procesare plăți</td><td>SUA + Irlanda</td><td>SCC + DPA + PCI DSS</td></tr>
                <tr><td><strong>ElevenLabs</strong></td><td>Clonare voce (opțional, per tenant)</td><td>SUA</td><td>SCC + DPA</td></tr>
                <tr><td><strong>Coolify / hosting</strong></td><td>Infrastructură</td><td>România</td><td>Intra-UE</td></tr>
                <tr><td><strong>Mailcow</strong></td><td>Email transactional</td><td>România</td><td>Intra-UE, self-hosted</td></tr>
            </tbody>
        </table>

        <h2>6. Cookies și tehnologii similare</h2>
        <p>Folosim:</p>
        <ul>
            <li><strong>Necesare</strong> (mereu active): sesiune, CSRF, autentificare, preferință limbă. Temeiul legal: executarea contractului / funcționare serviciu. Nu e necesar consimțământ (ePrivacy art. 5(3)).</li>
            <li><strong>Analitice</strong>: Google Analytics 4 prin Google Tag Manager — doar după acceptare explicită. Google operează în Consent Mode v2 cu „ads_data_redaction" și „url_passthrough".</li>
            <li><strong>Marketing</strong>: Google Ads + Meta Pixel — doar după acceptare explicită.</li>
            <li><strong>Personalizare</strong>: preferințe UI — doar după acceptare.</li>
        </ul>
        <p>Poți modifica oricând preferințele din butonul rotund „🍪" (jos-stânga pe fiecare pagină).</p>

        <h2>7. Drepturile tale GDPR</h2>
        <p>Ai următoarele drepturi, gratuit:</p>
        <ul>
            <li><strong>Acces</strong> (art. 15) — să primești o copie a datelor pe care le avem</li>
            <li><strong>Rectificare</strong> (art. 16) — să corectezi date inexacte</li>
            <li><strong>Ștergere / „drept de a fi uitat"</strong> (art. 17)</li>
            <li><strong>Restricționarea prelucrării</strong> (art. 18)</li>
            <li><strong>Portabilitate</strong> (art. 20) — export în format structurat (JSON / CSV)</li>
            <li><strong>Opoziție</strong> (art. 21) — inclusiv la marketing direct</li>
            <li><strong>Retragerea consimțământului</strong> oricând, fără efect retroactiv</li>
            <li><strong>Plângere la ANSPDCP</strong> — <a href="https://www.dataprotection.ro">dataprotection.ro</a></li>
        </ul>
        <p>Trimite cererea la <a href="mailto:servus@sambla.ro">servus@sambla.ro</a>. Răspundem în maxim 30 zile.</p>

        <h2>8. Securitate</h2>
        <p>Aplicăm măsuri tehnice și organizatorice: TLS 1.3 în tranzit, criptare la repaus, izolare per tenant, autentificare 2FA opțională, audit log, backup-uri criptate, testare de securitate periodică, principiul minimizării datelor.</p>

        <h2>9. Luarea de decizii automatizate</h2>
        <p>Agenții AI folosesc modele lingvistice generative pentru a răspunde vizitatorilor. Aceste răspunsuri nu produc efecte juridice sau efecte similare semnificative pentru tine. Poți cere oricând intervenție umană contactând operatorul.</p>

        <h2>10. Pentru clienții care embed-ează widget-ul nostru</h2>
        <p>Dacă ești un tenant Sambla și embed-ezi widget-ul nostru pe site-ul tău, ești <strong>operatorul</strong> datelor vizitatorilor tăi, iar Sambla acționează ca <strong>procesator</strong>. Contractul de procesare (DPA) e parte din Termenii noștri de utilizare și include SCC-urile standard.</p>

        <h2>11. Modificări</h2>
        <p>Vom actualiza această politică când apar modificări semnificative. Te anunțăm prin email pentru modificări majore. Versiunile anterioare sunt disponibile la cerere.</p>

        <h2>12. Contact</h2>
        <p>Operator: <strong>Sambla</strong> · <a href="mailto:servus@sambla.ro">servus@sambla.ro</a> · Data Protection Officer: același contact.</p>
    </div>
</section>
@endsection
