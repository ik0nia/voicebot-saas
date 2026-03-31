<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotEmbedController extends Controller
{
    /**
     * Servește embed.js — un loader minimal care:
     * 1. Citește data-channel-id din script tag
     * 2. Verifică domeniul curent contra API-ului
     * 3. Dacă e ok, injectează iframe-ul chatbot
     */
    public function embedScript(): Response
    {
        $js = view('chatbot.embed-js')->render();

        return response($js)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Endpoint de verificare domeniu (apelat de embed.js).
     * Verifică dacă originea request-ului e autorizată pentru channel-ul dat.
     */
    public function checkDomain(Request $request): JsonResponse
    {
        // Rate limiting: 60 domain checks per minute per IP
        $rateLimitKey = 'chatbot:domain:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            return response()->json(['error' => 'Rate limited'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $channelId = $request->input('channel_id');

        if (!$channelId) {
            return response()->json(['allowed' => false, 'error' => 'Missing channel_id.'], 400);
        }

        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('type', 'web_chatbot')
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['allowed' => false, 'error' => 'Channel not found or inactive.'], 404);
        }

        $bot = \App\Models\Bot::withoutGlobalScopes()->find($channel->bot_id);

        if (!$bot || !$bot->is_active) {
            return response()->json(['allowed' => false, 'error' => 'Bot inactive.'], 403);
        }

        // Obține originea din header
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '';

        // Extrage domeniul din origin
        $originDomain = $this->extractDomain($origin);

        // Dacă nu avem origin (same-origin request, Postman, etc.) — permit
        if (!$originDomain) {
            $settings = $channel->config ?? [];
            return response()->json([
                'allowed' => true,
                'config' => [
                    'bot_name' => $bot->name ?? 'Sambla Bot',
                    'greeting' => $settings['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
                    'color' => $settings['color'] ?? '#991b1b',
                    'language' => $bot->language ?? 'ro',
                    'channel_id' => $channel->id,
                ],
            ]);
        }

        // Verifică dacă domeniul e autorizat via site-ul asociat botului
        $site = $bot->site_id ? Site::withoutGlobalScopes()->find($bot->site_id) : null;

        if (!$site) {
            return response()->json(['allowed' => false, 'error' => 'No site associated with this bot.'], 403);
        }

        if (!$site->isVerified()) {
            return response()->json(['allowed' => false, 'error' => 'Site not verified.'], 403);
        }

        // Verifică originea contra listei de origini permise
        $allowedOrigins = $site->getAllowedOrigins();
        $originNormalized = rtrim(strtolower($origin), '/');

        $isAllowed = false;
        foreach ($allowedOrigins as $allowed) {
            if (rtrim(strtolower($allowed), '/') === $originNormalized) {
                $isAllowed = true;
                break;
            }
        }

        // Verifică și direct domeniul (fără protocol)
        if (!$isAllowed) {
            $siteDomain = strtolower($site->getDomainWithoutWww());
            $originDomainClean = strtolower(preg_replace('#^www\.#i', '', $originDomain));

            if ($siteDomain === $originDomainClean) {
                $isAllowed = true;
            }
        }

        if (!$isAllowed) {
            return response()->json([
                'allowed' => false,
                'error' => 'Domain not authorized for this chatbot.',
            ], 403)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Accept, Content-Type');
        }

        // Returnează config-ul chatbot-ului
        $settings = $channel->config ?? [];

        return response()->json([
            'allowed' => true,
            'config' => [
                'bot_name' => $bot->name ?? 'Sambla Bot',
                'greeting' => $settings['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
                'color' => $settings['color'] ?? '#991b1b',
                'language' => $bot->language ?? 'ro',
                'channel_id' => $channel->id,
            ],
        ])
        ->header('Access-Control-Allow-Origin', $origin)
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Accept, Content-Type');
    }

    /**
     * Render chatbot frame (text chat inside iframe).
     */
    public function frame(string $channelId)
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            abort(404);
        }

        $bot = \App\Models\Bot::withoutGlobalScopes()->find($channel->bot_id);

        if (!$bot || !$bot->is_active) {
            abort(404);
        }
        $config = $channel->config ?? [];

        return view('chatbot.frame', [
            'bot' => $bot,
            'channel' => $channel,
            'color' => $config['color'] ?? '#991b1b',
            'greeting' => $config['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
        ]);
    }

    /**
     * Extrage domeniul din URL/origin.
     */
    private function extractDomain(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $parsed = parse_url($url);

        return $parsed['host'] ?? '';
    }
}
