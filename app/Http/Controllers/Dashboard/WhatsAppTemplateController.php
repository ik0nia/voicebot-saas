<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\WhatsAppTemplate;
use App\Services\Channels\Meta\WhatsAppTemplateService;
use Illuminate\Http\Request;

class WhatsAppTemplateController extends Controller
{
    public function __construct(private WhatsAppTemplateService $templates) {}

    public function index(Bot $bot, Channel $channel)
    {
        $this->ensureWhatsAppChannel($bot, $channel);

        $templates = WhatsAppTemplate::query()
            ->where('channel_id', $channel->id)
            ->latest()
            ->get();

        return view('dashboard.bots.channels.whatsapp-templates.index', [
            'bot' => $bot,
            'channel' => $channel,
            'templates' => $templates,
        ]);
    }

    public function create(Bot $bot, Channel $channel)
    {
        $this->ensureWhatsAppChannel($bot, $channel);

        return view('dashboard.bots.channels.whatsapp-templates.composer', [
            'bot' => $bot,
            'channel' => $channel,
            'template' => null,
        ]);
    }

    public function store(Request $request, Bot $bot, Channel $channel)
    {
        $this->ensureWhatsAppChannel($bot, $channel);

        $validated = $this->validateComposer($request);

        $template = WhatsAppTemplate::create([
            'channel_id' => $channel->id,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'language' => $validated['language'],
            'status' => WhatsAppTemplate::STATUS_DRAFT,
            'components' => $this->buildComponents($validated),
            'sample_values' => $validated['sample_values'] ?? null,
        ]);

        return redirect()
            ->route('dashboard.bots.channels.whatsapp-templates.edit', [$bot, $channel, $template])
            ->with('success', 'Template-ul a fost salvat ca schiță. Trimite-l spre aprobare la Meta când e gata.');
    }

    public function edit(Bot $bot, Channel $channel, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToChannel($bot, $channel, $template);

        return view('dashboard.bots.channels.whatsapp-templates.composer', [
            'bot' => $bot,
            'channel' => $channel,
            'template' => $template,
        ]);
    }

    public function update(Request $request, Bot $bot, Channel $channel, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToChannel($bot, $channel, $template);

        // Once submitted to Meta, only sample_values is editable locally —
        // structural changes require resubmission as a new template
        // (Meta does not support template versioning).
        if (!$template->isDraft()) {
            $template->update([
                'sample_values' => $request->input('sample_values', $template->sample_values),
            ]);
            return redirect()
                ->route('dashboard.bots.channels.whatsapp-templates.edit', [$bot, $channel, $template])
                ->with('success', 'Sample values updated. Restul template-ului e blocat după submit la Meta.');
        }

        $validated = $this->validateComposer($request, $template->id);

        $template->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'language' => $validated['language'],
            'components' => $this->buildComponents($validated),
            'sample_values' => $validated['sample_values'] ?? null,
        ]);

        return redirect()
            ->route('dashboard.bots.channels.whatsapp-templates.edit', [$bot, $channel, $template])
            ->with('success', 'Schița a fost actualizată.');
    }

    public function submit(Bot $bot, Channel $channel, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToChannel($bot, $channel, $template);

        if (!$template->isDraft()) {
            return back()->withErrors(['template' => 'Doar schițele pot fi trimise la Meta.']);
        }

        $result = $this->templates->submit($template);

        if ($result['success']) {
            return redirect()
                ->route('dashboard.bots.channels.whatsapp-templates.index', [$bot, $channel])
                ->with('success', 'Template-ul a fost trimis la Meta. Aprobarea durează de obicei 1-24h.');
        }

        return back()->withErrors([
            'meta' => 'Meta a respins submiterea: ' . $result['error'],
        ]);
    }

    public function destroy(Bot $bot, Channel $channel, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToChannel($bot, $channel, $template);

        // Local-only delete. If template is approved on Meta, the tenant
        // must also delete from Business Manager — we don't proxy DELETE
        // because the (name, all-langs) shape of Meta's DELETE could
        // accidentally remove templates we don't manage locally.
        $template->delete();

        return redirect()
            ->route('dashboard.bots.channels.whatsapp-templates.index', [$bot, $channel])
            ->with('success', 'Template șters local. Dacă era aprobat la Meta, șterge-l manual și din Meta Business Manager.');
    }

    public function syncFromMeta(Bot $bot, Channel $channel)
    {
        $this->ensureWhatsAppChannel($bot, $channel);

        $result = $this->templates->syncFromMeta($channel);

        if ($result['success']) {
            return back()->with('success', "Sincronizat cu Meta: {$result['synced']} template-uri.");
        }
        return back()->withErrors(['meta' => 'Sincronizare eșuată: ' . $result['error']]);
    }

    private function validateComposer(Request $request, ?int $templateId = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:64',
                'regex:/^[a-z0-9_]+$/',
            ],
            'category' => 'required|in:' . implode(',', WhatsAppTemplate::CATEGORIES),
            'language' => ['required', 'string', 'max:16', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
            'header_type' => 'nullable|in:NONE,TEXT,IMAGE,VIDEO,DOCUMENT',
            'header_text' => 'nullable|string|max:60',
            'body_text' => 'required|string|max:1024',
            'footer_text' => 'nullable|string|max:60',
            'buttons' => 'nullable|array|max:10',
            'buttons.*.type' => 'nullable|in:QUICK_REPLY,URL,PHONE_NUMBER,COPY_CODE',
            'buttons.*.text' => 'nullable|string|max:25',
            'buttons.*.url' => 'nullable|url|max:2000',
            'buttons.*.phone_number' => 'nullable|string|max:20|regex:/^\+?[0-9]+$/',
            'sample_values' => 'nullable|array',
            'sample_values.*' => 'nullable|string|max:200',
        ], [
            'name.regex' => 'Numele template-ului trebuie să conțină doar litere mici, cifre și underscore.',
            'language.regex' => 'Limba trebuie format BCP-47 (ro, ro_RO, en_US).',
            'body_text.max' => 'Textul body depășește limita Meta de 1024 caractere.',
        ]);
    }

    private function buildComponents(array $validated): array
    {
        $components = [];

        if (($validated['header_type'] ?? 'NONE') !== 'NONE') {
            $header = ['type' => 'HEADER', 'format' => $validated['header_type']];
            if ($validated['header_type'] === 'TEXT' && !empty($validated['header_text'])) {
                $header['text'] = $validated['header_text'];
            }
            $components[] = $header;
        }

        $components[] = [
            'type' => 'BODY',
            'text' => $validated['body_text'],
        ];

        if (!empty($validated['footer_text'])) {
            $components[] = ['type' => 'FOOTER', 'text' => $validated['footer_text']];
        }

        $buttons = array_filter($validated['buttons'] ?? [], fn ($b) => !empty($b['type'] ?? null));
        if (!empty($buttons)) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => array_map(function ($b) {
                    $btn = ['type' => $b['type'], 'text' => $b['text'] ?? ''];
                    if ($b['type'] === 'URL' && !empty($b['url'])) {
                        $btn['url'] = $b['url'];
                    }
                    if ($b['type'] === 'PHONE_NUMBER' && !empty($b['phone_number'])) {
                        $btn['phone_number'] = $b['phone_number'];
                    }
                    return $btn;
                }, array_values($buttons)),
            ];
        }

        return $components;
    }

    private function ensureWhatsAppChannel(Bot $bot, Channel $channel): void
    {
        if ($channel->bot_id !== $bot->id) {
            abort(404);
        }
        if ($channel->type !== Channel::TYPE_WHATSAPP) {
            abort(404);
        }
    }

    private function ensureTemplateBelongsToChannel(Bot $bot, Channel $channel, WhatsAppTemplate $template): void
    {
        $this->ensureWhatsAppChannel($bot, $channel);
        if ($template->channel_id !== $channel->id) {
            abort(404);
        }
    }
}
