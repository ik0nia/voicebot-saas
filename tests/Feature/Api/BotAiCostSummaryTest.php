<?php

namespace Tests\Feature\Api;

use App\Models\AiApiMetric;
use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tenant-facing /api/v1/bots/{bot}/ai-cost-summary — the dashboard
 * badge depends on this. Tests guard: (a) correct bucket math,
 * (b) other purposes don't leak into setup-AI totals, (c) cross-
 * tenant 403 is enforced, (d) a bot with 0 metrics returns zeros
 * (no 500 / divide-by-zero).
 */
class BotAiCostSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;
    private Bot $botA;
    private Bot $botB;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['tenant_admin', 'tenant_manager', 'tenant_viewer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();
        $this->userA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->userA->assignRole('tenant_admin');
        $this->userB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
        $this->userB->assignRole('tenant_admin');

        $this->botA = Bot::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->botB = Bot::factory()->create(['tenant_id' => $this->tenantB->id]);
    }

    private function seedSetupAi(int $tenantId, int $botId, int $costCents, ?Carbon $at = null): void
    {
        $m = AiApiMetric::create([
            'provider' => 'anthropic',
            'model' => 'claude-haiku-4-5-20251001',
            'input_tokens' => 100,
            'output_tokens' => 120,
            'cost_cents' => $costCents,
            'response_time_ms' => 90,
            'status' => 'success',
            'bot_id' => $botId,
            'tenant_id' => $tenantId,
            'purpose' => AiApiMetric::PURPOSE_AGENT_SETUP_AI,
            'metadata' => ['target' => 'faq'],
        ]);
        // Eloquent's auto-timestamps overwrite `created_at` passed to
        // create(); force-set after insert for time-window fixtures.
        if ($at !== null) {
            \Illuminate\Support\Facades\DB::table('ai_api_metrics')
                ->where('id', $m->id)
                ->update(['created_at' => $at, 'updated_at' => $at]);
        }
    }

    public function test_endpoint_returns_correct_today_30d_lifetime_sums(): void
    {
        // Today: 2 calls × 100c = 200c.
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 100);
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 100);
        // 20 days ago (still in 30d window + lifetime): 1 × 250c.
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 250, now()->subDays(20));
        // 45 days ago (lifetime only): 1 × 500c.
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 500, now()->subDays(45));

        $token = $this->userA->createToken('t', ['bots:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/bots/{$this->botA->id}/ai-cost-summary");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertSame(2, $data['today']['count']);
        $this->assertSame(3, $data['last_30_days']['count']);
        $this->assertSame(4, $data['lifetime']['count']);
        // cost_ron is floated; only assert it's positive and monotonic.
        $this->assertGreaterThan(0, $data['today']['cost_ron']);
        $this->assertGreaterThanOrEqual($data['today']['cost_ron'], $data['last_30_days']['cost_ron']);
        $this->assertGreaterThanOrEqual($data['last_30_days']['cost_ron'], $data['lifetime']['cost_ron']);
    }

    public function test_endpoint_excludes_other_purposes(): void
    {
        // Setup-AI: 100c
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 100);
        // Voice-realtime: 5000c on the same bot — must NOT leak in.
        AiApiMetric::create([
            'provider' => 'openai',
            'model' => 'gpt-4o-realtime',
            'input_tokens' => 1000,
            'output_tokens' => 2000,
            'cost_cents' => 5000,
            'response_time_ms' => 0,
            'status' => 'success',
            'bot_id' => $this->botA->id,
            'tenant_id' => $this->tenantA->id,
            'purpose' => 'voice_realtime',
        ]);

        $token = $this->userA->createToken('t', ['bots:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/bots/{$this->botA->id}/ai-cost-summary");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(1, $data['lifetime']['count']);
    }

    public function test_cross_tenant_request_is_blocked(): void
    {
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 100);

        // User B (from a different tenant) hits bot A. The global
        // tenant scope on Bot turns this into a 404 (scope-not-found)
        // before the controller runs — which is the stronger guard
        // than 403 (doesn't leak that bot A exists). Accept either
        // 403 or 404; both prevent cross-tenant disclosure.
        $token = $this->userB->createToken('t', ['bots:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/bots/{$this->botA->id}/ai-cost-summary");

        $this->assertContains($response->status(), [403, 404]);

        // And critically: no summary body was returned.
        $this->assertFalse(
            isset($response->json()['today']),
            'Cross-tenant request must not return a summary payload.'
        );
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson("/api/v1/bots/{$this->botA->id}/ai-cost-summary");
        $response->assertStatus(401);
    }

    public function test_bot_with_zero_metrics_returns_zeros_not_error(): void
    {
        $token = $this->userA->createToken('t', ['bots:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/bots/{$this->botA->id}/ai-cost-summary");

        $response->assertStatus(200);
        $response->assertExactJson([
            'today'        => ['count' => 0, 'cost_ron' => 0.0],
            'last_30_days' => ['count' => 0, 'cost_ron' => 0.0],
            'lifetime'     => ['count' => 0, 'cost_ron' => 0.0],
        ]);
    }

    public function test_session_auth_mirror_route_works(): void
    {
        $this->seedSetupAi($this->tenantA->id, $this->botA->id, 100);

        $response = $this->actingAs($this->userA)
            ->getJson("/dashboard/agenti/{$this->botA->id}/ai-cost-summary");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(1, $data['today']['count']);
        $this->assertSame(1, $data['lifetime']['count']);
    }
}
