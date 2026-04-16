<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Iter 12 wired BotPolicy — create/update/delete require
        // tenant_admin or tenant_manager, so the test user needs a role.
        foreach (['tenant_admin', 'tenant_manager', 'tenant_viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->assignRole('tenant_admin');
    }

    public function test_bot_list_page_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard/agenti');

        $response->assertStatus(200);
    }

    public function test_can_create_bot(): void
    {
        $response = $this->actingAs($this->user)->post('/dashboard/agenti', [
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

        $response = $this->actingAs($this->user)->put("/dashboard/agenti/{$bot->id}", [
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

        $response = $this->actingAs($this->user)->delete("/dashboard/agenti/{$bot->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('bots', ['id' => $bot->id]);
    }

    public function test_can_toggle_bot_active_inactive(): void
    {
        $bot = Bot::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->patch("/dashboard/agenti/{$bot->id}/toggle");

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
        $response = $this->actingAs($userB)->get("/dashboard/agenti/{$botA->id}");

        $response->assertStatus(404);
    }
}
