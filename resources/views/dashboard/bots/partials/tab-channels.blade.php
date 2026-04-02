    <div id="section-channels" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100">
                        <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900">Canale & Numere</h2>
                </div>
                <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Gestioneaza
                </a>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Channels --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Canale conectate</h3>
                        @if($bot->channels && $bot->channels->count() > 0)
                            @php
                                $channelDotColors = [
                                    'connected' => 'bg-green-500',
                                    'pending' => 'bg-amber-500',
                                    'error' => 'bg-red-500',
                                    'disconnected' => 'bg-slate-400',
                                ];
                                $channelIconColors = [
                                    'voice' => 'text-red-600',
                                    'whatsapp' => 'text-green-600',
                                    'facebook_messenger' => 'text-blue-600',
                                    'instagram_dm' => 'text-pink-600',
                                    'web_chatbot' => 'text-slate-600',
                                ];
                            @endphp
                            <ul class="space-y-2.5">
                                @foreach($bot->channels as $channel)
                                    <li class="flex items-center gap-3 text-sm text-slate-700">
                                        <div class="relative shrink-0">
                                            <div class="w-8 h-8 rounded-lg {{ $channel->is_active ? 'bg-slate-100' : 'bg-slate-50' }} flex items-center justify-center">
                                                @include('dashboard.bots.channels._channel-icon', ['type' => $channel->type, 'class' => 'w-4 h-4 ' . ($channelIconColors[$channel->type] ?? 'text-slate-500')])
                                            </div>
                                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white {{ $channelDotColors[$channel->status] ?? 'bg-slate-400' }}"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-slate-900 truncate text-sm">{{ $channel->name ?? $channel->getDisplayName() }}</p>
                                            <p class="text-xs text-slate-400">{{ $channel->is_active ? 'Activ' : 'Inactiv' }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-400 italic">Niciun canal conectat.</p>
                        @endif
                    </div>

                    {{-- Phone numbers --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Numere de telefon</h3>
                        @if($bot->phoneNumbers && $bot->phoneNumbers->count() > 0)
                            <ul class="space-y-2">
                                @foreach($bot->phoneNumbers as $number)
                                    <li class="flex items-center gap-2 text-sm text-slate-700 font-mono py-1.5 px-3 rounded-lg bg-slate-50">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $number->number }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-400 italic">Niciun numar asociat.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>