@extends('layouts.dashboard')

@section('title', "Transcrieri — {$channelLabel}")

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-500">Transcrieri</span>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">{{ $channelLabel }}</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $channelLabel }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $conversations->total() }} conversații</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('dashboard.conversations.index', ['channelType' => $channelType]) }}" class="mb-6">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap gap-3">
                {{-- Search --}}
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Caută după contact..."
                           class="w-full rounded-lg border border-slate-300 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                </div>

                {{-- Bot filter --}}
                <select name="bot"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition min-w-[160px]">
                    <option value="">Toți boții</option>
                    @foreach($bots as $bot)
                        <option value="{{ $bot->id }}" {{ request('bot') == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
                    @endforeach
                </select>

                {{-- Status filter --}}
                <select name="status"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition min-w-[150px]">
                    <option value="">Toate statusurile</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activă</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completată</option>
                </select>

                {{-- Date from --}}
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />

                {{-- Date to --}}
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />

                {{-- Buttons --}}
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filtrează
                    </button>
                    @if(request()->hasAny(['search', 'bot', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('dashboard.conversations.index', ['channelType' => $channelType]) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            Resetează
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Table --}}
    @if($conversations->count() > 0)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="px-5 py-3 font-medium text-slate-500">#</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Bot</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Contact</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Mesaje</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Status</th>
                            @if(auth()->user()->isSuperAdmin())
                            <th class="px-5 py-3 font-medium text-slate-500">Cost</th>
                            @endif
                            <th class="px-5 py-3 font-medium text-slate-500">Data</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($conversations as $convo)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500 font-mono text-xs">
                                    {{ $convo->id }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">
                                    {{ $convo->bot?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                    @if($convo->contact_name)
                                        <span class="font-medium">{{ $convo->contact_name }}</span>
                                    @endif
                                    @if($convo->contact_identifier)
                                        <span class="text-slate-400 text-xs ml-1">{{ $convo->contact_identifier }}</span>
                                    @endif
                                    @if(!$convo->contact_name && !$convo->contact_identifier)
                                        <span class="text-slate-400">Anonim</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                    {{ $convo->messages_count ?? 0 }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    @if($convo->status === 'active')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Activă</span>
                                    @elseif($convo->status === 'completed')
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">Completată</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $convo->status ?? '—' }}</span>
                                    @endif
                                </td>
                                @if(auth()->user()->isSuperAdmin())
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600 font-mono text-xs">
                                    @if($convo->cost_cents)
                                        {{ number_format($convo->cost_cents / 100, 2, ',', '.') }}€
                                    @else
                                        —
                                    @endif
                                </td>
                                @endif
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500" title="{{ $convo->created_at->format('d.m.Y H:i:s') }}">
                                    @if($convo->created_at->diffInHours(now()) < 24)
                                        {{ $convo->created_at->diffForHumans() }}
                                    @else
                                        {{ $convo->created_at->format('d.m.Y H:i') }}
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('dashboard.conversations.show', $convo) }}" title="Vizualizează"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('dashboard.conversations.destroy', $convo) }}" class="shrink-0"
                                              onsubmit="return confirm('Ești sigur că vrei să ștergi această conversație?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Șterge"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 bg-white text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $conversations->links() }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-16 px-4">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Nicio conversație</h3>
            <p class="text-sm text-slate-500 mb-6 text-center max-w-sm">
                @if(request()->hasAny(['search', 'bot', 'status', 'date_from', 'date_to']))
                    Nu am găsit conversații care să corespundă filtrelor.
                @else
                    Nu există conversații pe canalul {{ $channelLabel }} momentan.
                @endif
            </p>
            @if(request()->hasAny(['search', 'bot', 'status', 'date_from', 'date_to']))
                <a href="{{ route('dashboard.conversations.index', ['channelType' => $channelType]) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Resetează filtrele
                </a>
            @endif
        </div>
    @endif
@endsection
