<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Services\Widget\WidgetContextResolver;
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
