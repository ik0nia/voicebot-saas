@extends('layouts.dashboard')

@section('title', 'Facturare')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Facturare</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-ink">Facturare &amp; Utilizare</h1>
        <p class="mt-1 text-sm text-muted">Monitorizează utilizarea și gestionează planul tău.</p>
    </div>

    @if(request('subscribed'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">✅ Abonamentul a fost activat cu succes.</div>
    @elseif(request('cancelled'))
        <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">Procesul de plată a fost anulat. Nu s-a făcut nicio reținere.</div>
    @elseif(request('topup') === 'ok')
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">✅ Creditele au fost adăugate în contul tău.</div>
    @elseif(request('topup') === 'cancelled')
        <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">Cumpărarea de credite a fost anulată.</div>
    @endif

    @if(($mode ?? 'live') === 'test')
        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-800 font-mono">
            🧪 Stripe în mod TEST — folosește carduri test Stripe (ex: 4242 4242 4242 4242, exp 12/34, cvc orice).
        </div>
    @endif

    @if(!$tenant || !$usage)
        <div class="rounded-xl border border-line bg-white p-8 text-center">
            <p class="text-muted">Nu există informații de facturare disponibile.</p>
        </div>
    @else

    {{-- Current Plan Card --}}
    <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-coralsoft text-coralh">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-xl font-bold text-ink">{{ $usage['plan']['name'] }}</h2>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-coralsoft text-coralh">
                            {{ ucfirst($usage['plan']['slug']) }}
                        </span>
                    </div>
                    <p class="mt-1 text-3xl font-extrabold text-ink">
                        {{ number_format($usage['plan']['price_monthly'], 0) }}<span class="text-base font-medium text-muted">&euro;/lună</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-col sm:items-end gap-2">
                @if($tenant->isOnTrial())
                    @php $trialDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false); @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-700">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Perioadă de probă &mdash; mai ai {{ $trialDaysLeft }} {{ $trialDaysLeft == 1 ? 'zi' : 'zile' }}
                    </span>
                @endif
                <p class="text-sm text-muted">Perioada: <span class="font-medium text-inkSoft">{{ $usage['period'] }}</span></p>
                <a href="/preturi" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold text-inkSoft hover:bg-cream transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                    Schimbă planul
                </a>
                @if($tenant->hasStripeId() ?? false)
                    <a href="{{ route('dashboard.billing.invoices') }}" class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold text-inkSoft hover:bg-cream transition-colors">
                        Facturi
                    </a>
                    <a href="{{ route('dashboard.billing.portal') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                        Gestionează abonamentul
                    </a>
                    @php
                        $sub = $tenant->subscription('default');
                        $isActive = $sub && $sub->active() && ! $sub->onGracePeriod();
                        $inGrace = $sub && $sub->onGracePeriod();
                    @endphp
                    @if($isActive)
                        <form method="POST" action="{{ route('dashboard.billing.cancel') }}" onsubmit="return confirm('Sigur vrei să anulezi abonamentul? Vei avea acces până la finalul ciclului curent.');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-coral/40 bg-white px-4 py-2 text-sm font-semibold text-coralh hover:bg-coralsoft transition-colors">
                                Anulează abonament
                            </button>
                        </form>
                    @elseif($inGrace)
                        <form method="POST" action="{{ route('dashboard.billing.resume') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                                Reactivează abonament
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Credit balances --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-line bg-white p-4">
            <div class="text-xs font-semibold text-muted uppercase">Credite mesaje</div>
            <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($tenant->message_credits ?? 0) }}</div>
            <div class="text-xs text-muted mt-1">extra peste cuota inclusă în abonament</div>
        </div>
        <div class="rounded-xl border border-line bg-white p-4">
            <div class="text-xs font-semibold text-muted uppercase">Credite minute</div>
            <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($tenant->minute_credits ?? 0) }}</div>
            <div class="text-xs text-muted mt-1">minute voce extra</div>
        </div>
        <div class="rounded-xl border border-line bg-white p-4">
            <div class="text-xs font-semibold text-muted uppercase">Credite produse</div>
            <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($tenant->product_credits ?? 0) }}</div>
            <div class="text-xs text-muted mt-1">capacitate produse extra</div>
        </div>
    </div>

    {{-- Top-up bundles available for current plan --}}
    @if(!empty($topups) && $currentPlan)
        <div class="rounded-xl border border-line bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-ink">Cumpără credite extra</h3>
                    <p class="text-xs text-muted mt-0.5">Pachete one-off, nu se reînnoiesc lunar. Creditele rămân până le consumi.</p>
                </div>
                <span class="text-xs text-muted">Pentru pachetul: {{ $currentPlan->name }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
                @foreach($topups as $idx => $bundle)
                    <div class="rounded-xl border border-line p-5 flex flex-col">
                        <div class="text-sm font-bold text-ink">{{ $bundle['name'] }}</div>
                        <div class="text-xs text-muted mt-1">{{ number_format($bundle['quantity']) }} {{ $bundle['unit'] === 'minutes' ? 'minute' : ($bundle['unit'] === 'products' ? 'produse' : 'mesaje') }}</div>
                        <div class="mt-3 text-2xl font-extrabold text-ink">{{ number_format($bundle['price'], 2) }} <span class="text-sm font-medium text-muted">lei +TVA</span></div>
                        @php $priceId = $currentPlan->stripeTopupPriceId((int) $idx); @endphp
                        <form method="POST" action="{{ route('dashboard.billing.topup', ['plan' => $currentPlan->id, 'bundleIndex' => $idx]) }}" class="mt-auto pt-4">
                            @csrf
                            <button type="submit"
                                    @if(!$priceId) disabled @endif
                                    class="w-full rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white hover:bg-coralh disabled:bg-line disabled:cursor-not-allowed transition-colors">
                                {{ $priceId ? 'Cumpără' : 'Nesincronizat în Stripe' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Usage Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Messages Usage --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">Mesaje</h3>
                </div>
                <span class="text-xs font-semibold {{ $usage['messages']['percent'] >= 90 ? 'text-coral' : ($usage['messages']['percent'] >= 70 ? 'text-yellow-600' : 'text-muted') }}">
                    {{ $usage['messages']['percent'] }}%
                </span>
            </div>
            <div class="h-2 w-full rounded-full bg-cream overflow-hidden mb-2">
                <div class="h-2 rounded-full transition-all {{ $usage['messages']['percent'] >= 90 ? 'bg-red-500' : ($usage['messages']['percent'] >= 70 ? 'bg-yellow-500' : 'bg-emerald-500') }}"
                     style="width: {{ min($usage['messages']['percent'], 100) }}%"></div>
            </div>
            <p class="text-sm text-muted">
                <span class="font-semibold text-ink">{{ number_format($usage['messages']['used']) }}</span>
                / {{ number_format($usage['messages']['limit']) }} mesaje
            </p>
            @if($usage['messages']['overage'] > 0)
                <p class="text-xs text-coral font-medium mt-1">
                    +{{ number_format($usage['messages']['overage']) }} extra &middot; &euro;{{ number_format($usage['messages']['overage_cost'], 2) }} overage
                </p>
            @endif
            @if($usage['messages']['overage_unit_cost'] > 0)
                <p class="text-xs text-muted mt-1">Suplimentar: &euro;{{ number_format($usage['messages']['overage_unit_cost'], 2) }}/mesaj</p>
            @endif
        </div>

        {{-- Bots Usage --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">Chatboți</h3>
                </div>
                <span class="text-xs font-semibold {{ $usage['bots']['percent'] >= 100 ? 'text-coral' : 'text-muted' }}">
                    {{ $usage['bots']['percent'] }}%
                </span>
            </div>
            <div class="h-2 w-full rounded-full bg-cream overflow-hidden mb-2">
                <div class="h-2 rounded-full transition-all {{ $usage['bots']['percent'] >= 100 ? 'bg-red-500' : 'bg-blue-500' }}"
                     style="width: {{ min($usage['bots']['percent'], 100) }}%"></div>
            </div>
            <p class="text-sm text-muted">
                <span class="font-semibold text-ink">{{ $usage['bots']['used'] }}</span>
                / {{ $usage['bots']['limit'] }} agenți AI incluși
            </p>
            @if($usage['bots']['used'] > $usage['bots']['limit'])
                <p class="text-xs text-coral font-medium mt-1">
                    +{{ $usage['bots']['used'] - $usage['bots']['limit'] }} extra &middot; &euro;{{ number_format(($usage['bots']['used'] - $usage['bots']['limit']) * $usage['bots']['overage_unit_cost'], 0) }}/lună overage
                </p>
            @endif
            @if($usage['bots']['overage_unit_cost'] > 0)
                <p class="text-xs text-muted mt-1">Agent AI suplimentar: &euro;{{ number_format($usage['bots']['overage_unit_cost'], 0) }}/lună</p>
            @endif
        </div>

        {{-- Voice Minutes --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">Minute voce</h3>
                </div>
                @if($usage['voice_minutes']['has_voice'])
                    <span class="text-xs font-semibold {{ $usage['voice_minutes']['percent'] >= 90 ? 'text-coral' : 'text-muted' }}">
                        {{ $usage['voice_minutes']['percent'] }}%
                    </span>
                @endif
            </div>
            @if($usage['voice_minutes']['has_voice'])
                <div class="h-2 w-full rounded-full bg-cream overflow-hidden mb-2">
                    <div class="h-2 rounded-full transition-all {{ $usage['voice_minutes']['percent'] >= 90 ? 'bg-red-500' : 'bg-purple-500' }}"
                         style="width: {{ min($usage['voice_minutes']['percent'], 100) }}%"></div>
                </div>
                <p class="text-sm text-muted">
                    <span class="font-semibold text-ink">{{ number_format($usage['voice_minutes']['used']) }}</span>
                    / {{ $usage['voice_minutes']['limit'] == -1 ? 'nelimitat' : number_format($usage['voice_minutes']['limit']) }} minute
                </p>
            @else
                <div class="mt-2">
                    <p class="text-sm text-muted">Fără addon de voce activ.</p>
                    <a href="/preturi#voice" class="text-xs text-coralh font-medium hover:underline mt-1 inline-block">Adaugă pachet voce &rarr;</a>
                </div>
            @endif
        </div>

    </div>

    {{-- Detailed Usage Table --}}
    <div class="rounded-xl border border-line bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-line">
            <h3 class="text-base font-semibold text-ink">Utilizare detaliată</h3>
        </div>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-cream">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-muted uppercase">Resursă</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-muted uppercase">Utilizat</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-muted uppercase">Limită</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-muted uppercase">Utilizare</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach([
                    ['Mesaje', $usage['messages']['used'], $usage['messages']['limit'], $usage['messages']['percent']],
                    ['Chatboți', $usage['bots']['used'], $usage['bots']['limit'], $usage['bots']['percent']],
                    ['Minute voce', $usage['voice_minutes']['used'], $usage['voice_minutes']['has_voice'] ? ($usage['voice_minutes']['limit'] == -1 ? '∞' : $usage['voice_minutes']['limit']) : 'N/A', $usage['voice_minutes']['percent']],
                    ['Site-uri', $usage['sites']['used'], $usage['sites']['limit'], $usage['sites']['percent']],
                    ['Rulări agenți AI', $usage['agent_runs']['used'], $usage['agent_runs']['limit'], $usage['agent_runs']['percent']],
                    ['Pagini scanate', $usage['pages_scanned']['used'], $usage['pages_scanned']['limit'], $usage['pages_scanned']['percent']],
                    ['Conectori', $usage['connectors']['used'], $usage['connectors']['limit'], $usage['connectors']['percent']],
                ] as [$label, $used, $limit, $percent])
                <tr>
                    <td class="px-6 py-3 text-sm font-medium text-inkSoft">{{ $label }}</td>
                    <td class="px-6 py-3 text-sm text-right text-ink font-semibold">{{ number_format($used) }}</td>
                    <td class="px-6 py-3 text-sm text-right text-muted">{{ is_numeric($limit) ? number_format($limit) : $limit }}</td>
                    <td class="px-6 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <div class="w-16 h-1.5 bg-cream rounded-full overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $percent >= 90 ? 'bg-red-500' : ($percent >= 70 ? 'bg-yellow-500' : 'bg-emerald-500') }}"
                                     style="width: {{ min($percent, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium {{ $percent >= 90 ? 'text-coral' : 'text-muted' }}">{{ $percent }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Overage Summary --}}
    @php
        $totalOverage = ($usage['messages']['overage_cost'] ?? 0);
        $botOverage = max(0, $usage['bots']['used'] - $usage['bots']['limit']) * ($usage['bots']['overage_unit_cost'] ?? 0);
        $totalOverage += $botOverage;
    @endphp
    @if($totalOverage > 0)
    <div class="rounded-xl border border-coral/30 bg-coralsoft p-6">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-5 h-5 text-coral" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <h3 class="text-base font-semibold text-coralh">Cost suplimentar luna aceasta</h3>
        </div>
        <div class="space-y-1 text-sm text-coralh">
            @if(($usage['messages']['overage_cost'] ?? 0) > 0)
                <div class="flex justify-between">
                    <span>{{ number_format($usage['messages']['overage']) }} mesaje extra &times; &euro;{{ number_format($usage['messages']['overage_unit_cost'], 2) }}</span>
                    <span class="font-semibold">&euro;{{ number_format($usage['messages']['overage_cost'], 2) }}</span>
                </div>
            @endif
            @if($botOverage > 0)
                <div class="flex justify-between">
                    <span>{{ max(0, $usage['bots']['used'] - $usage['bots']['limit']) }} agenți AI extra &times; &euro;{{ number_format($usage['bots']['overage_unit_cost'], 0) }}/lună</span>
                    <span class="font-semibold">&euro;{{ number_format($botOverage, 0) }}</span>
                </div>
            @endif
            <div class="flex justify-between pt-2 border-t border-coral/30 font-bold">
                <span>Total overage</span>
                <span>&euro;{{ number_format($totalOverage, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Recent purchases --}}
    @if($recentPurchases->isNotEmpty())
        <div class="rounded-xl border border-line bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-line">
                <h3 class="text-base font-semibold text-ink">Istoric cumpărări credite</h3>
            </div>
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-cream text-xs uppercase text-muted">
                    <tr>
                        <th class="px-6 py-3 text-left">Data</th>
                        <th class="px-6 py-3 text-left">Tip</th>
                        <th class="px-6 py-3 text-right">Cantitate</th>
                        <th class="px-6 py-3 text-right">Preț</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($recentPurchases as $p)
                        <tr>
                            <td class="px-6 py-3 text-muted">{{ $p->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-3">{{ $p->unit === 'minutes' ? 'Minute voce' : ($p->unit === 'products' ? 'Capacitate produse' : 'Mesaje') }}</td>
                            <td class="px-6 py-3 text-right font-semibold">{{ number_format($p->quantity) }}</td>
                            <td class="px-6 py-3 text-right">{{ number_format($p->price_cents / 100, 2) }} {{ $p->currency === 'ron' ? 'lei' : strtoupper($p->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Plan comparison + subscribe --}}
    @if($webchatPlans->isNotEmpty() || $voicePlans->isNotEmpty())
        <div class="rounded-xl border border-line bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-line">
                <h3 class="text-base font-semibold text-ink">Schimbă pachetul</h3>
                <p class="text-xs text-muted mt-0.5">Apasă "Abonează-te" și vei fi redirecționat la Stripe Checkout.</p>
            </div>
            <div class="p-6 space-y-6">
                @foreach(['Webchat' => $webchatPlans, 'Voce' => $voicePlans] as $section => $list)
                    @if($list->isNotEmpty())
                        <div>
                            <div class="text-xs font-semibold text-muted uppercase mb-3">{{ $section }}</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach($list as $p)
                                    @php $isCurrent = $currentPlan && $currentPlan->id === $p->id; @endphp
                                    <div class="rounded-xl border @if($isCurrent) border-coral/40 bg-coralsoft @else border-line @endif p-5 flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-ink">{{ $p->name }}</h4>
                                            @if($p->is_popular)<span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">Popular</span>@endif
                                            @if($isCurrent)<span class="inline-flex items-center rounded-full bg-red-200 px-2 py-0.5 text-xs font-semibold text-coralh">Actual</span>@endif
                                        </div>
                                        <div class="mt-2 text-2xl font-extrabold text-ink">{{ number_format($p->price_monthly, 0) }} <span class="text-sm font-medium text-muted">lei/lună +TVA</span></div>
                                        @php
                                            $monthlyTotal = $p->price_monthly * 12;
                                            $yearlyTotal = $p->price_yearly * 12;
                                            $savings = max(0, $monthlyTotal - $yearlyTotal);
                                            $savingsPct = $monthlyTotal > 0 ? (int) round(100 * $savings / $monthlyTotal) : 0;
                                        @endphp
                                        <div class="text-xs text-muted">
                                            sau {{ number_format($p->price_yearly, 0) }} lei/lună +TVA (anual)
                                            @if($savingsPct > 0)
                                                <span class="ml-1 inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800">-{{ $savingsPct }}%</span>
                                            @endif
                                        </div>
                                        @if($p->description)
                                            <p class="mt-2 text-xs text-muted">{{ $p->description }}</p>
                                        @endif
                                        @php
                                            // If the tenant already has an active subscription, use the
                                            // change-plan route (no new Checkout). Otherwise subscribe fresh.
                                            $hasSub = optional($tenant->subscription('default'))->active();
                                            $action = $hasSub
                                                ? route('dashboard.billing.changePlan', $p->id)
                                                : route('dashboard.billing.subscribe', $p->id);
                                            $btnLabelPrefix = $hasSub ? 'Schimbă · ' : '';
                                        @endphp
                                        <div class="mt-auto pt-4 grid grid-cols-2 gap-2">
                                            <form method="POST" action="{{ $action }}">
                                                @csrf
                                                <input type="hidden" name="interval" value="monthly">
                                                <button type="submit"
                                                        @if($isCurrent || !$p->stripePriceId('monthly')) disabled @endif
                                                        class="w-full rounded-lg bg-coral px-3 py-2 text-xs font-semibold text-white hover:bg-coralh disabled:bg-line disabled:cursor-not-allowed">
                                                    {{ $btnLabelPrefix }}Lunar
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ $action }}">
                                                @csrf
                                                <input type="hidden" name="interval" value="yearly">
                                                <button type="submit"
                                                        @if($isCurrent || !$p->stripePriceId('yearly')) disabled @endif
                                                        class="w-full rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800 disabled:bg-line disabled:cursor-not-allowed">
                                                    {{ $btnLabelPrefix }}Anual
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @endif

</div>
@endsection
