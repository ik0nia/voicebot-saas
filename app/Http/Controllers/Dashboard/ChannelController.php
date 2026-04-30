<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Services\Widget\WidgetContextResolver;
use App\Services\WidgetThemeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    public function index(Bot $bot)
    {
        $channels = $bot->channels()->latest()->get();

        return view('dashboard.bots.channels.index', compact('bot', 'channels'));
    }

    public function store(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', Channel::TYPES),
            'name' => 'nullable|string|max:255',
            'external_id' => 'nullable|string|max:255',
            'config' => 'nullable|array',
        ]);

        // Check plan limits for allowed channels
        $tenant = auth()->user()->tenant;
        $allowedChannels = $tenant->settings['allowed_channels'] ?? ['voice'];

        if (!in_array($validated['type'], $allowedChannels)) {
            return back()->withErrors(['type' => 'Planul tău nu include acest tip de canal. Fă upgrade pentru acces.']);
        }

        // Check if this channel type + external_id combo already exists
        $exists = $bot->channels()
            ->where('type', $validated['type'])
            ->where('external_id', $validated['external_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->withErrors(['type' => 'Acest canal există deja pentru acest bot.']);
        }

        $validated['webhook_secret'] = Str::random(32);
        $validated['status'] = 'pending';

        $channel = $bot->channels()->create($validated);

        return redirect()->route('dashboard.bots.channels.index', $bot)
            ->with('success', 'Canalul a fost adăugat cu succes!');
    }

    public function update(Request $request, Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'external_id' => 'nullable|string|max:255',
            'config' => 'nullable|array',
        ]);

        $channel->update($validated);

        return redirect()->route('dashboard.bots.channels.index', $bot)
            ->with('success', 'Canalul a fost actualizat!');
    }

    public function destroy(Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $channel->delete();

        return redirect()->route('dashboard.bots.channels.index', $bot)
            ->with('success', 'Canalul a fost șters.');
    }

    /**
     * Render the manual-paste wizard for connecting a WhatsApp Cloud API
     * channel. Used as the fallback to embedded signup until we hold a
     * Meta Tech Provider relationship; also useful for tenants who already
     * have credentials issued by another BSP.
     */
    public function connectWhatsApp(Bot $bot)
    {
        $tenant = auth()->user()->tenant;
        $allowedChannels = $tenant->settings['allowed_channels'] ?? ['voice'];

        if (!in_array(Channel::TYPE_WHATSAPP, $allowedChannels, true)) {
            return redirect()
                ->route('dashboard.bots.channels.index', $bot)
                ->withErrors(['type' => 'Planul tău nu include canalul WhatsApp.']);
        }

        $webhookUrl = url('/webhook/whatsapp');

        return view('dashboard.bots.channels.connect-whatsapp', [
            'bot' => $bot,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    /**
     * Store the credentials pasted in the wizard. We generate the
     * webhook_secret server-side (also used as Meta's verify_token at
     * subscription time) so the tenant can't accidentally pick a weak
     * value or reuse one from another channel.
     */
    public function storeWhatsApp(Request $request, Bot $bot)
    {
        $tenant = auth()->user()->tenant;
        $allowedChannels = $tenant->settings['allowed_channels'] ?? ['voice'];
        if (!in_array(Channel::TYPE_WHATSAPP, $allowedChannels, true)) {
            return back()->withErrors(['type' => 'Planul tău nu include canalul WhatsApp.']);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'waba_id' => 'required|string|max:64|regex:/^[0-9]+$/',
            'phone_number_id' => 'required|string|max:64|regex:/^[0-9]+$/',
            // Meta system-user tokens are EAA-prefixed and 200+ chars in 2026.
            // Anything shorter is almost certainly the wrong copy/paste.
            'access_token' => ['required', 'string', 'min:100', 'max:2048', 'regex:/^[A-Za-z0-9_\-]+$/'],
            // App secret is 32-char lowercase hex.
            'app_secret' => ['nullable', 'string', 'regex:/^[a-f0-9]{32}$/i'],
        ], [
            'waba_id.regex' => 'WABA ID trebuie să fie numeric.',
            'phone_number_id.regex' => 'Phone Number ID trebuie să fie numeric.',
            'access_token.min' => 'Token-ul pare prea scurt. Folosește un System User Access Token din Meta Business Manager (de obicei 200+ caractere).',
            'access_token.regex' => 'Token-ul conține caractere invalide (fără spații sau ghilimele copiate accidental).',
            'app_secret.regex' => 'App Secret trebuie să fie 32 caractere hex lowercase.',
        ]);

        // Cross-tenant guard: a Meta phone_number_id is globally unique on
        // Meta's side, so nobody else should ever claim one we already
        // store. The standard query is tenant-scoped (BelongsToTenant) — we
        // must bypass that scope to detect collisions across all tenants.
        $duplicate = Channel::query()
            ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('type', Channel::TYPE_WHATSAPP)
            ->where('external_id', $validated['phone_number_id'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['phone_number_id' => 'Acest Phone Number ID este deja conectat. Verifică dacă l-ai introdus corect sau contactează-ne dacă crezi că e o eroare.']);
        }

        $channel = $bot->channels()->create([
            'type' => Channel::TYPE_WHATSAPP,
            'name' => $validated['name'] ?: 'WhatsApp',
            'external_id' => $validated['phone_number_id'],
            'webhook_secret' => Str::random(48),
            'is_active' => true,
            'status' => 'pending',
        ]);

        $channel->setCredential('waba_id', $validated['waba_id']);
        $channel->setCredential('phone_number_id', $validated['phone_number_id']);
        $channel->setCredential('access_token', $validated['access_token']);
        if (!empty($validated['app_secret'])) {
            $channel->setCredential('app_secret', $validated['app_secret']);
        }
        $channel->save();

        return redirect()
            ->route('dashboard.bots.channels.whatsapp.connected', ['bot' => $bot, 'channel' => $channel])
            ->with('success', 'Canal WhatsApp creat. Configurează webhook-ul în Meta Business Manager urmând pașii de mai jos.');
    }

    /**
     * Show the post-connect instructions: webhook URL, verify token, and a
     * test-message hint. Reachable only by the bot's owner tenant.
     */
    public function whatsAppConnected(Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        if ($channel->type !== Channel::TYPE_WHATSAPP) {
            abort(404);
        }

        return view('dashboard.bots.channels.whatsapp-connected', [
            'bot' => $bot,
            'channel' => $channel,
            'webhookUrl' => url('/webhook/whatsapp'),
        ]);
    }

    public function toggleActive(Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $channel->update(['is_active' => !$channel->is_active]);

        return back()->with('success', $channel->is_active ? 'Canal activat.' : 'Canal dezactivat.');
    }

    /**
     * Show the quick-replies editor for a web-chatbot channel.
     * One card per page_type (general/product/category/cart + booking &
     * hospitality when the engine_type matches), with resolved chips and
     * the corresponding niche/universal fallback so the user can reset.
     */
    public function editChips(Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $niche = $bot->niche_slug ?: '_default';
        $nicheMap = config('widget_contexts.' . $niche, []);
        $defaultMap = config('widget_contexts._default', []);
        $overrides = (array) data_get($channel->config ?? [], 'widget_contexts', []);

        $types = $this->pageTypesFor($bot);
        $cards = [];
        foreach ($types as $type => $meta) {
            $override = $overrides[$type] ?? null;
            $niched = $nicheMap[$type] ?? null;
            $fallback = $defaultMap[$type] ?? null;

            $resolved = $override ?? $niched ?? $fallback ?? ['opening' => null, 'quick_replies' => []];
            $defaults = $niched ?? $fallback ?? ['opening' => null, 'quick_replies' => []];

            $cards[] = [
                'key' => $type,
                'title' => $meta['title'],
                'description' => $meta['description'],
                'is_overridden' => $override !== null,
                'opening' => (string) ($resolved['opening'] ?? ''),
                'chips' => array_values(array_map(
                    fn($c) => ['label' => (string) ($c['label'] ?? ''), 'text' => (string) ($c['text'] ?? '')],
                    (array) ($resolved['quick_replies'] ?? [])
                )),
                'defaults_opening' => (string) ($defaults['opening'] ?? ''),
                'defaults_chips' => array_values(array_map(
                    fn($c) => ['label' => (string) ($c['label'] ?? ''), 'text' => (string) ($c['text'] ?? '')],
                    (array) ($defaults['quick_replies'] ?? [])
                )),
            ];
        }

        return view('dashboard.bots.channels.chips', [
            'bot' => $bot,
            'channel' => $channel,
            'cards' => $cards,
            'niche' => $niche,
        ]);
    }

    /**
     * Save chip overrides into channel.config.widget_contexts.
     * The payload is a map keyed by page_type; any entry missing
     * `opening` / `quick_replies` clears that page_type's override
     * (i.e. falls back to niche/universal defaults).
     */
    public function updateChips(Request $request, Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $validated = $request->validate([
            'widget_contexts' => 'nullable|array',
            'widget_contexts.*.opening' => 'nullable|string|max:240',
            'widget_contexts.*.quick_replies' => 'nullable|array|max:6',
            'widget_contexts.*.quick_replies.*.label' => 'nullable|string|max:40',
            'widget_contexts.*.quick_replies.*.text' => 'nullable|string|max:500',
        ]);

        $allowedTypes = array_keys($this->pageTypesFor($bot));
        $incoming = $validated['widget_contexts'] ?? [];
        $clean = [];

        foreach ($incoming as $type => $entry) {
            if (!in_array($type, $allowedTypes, true) || !is_array($entry)) {
                continue;
            }

            $opening = isset($entry['opening']) ? trim((string) $entry['opening']) : '';
            $chips = [];
            foreach ((array) ($entry['quick_replies'] ?? []) as $r) {
                if (!is_array($r)) continue;
                $label = isset($r['label']) ? trim((string) $r['label']) : '';
                $text = isset($r['text']) ? trim((string) $r['text']) : '';
                if ($label === '' || $text === '') continue;
                $chips[] = ['label' => $label, 'text' => $text];
            }

            if ($opening === '' && empty($chips)) {
                continue;
            }

            $clean[$type] = [];
            if ($opening !== '') $clean[$type]['opening'] = $opening;
            if (!empty($chips)) $clean[$type]['quick_replies'] = $chips;
        }

        $config = $channel->config ?? [];
        if (empty($clean)) {
            unset($config['widget_contexts']);
        } else {
            $config['widget_contexts'] = $clean;
        }
        $channel->update(['config' => $config]);

        return redirect()
            ->route('dashboard.bots.channels.chips.edit', [$bot, $channel])
            ->with('success', 'Butoanele rapide au fost salvate.');
    }

    /**
     * Show the web chatbot appearance editor — theme preset gallery,
     * greeting, position, plus the copy-ready embed snippet.
     */
    public function chatbotSetup(Bot $bot, Channel $channel, WidgetThemeResolver $themes)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        if ($channel->type !== Channel::TYPE_WEB_CHATBOT) {
            abort(404);
        }

        return view('dashboard.bots.channels.chatbot-setup', [
            'bot' => $bot,
            'channel' => $channel,
            'themePresets' => $themes->catalog(),
            'activeTheme' => $themes->resolve($channel->config ?? []),
        ]);
    }

    /**
     * Persist web chatbot appearance changes from the setup editor.
     * Writes into channels.config JSON (theme_preset, color override,
     * greeting, position) without touching unrelated keys like
     * widget_contexts or icon_url.
     */
    public function saveChatbotSetup(Request $request, Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        if ($channel->type !== Channel::TYPE_WEB_CHATBOT) {
            abort(404);
        }

        $presetKeys = array_keys(config('widget-themes.presets', []));

        $validated = $request->validate([
            'theme_preset' => 'nullable|string|in:' . implode(',', array_merge($presetKeys, ['custom'])),
            'color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'greeting' => 'nullable|string|max:500',
            'position' => 'nullable|in:bottom-right,bottom-left',
        ]);

        $config = $channel->config ?? [];

        if (array_key_exists('theme_preset', $validated)) {
            if ($validated['theme_preset'] === 'custom' || $validated['theme_preset'] === null) {
                unset($config['theme_preset']);
            } else {
                $config['theme_preset'] = $validated['theme_preset'];
            }
        }

        if (!empty($validated['color'])) {
            $config['color'] = $validated['color'];
        }

        if (array_key_exists('greeting', $validated) && $validated['greeting'] !== null) {
            $config['greeting'] = $validated['greeting'];
        }

        if (array_key_exists('position', $validated) && $validated['position'] !== null) {
            $config['position'] = $validated['position'];
        }

        $channel->update(['config' => $config]);

        return redirect()
            ->route('dashboard.bots.channels.chatbot-setup', [$bot, $channel])
            ->with('success', 'Widget-ul a fost actualizat.');
    }

    /**
     * Page-type catalog filtered by engine_type — booking + hospitality
     * only show when the bot actually uses them.
     */
    private function pageTypesFor(Bot $bot): array
    {
        $types = [
            'general' => [
                'title' => 'Pagina principală / generic',
                'description' => 'Apar când vizitatorul deschide widget-ul pe homepage, blog, contact sau orice pagină care NU e produs/categorie/coș.',
            ],
            'product' => [
                'title' => 'Pagină de produs',
                'description' => 'Apar când vizitatorul e pe o pagină de produs (pluginul WooCommerce detectează automat).',
            ],
            'category' => [
                'title' => 'Pagină de categorie',
                'description' => 'Apar pe paginile de listă / categorie (archive WooCommerce).',
            ],
            'cart' => [
                'title' => 'Coș de cumpărături',
                'description' => 'Apar pe pagina de coș sau când vizitatorul are produse adăugate.',
            ],
        ];

        if (in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
            $types['booking'] = [
                'title' => 'Programare',
                'description' => 'Apar când vizitatorul e în context de programare (doar agenți booking/hybrid).',
            ];
        }
        if ($bot->engine_type === 'hospitality') {
            $types['hospitality'] = [
                'title' => 'Hospitality',
                'description' => 'Apar în context hotel / cazare (doar agenți hospitality).',
            ];
        }

        return $types;
    }

    private function ensureChannelBelongsToBot(Bot $bot, Channel $channel): void
    {
        if ($channel->bot_id !== $bot->id) {
            abort(404);
        }
    }
}
