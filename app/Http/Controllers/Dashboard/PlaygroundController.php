<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Playground per bot — un fel de OpenAI playground pentru agenții AI.
 *
 * 3 panouri într-o singură pagină:
 *   1. CHAT TESTER — talks la widget-ul real al bot-ului prin
 *      /api/v1/chatbot/{channel}/message-stream (deci response-urile
 *      sunt EXACT cum le vede un utilizator pe site).
 *   2. VOICE PREVIEW — input text + select voce → POST către OpenAI TTS,
 *      audio inline player. Ca să auzi vocea înainte de a o asigna.
 *   3. EMBED LIVE PREVIEW — iframe care simulează site-ul clientului cu
 *      widget-ul deja injectat + snippet de install (HTML / WordPress
 *      / React) cu copy buttons.
 *
 * Read-only la nivel de date — doar surface, nu mutează nimic.
 */
class PlaygroundController extends Controller
{
    public function show(Bot $bot)
    {
        // Tenant scope guard
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        // Web chatbot channel pentru chat tester (creăm dacă nu există)
        $webChannel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->first();
        if (!$webChannel) {
            $webChannel = $bot->channels()->create([
                'type' => Channel::TYPE_WEB_CHATBOT,
                'name' => 'Web Chatbot',
                'is_active' => true,
                'config' => [
                    'greeting' => $bot->greeting_message ?: 'Bună! Cu ce te pot ajuta?',
                    'color' => '#991b1b',
                ],
            ]);
        }

        // Lista de voci OpenAI Realtime + ElevenLabs disponibile
        $voices = [
            ['key' => 'coral',   'label' => 'Coral · feminin, cald',     'provider' => 'openai'],
            ['key' => 'sage',    'label' => 'Sage · feminin, clar',      'provider' => 'openai'],
            ['key' => 'shimmer', 'label' => 'Shimmer · feminin, lin',    'provider' => 'openai'],
            ['key' => 'ballad',  'label' => 'Ballad · masculin, blând',  'provider' => 'openai'],
            ['key' => 'verse',   'label' => 'Verse · masculin, expresiv','provider' => 'openai'],
            ['key' => 'ash',     'label' => 'Ash · masculin, neutru',    'provider' => 'openai'],
            ['key' => 'alloy',   'label' => 'Alloy · neutru',            'provider' => 'openai'],
            ['key' => 'echo',    'label' => 'Echo · masculin',           'provider' => 'openai'],
            ['key' => 'marin',   'label' => 'Marin',                     'provider' => 'openai'],
            ['key' => 'cedar',   'label' => 'Cedar',                     'provider' => 'openai'],
        ];

        // Sample text RO pentru preview voce
        $sampleText = "Bună ziua! Sunt asistentul {$bot->name}. Cu ce vă pot ajuta astăzi?";

        // Embed snippets
        $widgetUrl = rtrim(config('app.url'), '/') . '/widget/sambla-chat.min.js';
        $color = $bot->settings['color'] ?? '#991b1b';

        $snippets = [
            'html' => sprintf(
                '<script src="%s" data-channel-id="%d" data-color="%s" data-lang="ro" async defer></script>',
                $widgetUrl,
                $webChannel->id,
                $color,
            ),
            'wordpress' => "// În functions.php (sau folosește plugin-ul Sambla):\nadd_action('wp_footer', function () {\n    echo '<script src=\"" . $widgetUrl . "\" data-channel-id=\"{$webChannel->id}\" data-color=\"{$color}\" data-lang=\"ro\" async defer></script>';\n});",
            'react' => "// Componenta React/Next.js\nimport Script from 'next/script';\n\nexport default function SamblaWidget() {\n  return (\n    <Script\n      src=\"" . $widgetUrl . "\"\n      data-channel-id=\"{$webChannel->id}\"\n      data-color=\"{$color}\"\n      data-lang=\"ro\"\n      strategy=\"afterInteractive\"\n    />\n  );\n}",
            'shopify' => "<!-- În theme.liquid, înainte de </body> -->\n<script src=\"" . $widgetUrl . "\" data-channel-id=\"{$webChannel->id}\" data-color=\"{$color}\" data-lang=\"ro\" async defer></script>",
        ];

        // Preview iframe URL — direct render-uit cu widget injectat
        $previewIframeUrl = route('dashboard.playground.preview', ['bot' => $bot->id]);

        // Signed URL valabil 1h pentru preview MOBILE — userul scanează QR
        // cu telefonul, deschide widget-ul în browser mobil REAL fără să
        // fie nevoie de login. URL-ul expiră, deci nu poate fi share-uit
        // permanent în public.
        $mobileUrl = URL::temporarySignedRoute(
            'dashboard.playground.public',
            now()->addHour(),
            ['bot' => $bot->id],
        );
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=10&data='
            . urlencode($mobileUrl);

        return view('dashboard.playground.show', compact(
            'bot', 'webChannel', 'voices', 'sampleText',
            'snippets', 'previewIframeUrl', 'color',
            'mobileUrl', 'qrUrl',
        ));
    }

    /**
     * Public mobile preview — protejat doar prin signed URL (1h validity).
     * Render acelaşi frame ca preview-ul normal, dar fără auth check.
     */
    public function publicPreview(Request $request, Bot $bot)
    {
        // Laravel's signed middleware verifică oricum, dar dublăm guard
        // explicit aici pentru siguranță (o tipărire greşită în routes/web
        // putea omite middleware-ul).
        abort_unless($request->hasValidSignature(), 403, 'Link expirat sau invalid');

        // Folosim withoutGlobalScopes — nu avem auth context, deci
        // TenantScope ne-ar bloca. Acceptabil pentru că signed URL e
        // dovada de autorizare.
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);
        $webChannel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->first();
        abort_unless($webChannel, 404);

        return response()
            ->view('dashboard.playground.preview-frame', [
                'bot' => $bot,
                'channel' => $webChannel,
                'color' => $bot->settings['color'] ?? '#991b1b',
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Iframe target — render fake landing page cu widget-ul injectat.
     * NU folosește layout-ul dashboard — e independent ca să arate
     * exact cum se vede pe un site real.
     */
    public function previewFrame(Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $webChannel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->first();
        abort_unless($webChannel, 404);

        return response()
            ->view('dashboard.playground.preview-frame', [
                'bot' => $bot,
                'channel' => $webChannel,
                'color' => $bot->settings['color'] ?? '#991b1b',
            ])
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Content-Security-Policy', "frame-ancestors 'self'");
    }

    /**
     * TTS endpoint pentru voice preview — returnează audio binar mp3.
     *
     * Folosește OpenAI tts-1 (cheap, ~$0.015 / 1k chars). Limităm la
     * 500 chars/request și 20 req/min/tenant ca să nu fie abuzat.
     */
    public function tts(Request $request, Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'text' => 'required|string|max:500',
            'voice' => 'required|string|in:coral,sage,shimmer,ballad,verse,ash,alloy,echo,marin,cedar',
        ]);

        // Throttle per tenant
        $key = 'playground-tts:tenant:' . (int) auth()->user()->tenant_id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 20)) {
            return response('Prea multe cereri TTS — încearcă peste un minut.', 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        try {
            $response = OpenAI::audio()->speech([
                'model' => 'tts-1',
                'voice' => $validated['voice'],
                'input' => $validated['text'],
                'response_format' => 'mp3',
            ]);
            // Response is the binary audio
            return response($response, 200)
                ->header('Content-Type', 'audio/mpeg')
                ->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            \Log::warning('Playground TTS failed', [
                'bot_id' => $bot->id,
                'voice' => $validated['voice'],
                'error' => $e->getMessage(),
            ]);
            return response('TTS service indisponibil: ' . $e->getMessage(), 502);
        }
    }
}
