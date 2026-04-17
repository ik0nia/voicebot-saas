<?php

namespace Tests\Feature\Console;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers php artisan bots:normalize.
 *
 * Most cases run in bulk mode (--all-niche=... --yes) which is
 * non-interactive and deterministic, so the suite doesn't depend on
 * the Laravel test input stack for expectsQuestion() chains.
 *
 * The interactive-path test uses expectsQuestion / expectsConfirmation
 * to exercise the prompt/strategy flow end-to-end for a single bot.
 */
class BotsNormalizeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create([
            'settings' => ['website' => 'https://legacy-tenant.example.com'],
        ]);
    }

    public function test_bulk_mode_migrates_legacy_bot_with_all_nulls(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'niche_slug'   => null,
            'engine_type'  => null,
            'site_id'      => null,
            'system_prompt' => null,
        ]);

        $this->artisan('bots:normalize', [
            '--all-niche' => 'stomatologie',
            '--yes'       => true,
        ])->assertSuccessful();

        $bot->refresh();
        $this->assertSame('stomatologie', $bot->niche_slug);
        $this->assertSame('booking', $bot->engine_type);
        $this->assertNotNull($bot->site_id);
        $this->assertNotEmpty($bot->system_prompt);

        // Site created for the tenant (default scope applies to Site too).
        $this->assertDatabaseHas('sites', ['tenant_id' => $this->tenant->id]);

        // web_chatbot channel created.
        $this->assertDatabaseHas('channels', [
            'bot_id' => $bot->id,
            'type'   => Channel::TYPE_WEB_CHATBOT,
        ]);

        // Structured-prompt scaffold initialised but flag OFF (operator flips later).
        $settings = $bot->settings ?? [];
        $this->assertArrayHasKey('use_structured_prompt', $settings);
        $this->assertFalse($settings['use_structured_prompt']);
        $this->assertArrayHasKey('business_info', $settings);
        $this->assertArrayHasKey('faqs', $settings);
        $this->assertArrayHasKey('dont_rules', $settings);
        $this->assertArrayHasKey('tone_guide', $settings);

        // Booking seeds ran → service_types rows for this bot.
        $this->assertTrue(
            DB::table('service_types')->where('bot_id', $bot->id)->exists(),
            'Expected booking:seed-defaults to have inserted service_types rows.'
        );
    }

    public function test_append_strategy_concatenates_existing_and_niche_prompts(): void
    {
        $oldPrompt = 'Ești asistentul cabinetului dr. Popescu. Fii politicos.';
        $bot = Bot::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'niche_slug'    => null,
            'system_prompt' => $oldPrompt,
        ]);

        // Bulk mode uses Append by default for bots with an existing prompt.
        $this->artisan('bots:normalize', [
            '--all-niche' => 'stomatologie',
            '--yes'       => true,
        ])->assertSuccessful();

        $bot->refresh();
        $nicheAddon = trim(config('niches.stomatologie.prompt_addon'));
        $expected = trim($oldPrompt) . "\n\n---\n\nReguli operaționale:\n" . $nicheAddon;

        $this->assertSame($expected, $bot->system_prompt);
        $this->assertStringContainsString($oldPrompt, $bot->system_prompt);
        $this->assertStringContainsString('Reguli operaționale', $bot->system_prompt);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'niche_slug'  => null,
            'engine_type' => null,
            'site_id'     => null,
        ]);
        $originalPrompt = $bot->system_prompt;

        $this->artisan('bots:normalize', [
            '--all-niche' => 'beauty',
            '--yes'       => true,
            '--dry-run'   => true,
        ])->assertSuccessful();

        $bot->refresh();
        $this->assertNull($bot->niche_slug);
        $this->assertNull($bot->engine_type);
        $this->assertNull($bot->site_id);
        $this->assertSame($originalPrompt, $bot->system_prompt);

        // No channel created either.
        $this->assertDatabaseMissing('channels', [
            'bot_id' => $bot->id,
            'type'   => Channel::TYPE_WEB_CHATBOT,
        ]);
    }

    public function test_bulk_mode_migrates_multiple_bots_non_interactively(): void
    {
        $bots = collect(range(1, 3))->map(fn () => Bot::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'niche_slug' => null,
            'site_id'    => null,
        ]))->all();

        $this->artisan('bots:normalize', [
            '--all-niche' => 'stomatologie',
            '--yes'       => true,
        ])->assertSuccessful();

        foreach ($bots as $bot) {
            $bot->refresh();
            $this->assertSame('stomatologie', $bot->niche_slug);
            $this->assertSame('booking', $bot->engine_type);
            $this->assertNotNull($bot->site_id);
        }
    }

    public function test_invalid_niche_slug_fails_with_clear_error(): void
    {
        Bot::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'niche_slug' => null,
        ]);

        $this->artisan('bots:normalize', [
            '--all-niche' => 'does-not-exist',
            '--yes'       => true,
        ])
            ->expectsOutputToContain("Invalid --all-niche value 'does-not-exist'")
            ->assertFailed();
    }

    public function test_bot_already_having_niche_is_skipped_in_default_mode(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'niche_slug'  => 'beauty',
            'engine_type' => 'hybrid',
        ]);

        // No --bot, no --all-niche — the bot is filtered out by the
        // whereNull(niche_slug) query. Command exits with "No bots need
        // normalization" when that filter returns nothing.
        $this->artisan('bots:normalize')
            ->expectsOutput('No bots need normalization.')
            ->assertSuccessful();

        $bot->refresh();
        $this->assertSame('beauty', $bot->niche_slug);
    }

    public function test_orphan_bot_is_skipped_with_warning(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'niche_slug' => null,
        ]);
        // Nuke the tenant to create an orphan bot. Normal FK cascades
        // would wipe the bot too, so we clear the FK with a raw update
        // first — this simulates a historical data quirk, not typical ops.
        DB::table('bots')->where('id', $bot->id)->update(['tenant_id' => 999999]);

        $this->artisan('bots:normalize', [
            '--all-niche' => 'stomatologie',
            '--yes'       => true,
        ])
            ->expectsOutputToContain('orphaned')
            ->assertSuccessful();

        $fresh = DB::table('bots')->where('id', $bot->id)->first();
        $this->assertNull($fresh->niche_slug);
    }

    public function test_single_bot_mode_targets_only_that_bot(): void
    {
        $target = Bot::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'niche_slug' => null,
        ]);
        $other = Bot::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'niche_slug' => null,
        ]);

        $this->artisan('bots:normalize', [
            '--bot'       => $target->id,
            '--all-niche' => 'beauty',
            '--yes'       => true,
        ])->assertSuccessful();

        $target->refresh();
        $other->refresh();
        $this->assertSame('beauty', $target->niche_slug);
        $this->assertNull($other->niche_slug, 'Non-targeted bot must stay untouched.');
    }

    public function test_existing_site_is_reused_not_duplicated(): void
    {
        $existingSite = Site::create([
            'tenant_id' => $this->tenant->id,
            'domain'    => 'already-existing.example.com',
            'name'      => 'Existing Site',
            'status'    => 'active',
        ]);

        $bot = Bot::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'niche_slug' => null,
            'site_id'    => null,
        ]);

        $this->artisan('bots:normalize', [
            '--all-niche' => 'stomatologie',
            '--yes'       => true,
        ])->assertSuccessful();

        $bot->refresh();
        $this->assertSame($existingSite->id, $bot->site_id);

        // Still only one site for this tenant.
        $this->assertSame(
            1,
            Site::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count()
        );
    }

    public function test_interactive_flow_asks_niche_then_strategy(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'niche_slug'    => null,
            'system_prompt' => 'Prompt vechi custom.',
        ]);

        $this->artisan('bots:normalize', ['--bot' => $bot->id])
            ->expectsQuestion('Ce nișă? (s = skip, ? = listă, q = quit)', 'stomatologie')
            ->expectsQuestion('Alegere', 'A')
            ->expectsConfirmation("Apply niche 'stomatologie' to bot #{$bot->id}?", 'yes')
            ->assertSuccessful();

        $bot->refresh();
        $this->assertSame('stomatologie', $bot->niche_slug);
        $this->assertStringContainsString('Prompt vechi custom.', $bot->system_prompt);
        $this->assertStringContainsString('Reguli operaționale', $bot->system_prompt);
    }
}
