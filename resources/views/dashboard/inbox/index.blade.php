@extends('layouts.dashboard')

@section('title', 'Inbox')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Inbox</span>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- Stats bar + filter chips --}}
    @php
        $params = request()->only('status', 'channel_type', 'q');
        $isMine = request()->boolean('mine');
        $isUnassigned = request()->boolean('unassigned');
        $isBot = request()->boolean('bot_only');
        $isAll = !$isMine && !$isUnassigned && !$isBot;
    @endphp

    <div class="flex items-start justify-between mb-6 gap-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Inbox</h1>
            <p class="text-sm text-slate-500 mt-1">Toate conversațiile, pe toate canalele.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            @foreach(['status', 'channel_type'] as $k)
                @if(request($k))<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif
            @endforeach
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Caută după nume sau identificator..."
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-72">
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Caută</button>
        </form>
    </div>

    {{-- Quick filters --}}
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <a href="{{ route('dashboard.inbox', $params) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isAll ? 'bg-slate-900 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            Toate <span class="text-xs opacity-70">{{ number_format($stats['total']) }}</span>
        </a>
        <a href="{{ route('dashboard.inbox', array_merge($params, ['mine' => 1])) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isMine ? 'bg-red-700 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            Ale mele <span class="text-xs opacity-70">{{ number_format($stats['mine']) }}</span>
        </a>
        <a href="{{ route('dashboard.inbox', array_merge($params, ['unassigned' => 1])) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isUnassigned ? 'bg-amber-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            Neatribuite <span class="text-xs opacity-70">{{ number_format($stats['unassigned']) }}</span>
        </a>
        <a href="{{ route('dashboard.inbox', array_merge($params, ['bot_only' => 1])) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isBot ? 'bg-emerald-700 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            La bot <span class="text-xs opacity-70">{{ number_format($stats['bot']) }}</span>
        </a>

        <span class="border-l border-slate-300 h-5 mx-1"></span>

        @foreach([
            'voice' => ['Voice', 'red'],
            'whatsapp' => ['WhatsApp', 'green'],
            'facebook_messenger' => ['Facebook', 'blue'],
            'instagram_dm' => ['Instagram', 'pink'],
            'web_chatbot' => ['Web', 'slate'],
        ] as $type => [$label, $color])
            @php $active = request('channel_type') === $type; @endphp
            <a href="{{ route('dashboard.inbox', array_filter([
                'channel_type' => $active ? null : $type,
                'mine' => $isMine ? 1 : null,
                'unassigned' => $isUnassigned ? 1 : null,
                'bot_only' => $isBot ? 1 : null,
                'q' => request('q'),
            ])) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs transition-colors {{ $active ? 'bg-' . $color . '-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500"></span>
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Conversations list --}}
    @if($conversations->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-slate-200 px-8 py-16 text-center">
            <h2 class="text-base font-semibold text-slate-700">Nimic în inbox</h2>
            <p class="text-sm text-slate-500 mt-2">Nicio conversație nu corespunde filtrului curent.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Canal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Atribuit</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Mesaje</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Activitate</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($conversations as $convo)
                        @php
                            $assignType = $convo->isHumanAssigned() ? 'user' : ($convo->isBotAssigned() ? 'bot' : 'none');
                            $channelType = $convo->channel?->type ?? 'unknown';
                            $channelColor = [
                                'voice' => 'red', 'whatsapp' => 'green', 'facebook_messenger' => 'blue',
                                'instagram_dm' => 'pink', 'web_chatbot' => 'slate',
                            ][$channelType] ?? 'slate';
                        @endphp
                        <tr data-conversation-id="{{ $convo->id }}" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900">{{ $convo->contact_name ?: $convo->contact_identifier ?: '—' }}</div>
                                <div class="text-xs text-slate-500 font-mono truncate max-w-xs">{{ $convo->contact_identifier }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $channelColor }}-50 text-{{ $channelColor }}-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $channelColor }}-500"></span>
                                    {{ $convo->channel?->getDisplayName() ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span data-assignee-badge data-assignee-type="{{ $assignType }}"
                                      class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium
                                      @if($assignType === 'user') bg-amber-50 text-amber-700
                                      @elseif($assignType === 'bot') bg-emerald-50 text-emerald-700
                                      @else bg-slate-100 text-slate-500
                                      @endif">
                                    @if($assignType === 'user')
                                        Operator: {{ $convo->assigneeUser?->name ?? '?' }}
                                    @elseif($assignType === 'bot')
                                        Bot: {{ $convo->bot?->name ?? '?' }}
                                    @else
                                        Neatribuit
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $convo->messages_count }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ ($convo->last_activity_at ?? $convo->created_at)?->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('dashboard.conversations.show', $convo) }}" class="text-slate-500 hover:text-slate-700">Vezi</a>
                                    @if($assignType !== 'user')
                                        <form method="POST" action="{{ route('dashboard.conversations.take-over', $convo) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-emerald-700 hover:text-emerald-900 font-medium">Preia</button>
                                        </form>
                                    @elseif($convo->assignee_user_id === auth()->id())
                                        <form method="POST" action="{{ route('dashboard.conversations.hand-back', $convo) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-slate-500 hover:text-slate-700">Înapoi la bot</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $conversations->links() }}
        </div>
    @endif
@endsection
