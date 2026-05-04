@extends('layouts.new')

@section('title', 'Ștergerea datelor — Sambla')
@section('meta_description', 'Politica și procedura de ștergere a datelor pentru utilizatorii care au conectat conturile lor Facebook sau Instagram prin Sambla.')
@section('canonical', url('/legal/data-deletion'))

@section('content')
<section class="mx-auto max-w-3xl px-6 py-16">
    <h1 class="display text-4xl md:text-5xl font-semibold tracking-tight text-ink">Ștergerea datelor</h1>
    <p class="mt-4 text-base text-inkSoft">Cum și când îți eliminăm datele când revoci accesul aplicației Sambla din contul tău Facebook sau Instagram.</p>

    @if($code)
        <div class="mt-8 rounded-2xl border border-line bg-paper p-6">
            <p class="text-xs font-medium text-muted uppercase tracking-wide">Cerere de ștergere</p>
            <p class="mt-1 font-mono text-sm text-ink">{{ $code }}</p>

            @php
                $statusLabel = match ($status['status'] ?? 'unknown') {
                    'completed' => ['Finalizată', 'emerald'],
                    'pending' => ['În progres', 'amber'],
                    'unknown' => ['Necunoscut sau expirat', 'red'],
                    default => ['—', 'slate'],
                };
            @endphp

            <p class="mt-4 inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                @if($statusLabel[1] === 'emerald') bg-emerald-50 text-emerald-700
                @elseif($statusLabel[1] === 'amber') bg-amber-50 text-amber-700
                @elseif($statusLabel[1] === 'red') bg-red-50 text-red-700
                @else bg-cream text-muted @endif">
                {{ $statusLabel[0] }}
            </p>

            @if(!empty($status['requested_at']))
                <p class="mt-3 text-sm text-inkSoft">Solicitată la: <span class="font-mono">{{ $status['requested_at'] }}</span></p>
            @endif
            @if(!empty($status['channels_revoked']))
                <p class="mt-1 text-sm text-inkSoft">Canale dezactivate: {{ $status['channels_revoked'] }}</p>
            @endif
            @if(!empty($status['message']))
                <p class="mt-3 text-sm text-muted">{{ $status['message'] }}</p>
            @endif
        </div>
    @endif

    <div class="prose prose-sm max-w-none mt-10 text-inkSoft">
        <h2 class="display text-2xl font-semibold text-ink mt-8">Ce se întâmplă când revoci accesul</h2>
        <p>În momentul în care elimini Sambla din lista ta de aplicații Facebook (Settings → Apps and Websites → Sambla → Remove), Meta ne notifică automat printr-o solicitare semnată. Imediat ce primim notificarea:</p>
        <ul>
            <li>Dezactivăm canalele de Messenger și Instagram conectate prin acel cont</li>
            <li>Ștergem token-urile de acces la pagină și la Business Manager</li>
            <li>Marcăm conturile asociate ca <span class="font-mono">revoked</span> — bot-ul nu mai poate trimite mesaje în numele tău</li>
            <li>Generăm un cod de confirmare pe care îl primești la URL-ul de status (pagina aceasta)</li>
        </ul>

        <h2 class="display text-2xl font-semibold text-ink mt-8">Ce păstrăm și de ce</h2>
        <p>Conversațiile pe care bot-ul le-a avut cu clienții pe pagina ta rămân în spațiul de lucru al business-ului tău (tenant-ul Sambla), pentru că aparțin business-ului, nu administratorului care a făcut conectarea. Dacă vrei să le ștergi și pe acelea, scrie la <a href="mailto:{{ config('company.contact.dpo_email') }}" class="text-coral">{{ config('company.contact.dpo_email') }}</a> și le procesăm în maxim 30 de zile.</p>

        <h2 class="display text-2xl font-semibold text-ink mt-8">Operatorul datelor</h2>
        <p>Sambla este operată de <strong>{{ config('company.legal_name') }}</strong>, cu sediul în {{ config('company.address.street') }}, {{ config('company.address.city') }}, județul {{ config('company.address.county') }}, {{ config('company.address.postal_code') }}, înregistrată la Registrul Comerțului sub <span class="font-mono">{{ config('company.reg_com') }}</span>, CUI <span class="font-mono">{{ config('company.vat_prefix') }}{{ config('company.cui') }}</span>.</p>

        <h2 class="display text-2xl font-semibold text-ink mt-8">Cerere manuală de ștergere</h2>
        <p>Dacă nu mai ai acces la contul de Facebook prin care ai conectat Sambla, poți cere ștergerea trimițând un email la <a href="mailto:{{ config('company.contact.dpo_email') }}" class="text-coral">{{ config('company.contact.dpo_email') }}</a> cu:</p>
        <ul>
            <li>Adresa de email cu care te-ai înregistrat la Sambla</li>
            <li>Numele paginii Facebook sau contului Instagram</li>
            <li>O confirmare că ești proprietarul contului</li>
        </ul>
        <p>Procesăm cererea în maxim 30 de zile lucrătoare și îți confirmăm prin email când e gata.</p>

        <h2 class="display text-2xl font-semibold text-ink mt-8">Termenul de retenție</h2>
        <p>După ștergere, păstrăm doar metadata strict necesară pentru audit (cod confirmare, timestamp, tipul acțiunii) timp de 30 de zile. După această perioadă, totul se elimină definitiv.</p>

        <p class="text-xs text-muted mt-12">Ultima actualizare: {{ now()->locale('ro')->isoFormat('DD MMMM YYYY') }}</p>
    </div>
</section>
@endsection
