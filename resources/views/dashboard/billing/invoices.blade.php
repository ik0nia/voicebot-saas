@extends('layouts.dashboard')

@section('title', 'Facturi')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.billing.index') }}" class="hover:text-inkSoft">Facturare</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Facturi</span>
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-ink">Facturile tale</h1>
        <p class="mt-1 text-sm text-muted">Descarcă PDF-uri oficiale generate de Stripe (includ TVA 21%, CUI dacă l-ai furnizat la checkout).</p>
    </div>

    @if($invoices->isEmpty())
        <div class="rounded-xl border border-line bg-white p-8 text-center">
            <p class="text-muted">Nu există facturi încă.</p>
            <a href="{{ route('dashboard.billing.index') }}" class="mt-3 inline-block text-sm font-medium text-coralh hover:underline">← Înapoi la Facturare</a>
        </div>
    @else
        <div class="rounded-xl border border-line bg-white shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-cream text-xs uppercase text-muted">
                    <tr>
                        <th class="px-6 py-3 text-left">Număr</th>
                        <th class="px-6 py-3 text-left">Data</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-right">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($invoices as $invoice)
                        @php
                            $statusLabel = match($invoice->status) {
                                'paid' => ['Plătită', 'bg-emerald-100 text-emerald-800'],
                                'open' => ['Deschisă', 'bg-yellow-100 text-yellow-800'],
                                'void' => ['Anulată', 'bg-sand text-inkSoft'],
                                'uncollectible' => ['Necolectabilă', 'bg-coralsoft text-coralh'],
                                'draft' => ['Ciornă', 'bg-cream text-muted'],
                                default => [ucfirst((string)$invoice->status), 'bg-cream text-muted'],
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-3 font-mono text-xs text-inkSoft">{{ $invoice->number ?? $invoice->id }}</td>
                            <td class="px-6 py-3 text-muted">
                                @if($invoice->created)
                                    {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('d M Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                            </td>
                            <td class="px-6 py-3 text-right font-semibold">
                                {{ number_format(($invoice->total ?? 0) / 100, 2) }} {{ $invoice->currency === 'ron' ? 'lei' : strtoupper($invoice->currency) }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if($invoice->invoice_pdf)
                                    <a href="{{ $invoice->invoice_pdf }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-coralh hover:underline">
                                        PDF Stripe →
                                    </a>
                                @endif
                                <a href="{{ route('dashboard.billing.downloadInvoice', $invoice->id) }}"
                                   class="ml-3 inline-flex items-center gap-1 text-xs font-semibold text-inkSoft hover:underline">
                                    Descarcă (Sambla)
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
