<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Bot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->bot = Bot::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_call_list_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard/apeluri');

        $response->assertStatus(200);
    }

    public function test_can_view_call_details(): void
    {
        $call = Call::factory()->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
        ]);

        $response = $this->actingAs($this->user)->get("/dashboard/apeluri/{$call->id}");

        $response->assertStatus(200);
    }

    public function test_can_filter_calls_by_status(): void
    {
        Call::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'status' => 'completed',
        ]);

        Call::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard/apeluri?status=completed');

        $response->assertStatus(200);
    }

    public function test_can_filter_calls_by_bot(): void
    {
        $botA = Bot::factory()->create(['tenant_id' => $this->tenant->id]);
        $botB = Bot::factory()->create(['tenant_id' => $this->tenant->id]);

        Call::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $botA->id,
        ]);

        Call::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $botB->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/dashboard/apeluri?bot_id={$botA->id}");

        $response->assertStatus(200);
    }

    public function test_tenant_isolation_calls_not_visible_to_other_tenant(): void
    {
        $callA = Call::factory()->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
        ]);

        $tenantB = Tenant::factory()->create();
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        // User B should not be able to see Tenant A's call
        $response = $this->actingAs($userB)->get("/dashboard/apeluri/{$callA->id}");

        $response->assertStatus(404);
    }
}
