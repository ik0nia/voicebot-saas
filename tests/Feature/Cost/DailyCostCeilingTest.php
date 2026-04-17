<?php

namespace Tests\Feature\Cost;

use App\Models\AiApiMetric;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Cost\BnrExchangeRate;
use App\Services\Cost\DailyCostCeiling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Iteration B — per-tenant daily AI cost ceiling.
 *
 * The ceiling is feature-flagged via
 * `config('billing.daily_cost_ceiling_enabled')`. Tests exercise both
 * states + the super-admin bypass. BNR exchange is mocked at a flat
 * 4.50 so cost_cents → RON math is deterministic across CI runs.
 */
class DailyCostCeilingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'tenant_admin', 'tenant_manager', 'tenant_viewer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->assignRole('tenant_admin');

        // Predictable USD→RON for cost conversions.
        $this->app->singleton(BnrExchangeRate::class, function () {
            $m = Mockery::mock(BnrExchangeRate::class);
            $m->shouldReceive('usdToRon')->andReturn(4.50);
            $m->shouldReceive('convert')->andReturnUsing(fn ($usd) => round($usd * 4.50, 4));
            return $m;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Insert a row into ai_api_metrics so spentTodayRon() sees spend.
     * cost_cents is in USD cents; 100 cents = $1 = 4.50 RON at the mock.
     */
    private function seedSpend(int $costCents, ?int $tenantId = null): void
    {
        AiApiMetric::create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'input_tokens' => 100,
            'output_tokens' => 50,
            'cost_cents' => $costCents,
            'response_time_ms' => 100,
            'status' => 'success',
            'tenant_id' => $tenantId ?? $this->tenant->id,
            'purpose' => 'chat_completion',
        ]);
    }

    public function test_flag_off_always_allows_spend(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => false]);
        config(['billing.default_daily_ai_limit_ron' => 10.0]);

        // Spend 50 RON-equivalent (over limit) — should still pass
        // because the flag is OFF.
        $this->seedSpend(50_00); // $50 = 225 RON at 4.50
        $ceiling = app(DailyCostCeiling::class);

        $result = $ceiling->canSpend($this->tenant->id);

        $this->assertTrue($result['allowed']);
        $this->assertFalse($result['flag_enabled']);
        $this->assertGreaterThan(0, $result['spent_today_ron']);
    }

    public function test_under_limit_allows_spend(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => true]);
        config(['billing.default_daily_ai_limit_ron' => 10.0]);

        // 1 USD = 4.50 RON, which is under the 10 RON limit.
        $this->seedSpend(100);
        $ceiling = app(DailyCostCeiling::class);

        $result = $ceiling->canSpend($this->tenant->id);

        $this->assertTrue($result['allowed']);
        $this->assertSame(4.50, $result['spent_today_ron']);
        $this->assertSame(10.0, $result['limit_ron']);
        $this->assertNull($result['reason']);
    }

    public function test_over_limit_blocks_spend(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => true]);
        config(['billing.default_daily_ai_limit_ron' => 10.0]);

        // 300 cents = $3 = 13.50 RON at 4.50 rate — over 10 RON limit.
        $this->seedSpend(300);
        $ceiling = app(DailyCostCeiling::class);

        $result = $ceiling->canSpend($this->tenant->id);

        $this->assertFalse($result['allowed']);
        $this->assertSame('daily_limit_reached', $result['reason']);
        $this->assertGreaterThanOrEqual($result['limit_ron'], $result['spent_today_ron']);
    }

    public function test_estimate_that_would_cross_limit_is_blocked(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => true]);
        config(['billing.default_daily_ai_limit_ron' => 10.0]);

        // 200 cents = $2 = 9 RON (under limit), but plus a $0.50
        // estimate (2.25 RON) would land at 11.25 RON — block.
        $this->seedSpend(200);
        $ceiling = app(DailyCostCeiling::class);

        $result = $ceiling->canSpend($this->tenant->id, 50);

        $this->assertFalse($result['allowed']);
        $this->assertSame('would_exceed_daily_limit', $result['reason']);
    }

    public function test_super_admin_tenant_bypasses_ceiling(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => true]);
        config(['billing.default_daily_ai_limit_ron' => 10.0]);

        // Promote the tenant's user to super_admin — the service
        // bypasses the ceiling when any user on the tenant has that role.
        $this->user->assignRole('super_admin');

        // Blow the limit wide open.
        $this->seedSpend(50_00);

        $ceiling = app(DailyCostCeiling::class);
        $result = $ceiling->canSpend($this->tenant->id);

        $this->assertTrue($result['allowed']);
    }

    public function test_spent_today_reported_even_when_ceiling_off(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => false]);
        $this->seedSpend(450); // $4.50 = 20.25 RON at 4.50

        $ceiling = app(DailyCostCeiling::class);
        $result = $ceiling->canSpend($this->tenant->id);

        $this->assertEquals(20.25, round($result['spent_today_ron'], 2));
    }

    public function test_other_tenant_spend_does_not_count(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => true]);

        $other = Tenant::factory()->create();
        $this->seedSpend(50_00, $other->id);

        $ceiling = app(DailyCostCeiling::class);
        $result = $ceiling->canSpend($this->tenant->id);

        // Our tenant has 0 spend → allowed.
        $this->assertTrue($result['allowed']);
        $this->assertSame(0.0, $result['spent_today_ron']);
    }

    public function test_ai_usage_today_endpoint_returns_progress(): void
    {
        config(['billing.daily_cost_ceiling_enabled' => true]);
        config(['billing.default_daily_ai_limit_ron' => 10.0]);

        $this->seedSpend(100); // $1 = 4.50 RON

        $response = $this->actingAs($this->user)->getJson('/dashboard/ai-usage-today');

        $response->assertOk()
            ->assertJsonStructure(['cost_ron', 'calls', 'limit_ron', 'pct_of_limit', 'flag_enabled']);
        $this->assertSame(4.50, $response->json('cost_ron'));
        $this->assertSame(10.0, $response->json('limit_ron'));
        $this->assertGreaterThan(0, $response->json('calls'));
    }
}
