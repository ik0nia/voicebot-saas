<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * php artisan niche-demo:seed {--niche=} {--force}
 *
 * Seeds a platform-owned "Sambla Demo" tenant plus one demo bot + web
 * chatbot channel per curated niche. The resulting slug => channel_id
 * map is what populates config/niche-demo.php (or resolves dynamically
 * via NicheDemoResolver::autoResolveChannels() when the config is
 * empty).
 *
 * Design:
 *   - Single canonical demo tenant (slug sambla-demo,
 *     settings.is_demo_tenant=true). Re-runs reuse it.
 *   - Each bot's slug is deterministic: "demo-{config-niche-slug}" so
 *     the command stays idempotent and config references survive
 *     environment moves.
 *   - --force rewrites the system_prompt + channel greeting from the
 *     niche template. Without --force, existing bots/channels are
 *     left alone (we only create what's missing).
 *   - --niche=X restricts to one curated niche for quick iteration.
 *
 * Does NOT flip NICHE_PUBLIC_DEMO_ENABLED — that's a deliberate ops
 * decision after verifying the seeded demos work.
 */
class SeedNicheDemoBots extends Command
{
    protected $signature = 'niche-demo:seed {--niche= : Restrict to a single config niche slug} {--force : Overwrite existing bot prompt/channel greeting}';
    protected $description = 'Create demo tenant + one bot/channel per curated niche for public niche landing pages.';

    /**
     * Curated niches the public landing pages currently target.
     *
     * Key is the config/niches.php slug (short form used by the Bot
     * templating + engine stack). Value is the matching DB Niche
     * slug (long form used by landing page URLs at /pentru/{slug}).
     * The demo config file is keyed by the DB slug because
     * NicheDemoResolver::forNiche() is called with $niche->slug
     * from the DB Niche model.
     */
    private const CURATED = [
        'magazin-online' => 'magazine-online',
        'stomatologie'   => 'cabinete-stomatologice',
        'florarii'       => 'florarii',         // no DB niche yet — map skipped gracefully
        'medical'        => 'cabinete-medicale',
        'beauty'         => 'salon-beauty',
        'restaurant'     => 'restaurante-delivery',
        'imobiliare'     => 'agentii-imobiliare',
        'avocatura'      => 'birouri-avocatura',
    ];

    public function handle(): int
    {
        $onlyNiche = $this->option('niche');
        $force = (bool) $this->option('force');

        if ($onlyNiche && !array_key_exists($onlyNiche, self::CURATED)) {
            $this->error("Unknown niche '{$onlyNiche}'. Expected one of: " . implode(', ', array_keys(self::CURATED)));
            return self::FAILURE;
        }

        $tenant = $this->ensureDemoTenant();
        $this->info("Demo tenant: #{$tenant->id} ({$tenant->slug})");

        $map = []; // DB niche slug => channel id
        $targets = $onlyNiche ? [$onlyNiche => self::CURATED[$onlyNiche]] : self::CURATED;

        foreach ($targets as $configSlug => $dbSlug) {
            $niche = config('niches.' . $configSlug);
            if (!$niche) {
                $this->warn("  - skip '{$configSlug}': not in config/niches.php");
                continue;
            }

            $result = $this->seedOne($tenant, $configSlug, $dbSlug, $niche, $force);
            if ($result) {
                $map[$dbSlug] = $result;
            }
        }

        $this->line('');
        $this->info('Seeded demo channels (niche_slug => channel_id):');
        foreach ($map as $slug => $info) {
            $this->line(sprintf(
                '  %-30s => channel #%d (bot: %s)',
                $slug,
                $info['channel_id'],
                $info['bot_slug']
            ));
        }

        // Bust any cached resolver entries so the next landing page
        // hit picks up the freshly-seeded mapping.
        foreach (array_keys($map) as $slug) {
            \Illuminate\Support\Facades\Cache::forget("niche-demo:$slug");
        }
        \Illuminate\Support\Facades\Cache::forget('niche-demo:auto-resolve');

        return self::SUCCESS;
    }

    /**
     * Ensure a single canonical demo tenant exists. Matched by slug
     * 'sambla-demo' AND settings.is_demo_tenant=true to avoid
     * colliding with a real tenant that happens to pick the same
     * slug.
     */
    private function ensureDemoTenant(): Tenant
    {
        $tenant = Tenant::withoutGlobalScopes()
            ->where('slug', 'sambla-demo')
            ->first();

        if ($tenant) {
            // Keep the marker up to date even if someone toggled it.
            $settings = $tenant->settings ?? [];
            if (empty($settings['is_demo_tenant'])) {
                $settings['is_demo_tenant'] = true;
                $tenant->settings = $settings;
                $tenant->save();
            }
            return $tenant;
        }

        return Tenant::create([
            'name'          => 'Sambla Demo',
            'slug'          => 'sambla-demo',
            'plan'          => 'internal',
            'plan_slug'     => 'internal',
            'trial_ends_at' => null,
            'settings'      => ['is_demo_tenant' => true],
        ]);
    }

    /**
     * Seed (or refresh) one niche's demo bot + channel. Returns the
     * channel id + bot slug on success, or null if something went
     * sideways (logged but non-fatal so the rest of the niches still
     * get provisioned).
     *
     * @return array{channel_id:int, bot_slug:string}|null
     */
    private function seedOne(Tenant $tenant, string $configSlug, string $dbSlug, array $nicheCfg, bool $force): ?array
    {
        $botSlug = 'demo-' . $configSlug;
        $engine = $nicheCfg['engine'] ?? 'lead';
        $displayName = $nicheCfg['display_name'] ?? ucfirst(str_replace('-', ' ', $configSlug));

        return DB::transaction(function () use ($tenant, $configSlug, $dbSlug, $nicheCfg, $force, $botSlug, $engine, $displayName) {
            $bot = Bot::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('slug', $botSlug)
                ->first();

            $systemPrompt = trim($nicheCfg['prompt_addon'] ?? '');
            $greeting = $nicheCfg['wow_demo']
                ? 'Salut! Sunt demo-ul Sambla pentru „' . $displayName . '". Întreabă-mă orice — de exemplu: „' . $nicheCfg['wow_demo'] . '"'
                : 'Salut! Sunt demo-ul Sambla pentru „' . $displayName . '". Cu ce te pot ajuta?';

            if (!$bot) {
                $bot = Bot::create([
                    'tenant_id'        => $tenant->id,
                    'name'             => 'Demo · ' . $displayName,
                    'slug'             => $botSlug,
                    'system_prompt'    => $systemPrompt,
                    'greeting_message' => $greeting,
                    'voice'            => 'sage',
                    'language'         => 'ro',
                    'engine_type'      => $engine,
                    'niche_slug'       => $configSlug,
                    'is_active'        => true,
                    'settings'         => [
                        'use_structured_prompt' => true,
                        'is_demo'               => true,
                        'demo_db_niche_slug'    => $dbSlug,
                        // Keys match config/niches.php — suggested_faqs,
                        // standard_rules, default_tone — the same ones
                        // SetupWowController copies into new bots.
                        'faqs'                  => array_values(array_slice($nicheCfg['suggested_faqs'] ?? [], 0, 8)),
                        'dont_rules'            => array_values($nicheCfg['standard_rules'] ?? []),
                        'tone_guide'            => $nicheCfg['default_tone'] ?? [
                            'length' => 'medium', 'register' => 'tu', 'emoji_ok' => false, 'languages' => ['ro'],
                        ],
                    ],
                ]);
                $this->line("  + bot created: {$botSlug} (engine={$engine})");
            } elseif ($force) {
                $settings = $bot->settings ?? [];
                $settings['use_structured_prompt'] = true;
                $settings['is_demo'] = true;
                $settings['demo_db_niche_slug'] = $dbSlug;
                $settings['faqs'] = array_values(array_slice($nicheCfg['suggested_faqs'] ?? [], 0, 8))
                    ?: ($settings['faqs'] ?? []);
                $settings['dont_rules'] = array_values($nicheCfg['standard_rules'] ?? [])
                    ?: ($settings['dont_rules'] ?? []);
                $settings['tone_guide'] = $nicheCfg['default_tone']
                    ?? ($settings['tone_guide'] ?? ['length' => 'medium', 'register' => 'tu', 'emoji_ok' => false, 'languages' => ['ro']]);
                $bot->fill([
                    'system_prompt'    => $systemPrompt,
                    'greeting_message' => $greeting,
                    'engine_type'      => $engine,
                    'niche_slug'       => $configSlug,
                    'is_active'        => true,
                    'settings'         => $settings,
                ])->save();
                $this->line("  ~ bot refreshed: {$botSlug} (--force)");
            } else {
                $this->line("  = bot kept:    {$botSlug}");
            }

            // Engine-specific seeding
            if (in_array($engine, ['booking', 'hybrid'], true)) {
                try {
                    Artisan::call('booking:seed-defaults', ['bot_id' => $bot->id]);
                } catch (\Throwable $e) {
                    $this->warn("    booking:seed-defaults failed: {$e->getMessage()}");
                }
            } elseif ($engine === 'hospitality') {
                try {
                    Artisan::call('hospitality:seed-defaults', ['bot_id' => $bot->id]);
                } catch (\Throwable $e) {
                    $this->warn("    hospitality:seed-defaults failed: {$e->getMessage()}");
                }
            }

            // Web chatbot channel (required for iframe embed)
            $channel = Channel::where('bot_id', $bot->id)
                ->where('type', Channel::TYPE_WEB_CHATBOT)
                ->first();

            if (!$channel) {
                $channel = Channel::create([
                    'bot_id'    => $bot->id,
                    'type'      => Channel::TYPE_WEB_CHATBOT,
                    'name'      => 'Demo ' . $displayName,
                    'is_active' => true,
                    'status'    => 'connected',
                    'config'    => [
                        'greeting' => $greeting,
                        'color'    => '#991b1b',
                        'is_demo'  => true,
                    ],
                ]);
                $this->line("    + channel created: #{$channel->id}");
            } elseif ($force) {
                $cfg = $channel->config ?? [];
                $cfg['greeting'] = $greeting;
                $cfg['color'] = $cfg['color'] ?? '#991b1b';
                $cfg['is_demo'] = true;
                $channel->fill([
                    'is_active' => true,
                    'status'    => 'connected',
                    'config'    => $cfg,
                ])->save();
                $this->line("    ~ channel refreshed: #{$channel->id}");
            } else {
                $this->line("    = channel kept:    #{$channel->id}");
            }

            return ['channel_id' => $channel->id, 'bot_slug' => $bot->slug];
        });
    }
}
