@extends('layouts.new')

@section('title', 'Politica de confidențialitate — Sambla')
@section('meta_description', 'Cum colectăm, procesăm și protejăm datele tale personale și pe cele ale clienților tăi. Conformitate GDPR.')
@section('canonical', url('/confidentialitate'))

@section('content')

<section class="max-w-3xl mx-auto px-6 pt-14 pb-20 leading-relaxed">
    <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ document legal</div>
    <h1 class="display h-display-l mb-8">Politica de confidențialitate</h1>
    <p class="text-sm mb-10" style="color: var(--muted);">Ultima actualizare: {{ date('j F Y') }}</p>

    <div class="prose prose-lg">
        <h2 class="display text-2xl font-semibold mt-8 mb-3">Cine este operatorul datelor</h2>
        <p>Platforma Sambla este operată de <strong>{{ config('company.legal_name') }}</strong>, persoană juridică română cu sediul în {{ config('company.address.street') }}, {{ config('company.address.city') }}, județul {{ config('company.address.county') }}, cod poștal {{ config('company.address.postal_code') }}, înregistrată la Oficiul Registrului Comerțului sub nr. <span class="mono">{{ config('company.reg_com') }}</span>, cod fiscal <span class="mono">{{ config('company.vat_prefix') }}{{ config('company.cui') }}</span>.</p>
        <p>{{ config('company.legal_name') }} procesează datele în calitate de operator pentru datele proprii clienților noștri (numele firmei, e-mail-ul de contact, facturare) și ca împuternicit pentru datele clienților finali pe care îi procesează agentul AI configurat de tine.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Ce date colectăm</h2>
        <ul class="list-disc ml-6 space-y-2">
            <li>Date de cont: nume, e-mail, telefon, nume firmă, CUI</li>
            <li>Date de facturare: adresă sediu, date carduri (procesate de furnizorul nostru de plăți)</li>
            <li>Date de utilizare: evenimente în platformă, pagini vizitate, configurări</li>
            <li>Conținut platformă: documente pe care le încarci, conversații derulate de agenți, lead-uri captate</li>
        </ul>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Pe ce temei legal</h2>
        <p>Prelucrăm datele pe baza contractului (pentru furnizarea serviciului), a interesului legitim (pentru securitate și îmbunătățirea produsului) sau a consimțământului (pentru marketing, statistici opționale).</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Cât timp păstrăm datele</h2>
        <p>Datele contului — pe durata abonamentului + 30 de zile după anulare. Datele de facturare — 10 ani conform legii fiscale. Conversațiile agentului — conform politicii tale de retenție (configurabilă per cont).</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Cu cine împărtășim datele</h2>
        <p>Datele nu sunt vândute sau împărtășite cu terți în scopuri de marketing. Folosim sub-procesatori strict pentru furnizarea serviciului (hosting, e-mail tranzacțional, procesare plăți, infrastructură AI). Lista completă de sub-procesatori disponibilă la cerere.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Transferuri internaționale</h2>
        <p>Datele personale sunt stocate primar în UE. Unele servicii operaționale pot transfera date în afara UE, exclusiv către furnizori care au clauze contractuale standard aprobate de Comisia Europeană.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Drepturile tale (GDPR)</h2>
        <ul class="list-disc ml-6 space-y-2">
            <li>Acces: poți cere o copie a datelor tale</li>
            <li>Rectificare: poți corecta date inexacte</li>
            <li>Ștergere: poți cere ștergerea (cu excepția datelor pe care le reținem conform legii)</li>
            <li>Portabilitate: primești datele într-un format standard</li>
            <li>Opoziție: poți refuza procesările pe bază de interes legitim</li>
            <li>Plângere: te poți adresa ANSPDCP</li>
        </ul>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Securitate</h2>
        <p>Criptare TLS 1.2+ pentru date în tranzit, criptare la rest pentru date sensibile, acces rolebazat intern, audit trail, backup zilnic. Incidente de securitate notificate în 72 de ore.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Contact DPO</h2>
        <p>Pentru orice solicitare privind datele personale (acces, rectificare, ștergere, portabilitate, opoziție): <a href="mailto:{{ config('company.contact.dpo_email') }}" class="accent-text underline">{{ config('company.contact.dpo_email') }}</a>. Răspundem în maxim 30 de zile calendaristice.</p>

        <h2 class="display text-2xl font-semibold mt-8 mb-3">Date de identificare ale operatorului</h2>
        <div class="rounded-2xl border p-5 text-sm" style="border-color: var(--line); background: var(--paper);">
            <dl class="grid grid-cols-1 sm:grid-cols-[max-content_1fr] gap-x-6 gap-y-1.5">
                <dt class="text-muted">Denumire</dt>           <dd class="font-medium">{{ config('company.legal_name') }}</dd>
                <dt class="text-muted">Cod fiscal</dt>          <dd class="mono">{{ config('company.vat_prefix') }}{{ config('company.cui') }}</dd>
                <dt class="text-muted">Reg. Com.</dt>           <dd class="mono">{{ config('company.reg_com') }}</dd>
                <dt class="text-muted">EUID</dt>                <dd class="mono">{{ config('company.euid') }}</dd>
                <dt class="text-muted">Sediu social</dt>        <dd>{{ config('company.address.street') }}, {{ config('company.address.city') }}, {{ config('company.address.county') }}, {{ config('company.address.postal_code') }}, {{ config('company.address.country') }}</dd>
                <dt class="text-muted">Email contact</dt>       <dd><a href="mailto:{{ config('company.contact.email') }}" class="accent-text">{{ config('company.contact.email') }}</a></dd>
            </dl>
        </div>
    </div>
</section>

@endsection
