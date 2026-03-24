<?php

namespace App\View\Composers;

use App\Models\Call;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TranscriptSidebarComposer
{
    public function compose(View $view): void
    {
        $tenantId = auth()->check() ? auth()->user()->tenant_id : null;
        $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();
        $cacheKey = 'sidebar_channels_' . ($isSuperAdmin ? 'admin' : ($tenantId ?? 'guest'));

        $channels = Cache::remember($cacheKey, 60, function () use ($isSuperAdmin) {
            $items = [];

            // Voice — check if any calls exist
            $callsQuery = Call::query();
            if ($callsQuery->exists()) {
                $items[] = [
                    'type' => 'voice',
                    'label' => 'Voce',
                    'url' => route('dashboard.calls.index'),
                    'route_match' => 'dashboard/apeluri*',
                    'icon' => 'phone',
                ];
            }

            // Text-based channels
            $channelTypes = [
                Channel::TYPE_WEB_CHATBOT => ['label' => 'Web Chatbot', 'icon' => 'globe'],
                Channel::TYPE_WHATSAPP => ['label' => 'WhatsApp', 'icon' => 'message-circle'],
                Channel::TYPE_FACEBOOK_MESSENGER => ['label' => 'Facebook Messenger', 'icon' => 'facebook'],
                Channel::TYPE_INSTAGRAM_DM => ['label' => 'Instagram DM', 'icon' => 'instagram'],
            ];

            foreach ($channelTypes as $type => $meta) {
                $channelIds = Channel::where('type', $type)->pluck('id');
                if ($channelIds->isEmpty()) {
                    continue;
                }

                $hasConversations = Conversation::whereIn('channel_id', $channelIds)->exists();
                if ($hasConversations) {
                    $items[] = [
                        'type' => $type,
                        'label' => $meta['label'],
                        'url' => route('dashboard.conversations.index', ['channelType' => $type]),
                        'route_match' => "dashboard/transcrieri/{$type}*",
                        'icon' => $meta['icon'],
                    ];
                }
            }

            return $items;
        });

        $view->with('sidebarTranscriptChannels', $channels);
    }
}
