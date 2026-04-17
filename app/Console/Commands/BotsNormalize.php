<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * php artisan bots:normalize
 *   {--bot=       : Run only for this bot ID}
 *   {--dry-run    : Preview everything without writing}
 *   {--all-niche= : Bulk non-interactive mode — applies this niche to every
 *                   bot with niche_slug=NULL}
 *   {--yes        : Skip confirmations (implied by --all-niche)}
 *
 * Walks every legacy bot (bots with niche_slug=NULL, predating the niche /
 * engine architecture introduced on 2026-04-17) and, for each one, asks the
 * operator which niche it belongs to, then applies the same setup a fresh
 * bot would get from setup-wow:
 *
 *   - niche_slug + engine_type set from config/niches.php
 *   - system_prompt updated (Keep / Append / Replace strategy)
 *   - Site row ensured (reused if tenant already has one)
 *   - Structured-prompt settings scaffolded empty (operator fills later)
 *   - web_chatbot Channel row ensured
 *   - booking:seed-defaults or hospitality:seed-defaults invoked per engine
 *
 * Each bot runs in its own DB transaction. Seeds run AFTER commit — if they
 * fail we log + continue; the bot stays migrated (transactions do not
 * magically roll back external side-effects from other commands). Other
 * bots in the batch are unaffected by a single-bot failure.
 *
 * Always uses withoutGlobalScopes() on Bot/Site/Channel queries because
 * this command runs as a scheduled/admin task without tenant context.
 */
class BotsNormalize extends Command
{
    protected $signature = 'bots:normalize
        {--bot= : Only this bot ID}
        {--dry-run : Preview without changes}
        {--all-niche= : Apply the same niche to every null-niche bot (bulk, non-interactive)}
        {--yes : Skip confirmations}';

    protected $description = 'Interactively migrate legacy bots onto the new niche/engine structure.';

    /** Counters for the final summary. */
    private int $migrated = 0;
    private int $skipped = 0;
    private int $failed = 0;
    private array $perNiche = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $allNiche = $this->option('all-niche');
        $botId = $this->option('bot');
        $yes = (bool) $this->option('yes');

        // Load niche catalog once. The wizard prompts read straight from here.
        $niches = config('niches', []);
        if (!is_array($niches) || empty($niches)) {
            $this->error('config/niches.php is empty or missing — nothing to migrate against.');
            return self::FAILURE;
        }
        $niches = collect($niches)->map(fn ($cfg, $slug) => array_merge($cfg, ['slug' => $slug]))->all();

        // Validate --all-niche up-front so bulk runs fail fast, not mid-stream.
        if ($allNiche !== null && !array_key_exists($allNiche, $niches)) {
            $this->error("Invalid --all-niche value '{$allNiche}'. Valid slugs: " . implode(', ', array_keys($niches)));
            return self::FAILURE;
        }

        // Query the bot set. --bot overrides the legacy filter: if the
        // operator named a specific bot, trust them; they may be re-running
        // the migration for a bot they already touched.
        $query = Bot::withoutGlobalScopes()->with('tenant');
        if ($botId) {
            $query->where('id', $botId);
        } else {
            $query->whereNull('niche_slug');
        }
        $bots = $query->orderBy('id')->get();

        if ($bots->isEmpty()) {
            $this->info('No bots need normalization.');
            return self::SUCCESS;
        }

        $this->line(sprintf('Found %d bot(s) to consider.', $bots->count()));
        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }
        $this->newLine();

        foreach ($bots as $bot) {
            // --bot lets operators re-normalize an already-migrated bot
            // only if they explicitly pass its id. Otherwise we skip bots
            // that already have a niche.
            if (!$botId && $bot->niche_slug) {
                $this->line("Skipping bot #{$bot->id} — already has niche '{$bot->niche_slug}'.");
                $this->skipped++;
                continue;
            }

            // Orphan / deleted tenant → cannot create Site/Channel, skip.
            if (!$bot->tenant) {
                $this->warn("Skipping bot #{$bot->id} — no tenant (orphaned).");
                $this->skipped++;
                continue;
            }

            try {
                $result = $this->processBot($bot, $niches, $allNiche, $dryRun, $yes);
                match ($result) {
                    'migrated' => $this->migrated++,
                    'skipped'  => $this->skipped++,
                    'quit'     => null,
                    default    => null,
                };
                if ($result === 'quit') {
                    $this->warn('Aborted by operator.');
                    break;
                }
            } catch (Throwable $e) {
                $this->failed++;
                $this->error("Bot #{$bot->id} failed: " . $e->getMessage());
                report($e);
            }
        }

        $this->printSummary($dryRun);
        return self::SUCCESS;
    }

    /**
     * Handle a single bot: prompt, apply changes, run seeds.
     *
     * @param  array<string, array<string, mixed>>  $niches
     * @return string  One of: 'migrated' | 'skipped' | 'quit'
     */
    private function processBot(Bot $bot, array $niches, ?string $allNiche, bool $dryRun, bool $yes): string
    {
        $this->renderCard($bot);

        // Niche selection.
        if ($allNiche !== null) {
            $selectedSlug = $allNiche;
            $this->line("  → niche (bulk mode): <info>{$selectedSlug}</info>");
        } else {
            $selectedSlug = $this->promptNiche($niches);
            if ($selectedSlug === null) {
                $this->line("  → skipped.");
                $this->newLine();
                return 'skipped';
            }
            if ($selectedSlug === '__QUIT__') {
                return 'quit';
            }
        }

        $niche = $niches[$selectedSlug];

        // Prompt strategy. Empty/null prompts skip straight to niche addon.
        $oldPrompt = (string) ($bot->system_prompt ?? '');
        $hasOld = trim($oldPrompt) !== '';
        $strategy = 'A'; // default
        if ($hasOld && $allNiche === null) {
            $strategy = $this->promptStrategy($oldPrompt);
        }

        // Confirm before writing (unless --yes or --dry-run).
        if (!$dryRun && !$yes && $allNiche === null) {
            if (!$this->confirm("Apply niche '{$selectedSlug}' to bot #{$bot->id}?", true)) {
                $this->line("  → skipped.");
                $this->newLine();
                return 'skipped';
            }
        }

        $nichePromptAddon = trim((string) ($niche['prompt_addon'] ?? ''));
        $newPrompt = match ($strategy) {
            'K' => $oldPrompt,
            'R' => $nichePromptAddon,
            default => $hasOld
                ? trim($oldPrompt) . "\n\n---\n\nReguli operaționale:\n" . $nichePromptAddon
                : $nichePromptAddon,
        };

        if ($dryRun) {
            $this->reportDryRun($bot, $selectedSlug, $niche, $oldPrompt, $newPrompt, $strategy);
            $this->perNiche[$selectedSlug] = ($this->perNiche[$selectedSlug] ?? 0) + 1;
            return 'migrated';
        }

        // Apply atomically. Seeds run OUTSIDE the transaction — they
        // open nested transactions of their own and we don't want a
        // seed failure rolling back the already-committed bot row.
        $resultDetails = DB::transaction(function () use ($bot, $selectedSlug, $niche, $newPrompt) {
            return $this->applyChanges($bot, $selectedSlug, $niche, $newPrompt);
        });

        // Seeds after commit.
        $seedReport = $this->runSeeds($bot->fresh(), $niche);

        $this->reportSuccess($bot, $selectedSlug, $niche, $resultDetails, $seedReport, $strategy, strlen($oldPrompt), strlen($newPrompt));
        $this->perNiche[$selectedSlug] = ($this->perNiche[$selectedSlug] ?? 0) + 1;
        return 'migrated';
    }

    /**
     * Apply all DB changes to bot/site/channel within a transaction.
     *
     * @param  array<string, mixed>  $niche
     * @return array{site_id:int, site_created:bool, channel_id:int, channel_created:bool}
     */
    private function applyChanges(Bot $bot, string $selectedSlug, array $niche, string $newPrompt): array
    {
        $tenant = $bot->tenant;
        $engine = $niche['engine'] ?? 'lead';

        // 1. Ensure a Site row exists for the tenant. We reuse the first
        //    one — most legacy tenants only ever had zero or one site.
        $site = Site::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->first();
        $siteCreated = false;
        if (!$site) {
            $domain = $this->deriveDomain($tenant);
            $site = new Site();
            $site->tenant_id = $tenant->id;
            $site->domain = $domain;
            $site->name = $tenant->name ?: 'Site principal';
            $site->status = 'pending';
            $site->save();
            $siteCreated = true;
        }

        // 2. Bot fields.
        $bot->niche_slug = $selectedSlug;
        $bot->engine_type = $engine;
        $bot->system_prompt = $newPrompt;
        if (!$bot->site_id) {
            $bot->site_id = $site->id;
        }

        // 3. Structured-prompt scaffold. Keep existing keys (operator may
        //    have already edited them); only initialise what's missing.
        //    use_structured_prompt stays false — tenant flips it later.
        $settings = is_array($bot->settings) ? $bot->settings : [];
        $defaults = [
            'use_structured_prompt' => false,
            'business_info' => [
                'address' => '',
                'hours_text' => '',
                'hours_schedule' => [],
                'phone' => '',
                'email' => '',
                'website' => '',
                'whatsapp' => '',
                'facebook' => '',
                'instagram' => '',
                'extras' => '',
            ],
            'faqs' => [],
            'dont_rules' => [],
            'tone_guide' => [
                'length' => 'medium',
                'register' => 'tu',
                'emoji_ok' => false,
                'languages' => ['ro'],
            ],
        ];
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $settings)) {
                $settings[$k] = $v;
            }
        }
        $bot->settings = $settings;
        $bot->save();

        // 4. web_chatbot Channel — mirrors SetupWowController::saveAgent.
        $existingChannel = Channel::where('bot_id', $bot->id)
            ->where('type', Channel::TYPE_WEB_CHATBOT)
            ->first();
        $channelCreated = false;
        if ($existingChannel) {
            $channel = $existingChannel;
        } else {
            $greeting = $bot->greeting_message
                ?: (!empty($niche['wow_demo'])
                    ? 'Salut! Întreabă-mă: „' . $niche['wow_demo'] . '"'
                    : 'Salut! Cu ce te pot ajuta?');
            $channel = Channel::create([
                'bot_id'    => $bot->id,
                'type'      => Channel::TYPE_WEB_CHATBOT,
                'name'      => $bot->name,
                'is_active' => true,
                'status'    => 'connected',
                'config'    => [
                    'greeting' => $greeting,
                    'color'    => '#991b1b',
                ],
            ]);
            $channelCreated = true;
        }

        return [
            'site_id'         => $site->id,
            'site_created'    => $siteCreated,
            'channel_id'      => $channel->id,
            'channel_created' => $channelCreated,
        ];
    }

    /**
     * Run the engine-specific seed command. Never throws — a seed failure
     * is logged but the bot row itself stays migrated (transaction already
     * committed at this point).
     *
     * @param  array<string, mixed>  $niche
     * @return string  Human-readable one-line report.
     */
    private function runSeeds(Bot $bot, array $niche): string
    {
        $engine = $niche['engine'] ?? null;

        try {
            if (in_array($engine, ['booking', 'hybrid'], true)) {
                Artisan::call('booking:seed-defaults', ['bot_id' => $bot->id]);
                return 'booking seeds ran (' . trim(Artisan::output()) . ')';
            }
            if ($engine === 'hospitality') {
                Artisan::call('hospitality:seed-defaults', ['bot_id' => $bot->id]);
                return 'hospitality seeds ran (' . trim(Artisan::output()) . ')';
            }
            return 'no seeds needed for engine=' . ($engine ?? 'none');
        } catch (Throwable $e) {
            $msg = 'seed command failed: ' . $e->getMessage();
            $this->warn('  ! ' . $msg);
            report($e);
            return $msg;
        }
    }

    // ─────────────────────────────────────────────────────────
    //  Presentation
    // ─────────────────────────────────────────────────────────

    private function renderCard(Bot $bot): void
    {
        $tenant = $bot->tenant;
        $created = optional($bot->created_at)->format('Y-m-d H:i');
        $ago = optional($bot->created_at)?->diffForHumans();
        $callsCount = (int) ($bot->calls_count ?? 0);
        $convCount = 0;
        try {
            $convCount = DB::table('conversations')->where('bot_id', $bot->id)->count();
        } catch (Throwable $e) {
            // Table may not exist in every environment — not fatal.
        }
        $prompt = (string) ($bot->system_prompt ?? '');
        $promptLen = strlen($prompt);
        $snippet = $promptLen > 160
            ? trim(preg_replace('/\s+/', ' ', mb_substr($prompt, 0, 160))) . '…'
            : trim(preg_replace('/\s+/', ' ', $prompt));

        $tenantWebsite = '(necunoscut)';
        $settings = is_array($tenant->settings ?? null) ? $tenant->settings : [];
        if (!empty($settings['website'])) {
            $tenantWebsite = (string) $settings['website'];
        }

        $this->line('──────────────────────────────────────────');
        $this->line(sprintf('<options=bold>Bot #%d</> — "%s"', $bot->id, $bot->name));
        $this->line(sprintf('  tenant:   %s (tenant_id=%d)', $tenant->name, $tenant->id));
        $this->line(sprintf('  creat:    %s%s', $created ?: 'n/a', $ago ? " ({$ago})" : ''));
        $this->line(sprintf('  activ:    %s    apeluri:  %s    conversații:  %s',
            $bot->is_active ? 'DA' : 'NU',
            number_format($callsCount),
            number_format($convCount)
        ));
        $this->line(sprintf('  voice:    %s    language:  %s', $bot->voice ?? '-', $bot->language ?? '-'));
        $this->line('');
        if ($promptLen > 0) {
            $this->line(sprintf('  system_prompt (%d chars):', $promptLen));
            $this->line('    "' . $snippet . '"');
        } else {
            $this->line('  system_prompt: (gol)');
        }
        $this->line('');
        $this->line(sprintf('  tenant website:  %s', $tenantWebsite));
        $this->line(sprintf('  site_id:         %s', $bot->site_id ?: 'NULL (lipsă)'));
        $this->line(sprintf('  engine_type:     %s', $bot->engine_type ?: 'NULL (lipsă)'));
        $this->line('──────────────────────────────────────────');
    }

    /**
     * Ask the user for the niche slug, with partial matching + list support.
     * Returns null on skip, '__QUIT__' on quit.
     *
     * @param  array<string, array<string, mixed>>  $niches
     */
    private function promptNiche(array $niches): ?string
    {
        $slugs = array_keys($niches);
        while (true) {
            $answer = trim((string) $this->ask('Ce nișă? (s = skip, ? = listă, q = quit)'));

            if ($answer === '' || strtolower($answer) === 's' || strtolower($answer) === 'skip') {
                return null;
            }
            if (strtolower($answer) === 'q' || strtolower($answer) === 'quit') {
                return '__QUIT__';
            }
            if ($answer === '?' || strtolower($answer) === 'list') {
                $this->renderNicheTable($niches);
                continue;
            }

            $needle = strtolower($answer);

            // Exact first.
            if (in_array($needle, $slugs, true)) {
                return $needle;
            }

            // Partial match.
            $matches = array_values(array_filter($slugs, fn ($s) => str_contains($s, $needle)));
            if (count($matches) === 1) {
                $pick = $matches[0];
                if ($this->confirm("'{$needle}' → '{$pick}'?", true)) {
                    return $pick;
                }
                continue;
            }
            if (count($matches) > 1) {
                $this->line('Potriviri multiple: ' . implode(', ', $matches));
                continue;
            }
            $this->warn("Niciun slug nu se potrivește cu '{$needle}'. Scrie '?' pentru listă.");
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $niches
     */
    private function renderNicheTable(array $niches): void
    {
        $rows = [];
        foreach ($niches as $slug => $cfg) {
            $rows[] = [
                $slug,
                $cfg['display_name'] ?? $slug,
                $cfg['archetype'] ?? '-',
                $cfg['engine'] ?? '-',
            ];
        }
        $this->table(['slug', 'display_name', 'archetype', 'engine'], $rows);
    }

    /**
     * Ask what to do with an existing system_prompt.
     * Returns 'K'|'A'|'R' (Keep / Append / Replace).
     */
    private function promptStrategy(string $oldPrompt): string
    {
        $len = strlen($oldPrompt);
        while (true) {
            $this->line(sprintf('Bot-ul are un system_prompt existent (%d chars). Ce facem cu el?', $len));
            $this->line('  [K] Keep    — păstrează doar vechiul prompt (netouchat)');
            $this->line('  [A] Append  — vechi + nou niche prompt_addon concatenat  (recomandat)');
            $this->line('  [R] Replace — doar niche prompt_addon (pierzi ce era custom)');
            $this->line('  [V] View    — vezi promptul existent complet');
            $answer = strtoupper(trim((string) $this->ask('Alegere', 'A')));
            if ($answer === 'V') {
                $this->line('─── system_prompt existent ───');
                $this->line($oldPrompt);
                $this->line('─── sfârșit ───');
                continue;
            }
            if (in_array($answer, ['K', 'A', 'R'], true)) {
                return $answer;
            }
            $this->warn('Te rog alege K, A, R, sau V.');
        }
    }

    /**
     * Print the dry-run preview (nothing is persisted).
     *
     * @param  array<string, mixed>  $niche
     */
    private function reportDryRun(Bot $bot, string $slug, array $niche, string $oldPrompt, string $newPrompt, string $strategy): void
    {
        $this->line('🧪 DRY RUN — pentru bot #' . $bot->id . ':');
        $this->line('   - niche:          ' . $slug);
        $this->line('   - engine:         ' . ($niche['engine'] ?? '-'));
        $this->line('   - strategy:       ' . $this->strategyLabel($strategy));
        $this->line('   - prompt length:  ' . strlen($oldPrompt) . ' → ' . strlen($newPrompt) . ' chars');
        $this->line('   - site_id:        ' . ($bot->site_id ?: 'would create or attach'));
        $this->line('   - channel:        web_chatbot ensured');
        $this->line('   - seeds:          ' . $this->seedSummaryForEngine($niche['engine'] ?? null));
        $this->line('🧪 DRY RUN — no changes written.');
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $niche
     * @param  array{site_id:int,site_created:bool,channel_id:int,channel_created:bool}  $result
     */
    private function reportSuccess(Bot $bot, string $slug, array $niche, array $result, string $seedReport, string $strategy, int $oldLen, int $newLen): void
    {
        $this->info(sprintf('✅ Bot #%d migrated:', $bot->id));
        $this->line('   - niche:         ' . $slug);
        $this->line('   - engine:        ' . ($niche['engine'] ?? '-'));
        $this->line('   - system_prompt: ' . $this->strategyLabel($strategy) . " ({$oldLen} → {$newLen} chars)");
        $this->line('   - site_id:       ' . $result['site_id'] . ($result['site_created'] ? ' (created new)' : ' (reused)'));
        $this->line('   - channel:       #' . $result['channel_id'] . ' (web_chatbot, ' . ($result['channel_created'] ? 'created' : 'already existed') . ')');
        $this->line('   - seeds:         ' . $seedReport);
        $this->newLine();
    }

    private function strategyLabel(string $s): string
    {
        return match ($s) {
            'K' => 'Keep (unchanged)',
            'R' => 'Replaced',
            default => 'Appended',
        };
    }

    private function seedSummaryForEngine(?string $engine): string
    {
        return match ($engine) {
            'booking', 'hybrid' => 'booking:seed-defaults would run',
            'hospitality' => 'hospitality:seed-defaults would run',
            default => 'none (engine=' . ($engine ?? 'unknown') . ')',
        };
    }

    private function printSummary(bool $dryRun): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════');
        $this->line(sprintf(' Migrat:  %d bots%s', $this->migrated, $dryRun ? ' (dry run)' : ''));
        $this->line(sprintf(' Skipped: %d bots', $this->skipped));
        $this->line(sprintf(' Eșuat:   %d bots', $this->failed));
        if (!empty($this->perNiche)) {
            $this->line('');
            $this->line(' Pe nișe:');
            $max = max($this->perNiche);
            foreach ($this->perNiche as $slug => $count) {
                $bar = str_repeat('█', max(1, (int) round(($count / $max) * 10)));
                $this->line(sprintf('   %-22s %s %d', $slug, $bar, $count));
            }
        }
        if ($this->migrated > 0 && !$dryRun) {
            $this->line('');
            $this->line(' Pași următori:');
            $this->line('   1. Cereți tenanților să intre pe /dashboard/agenti/{id}/edit să completeze Informații business + FAQ + Reguli');
            $this->line('   2. După completare, pot activa "use_structured_prompt=true" din tab-ul Avansat');
            $this->line('   3. Monitorizați /admin/costuri pentru costurile AI de setup');
        }
        $this->line('═══════════════════════════════════════');
    }

    /**
     * Best-effort guess for the tenant's domain when we have to create a
     * Site row from scratch. Falls back to a slug-based placeholder so
     * the UNIQUE constraint on sites.domain stays satisfied.
     */
    private function deriveDomain(Tenant $tenant): string
    {
        $settings = is_array($tenant->settings ?? null) ? $tenant->settings : [];
        foreach (['website', 'domain', 'site_url'] as $key) {
            if (!empty($settings[$key])) {
                $host = parse_url((string) $settings[$key], PHP_URL_HOST);
                if ($host) {
                    return strtolower($host);
                }
            }
        }
        // Fallback keeps the UNIQUE index happy — tenant can rename later.
        return strtolower($tenant->slug ?: ('tenant-' . $tenant->id)) . '.unknown.local';
    }
}
