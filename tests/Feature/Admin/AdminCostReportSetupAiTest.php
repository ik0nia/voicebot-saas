<?php

namespace Tests\Feature\Admin;

use App\Models\AiApiMetric;
use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin cost page — setup-AI visibility extensions. Feature-tests
 * the paths a super-admin clicks through when auditing agent-setup
 * spend: the summary category, per-tenant drill-down, filter toggle,
 * top-abusers panel, and non-admin 403 gate.
 */
class AdminCostReportSetupAiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $tenantUser;
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'tenant_admin', 'tenant_manager', 'tenant_viewer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        // Super-admin is tenant-less (their tenant_id is irrelevant —
        // EnsureSuperAdmin allows through regardless of tenant).
        $this->tenantA = Tenant::create(['name' => 'Tenant Alpha', 'slug' => 'tenant-alpha']);
        $this->tenantB = Tenant::create(['name' => 'Tenant Beta', 'slug' => 'tenant-beta']);

        $this->superAdmin = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->superAdmin->assignRole('super_admin');

        $this->tenantUser = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->tenantUser->assignRole('tenant_admin');
    }

    private function seedSetupAiMetric(Tenant $tenant, int $costCents, ?int $botId = null, ?int $userId = null, array $meta = [], ?Carbon $at = null): AiApiMetric
    {
        $m = AiApiMetric::create([
            'provider' => 'anthropic',
            'model' => 'claude-haiku-4-5-20251001',
            'input_tokens' => 200,
            'output_tokens' => 300,
            'cost_cents' => $costCents,
            'response_time_ms' => 120,
            'status' => 'success',
            'bot_id' => $botId,
            'tenant_id' => $tenant->id,
            'user_id' => $userId,
            'purpose' => AiApiMetric::PURPOSE_AGENT_SETUP_AI,
            'metadata' => $meta,
        ]);
        if ($at !== null) {
            \Illuminate\Support\Facades\DB::table('ai_api_metrics')
                ->where('id', $m->id)
                ->update(['created_at' => $at, 'updated_at' => $at]);
            $m->created_at = $at;
            $m->updated_at = $at;
        }
        return $m;
    }

    public function test_super_admin_sees_agent_setup_ai_category_in_summary(): void
    {
        // $5.00 on setup-AI for tenantA this month.
        $this->seedSetupAiMetric($this->tenantA, 500);
        $this->seedSetupAiMetric($this->tenantB, 250);

        $response = $this->actingAs($this->superAdmin)->get('/admin/costuri');

        $response->assertStatus(200);
        $response->assertSee('Configurare agenți (AI)', false);
        // Cost displayed with 4-decimal precision (cheap calls).
        $response->assertSee('7.5000');
        // Call count pill on the KPI card — 2 requests total.
        $response->assertSeeText('2 apeluri');
    }

    public function test_per_tenant_drill_down_returns_correct_sums(): void
    {
        $botA = Bot::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Bot A',
            'slug' => 'bot-a',
            'system_prompt' => 'x',
        ]);
        $botB = Bot::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Bot B',
            'slug' => 'bot-b',
            'system_prompt' => 'y',
        ]);

        $this->seedSetupAiMetric($this->tenantA, 100, $botA->id, $this->tenantUser->id, ['target' => 'faq']);
        $this->seedSetupAiMetric($this->tenantA, 150, $botA->id, $this->tenantUser->id, ['target' => 'rules']);
        $this->seedSetupAiMetric($this->tenantA, 75,  $botB->id, $this->tenantUser->id, ['target' => 'tone']);

        // Use the service directly — it's the same path the controller
        // uses, and asserting cents keeps the test independent of the
        // live BNR rate.
        $report = app(\App\Services\Cost\SetupAiCostReport::class);
        $bots = $report->tenantBotBreakdown($this->tenantA->id, now()->subDay(), now());

        $this->assertCount(2, $bots);
        $row = $bots->firstWhere('bot_id', $botA->id);
        $this->assertSame(250.0, (float) $row->cost_cents);
        $this->assertSame(2, (int) $row->call_count);
    }

    public function test_doar_setup_ai_filter_returns_only_matching_rows(): void
    {
        // Setup-AI row (should appear).
        $setupRow = $this->seedSetupAiMetric($this->tenantA, 50, null, $this->tenantUser->id, ['target' => 'greeting']);

        // A non-setup-AI metric — e.g. an embedding call that shares
        // the tenant but a different purpose. Must NOT appear in the
        // filtered view.
        AiApiMetric::create([
            'provider' => 'openai',
            'model' => 'text-embedding-3-large',
            'input_tokens' => 500,
            'output_tokens' => 0,
            'cost_cents' => 999,
            'response_time_ms' => 80,
            'status' => 'success',
            'tenant_id' => $this->tenantA->id,
            'purpose' => 'kb_indexing',
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/admin/costuri?setup_ai_only=1');

        $response->assertStatus(200);
        $response->assertSee('Setup AI — detaliat');
        $response->assertSee('greeting'); // metadata target rendered
        $response->assertDontSee('text-embedding-3-large');
    }

    public function test_non_admin_user_hits_403(): void
    {
        $response = $this->actingAs($this->tenantUser)->get('/admin/costuri');
        $response->assertStatus(403);

        $response2 = $this->actingAs($this->tenantUser)->get('/admin/costuri?setup_ai_only=1');
        $response2->assertStatus(403);
    }

    public function test_top_abusers_panel_shows_top_5_by_cost_and_count(): void
    {
        // 6 tenants with varying spend + call count. Expect only top
        // 5 in each list.
        $tenants = collect(range(1, 6))->map(fn ($i) => Tenant::create([
            'name' => "Abuser {$i}", 'slug' => "abuser-{$i}",
        ]));

        foreach ($tenants as $i => $t) {
            // Tenant {$i+1} gets ({$i+1}) calls at 100 cents each.
            // That ranks them exactly by i → largest i = biggest spender.
            for ($j = 0; $j <= $i; $j++) {
                $this->seedSetupAiMetric($t, 100);
            }
        }

        $report = app(\App\Services\Cost\SetupAiCostReport::class);
        $abusers = $report->topAbusers();

        $this->assertCount(5, $abusers['by_cost']);
        $this->assertCount(5, $abusers['by_count']);

        // Biggest spender = last tenant (6 calls × 100c = 600c).
        $this->assertSame($tenants->last()->id, (int) $abusers['by_cost']->first()->tenant_id);
        $this->assertSame(600.0, (float) $abusers['by_cost']->first()->cost_cents);

        $response = $this->actingAs($this->superAdmin)->get('/admin/costuri');
        $response->assertStatus(200);
        $response->assertSee('Setup AI — top consumatori');
        $response->assertSee('Abuser 6'); // top spender is visible
    }

    public function test_setup_ai_limit_persists_to_tenant_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post("/admin/costuri/setup-ai/limita/{$this->tenantA->id}", [
                'max_setup_ai_ron_per_month' => 42.50,
            ]);

        $response->assertRedirect();
        $this->tenantA->refresh();
        $this->assertSame(42.50, (float) ($this->tenantA->settings['setup_ai_limit_ron'] ?? null));
    }

    public function test_30d_warning_thresholds_computed_correctly(): void
    {
        $rate = app(\App\Services\Cost\BnrExchangeRate::class)->usdToRon();

        // Amber: > 10 RON but < 50 RON. Pick cents so that
        // cents/100 * rate ≈ 20 RON.
        $amberCents = (int) ceil((20.0 / $rate) * 100);
        $this->seedSetupAiMetric($this->tenantA, $amberCents, null, null, [], now()->subDays(2));

        // Red: > 50 RON. ≈ 80 RON.
        $redCents = (int) ceil((80.0 / $rate) * 100);
        $this->seedSetupAiMetric($this->tenantB, $redCents, null, null, [], now()->subDays(2));

        // Assert via the service so the computation is independent of
        // DailyCostRollup (the main view needs a rollup row to render
        // a tenant — not seeded here). The Blade template itself
        // switches the CSS class based on the same RON value.
        $report = app(\App\Services\Cost\SetupAiCostReport::class);
        $byTenant = $report->tenantRollup(now()->subDays(30), now())->keyBy('tenant_id');

        $this->assertGreaterThan(10.0, (float) $byTenant[$this->tenantA->id]->cost_ron);
        $this->assertLessThan(50.0, (float) $byTenant[$this->tenantA->id]->cost_ron);
        $this->assertGreaterThan(50.0, (float) $byTenant[$this->tenantB->id]->cost_ron);
    }
}
