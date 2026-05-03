<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Embed customizer cu live preview — color picker, poziție, greeting,
 * limbă. Iframe-ul randa pagina demo cu widget-ul configurat la noile
 * valori, în timp real.
 *
 * URL: /dashboard/agenti/{bot}/embed-customizer
 */
class EmbedCustomizerController extends Controller
{
    public function show(Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $webChannel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->first();
        if (!$webChannel) {
            $webChannel = $bot->channels()->create([
                'type' => Channel::TYPE_WEB_CHATBOT,
                'name' => 'Web Chatbot',
                'is_active' => true,
                'config' => ['greeting' => $bot->greeting_message ?: 'Bună!', 'color' => '#991b1b'],
            ]);
        }

        $config = $webChannel->config ?? [];

        return view('dashboard.embed-customizer.show', [
            'bot' => $bot,
            'channel' => $webChannel,
            'currentColor' => $config['color'] ?? '#991b1b',
            'currentGreeting' => $config['greeting'] ?? ($bot->greeting_message ?: 'Bună! Cu ce te pot ajuta?'),
            'currentPosition' => $config['position'] ?? 'bottom-right',
            'currentLang' => $config['lang'] ?? 'ro',
        ]);
    }

    /**
     * Save customizations înapoi în channel.config.
     */
    public function update(Request $request, Bot $bot): RedirectResponse
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'greeting' => 'required|string|max:300',
            'position' => 'required|in:bottom-right,bottom-left,top-right,top-left',
            'lang' => 'required|in:ro,en,de,fr,es',
        ]);

        $channel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->firstOrFail();
        $config = $channel->config ?? [];
        $config['color'] = $validated['color'];
        $config['greeting'] = $validated['greeting'];
        $config['position'] = $validated['position'];
        $config['lang'] = $validated['lang'];
        $channel->config = $config;
        $channel->save();

        return redirect()->route('dashboard.embed-customizer.show', $bot)
            ->with('success', 'Configurația widget-ului a fost salvată. Site-urile cu acest widget vor folosi noile valori în maxim 60s.');
    }

    /**
     * Preview frame cu params via query string — iframe-ul de pe
     * customizer apelează acest URL cu noile valori la fiecare schimbare.
     * Public dar fără cache (no-store).
     */
    public function previewFrame(Request $request, Bot $bot)
    {
        // Auth check + signed allowed
        $authorized = (auth()->check() && (
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin()
        )) || $request->hasValidSignature();

        abort_unless($authorized, 403);

        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);
        $channel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->first();
        abort_unless($channel, 404);

        $params = $request->validate([
            'color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'greeting' => 'nullable|string|max:300',
            'position' => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'lang' => 'nullable|in:ro,en,de,fr,es',
        ]);

        return response()
            ->view('dashboard.embed-customizer.preview-frame', [
                'bot' => $bot,
                'channel' => $channel,
                'color' => $params['color'] ?? '#991b1b',
                'greeting' => $params['greeting'] ?? 'Bună!',
                'position' => $params['position'] ?? 'bottom-right',
                'lang' => $params['lang'] ?? 'ro',
            ])
            ->header('Cache-Control', 'no-store')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
