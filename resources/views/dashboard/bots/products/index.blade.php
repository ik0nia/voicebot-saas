@extends('layouts.dashboard')

@section('title', 'Produse / Servicii — ' . $bot->name)

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <div class="text-xs text-muted mb-1">
                <a href="{{ route('dashboard.bots.edit', $bot) }}" class="hover:text-coralh">← {{ $bot->name }}</a>
            </div>
            <h1 class="text-2xl font-bold text-ink">Produse / servicii</h1>
            <p class="text-sm text-muted mt-1">Catalogul folosit de agent pentru recomandări. Poți adăuga manual sau sincroniza din WooCommerce.</p>
        </div>
        <a href="{{ route('dashboard.bots.products.create', $bot) }}"
           class="btn-coral inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold">
            + Adaugă manual
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter pills --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach(['all' => 'Toate', 'manual' => 'Manuale', 'sync' => 'Din WooCommerce'] as $key => $label)
            <a href="{{ route('dashboard.bots.products.index', ['bot' => $bot, 'source' => $key]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill text-xs font-medium border
                      {{ $source === $key ? 'bg-coral text-white border-coral' : 'bg-white text-inkSoft border-line hover:bg-cream' }}">
                {{ $label }}
                <span class="text-2xs opacity-70">
                    @switch($key)
                        @case('all') {{ $counts['total'] }} @break
                        @case('manual') {{ $counts['manual'] }} @break
                        @case('sync') {{ $counts['sync'] }} @break
                    @endswitch
                </span>
            </a>
        @endforeach

        <form method="GET" action="{{ route('dashboard.bots.products.index', $bot) }}" class="flex-1 max-w-xs ml-auto">
            <input type="hidden" name="source" value="{{ $source }}">
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="Caută după nume sau SKU…"
                   class="w-full rounded-lg border border-line bg-white px-3 py-1.5 text-xs">
        </form>
    </div>

    @if($products->isEmpty())
        <div class="rounded-xl border border-line bg-white p-10 text-center">
            <div class="text-3xl mb-2">📦</div>
            <p class="text-sm text-muted">
                @if(request('search')) Niciun produs nu se potrivește căutării.
                @elseif($source === 'manual') Niciun produs manual încă.
                    <a href="{{ route('dashboard.bots.products.create', $bot) }}" class="text-coralh hover:underline">Adaugă primul →</a>
                @else Niciun produs încă. Adaugă manual sau sincronizează WooCommerce.
                @endif
            </p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-line overflow-hidden">
            <div class="divide-y divide-line">
                @foreach($products as $product)
                    @php $isManual = str_starts_with((string) $product->site_url, 'manual:'); @endphp
                    <div class="flex items-start gap-4 px-4 py-3 hover:bg-cream/40 transition">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="" loading="lazy"
                                 class="w-14 h-14 rounded-lg object-cover border border-line shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-lg bg-cream border border-line shrink-0 flex items-center justify-center text-2xl">
                                📦
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                <span class="font-semibold text-ink truncate">{{ $product->name }}</span>
                                @if($isManual)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-blue-50 text-blue-700 border border-blue-200">manual</span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-emerald-50 text-emerald-700 border border-emerald-200">WooCommerce</span>
                                @endif
                                @if($product->stock_status === 'outofstock')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-coralsoft text-coralh border border-coral/30">indisponibil</span>
                                @endif
                            </div>
                            <div class="text-xs text-muted space-x-2">
                                @if($product->price)
                                    <span class="font-semibold text-coralh">{{ number_format((float) $product->price, 2) }} {{ $product->currency ?: 'RON' }}{{ $product->price_unit ? ' / ' . $product->price_unit : '' }}</span>
                                @endif
                                @if($product->sku)
                                    <span>· SKU {{ $product->sku }}</span>
                                @endif
                                @if(is_array($product->categories) && !empty($product->categories))
                                    <span>· {{ implode(', ', array_slice($product->categories, 0, 3)) }}</span>
                                @endif
                            </div>
                            @if($product->short_description)
                                <p class="text-xs text-muted mt-1 line-clamp-2">{{ strip_tags($product->short_description) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            @if($isManual)
                                <a href="{{ route('dashboard.bots.products.edit', [$bot, $product]) }}"
                                   class="text-xs text-muted hover:text-ink p-1.5 rounded hover:bg-cream" title="Editează">
                                    ✎
                                </a>
                                <form method="POST" action="{{ route('dashboard.bots.products.destroy', [$bot, $product]) }}"
                                      onsubmit="return confirm('Ștergi {{ addslashes($product->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-muted hover:text-coralh p-1.5 rounded hover:bg-coralsoft" title="Șterge">
                                        🗑
                                    </button>
                                </form>
                            @else
                                <span class="text-2xs text-muted px-2" title="Doar din UI-ul WordPress">read-only</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</div>
@endsection
