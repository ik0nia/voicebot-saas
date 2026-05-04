@extends('layouts.dashboard')

@section('title', 'Inbox')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Inbox</span>
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
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Inbox</h1>
            <p class="text-sm text-muted mt-2">Toate conversațiile, pe toate canalele.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            @foreach(['status', 'channel_type'] as $k)
                @if(request($k))<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif
            @endforeach
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Caută după nume sau identificator..."
                   class="rounded-lg border border-line bg-paper px-3 py-2 text-sm w-72 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            <button class="rounded-pill bg-ink hover:bg-inkSoft px-4 py-2 text-sm font-medium text-cream transition">Caută</button>
        </form>
    </div>

    {{-- Quick filters --}}
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <a href="{{ route('dashboard.inbox', $params) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isAll ? 'bg-ink text-cream' : 'bg-white border border-line text-inkSoft hover:bg-cream' }}">
            Toate <span class="text-xs opacity-70">{{ number_format($stats['total']) }}</span>
        </a>
        <a href="{{ route('dashboard.inbox', array_merge($params, ['mine' => 1])) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isMine ? 'bg-coral text-white' : 'bg-white border border-line text-inkSoft hover:bg-cream' }}">
            Ale mele <span class="text-xs opacity-70">{{ number_format($stats['mine']) }}</span>
        </a>
        <a href="{{ route('dashboard.inbox', array_merge($params, ['unassigned' => 1])) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isUnassigned ? 'bg-amber-600 text-white' : 'bg-white border border-line text-inkSoft hover:bg-cream' }}">
            Neatribuite <span class="text-xs opacity-70">{{ number_format($stats['unassigned']) }}</span>
        </a>
        <a href="{{ route('dashboard.inbox', array_merge($params, ['bot_only' => 1])) }}"
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors {{ $isBot ? 'bg-emerald-700 text-white' : 'bg-white border border-line text-inkSoft hover:bg-cream' }}">
            La bot <span class="text-xs opacity-70">{{ number_format($stats['bot']) }}</span>
        </a>

        <span class="border-l border-line h-5 mx-1"></span>

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
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs transition-colors {{ $active ? 'bg-' . $color . '-600 text-white' : 'bg-white border border-line text-inkSoft hover:bg-cream' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500"></span>
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Advanced filters: collapsed by default, opens to date range, bot, status, direction.
         Mutually-exclusive doesn't apply (calls and convs share the same params); when a
         filter doesn't apply to one stream it's just ignored there (e.g., direction is
         meaningful only for calls). --}}
    @php
        $hasAdvanced = request()->hasAny(['date_from', 'date_to', 'bot', 'status', 'direction']);
    @endphp
    <details class="mb-4 rounded-xl border border-line bg-white" {{ $hasAdvanced ? 'open' : '' }}>
        <summary class="px-4 py-2.5 cursor-pointer text-sm font-medium text-inkSoft hover:bg-cream transition select-none flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Filtre avansate
            @if($hasAdvanced)
                <span class="text-xs text-coral">●</span>
            @endif
        </summary>
        <form method="GET" class="px-4 pb-4 pt-2 grid grid-cols-2 md:grid-cols-5 gap-3">
            {{-- Preserve current chip + sort state --}}
            @foreach(['mine', 'unassigned', 'bot_only', 'channel_type', 'q', 'sort', 'dir'] as $k)
                @if(request($k))<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif
            @endforeach

            <div>
                <label class="block text-xs font-medium text-muted mb-1">De la</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-muted mb-1">Până la</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-muted mb-1">Agent</label>
                <select name="bot" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="">— toți —</option>
                    @foreach(($bots ?? collect()) as $b)
                        <option value="{{ $b->id }}" {{ request('bot') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-muted mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="">— oricare —</option>
                    @foreach(['active', 'completed', 'failed', 'busy', 'no_answer', 'canceled', 'abandoned'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-muted mb-1">Direcție (apel)</label>
                <select name="direction" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="">— ambele —</option>
                    <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Primit</option>
                    <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Ieșit</option>
                </select>
            </div>

            <div class="col-span-2 md:col-span-5 flex items-center justify-end gap-2 pt-1">
                @if($hasAdvanced)
                    <a href="{{ route('dashboard.inbox', request()->except(['date_from', 'date_to', 'bot', 'status', 'direction', 'page'])) }}"
                       class="text-xs text-muted hover:text-inkSoft">Resetează filtrele</a>
                @endif
                <button class="rounded-pill bg-ink hover:bg-inkSoft px-4 py-2 text-sm font-medium text-cream transition">Aplică</button>
            </div>
        </form>
    </details>

    @php
        // Helper for sortable headers — preserves all current filters,
        // toggles direction when clicking the same column twice. The
        // controller whitelists which sort keys are allowed, so an
        // operator pasting a weird ?sort= won't fall back to a SQL error.
        $sortLink = function (string $col, string $label) use ($sort, $dir) {
            $nextDir = ($sort === $col && $dir === 'desc') ? 'asc' : 'desc';
            $arrow = $sort === $col ? ($dir === 'desc' ? '↓' : '↑') : '';
            $url = request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nextDir]);
            $active = $sort === $col;
            return [
                'href' => $url,
                'label' => $label,
                'arrow' => $arrow,
                'active' => $active,
            ];
        };
    @endphp

    {{-- Conversations list --}}
    @if($conversations->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-line px-8 py-16 text-center">
            <h2 class="text-base font-semibold text-inkSoft">Nimic în inbox</h2>
            <p class="text-sm text-muted mt-2">Nicio conversație nu corespunde filtrului curent.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-line bg-white shadow-sm">
            <table class="min-w-full divide-y divide-line">
                <thead class="bg-cream">
                    <tr>
                        @php $h = $sortLink('contact_name', 'Contact'); @endphp
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase {{ $h['active'] ? 'text-ink' : 'text-muted' }}">
                            <a href="{{ $h['href'] }}" class="inline-flex items-center gap-1 hover:text-ink">{{ $h['label'] }} <span class="text-coral">{{ $h['arrow'] }}</span></a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Canal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Atribuit</th>
                        @php $h = $sortLink('messages_count', 'Volum'); @endphp
                        <th title="Mesaje pentru text, durată pentru voce" class="px-4 py-3 text-left text-xs font-semibold uppercase {{ $h['active'] ? 'text-ink' : 'text-muted' }}">
                            <a href="{{ $h['href'] }}" class="inline-flex items-center gap-1 hover:text-ink">{{ $h['label'] }} <span class="text-coral">{{ $h['arrow'] }}</span></a>
                        </th>
                        @php $h = $sortLink('last_activity_at', 'Activitate'); @endphp
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase {{ $h['active'] ? 'text-ink' : 'text-muted' }}">
                            <a href="{{ $h['href'] }}" class="inline-flex items-center gap-1 hover:text-ink">{{ $h['label'] }} <span class="text-coral">{{ $h['arrow'] }}</span></a>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-muted uppercase">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($conversations as $item)
                        @php
                            $isCall = $item->_type === 'call';
                            $assignType = $item->is_human_assigned ? 'user' : ($item->is_bot_assigned ? 'bot' : 'none');
                            $channelType = $item->channel_type;
                            $channelColor = [
                                'voice' => 'red', 'whatsapp' => 'green', 'facebook_messenger' => 'blue',
                                'instagram_dm' => 'pink', 'web_chatbot' => 'slate',
                            ][$channelType] ?? 'slate';
                            $duration = null;
                            if ($isCall && $item->duration_seconds !== null) {
                                $m = intdiv($item->duration_seconds, 60);
                                $s = $item->duration_seconds % 60;
                                $duration = $m > 0 ? sprintf('%dm %ds', $m, $s) : sprintf('%ds', $s);
                            }
                        @endphp
                        <tr data-item-type="{{ $item->_type }}" data-item-id="{{ $item->id }}" class="hover:bg-cream">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-ink flex items-center gap-1.5">
                                    {{ $item->contact_name ?: $item->contact_identifier ?: '—' }}
                                    @if($isCall && $item->direction === 'inbound')
                                        <span title="Apel primit" class="text-emerald-600 text-xs">↓</span>
                                    @elseif($isCall && $item->direction === 'outbound')
                                        <span title="Apel ieșit" class="text-blue-600 text-xs">↑</span>
                                    @endif
                                </div>
                                <div class="text-xs text-muted font-mono truncate max-w-xs">{{ $item->contact_identifier }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $channelColor }}-50 text-{{ $channelColor }}-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $channelColor }}-500"></span>
                                    {{ $item->channel_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span data-assignee-badge data-assignee-type="{{ $assignType }}"
                                      class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium
                                      @if($assignType === 'user') bg-amber-50 text-amber-700
                                      @elseif($assignType === 'bot') bg-emerald-50 text-emerald-700
                                      @else bg-cream text-muted
                                      @endif">
                                    @if($assignType === 'user')
                                        Operator: {{ $item->assignee_user_name ?? '?' }}
                                    @elseif($assignType === 'bot')
                                        Bot: {{ $item->bot_name ?? '?' }}
                                    @else
                                        Neatribuit
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-inkSoft">
                                @if($isCall)
                                    <span class="text-xs">
                                        {{ $duration ?? '—' }}
                                        @if($item->cost_cents !== null && $item->cost_cents > 0)
                                            <span class="text-line ml-1">· {{ number_format($item->cost_cents / 100, 2) }} EUR</span>
                                        @endif
                                        @if($item->recording_url)
                                            <span title="Are recording" class="text-coral ml-1">●</span>
                                        @endif
                                    </span>
                                @else
                                    {{ $item->messages_count }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-muted">
                                @php
                                    $when = $item->last_activity_at;
                                @endphp
                                @if($when)
                                    <span title="{{ $when->locale('ro')->isoFormat('DD MMM YYYY, HH:mm') }}" class="cursor-help">
                                        {{ $when->locale('ro')->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-line">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route($item->route_name, $item->route_params) }}" class="text-muted hover:text-inkSoft">Vezi</a>
                                    @if(!$isCall)
                                        @if($assignType !== 'user')
                                            <form method="POST" action="{{ route('dashboard.conversations.take-over', $item->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-emerald-700 hover:text-emerald-900 font-medium">Preia</button>
                                            </form>
                                        @elseif($item->assignee_user_id === auth()->id())
                                            <form method="POST" action="{{ route('dashboard.conversations.hand-back', $item->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-muted hover:text-inkSoft">Înapoi la bot</button>
                                            </form>
                                        @endif
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
