@extends('layouts.dashboard')

@section('title', 'Canale')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Canale</span>
@endsection

@php
    use App\Models\Channel;

    $channelMeta = [
        Channel::TYPE_WEB_CHATBOT => [
            'label' => 'Chat web',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>',
            'tint' => 'bg-coralsoft text-coralh',
            'desc' => 'Widget pe site-ul tău',
            'connectAction' => 'inline', // se setează automat la creare bot
        ],
        Channel::TYPE_VOICE => [
            'label' => 'Voce telefonică',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>',
            'tint' => 'bg-[#DCEBFA] text-[#1E40AF]',
            'desc' => 'Răspunde la apeluri',
            'connectAction' => 'numbers', // /dashboard/numere
        ],
        Channel::TYPE_WHATSAPP => [
            'label' => 'WhatsApp Business',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>',
            'tint' => 'bg-[#D7EFE0] text-emerald-700',
            'desc' => 'Mesaje WhatsApp Business',
            'connectAction' => 'whatsapp',
        ],
        Channel::TYPE_FACEBOOK_MESSENGER => [
            'label' => 'Facebook Messenger',
            'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.4 0 0 4.97 0 11.1c0 3.5 1.74 6.6 4.46 8.6V24l4.08-2.24c1.09.3 2.24.46 3.46.46 6.6 0 12-4.97 12-11.1S18.6 0 12 0zm1.18 14.93l-3.06-3.27-5.97 3.27 6.57-6.97 3.13 3.27 5.91-3.27-6.58 6.97z"/></svg>',
            'tint' => 'bg-[#DCEBFA] text-[#1877F2]',
            'desc' => 'DM Facebook Page',
            'connectAction' => 'facebook',
        ],
        Channel::TYPE_INSTAGRAM_DM => [
            'label' => 'Instagram DM',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg>',
            'tint' => 'bg-[#E6DFF3] text-[#5B21B6]',
            'desc' => 'DM Instagram Business',
            'connectAction' => 'instagram',
        ],
    ];

    $statusBadge = function (?\App\Models\Channel $channel) {
        if (!$channel) {
            return ['Neconectat', 'bg-cream text-muted border-line'];
        }
        if ($channel->is_active && ($channel->status ?? '') === 'connected') {
            return ['Conectat', 'bg-emerald-50 text-emerald-700 border-emerald-200'];
        }
        if ($channel->is_active) {
            return [ucfirst($channel->status ?: 'în setup'), 'bg-amber-50 text-amber-800 border-amber-200'];
        }
        return ['Inactiv', 'bg-cream text-muted border-line'];
    };
@endphp

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Canale</h1>
            <p class="mt-2 text-sm text-muted">Fiecare canal se conectează la <strong>un agent anume</strong> — un agent poate avea WhatsApp, alt agent Facebook, etc. Aici vezi statusul tuturor într-un singur loc.</p>
        </div>
    </div>

    @if(!empty($noTenant))
        <div class="card p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-coralsoft text-coralh mx-auto mb-3 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="display text-base font-semibold text-ink mb-1">Vezi platforma ca un tenant</h3>
            <p class="text-sm text-muted">Folosește selectorul „Vezi ca" din dreapta-sus pentru a alege un tenant.</p>
        </div>
    @elseif($bots->isEmpty())
        <div class="card p-12 text-center max-w-2xl mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-coralsoft text-coralh mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
            </div>
            <h3 class="display text-2xl font-semibold text-ink mb-2">Nu ai niciun agent încă</h3>
            <p class="text-sm text-muted mb-5 max-w-md mx-auto">Canalele se conectează la un agent. Creează primul agent ca să poți conecta WhatsApp/FB/Instagram.</p>
            <a href="{{ route('dashboard.bots.create') }}" class="btn-coral inline-flex items-center gap-2 rounded-pill px-5 py-3 text-sm font-medium">
                Creează primul agent →
            </a>
        </div>
    @else
        {{-- Quick start strip — Meta channels prominent --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach([Channel::TYPE_WHATSAPP, Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM] as $type)
                @php
                    $meta = $channelMeta[$type];
                    $allowed = in_array($type, $allowedChannels, true);
                @endphp
                <div class="card p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $meta['tint'] }} shrink-0">
                        {!! $meta['icon'] !!}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-ink truncate">{{ $meta['label'] }}</div>
                        @if(!$allowed)
                            <div class="text-2xs text-amber-700">Nu e în plan · <a href="{{ route('dashboard.billing.index') }}" class="underline">upgrade</a></div>
                        @else
                            <div class="text-2xs text-muted">{{ $meta['desc'] }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Matrix per bot --}}
        <div class="space-y-4">
            @foreach($bots as $bot)
                <div class="card overflow-hidden">
                    <div class="px-5 py-3 border-b border-line flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <a href="{{ route('dashboard.workspace.show', $bot) }}" class="display text-base font-semibold text-ink truncate hover:text-coralh">
                                {{ $bot->name }}
                            </a>
                            @if($bot->is_active)
                                <span class="inline-flex items-center gap-1.5 text-2xs text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> activ
                                </span>
                            @else
                                <span class="text-2xs text-muted">inactiv</span>
                            @endif
                        </div>
                        <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-2xs text-coralh hover:underline shrink-0">Pagina canalelor →</a>
                    </div>

                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line">
                            @foreach(\App\Http\Controllers\Dashboard\ChannelsHubController::CHANNEL_TYPES as $type)
                                @php
                                    $meta = $channelMeta[$type];
                                    $channel = $channelMatrix[$bot->id][$type] ?? null;
                                    [$badgeText, $badgeClass] = $statusBadge($channel);
                                    $allowed = in_array($type, $allowedChannels, true);

                                    // CTA URL per channel type
                                    $cta = null;
                                    $ctaLabel = null;
                                    if ($channel) {
                                        $cta = match($type) {
                                            Channel::TYPE_WHATSAPP => route('dashboard.bots.channels.whatsapp.connected', ['bot' => $bot, 'channel' => $channel]),
                                            Channel::TYPE_FACEBOOK_MESSENGER => route('dashboard.bots.channels.facebook.connected', ['bot' => $bot, 'channel' => $channel]),
                                            Channel::TYPE_INSTAGRAM_DM => route('dashboard.bots.channels.instagram.connected', ['bot' => $bot, 'channel' => $channel]),
                                            Channel::TYPE_WEB_CHATBOT => route('dashboard.bots.channels.chatbot-setup', ['bot' => $bot, 'channel' => $channel]),
                                            default => route('dashboard.bots.channels.index', $bot),
                                        };
                                        $ctaLabel = 'Setări';
                                    } elseif ($allowed) {
                                        // FB Messenger + Instagram DM share the same Meta OAuth flow.
                                        // The new flow lists pages, lets user pick which to attach,
                                        // and exchanges tokens server-side — no more manual page_id +
                                        // access_token paste from Meta Business Manager.
                                        $cta = match($type) {
                                            Channel::TYPE_WHATSAPP => route('dashboard.bots.channels.whatsapp.connect', $bot),
                                            Channel::TYPE_FACEBOOK_MESSENGER,
                                            Channel::TYPE_INSTAGRAM_DM => route('dashboard.bots.channels.meta.connect', $bot),
                                            Channel::TYPE_VOICE => route('dashboard.numbers.index'),
                                            default => route('dashboard.bots.channels.index', $bot),
                                        };
                                        $ctaLabel = in_array($type, [Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM], true)
                                            ? 'Conectează cu Facebook →'
                                            : 'Conectează →';
                                    } else {
                                        $cta = route('dashboard.billing.index');
                                        $ctaLabel = 'Upgrade plan';
                                    }
                                @endphp
                                <tr class="hover:bg-cream/40 transition">
                                    <td class="px-5 py-3 w-12">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $meta['tint'] }}">
                                            {!! $meta['icon'] !!}
                                        </div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="text-sm font-medium text-ink">{{ $meta['label'] }}</div>
                                        <div class="text-2xs text-muted">{{ $meta['desc'] }}</div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs font-medium border {{ $badgeClass }}">{{ $badgeText }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if($allowed || $channel)
                                            <a href="{{ $cta }}" class="text-xs font-medium {{ $channel ? 'text-coralh' : 'text-coralh' }} hover:underline whitespace-nowrap">{{ $ctaLabel }}</a>
                                        @else
                                            <span class="text-2xs text-amber-700">Nu e în plan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        {{-- Help footer --}}
        <div class="card p-5 bg-cream/40 border-dashed">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-coralsoft text-coralh flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-sm text-inkSoft space-y-1.5">
                    <div><strong class="text-ink">Pentru WhatsApp / Facebook / Instagram</strong> ai nevoie de o pagină Facebook Business + verificare Meta. Wizard-ul de conectare îți spune ce să faci pas cu pas.</div>
                    <div class="text-2xs text-muted">Pentru voce telefonică, cumpără un număr din <a href="{{ route('dashboard.numbers.index') }}" class="text-coralh hover:underline">Numere telefon</a> și asignează-l la agent.</div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
