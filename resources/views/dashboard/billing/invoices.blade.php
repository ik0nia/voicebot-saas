@extends('layouts.dashboard')

@section('title', 'Facturi')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.billing.index') }}" class="hover:text-slate-700">Facturare</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Facturi</span>
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Facturile tale</h1>
        <p class="mt-1 text-sm text-slate-500">Descarcă PDF-uri oficiale generate de Stripe (includ TVA 21%, CUI dacă l-ai furnizat la checkout).</p>
    </div>

    @if($invoices->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-slate-500">Nu există facturi încă.</p>
            <a href="{{ route('dashboard.billing.index') }}" class="mt-3 inline-block text-sm font-medium text-red-700 hover:underline">← Înapoi la Facturare</a>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Număr</th>
                        <th class="px-6 py-3 text-left">Data</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-right">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoices as $invoice)
                        @php
                            $statusLabel = match($invoice->status) {
                                'paid' => ['Plătită', 'bg-emerald-100 text-emerald-800'],
                                'open' => ['Deschisă', 'bg-yellow-100 text-yellow-800'],
                                'void' => ['Anulată', 'bg-slate-200 text-slate-700'],
                                'uncollectible' => ['Necolectabilă', 'bg-red-100 text-red-800'],
                                'draft' => ['Ciornă', 'bg-slate-100 text-slate-600'],
                                default => [ucfirst((string)$invoice->status), 'bg-slate-100 text-slate-600'],
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ $invoice->number ?? $invoice->id }}</td>
                            <td class="px-6 py-3 text-slate-500">
                                @if($invoice->created)
                                    {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('d M Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                            </td>
                            <td class="px-6 py-3 text-right font-semibold">
                                {{ number_format(($invoice->total ?? 0) / 100, 2) }} {{ strtoupper($invoice->currency ?? 'RON') }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if($invoice->invoice_pdf)
                                    <a href="{{ $invoice->invoice_pdf }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 hover:underline">
                                        PDF Stripe →
                                    </a>
                                @endif
                                <a href="{{ route('dashboard.billing.downloadInvoice', $invoice->id) }}"
                                   class="ml-3 inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:underline">
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
