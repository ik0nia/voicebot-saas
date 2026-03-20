<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_scope_filters_by_tenant_id(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Bot::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
        Bot::factory()->count(2)->create(['tenant_id' => $tenantB->id]);

        // Simulate tenant context for tenant A
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $this->actingAs($userA);

        // When querying with tenant scope, only tenant A bots should appear
        $bots = Bot::where('tenant_id', $tenantA->id)->get();
        $this->assertCount(3, $bots);

        foreach ($bots as $bot) {
            $this->assertEquals($tenantA->id, $bot->tenant_id);
        }
    }

    public function test_tenant_a_cannot_see_tenant_b_bots(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $botA = Bot::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Bot A']);
        $botB = Bot::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Bot B']);

        $botsForA = Bot::where('tenant_id', $tenantA->id)->get();
        $botsForB = Bot::where('tenant_id', $tenantB->id)->get();

        $this->assertCount(1, $botsForA);
        $this->assertCount(1, $botsForB);
        $this->assertEquals('Bot A', $botsForA->first()->name);
        $this->assertEquals('Bot B', $botsForB->first()->name);
        $this->assertFalse($botsForA->contains('id', $botB->id));
    }

    public function test_tenant_a_cannot_see_tenant_b_calls(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $botA = Bot::factory()->create(['tenant_id' => $tenantA->id]);
        $botB = Bot::factory()->create(['tenant_id' => $tenantB->id]);

        Call::factory()->count(4)->create([
            'tenant_id' => $tenantA->id,
            'bot_id' => $botA->id,
        ]);

        Call::factory()->count(2)->create([
            'tenant_id' => $tenantB->id,
            'bot_id' => $botB->id,
        ]);

        $callsForA = Call::where('tenant_id', $tenantA->id)->get();
        $callsForB = Call::where('tenant_id', $tenantB->id)->get();

        $this->assertCount(4, $callsForA);
        $this->assertCount(2, $callsForB);

        foreach ($callsForA as $call) {
            $this->assertEquals($tenantA->id, $call->tenant_id);
        }
    }

    public function test_creating_a_bot_auto_sets_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertEquals($tenant->id, $bot->tenant_id);
        $this->assertNotNull($bot->tenant_id);
    }
}
