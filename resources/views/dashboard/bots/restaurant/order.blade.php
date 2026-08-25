@extends('layouts.dashboard')

@section('title', 'Comanda ' . $order->reference() . ' — ' . $bot->name)

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="text-xs text-muted mb-1">
        <a href="{{ route('dashboard.bots.restaurant.orders', $bot) }}" class="hover:text-coralh">← Toate comenzile</a>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">
                Comanda <span class="font-mono">{{ $order->reference() }}</span>
            </h1>
            <p class="text-sm text-muted mt-1">
                {{ $order->source === 'voice' ? '📞 Telefonic' : '💬 Chat' }}
                · {{ $order->created_at->format('d.m.Y H:i') }}
                @if($order->placed_at)
                    · confirmată la {{ $order->placed_at->format('H:i') }}
                @endif
            </p>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-coralh">{{ $order->formattedTotal() }}</div>
            @if($order->payment_method)
                <div class="text-xs text-muted mt-0.5">
                    {{ $order->payment_method === 'card_on_delivery' ? 'card la livrare' : 'cash' }}
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($order->isDraft())
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="mr-1">⚠️</span>
            <strong>Coș abandonat.</strong>
            Clientul a început comanda dar nu a confirmat-o — probabil a închis telefonul.
            Poți să-l suni și, dacă îți confirmă, marchează comanda ca primită mai jos.
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-3">
        {{-- Lines --}}
        <div class="md:col-span-2 space-y-5">
            <div class="bg-white rounded-xl border border-line overflow-hidden">
                <div class="px-4 py-2.5 border-b border-line text-xs font-semibold text-muted uppercase">Produse</div>
                <div class="divide-y divide-line">
                    @forelse($order->items as $item)
                        <div class="flex items-start gap-3 px-4 py-3">
                            <div class="shrink-0 w-8 text-center font-semibold text-ink">{{ $item->quantity }}×</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-ink">{{ $item->name_snapshot }}</div>
                                @if($item->options_label)
                                    <div class="text-xs text-muted mt-0.5">{{ $item->options_label }}</div>
                                @endif
                                @if($item->notes)
                                    <div class="text-xs text-coralh mt-0.5">📝 {{ $item->notes }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right text-sm">
                                <div class="font-semibold text-ink">{{ $order->formatCents($item->line_total_cents) }}</div>
                                @if($item->quantity > 1)
                                    <div class="text-2xs text-muted">{{ $order->formatCents($item->unit_price_cents) }} / buc</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-muted">Comanda nu are produse.</div>
                    @endforelse
                </div>
                <div class="px-4 py-3 bg-cream/40 border-t border-line text-sm space-y-1">
                    <div class="flex justify-between text-muted">
                        <span>Subtotal</span>
                        <span>{{ $order->formatCents($order->subtotal_cents) }}</span>
                    </div>
                    @if($order->isDelivery())
                        <div class="flex justify-between text-muted">
                            <span>Livrare</span>
                            <span>{{ $order->delivery_fee_cents === 0 ? 'gratuită' : $order->formatCents($order->delivery_fee_cents) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-ink pt-1 border-t border-line">
                        <span>Total</span>
                        <span>{{ $order->formattedTotal() }}</span>
                    </div>
                </div>
            </div>

            {{-- Status control --}}
            <div class="bg-white rounded-xl border border-line p-4">
                <div class="text-xs font-semibold text-muted uppercase mb-3">Stare comandă</div>
                <form method="POST" action="{{ route('dashboard.bots.restaurant.order.status', [$bot, $order]) }}"
                      x-data="{ status: '{{ $order->status }}' }">
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($statuses as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="{{ $value }}" x-model="status" class="sr-only">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border transition"
                                      :class="status === '{{ $value }}'
                                          ? 'bg-coral text-white border-coral'
                                          : 'bg-white text-inkSoft border-line hover:bg-cream'">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div x-show="status === 'canceled'" x-cloak class="mb-3">
                        <input type="text" name="cancel_reason" value="{{ $order->cancel_reason }}"
                               placeholder="Motivul anulării (opțional)"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                    </div>

                    <button type="submit" class="btn-coral rounded-lg px-4 py-2 text-sm font-semibold">
                        Salvează starea
                    </button>
                </form>
            </div>
        </div>

        {{-- Customer --}}
        <div class="space-y-5">
            <div class="bg-white rounded-xl border border-line p-4 text-sm">
                <div class="text-xs font-semibold text-muted uppercase mb-3">Client</div>
                <div class="space-y-2">
                    <div>
                        <div class="text-2xs text-muted">Nume</div>
                        <div class="text-ink">{{ $order->customer_name ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-2xs text-muted">Telefon</div>
                        @if($order->customer_phone)
                            <a href="tel:{{ $order->customer_phone }}" class="text-coralh hover:underline">{{ $order->customer_phone }}</a>
                        @else
                            <span class="text-ink">—</span>
                        @endif
                    </div>
                    @if($order->fulfilment)
                        <div>
                            <div class="text-2xs text-muted">Mod</div>
                            <div class="text-ink">{{ $order->isDelivery() ? 'Livrare' : 'Ridicare personală' }}</div>
                        </div>
                    @endif
                    @if($order->delivery_address)
                        <div>
                            <div class="text-2xs text-muted">Adresă</div>
                            <div class="text-ink">{{ $order->delivery_address }}</div>
                        </div>
                    @endif
                    @if($order->delivery_zone)
                        <div>
                            <div class="text-2xs text-muted">Zonă</div>
                            <div class="text-ink">{{ $order->delivery_zone }}</div>
                        </div>
                    @endif
                    @if($order->delivery_notes)
                        <div>
                            <div class="text-2xs text-muted">Mențiuni la livrare</div>
                            <div class="text-ink">{{ $order->delivery_notes }}</div>
                        </div>
                    @endif
                    @if($order->estimated_minutes)
                        <div>
                            <div class="text-2xs text-muted">Timp estimat comunicat</div>
                            <div class="text-ink">{{ $order->estimated_minutes }} minute</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- The transcript is the first thing anyone asks for when a
                 customer disputes what they ordered. --}}
            @if($order->call_id || $order->conversation_id)
                <div class="bg-white rounded-xl border border-line p-4 text-sm">
                    <div class="text-xs font-semibold text-muted uppercase mb-3">Sursă</div>
                    @if($order->call_id)
                        <a href="{{ url('/dashboard/apeluri/' . $order->call_id) }}" class="text-coralh hover:underline">
                            📞 Vezi apelul #{{ $order->call_id }} →
                        </a>
                    @endif
                    @if($order->conversation_id)
                        <a href="{{ url('/dashboard/inbox?conversation=' . $order->conversation_id) }}" class="text-coralh hover:underline">
                            💬 Vezi conversația #{{ $order->conversation_id }} →
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
