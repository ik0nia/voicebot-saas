@extends('layouts.dashboard')

@section('title', "Conversație #{$conversation->id}")

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-500">Transcrieri</span>
    <span class="text-slate-400">/</span>
    @php
        $chType = $conversation->channel?->type ?? 'web_chatbot';
        $chLabel = match ($chType) {
            'web_chatbot' => 'Web Chatbot',
            'whatsapp' => 'WhatsApp',
            'facebook_messenger' => 'Facebook Messenger',
            'instagram_dm' => 'Instagram DM',
            default => ucfirst($chType),
        };
    @endphp
    <a href="{{ route('dashboard.conversations.index', ['channelType' => $chType]) }}" class="font-medium text-slate-500 hover:text-slate-700 transition-colors">{{ $chLabel }}</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Conversație #{{ $conversation->id }}</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900">Conversație #{{ $conversation->id }}</h1>
            @if($conversation->status === 'active')
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">Activă</span>
            @elseif($conversation->status === 'completed')
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Completată</span>
            @else
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ $conversation->status }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.conversations.index', ['channelType' => $chType]) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Înapoi
            </a>
            <form method="POST" action="{{ route('dashboard.conversations.destroy', $conversation) }}"
                  onsubmit="return confirm('Ești sigur că vrei să ștergi această conversație?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Șterge
                </button>
            </form>
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Bot</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $conversation->bot?->name ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Canal</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $chLabel }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Contact</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ $conversation->contact_name ?? $conversation->contact_identifier ?? 'Anonim' }}
            </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Mesaje</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $messages->count() }}</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Cost</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
                @if(($conversation->real_cost_cents ?? 0) > 0)
                    {{ number_format($conversation->real_cost_cents / 100, 4) }} $
                @else
                    —
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Messages --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Conversație</h3>
        </div>
        <div class="p-5">
            @if($messages->count() > 0)
                <div class="space-y-4 max-w-2xl mx-auto">
                    @foreach($messages as $msg)
                        @if($msg->direction === 'outbound')
                            {{-- Bot message - left aligned --}}
                            <div class="flex justify-start">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl rounded-tl-sm bg-slate-100 px-4 py-3 text-sm text-slate-800">
                                        {{ $msg->content }}
                                    </div>
                                    @if(!empty($msg->metadata['products']))
                                        <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                                            @foreach($msg->metadata['products'] as $product)
                                                <div class="flex-shrink-0 w-36 rounded-lg border border-slate-200 bg-white overflow-hidden shadow-sm">
                                                    @if(!empty($product['image_url']))
                                                        <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="w-full h-20 object-cover">
                                                    @endif
                                                    <div class="p-2">
                                                        <p class="text-[11px] font-semibold text-slate-800 leading-tight line-clamp-2">{{ $product['name'] }}</p>
                                                        @if(!empty($product['sale_price']) && !empty($product['regular_price']))
                                                            <p class="mt-1 text-xs font-bold text-red-600">{{ $product['sale_price'] }} {{ $product['currency'] ?? 'RON' }} <span class="text-[10px] text-slate-400 line-through font-normal">{{ $product['regular_price'] }}</span></p>
                                                        @else
                                                            <p class="mt-1 text-xs font-bold text-slate-800">{{ $product['price'] ?? '' }} {{ $product['currency'] ?? 'RON' }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="mt-1 text-[11px] text-slate-400 ml-1">
                                        Bot
                                        @if($msg->sent_at)
                                            &middot; {{ $msg->sent_at->format('H:i') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @else
                            {{-- User message - right aligned --}}
                            <div class="flex justify-end">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl rounded-tr-sm bg-red-800 px-4 py-3 text-sm text-white">
                                        {{ $msg->content }}
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400 text-right mr-1">
                                        {{ $conversation->contact_name ?? 'Client' }}
                                        @if($msg->sent_at)
                                            &middot; {{ $msg->sent_at->format('H:i') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500">Niciun mesaj în această conversație.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Metadata --}}
    @if($conversation->metadata)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900">Metadate</h3>
            </div>
            <div class="p-5">
                <pre class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700 overflow-x-auto"><code>{{ json_encode($conversation->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    @endif
@endsection
