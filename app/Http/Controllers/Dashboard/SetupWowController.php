<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\PlatformSetting;
use App\Models\Site;
use App\Models\WebsiteScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Niche-driven onboarding wizard (Phase 3 — "test before you pay").
 * Runs parallel to the existing SetupWizardController; tenants land
 * here only when platform setting onboarding_v2_enabled is on.
 *
 * Steps are session-driven so back/forward works without a DB table.
 * The wizard ends with a widget-embed "test now" pane pointed at
 * the bot it just created + templated.
 */
class SetupWowController extends Controller
{
    private const STEPS = ['niche', 'website', 'agent', 'test'];

    public function start(Request $request)
    {
        $request->session()->forget('wow_wizard');
        return redirect()->route('dashboard.setup-wow.step', ['step' => 'niche']);
    }

    public function step(Request $request, string $step)
    {
        if (!in_array($step, self::STEPS, true)) {
            return redirect()->route('dashboard.setup-wow.step', ['step' => 'niche']);
        }

        $state = $request->session()->get('wow_wizard', []);
        $niches = collect(config('niches', []))->map(function ($cfg, $slug) {
            return [
                'slug'         => $slug,
                'display_name' => $cfg['display_name'] ?? $slug,
                'archetype'    => $cfg['archetype'] ?? '',
                'engine'       => $cfg['engine'] ?? '',
                'wow_demo'     => $cfg['wow_demo'] ?? '',
            ];
        })->values()->all();

        return view('dashboard.setup-wow.' . $step, [
            'step'    => $step,
            'state'   => $state,
            'niches'  => $niches,
            'bot'     => !empty($state['bot_id']) ? Bot::find($state['bot_id']) : null,
        ]);
    }

    public function saveNiche(Request $request)
    {
        $validated = $request->validate([
            'niche_slug' => 'required|string|max:60',
        ]);
        $cfg = config('niches.' . $validated['niche_slug']);
        if (!$cfg) {
            return back()->withErrors(['niche_slug' => 'Nișă invalidă.']);
        }
        $request->session()->put('wow_wizard.niche_slug', $validated['niche_slug']);
        return redirect()->route('dashboard.setup-wow.step', ['step' => 'website']);
    }

    public function saveWebsite(Request $request)
    {
        $validated = $request->validate([
            'website_url' => 'required|url|max:500',
            'business_name' => 'nullable|string|max:180',
        ]);
        $request->session()->put('wow_wizard.website_url', $validated['website_url']);
        $request->session()->put('wow_wizard.business_name', $validated['business_name'] ?? '');
        return redirect()->route('dashboard.setup-wow.step', ['step' => 'agent']);
    }

    /**
     * Commits the wizard: creates the bot (or updates if exists),
     * applies the niche template (prompt + KB + booking seeds),
     * provisions a web-chatbot channel, triggers a site scan in
     * the background, and redirects to the "test now" step.
     */
    public function saveAgent(Request $request)
    {
        $state = $request->session()->get('wow_wizard', []);
        if (empty($state['niche_slug']) || empty($state['website_url'])) {
            return redirect()->route('dashboard.setup-wow.step', ['step' => 'niche']);
        }

        $validated = $request->validate([
            'agent_name'  => 'required|string|max:120',
            'greeting'    => 'nullable|string|max:500',
            'voice_style' => 'nullable|string|in:professional,friendly,empathetic',
        ]);

        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $niche = config('niches.' . $state['niche_slug']);
        $engine = $niche['engine'] ?? 'lead';

        // Ensure the tenant has a Site row — existing setup wizard
        // creates this too; reuse when present to stay idempotent.
        $site = Site::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->first();
        if (!$site) {
            $site = Site::create([
                'tenant_id'   => $tenant->id,
                'domain'      => parse_url($state['website_url'], PHP_URL_HOST) ?: 'unknown.local',
                'display_name' => $state['business_name'] ?: $tenant->name,
            ]);
        }

        $bot = Bot::create([
            'tenant_id'        => $tenant->id,
            'site_id'          => $site->id,
            'name'             => $validated['agent_name'],
            'slug'             => Str::slug($validated['agent_name']) . '-' . Str::random(4),
            'voice'            => $this->voicePreset($validated['voice_style'] ?? 'professional'),
            'language'         => 'ro',
            'engine_type'      => $engine,
            'niche_slug'       => $state['niche_slug'],
            'greeting_message' => $validated['greeting'] ?: null,
            'is_active'        => true,
        ]);

        // System prompt from niche template addon.
        $bot->system_prompt = trim($niche['prompt_addon'] ?? '');
        $bot->save();

        // Booking seeds (service types + staff + working hours)
        // when the niche is on the booking/hybrid engine.
        if (in_array($engine, ['booking', 'hybrid'], true)) {
            Artisan::call('booking:seed-defaults', ['bot_id' => $bot->id]);
        }

        // Web-chatbot channel — required for the widget embed the
        // test step uses. Skip if one already exists for the bot.
        $channel = Channel::firstOrCreate(
            ['bot_id' => $bot->id, 'type' => Channel::TYPE_WEB_CHATBOT],
            [
                'tenant_id' => $tenant->id,
                'name'      => $bot->name,
                'is_active' => true,
                'status'    => 'connected',
                'config'    => [
                    'greeting' => $bot->greeting_message
                        ?: ($niche['wow_demo'] ? 'Salut! Întreabă-mă: „' . $niche['wow_demo'] . '"' : 'Salut! Cu ce te pot ajuta?'),
                    'color'    => '#991b1b',
                ],
            ]
        );

        // Background website scan for KB seed. Non-blocking — the
        // test step still works without KB (falls back to the
        // niche prompt only).
        try {
            WebsiteScan::create([
                'bot_id'    => $bot->id,
                'base_url'  => $state['website_url'],
                'status'    => 'pending',
                'max_pages' => 10,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('SetupWow: website scan dispatch failed', ['err' => $e->getMessage()]);
        }

        $request->session()->put('wow_wizard.bot_id', $bot->id);
        $request->session()->put('wow_wizard.channel_id', $channel->id);

        return redirect()->route('dashboard.setup-wow.step', ['step' => 'test']);
    }

    public function publish(Request $request)
    {
        $state = $request->session()->get('wow_wizard', []);
        if (empty($state['bot_id'])) {
            return redirect()->route('dashboard.setup-wow.step', ['step' => 'niche']);
        }
        $request->session()->forget('wow_wizard');
        return redirect('/dashboard/agenti/' . $state['bot_id']);
    }

    /**
     * Maps user-facing voice-style label to the internal voice ID
     * OpenAI Realtime accepts. Kept minimal — production uses the
     * tenant's cloned voice when available (handled elsewhere).
     */
    private function voicePreset(string $style): string
    {
        return match ($style) {
            'friendly'   => 'alloy',
            'empathetic' => 'shimmer',
            default      => 'sage',
        };
    }
}
