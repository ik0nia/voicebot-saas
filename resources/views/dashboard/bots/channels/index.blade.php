@extends('layouts.dashboard')

@section('title', 'Canale - ' . $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boți</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Canale</span>
@endsection

@section('content')
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Canale - {{ $bot->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Gestionează canalele de comunicare ale botului</p>
        </div>
        <a href="{{ route('dashboard.bots.show', $bot) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Înapoi la bot
        </a>
    </div>

    @php
        $tenant = auth()->user()->tenant;
        $allowedChannels = $tenant->settings['allowed_channels'] ?? ['voice'];
        $connectedTypes = $channels->pluck('type')->toArray();

        $channelMeta = [
            'voice' => [
                'label' => 'Voice',
                'description' => 'Apeluri telefonice cu voce AI naturală',
                'bg' => 'bg-red-50',
                'icon_bg' => 'bg-red-100',
                'icon_color' => 'text-red-600',
                'border' => 'border-red-200',
                'dot' => 'bg-red-500',
            ],
            'whatsapp' => [
                'label' => 'WhatsApp (in curând)',
                'description' => 'Răspunsuri instant pe WhatsApp Business, 24/7',
                'bg' => 'bg-green-50',
                'icon_bg' => 'bg-green-100',
                'icon_color' => 'text-green-600',
                'border' => 'border-green-200',
                'dot' => 'bg-green-500',
            ],
            'facebook_messenger' => [
                'label' => 'Facebook Messenger (in curând)',
                'description' => 'Conectează pagina Facebook și răspunde automat',
                'bg' => 'bg-blue-50',
                'icon_bg' => 'bg-blue-100',
                'icon_color' => 'text-blue-600',
                'border' => 'border-blue-200',
                'dot' => 'bg-blue-500',
            ],
            'instagram_dm' => [
                'label' => 'Instagram DM (in curând)',
                'description' => 'Gestionează mesajele private Instagram cu AI',
                'bg' => 'bg-pink-50',
                'icon_bg' => 'bg-pink-100',
                'icon_color' => 'text-pink-600',
                'border' => 'border-pink-200',
                'dot' => 'bg-pink-500',
            ],
            'web_chatbot' => [
                'label' => 'Web Chatbot',
                'description' => 'Widget de chat pe site-ul tău, instalare rapidă',
                'bg' => 'bg-slate-50',
                'icon_bg' => 'bg-slate-100',
                'icon_color' => 'text-slate-600',
                'border' => 'border-slate-200',
                'dot' => 'bg-slate-500',
            ],
        ];

        $statusColors = [
            'connected' => 'bg-green-50 text-green-700',
            'pending' => 'bg-amber-50 text-amber-700',
            'error' => 'bg-red-50 text-red-700',
            'disconnected' => 'bg-slate-100 text-slate-600',
        ];
        $statusLabels = [
            'connected' => 'Conectat',
            'pending' => 'În așteptare',
            'error' => 'Eroare',
            'disconnected' => 'Deconectat',
        ];
    @endphp

    {{-- Connected channels --}}
    @if($channels->count() > 0)
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Canale conectate ({{ $channels->where('is_active', true)->count() }}/{{ $channels->count() }})</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($channels as $channel)
                    @php $meta = $channelMeta[$channel->type] ?? $channelMeta['web_chatbot']; @endphp
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl {{ $meta['icon_bg'] }} flex items-center justify-center shrink-0">
                                    @include('dashboard.bots.channels._channel-icon', ['type' => $channel->type, 'class' => 'w-6 h-6 ' . $meta['icon_color']])
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $channel->name ?? $channel->getDisplayName() }}</h3>
                                    <p class="text-xs text-slate-500">{{ $channelMeta[$channel->type]['label'] ?? ucfirst($channel->type) }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$channel->status] ?? $statusColors['disconnected'] }}">
                                {{ $statusLabels[$channel->status] ?? $channel->status }}
                            </span>
                        </div>

                        @if($channel->last_activity_at)
                            <p class="text-xs text-slate-400 mb-4">
                                Ultima activitate: {{ $channel->last_activity_at->diffForHumans() }}
                            </p>
                        @endif

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            {{-- Toggle active --}}
                            <form method="POST" action="{{ route('dashboard.bots.channels.toggle', [$bot, $channel]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $channel->is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $channel->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                                    {{ $channel->is_active ? 'Activ' : 'Inactiv' }}
                                </button>
                            </form>

                            <div class="flex items-center gap-2">
                                {{-- Edit button --}}
                                <button type="button"
                                        onclick="openEditModal({{ $channel->id }}, '{{ addslashes($channel->name ?? '') }}', '{{ addslashes($channel->external_id ?? '') }}')"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editează
                                </button>

                                {{-- Delete button --}}
                                <form method="POST" action="{{ route('dashboard.bots.channels.destroy', [$bot, $channel]) }}"
                                      onsubmit="return confirm('Ești sigur că vrei să ștergi acest canal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Șterge
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Add new channels --}}
    <div>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Adaugă Canal</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(\App\Models\Channel::TYPES as $type)
                @php
                    $meta = $channelMeta[$type];
                    $isConnected = in_array($type, $connectedTypes);
                    $isAllowed = in_array($type, $allowedChannels);
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-11 h-11 rounded-xl {{ $meta['icon_bg'] }} flex items-center justify-center shrink-0">
                            @include('dashboard.bots.channels._channel-icon', ['type' => $type, 'class' => 'w-6 h-6 ' . $meta['icon_color']])
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $meta['label'] }}</h3>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mb-4 flex-1">{{ $meta['description'] }}</p>

                    @if($isConnected)
                        <span class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm font-medium text-green-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Conectat
                        </span>
                    @elseif(!$isAllowed)
                        <a href="{{ route('dashboard.billing.index') }}"
                           class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-100 border border-slate-200 px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Upgrade necessar
                        </a>
                    @else
                        <button type="button"
                                onclick="openAddModal('{{ $type }}', '{{ $meta['label'] }}')"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Conectează
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Add channel modal --}}
    <div id="add-modal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-slate-900/50" onclick="closeAddModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900" id="add-modal-title">Adaugă Canal</h3>
                </div>
                <form method="POST" action="{{ route('dashboard.bots.channels.store', $bot) }}">
                    @csrf
                    <input type="hidden" name="type" id="add-channel-type">
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="add-channel-name" class="block text-sm font-medium text-slate-700 mb-1">Nume canal (opțional)</label>
                            <input type="text" name="name" id="add-channel-name"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none"
                                   placeholder="ex. WhatsApp Business Principal">
                        </div>
                        <div>
                            <label for="add-channel-external-id" class="block text-sm font-medium text-slate-700 mb-1">ID Extern (opțional)</label>
                            <input type="text" name="external_id" id="add-channel-external-id"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none"
                                   placeholder="ex. ID pagină, număr telefon">
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" onclick="closeAddModal()"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            Anulează
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors">
                            Adaugă Canal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit channel modal --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-slate-900/50" onclick="closeEditModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">Editează Canal</h3>
                </div>
                <form method="POST" id="edit-channel-form">
                    @csrf
                    @method('PUT')
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="edit-channel-name" class="block text-sm font-medium text-slate-700 mb-1">Nume canal</label>
                            <input type="text" name="name" id="edit-channel-name"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none">
                        </div>
                        <div>
                            <label for="edit-channel-external-id" class="block text-sm font-medium text-slate-700 mb-1">ID Extern</label>
                            <input type="text" name="external_id" id="edit-channel-external-id"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none">
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" onclick="closeEditModal()"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            Anulează
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors">
                            Salvează
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openAddModal(type, label) {
        document.getElementById('add-channel-type').value = type;
        document.getElementById('add-modal-title').textContent = 'Adaugă Canal - ' + label;
        document.getElementById('add-channel-name').value = '';
        document.getElementById('add-channel-external-id').value = '';
        document.getElementById('add-modal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('add-modal').classList.add('hidden');
    }

    function openEditModal(channelId, name, externalId) {
        document.getElementById('edit-channel-name').value = name;
        document.getElementById('edit-channel-external-id').value = externalId;
        document.getElementById('edit-channel-form').action = '{{ route("dashboard.bots.channels.index", $bot) }}/' + channelId;
        document.getElementById('edit-modal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
    }
</script>
@endpush
