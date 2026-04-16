@extends('layouts.admin')

@section('title', 'Lead — ' . $lead->name)
@section('breadcrumb')
    <a href="{{ route('admin.leads.index') }}" class="text-slate-500 hover:text-slate-900">Lead-uri</a>
    <span class="text-slate-300 mx-1">/</span>
    <span class="text-slate-900 font-medium">{{ $lead->name }}</span>
@endsection

@section('content')
@if(session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900">
        @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
    </div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Original message --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $lead->subject ?: 'Mesaj fără subiect' }}</h2>
                    <p class="text-xs text-slate-500 mt-1">de la <span class="font-medium">{{ $lead->name }}</span> · {{ $lead->created_at->format('d M Y, H:i') }}</p>
                </div>
                <span class="shrink-0 inline-flex px-2 py-0.5 rounded text-xs font-semibold
                    @switch($lead->status)
                        @case('new')     bg-red-100 text-red-800 @break
                        @case('contacted')  bg-amber-100 text-amber-800 @break
                        @case('qualified')  bg-indigo-100 text-indigo-800 @break
                        @case('converted')  bg-emerald-100 text-emerald-800 @break
                        @default bg-slate-100 text-slate-600
                    @endswitch">{{ $lead->status }}</span>
            </div>
            <div class="prose prose-sm max-w-none text-slate-700 whitespace-pre-wrap">{{ $lead->message }}</div>
        </div>

        {{-- Replies thread --}}
        @foreach($lead->replies as $r)
            <div class="rounded-xl p-5 {{ $r->direction === 'out' ? 'bg-red-50 border border-red-100' : 'bg-white border border-slate-200' }}">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ $r->direction === 'out' ? ('Sambla — ' . ($r->sender?->name ?? 'Admin')) : $lead->name }}
                    </p>
                    <p class="text-xs text-slate-500">{{ $r->sent_at?->format('d M Y, H:i') ?? $r->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($r->subject)<p class="text-sm font-medium text-slate-800 mb-1">{{ $r->subject }}</p>@endif
                <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $r->body }}</div>
            </div>
        @endforeach

        {{-- Reply form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Răspunde</h3>
            <form method="POST" action="{{ route('admin.leads.reply', $lead) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Subiect</label>
                    <input type="text" name="subject" value="Re: {{ $lead->subject ?: 'Sambla' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Mesaj</label>
                    <textarea name="body" rows="6" required placeholder="Scrie răspunsul tău…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white hover:bg-red-800">
                        Trimite
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Status changer --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold uppercase text-slate-600 mb-3">Status</h3>
            <form method="POST" action="{{ route('admin.leads.updateStatus', $lead) }}" class="flex flex-col gap-2">
                @csrf
                @foreach(['new'=>'Nou','contacted'=>'Contactat','qualified'=>'Calificat','disqualified'=>'Descalificat','converted'=>'Convertit'] as $k=>$v)
                    <button type="submit" name="status" value="{{ $k }}"
                            class="text-left px-3 py-2 rounded-lg text-sm border transition-colors
                                   {{ $lead->status === $k ? 'bg-red-50 border-red-200 text-red-800 font-semibold' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        {{ $v }}
                    </button>
                @endforeach
            </form>
        </div>

        {{-- Contact details --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold uppercase text-slate-600 mb-3">Detalii contact</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-xs text-slate-500">Email</dt><dd><a href="mailto:{{ $lead->email }}" class="text-red-700 hover:underline">{{ $lead->email }}</a></dd></div>
                @if($lead->phone)<div><dt class="text-xs text-slate-500">Telefon</dt><dd><a href="tel:{{ $lead->phone }}" class="text-slate-900">{{ $lead->phone }}</a></dd></div>@endif
                @if($lead->company)<div><dt class="text-xs text-slate-500">Companie</dt><dd class="text-slate-900">{{ $lead->company }}</dd></div>@endif
                <div><dt class="text-xs text-slate-500">Sursă</dt><dd class="text-slate-900">{{ $lead->source }}</dd></div>
                @if($lead->source_url)<div><dt class="text-xs text-slate-500">Pagină</dt><dd class="text-xs text-slate-600 break-all">{{ $lead->source_url }}</dd></div>@endif
            </dl>
        </div>

        {{-- Attribution --}}
        @if($lead->utm_source || $lead->utm_campaign || $lead->gclid || $lead->fbclid)
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="text-xs font-semibold uppercase text-slate-600 mb-3">Atribuire campanie</h3>
                <dl class="space-y-2 text-sm">
                    @if($lead->utm_source)<div><dt class="text-xs text-slate-500">Sursă</dt><dd class="font-mono">{{ $lead->utm_source }}</dd></div>@endif
                    @if($lead->utm_medium)<div><dt class="text-xs text-slate-500">Mediu</dt><dd class="font-mono">{{ $lead->utm_medium }}</dd></div>@endif
                    @if($lead->utm_campaign)<div><dt class="text-xs text-slate-500">Campanie</dt><dd class="font-mono">{{ $lead->utm_campaign }}</dd></div>@endif
                    @if($lead->gclid)<div><dt class="text-xs text-slate-500">Google Ads (gclid)</dt><dd class="font-mono text-xs break-all">{{ Str::limit($lead->gclid, 40) }}</dd></div>@endif
                    @if($lead->fbclid)<div><dt class="text-xs text-slate-500">Meta (fbclid)</dt><dd class="font-mono text-xs break-all">{{ Str::limit($lead->fbclid, 40) }}</dd></div>@endif
                </dl>
            </div>
        @endif

        {{-- Meta --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold uppercase text-slate-600 mb-3">Meta</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-xs text-slate-500">IP</dt><dd class="font-mono">{{ $lead->ip ?: '—' }}</dd></div>
                @if($lead->user_agent)<div><dt class="text-xs text-slate-500">User Agent</dt><dd class="text-xs text-slate-600 break-all">{{ Str::limit($lead->user_agent, 100) }}</dd></div>@endif
                @if($lead->handled_at)<div><dt class="text-xs text-slate-500">Manipulat</dt><dd>{{ $lead->handled_at->format('d M Y, H:i') }}</dd></div>@endif
            </dl>
        </div>
    </div>
</div>
@endsection
