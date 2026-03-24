@extends('layouts.admin')

@section('title', 'Pachete & Prețuri')
@section('breadcrumb')<span class="text-slate-900 font-medium">Pachete & Prețuri</span>@endsection

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Pachete & Prețuri</h1>
                <p class="text-sm text-slate-500">Gestionează pachetele disponibile pentru clienți</p>
            </div>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Adaugă pachet
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    @php
        $typeLabels = [
            'webchat' => ['label' => 'Webchat', 'color' => 'emerald', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            'voice' => ['label' => 'Voice', 'color' => 'blue', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
            'bundle' => ['label' => 'Bundle', 'color' => 'purple', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ];
        $colorMap = [
            'emerald' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-700', 'border' => 'border-emerald-200'],
            'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'badge' => 'bg-blue-100 text-blue-700', 'border' => 'border-blue-200'],
            'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'badge' => 'bg-purple-100 text-purple-700', 'border' => 'border-purple-200'],
        ];
    @endphp

    @foreach(['webchat', 'voice', 'bundle'] as $type)
        @php
            $info = $typeLabels[$type];
            $colors = $colorMap[$info['color']];
            $typePlans = $grouped->get($type, collect());
        @endphp

        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 {{ $colors['bg'] }} rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $colors['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $info['icon'] }}"/></svg>
                </div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">{{ $info['label'] }}</h2>
                <span class="text-xs text-slate-400">({{ $typePlans->count() }} pachete)</span>
            </div>

            @if($typePlans->isEmpty())
                <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
                    <p class="text-sm text-slate-400">Nu există pachete de tip {{ $info['label'] }}.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($typePlans as $plan)
                        <div class="bg-white rounded-xl border {{ $plan->is_popular ? 'border-red-300 ring-2 ring-red-100' : 'border-slate-200' }} p-5 relative {{ !$plan->is_active ? 'opacity-60' : '' }}">
                            {{-- Popular badge --}}
                            @if($plan->is_popular)
                                <div class="absolute -top-2.5 left-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-600 text-white">Popular</span>
                                </div>
                            @endif

                            {{-- Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">{{ $plan->name }}</h3>
                                    <span class="text-xs font-mono text-slate-400">{{ $plan->slug }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    {{-- Active toggle --}}
                                    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="name" value="{{ $plan->name }}">
                                        <input type="hidden" name="slug" value="{{ $plan->slug }}">
                                        <input type="hidden" name="type" value="{{ $plan->type }}">
                                        <input type="hidden" name="price_monthly" value="{{ $plan->price_monthly }}">
                                        <input type="hidden" name="price_yearly" value="{{ $plan->price_yearly }}">
                                        <input type="hidden" name="sort_order" value="{{ $plan->sort_order }}">
                                        <input type="hidden" name="is_popular" value="{{ $plan->is_popular ? '1' : '0' }}">
                                        <input type="hidden" name="is_active" value="{{ $plan->is_active ? '0' : '1' }}">
                                        <input type="hidden" name="features_text" value="{{ is_array($plan->features) ? implode("\n", $plan->features) : '' }}">
                                        @if(is_array($plan->limits))
                                            @foreach($plan->limits as $lk => $lv)
                                                @if(is_array($lv))
                                                    @foreach($lv as $cv) <input type="hidden" name="limits[{{ $lk }}][]" value="{{ $cv }}"> @endforeach
                                                @else
                                                    <input type="hidden" name="limits[{{ $lk }}]" value="{{ $lv }}">
                                                @endif
                                            @endforeach
                                        @endif
                                        @if(is_array($plan->overage))
                                            @foreach($plan->overage as $ok => $ov)
                                                <input type="hidden" name="overage[{{ $ok }}]" value="{{ $ov }}">
                                            @endforeach
                                        @endif
                                        <button type="submit" title="{{ $plan->is_active ? 'Dezactivează' : 'Activează' }}">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $plan->is_active ? 'Activ' : 'Inactiv' }}
                                            </span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Pricing --}}
                            <div class="flex items-baseline gap-2 mb-3">
                                <span class="text-2xl font-bold text-slate-900">{{ number_format($plan->price_monthly, 0) }} <span class="text-sm font-normal text-slate-500">RON/lună</span></span>
                                @if($plan->price_yearly > 0)
                                    <span class="text-xs text-slate-400">/ {{ number_format($plan->price_yearly, 0) }} RON/an</span>
                                @endif
                            </div>

                            @if($plan->description)
                                <p class="text-xs text-slate-500 mb-3">{{ $plan->description }}</p>
                            @endif

                            {{-- Limits --}}
                            @if(is_array($plan->limits) && count($plan->limits) > 0)
                                <div class="mb-3 space-y-1">
                                    <p class="text-xs font-semibold text-slate-600 uppercase">Limite</p>
                                    @php
                                        $limitLabels = [
                                            'bots' => 'Boți',
                                            'messages_per_month' => 'Mesaje/lună',
                                            'knowledge_entries' => 'Knowledge entries',
                                            'products' => 'Produse',
                                            'minutes_per_month' => 'Minute/lună',
                                            'channels' => 'Canale',
                                        ];
                                    @endphp
                                    @foreach($plan->limits as $key => $value)
                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">{{ $limitLabels[$key] ?? $key }}</span>
                                            <span class="font-medium text-slate-700">
                                                @if(is_array($value))
                                                    {{ implode(', ', $value) }}
                                                @elseif($value == -1)
                                                    Nelimitat
                                                @else
                                                    {{ number_format($value) }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Overage --}}
                            @if(is_array($plan->overage) && count($plan->overage) > 0)
                                <div class="mb-3 space-y-1">
                                    <p class="text-xs font-semibold text-slate-600 uppercase">Overage</p>
                                    @php
                                        $overageLabels = [
                                            'cost_per_message' => 'Cost/mesaj',
                                            'cost_per_word' => 'Cost/cuvânt',
                                            'cost_per_minute' => 'Cost/minut',
                                        ];
                                    @endphp
                                    @foreach($plan->overage as $key => $value)
                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">{{ $overageLabels[$key] ?? $key }}</span>
                                            <span class="font-mono font-medium text-slate-700">{{ number_format($value, 4) }} RON</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Sort order --}}
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-3">
                                <span>Ordine: {{ $plan->sort_order ?? 0 }}</span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="flex-1 text-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                    Editează
                                </a>
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="flex-1"
                                      onsubmit="return confirm('Ștergi pachetul {{ $plan->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-red-600 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">
                                        Șterge
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

</div>
@endsection
