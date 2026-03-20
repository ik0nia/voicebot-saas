<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_bot_list_page_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard/boti');

        $response->assertStatus(200);
    }

    public function test_can_create_bot(): void
    {
        $response = $this->actingAs($this->user)->post('/dashboard/boti', [
            'name' => 'My Test Bot',
            'system_prompt' => 'Ești un asistent vocal de test.',
            'voice' => 'alloy',
            'language' => 'ro',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bots', [
            'tenant_id' => $this->tenant->id,
            'name' => 'My Test Bot',
        ]);
    }

    public function test_can_update_bot(): void
    {
        $bot = Bot::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->put("/dashboard/boti/{$bot->id}", [
            'name' => 'Updated Bot Name',
            'system_prompt' => $bot->system_prompt,
            'voice' => $bot->voice,
            'language' => $bot->language,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
            'name' => 'Updated Bot Name',
        ]);
    }

    public function test_can_delete_bot(): void
    {
        $bot = Bot::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->delete("/dashboard/boti/{$bot->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('bots', ['id' => $bot->id]);
    }

    public function test_can_toggle_bot_active_inactive(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->patch("/dashboard/boti/{$bot->id}/toggle");

        $response->assertRedirect();
        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
            'is_active' => false,
        ]);
    }

    public function test_bot_belongs_to_tenant_isolation(): void
    {
        $tenantA = $this->tenant;
        $botA = Bot::factory()->create(['tenant_id' => $tenantA->id]);

        $tenantB = Tenant::factory()->create();
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        // User B should not see Tenant A's bot
        $response = $this->actingAs($userB)->get("/dashboard/boti/{$botA->id}");

        $response->assertStatus(404);
    }
}
