@extends('layouts.admin')
@section('title', 'Conversatie #' . $conversation->id . ' - Admin')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.conversations.index') }}" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-2xl font-bold text-slate-900">Conversatie #{{ $conversation->id }}</h1>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $conversation->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $conversation->status === 'active' ? 'Activa' : 'Incheiata' }}</span>
    </div>
    <p class="text-sm text-slate-500">Tenant: <span class="font-medium text-slate-700">{{ $conversation->tenant?->name ?? '-' }}</span> | Bot: <span class="font-medium text-slate-700">{{ $conversation->bot?->name ?? '-' }}</span> | Contact: {{ $conversation->contact_name ?: ($conversation->contact_identifier ?: '-') }} | Cost total: <span class="font-mono font-medium text-slate-700">{{ ($conversation->real_cost_cents ?? 0) > 0 ? number_format($conversation->real_cost_cents / 100, 4) . ' $' : '0.00 $' }}</span></p>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Mesaje ({{ $messages->count() }})</h2></div>
    <div class="p-5 space-y-3">
        @forelse($messages as $msg)
        <div class="flex {{ $msg->direction === 'inbound' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[75%]">
                <div class="px-4 py-2.5 rounded-2xl text-sm {{ $msg->direction === 'inbound' ? 'bg-red-800 text-white rounded-br-md' : 'bg-slate-100 text-slate-700 rounded-bl-md' }}">
                    {{ $msg->content }}
                    <div class="flex items-center gap-2 text-[10px] mt-1 {{ $msg->direction === 'inbound' ? 'text-red-200' : 'text-slate-400' }}">
                        <span>{{ $msg->created_at->format('H:i') }}</span>
                        @if($msg->ai_model)
                            <span class="opacity-70">{{ $msg->ai_model }}</span>
                            <span class="opacity-70">{{ $msg->input_tokens + $msg->output_tokens }} tok</span>
                            @if($msg->cost_cents > 0)
                                <span class="opacity-70">{{ number_format($msg->cost_cents / 100, 4) }}$</span>
                            @endif
                        @endif
                    </div>
                </div>
                @if($msg->metadata && !empty($msg->metadata['products']))
                    <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                        @foreach($msg->metadata['products'] as $product)
                            <div class="flex-shrink-0 w-36 rounded-lg border border-slate-200 bg-white overflow-hidden">
                                @if(!empty($product['image_url']))
                                    <img src="{{ $product['image_url'] }}" class="w-full h-20 object-cover" loading="lazy">
                                @endif
                                <div class="p-2">
                                    <p class="text-[11px] font-semibold text-slate-700 line-clamp-2">{{ $product['name'] }}</p>
                                    <p class="text-xs font-bold text-slate-900 mt-0.5">{{ $product['price'] }} {{ $product['currency'] ?? 'RON' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @empty
        <p class="text-center text-slate-400 text-sm py-4">Niciun mesaj.</p>
        @endforelse
    </div>
</div>
@endsection
