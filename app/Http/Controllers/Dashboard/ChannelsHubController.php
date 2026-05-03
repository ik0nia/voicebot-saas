<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;

/**
 * Hub global pentru CANALE — listă agregată cross-bot.
 *
 * Înainte: pentru a conecta WhatsApp trebuia /agenți → click bot → tab Canale
 * → buton Connect (4 click-uri). Acum: sidebar „Canale" → vezi toți boții cu
 * statusul fiecărui canal + CTA direct către wizard (2 click-uri).
 *
 * Read-only la nivel global; mutațiile (connect, disconnect) rămân pe rutele
 * existente per bot, pentru a păstra audit log + tenant scope.
 */
class ChannelsHubController extends Controller
{
    /** Toate canalele Meta pe care un agent le poate avea, plus voice + web. */
    public const CHANNEL_TYPES = [
        Channel::TYPE_WEB_CHATBOT,
        Channel::TYPE_VOICE,
        Channel::TYPE_WHATSAPP,
        Channel::TYPE_FACEBOOK_MESSENGER,
        Channel::TYPE_INSTAGRAM_DM,
    ];

    public function index()
    {
        $tenant = auth()->user()->tenant;

        // Super-admin orphan: shell gol cu hint clear.
        if (!$tenant) {
            return view('dashboard.channels-hub.index', [
                'bots' => collect(),
                'channelMatrix' => [],
                'allowedChannels' => [],
                'noTenant' => true,
            ]);
        }

        // Toți boții activi sau inactivi ai tenantului.
        $bots = Bot::with(['channels' => function ($q) {
                $q->whereIn('type', self::CHANNEL_TYPES);
            }])
            ->orderBy('name')
            ->get();

        // Channels pe care planul tenantului permite să fie folosite.
        // data_get gestionează null-ul pe $tenant->settings (poate fi null
        // pentru tenanți noi); fără el, accesul cu cheie pe null = TypeError.
        $allowedChannels = data_get($tenant, 'settings.allowed_channels', ['voice', 'web_chatbot']);

        // Construiește matrix pentru tabel: bot.id => type => Channel|null
        $channelMatrix = [];
        foreach ($bots as $bot) {
            foreach (self::CHANNEL_TYPES as $type) {
                $channelMatrix[$bot->id][$type] = $bot->channels
                    ->firstWhere('type', $type);
            }
        }

        return view('dashboard.channels-hub.index', compact(
            'bots', 'channelMatrix', 'allowedChannels',
        ));
    }
}
