<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Iter 12 wires BotPolicy + CallPolicy into the dashboard mutation
 * endpoints. Before this change, role enforcement was cosmetic only —
 * `tenant_viewer` didn't see the delete button, but the DELETE route was
 * reachable by anyone in the same tenant. These tests lock the new
 * server-side gate in place so a future refactor that strips
 * `$this->authorize()` from a controller is loud.
 */
class PolicyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Bot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'tenant_admin', 'tenant_manager', 'tenant_viewer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        $this->tenant = Tenant::create([
            'name' => 'Acme', 'slug' => 'acme', 'plan' => 'pro', 'plan_slug' => 'pro',
        ]);
        $this->bot = Bot::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main',
            'slug' => 'main-bot',
            'is_active' => true,
            'system_prompt' => 'Ești un asistent.',
        ]);
    }

    private function userWithRole(string $role, ?int $tenantId = null): User
    {
        $user = User::factory()->create(['tenant_id' => $tenantId ?? $this->tenant->id]);
        $user->assignRole($role);
        return $user;
    }

    public function test_viewer_cannot_delete_bot(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->delete("/dashboard/agenti/{$this->bot->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('bots', ['id' => $this->bot->id]);
    }

    public function test_viewer_cannot_update_bot(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->put("/dashboard/agenti/{$this->bot->id}", [
                'name' => 'Renamed',
                'language' => 'ro',
                'voice' => 'alloy',
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('bots', ['id' => $this->bot->id, 'name' => 'Main']);
    }

    public function test_viewer_cannot_toggle_bot(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->patch("/dashboard/agenti/{$this->bot->id}/toggle");
        $response->assertStatus(403);
        $this->assertDatabaseHas('bots', ['id' => $this->bot->id, 'is_active' => true]);
    }

    public function test_manager_can_update_but_cannot_delete_bot(): void
    {
        $manager = $this->userWithRole('tenant_manager');

        $update = $this->actingAs($manager)->put("/dashboard/agenti/{$this->bot->id}", [
            'name' => 'Renamed by manager',
            'language' => 'ro',
            'voice' => 'alloy',
        ]);
        $update->assertRedirect();
        $this->assertDatabaseHas('bots', ['id' => $this->bot->id, 'name' => 'Renamed by manager']);

        $delete = $this->actingAs($manager)->delete("/dashboard/agenti/{$this->bot->id}");
        $delete->assertStatus(403);
        $this->assertDatabaseHas('bots', ['id' => $this->bot->id]);
    }

    public function test_admin_can_delete_bot(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_admin'))
            ->delete("/dashboard/agenti/{$this->bot->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('bots', ['id' => $this->bot->id]);
    }

    public function test_super_admin_bypasses_policy_in_another_tenant(): void
    {
        // Super admin belongs to a different tenant — Gate::before allows it.
        $otherTenant = Tenant::create([
            'name' => 'Other', 'slug' => 'other', 'plan' => 'pro', 'plan_slug' => 'pro',
        ]);
        $superAdmin = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $superAdmin->assignRole('super_admin');

        // Put super_admin into aggregate mode so resolveBot skips tenant scope.
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_view_all' => true])
            ->delete("/dashboard/agenti/{$this->bot->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('bots', ['id' => $this->bot->id]);
    }

    public function test_viewer_cannot_delete_call(): void
    {
        $phoneNumber = PhoneNumber::create([
            'tenant_id' => $this->tenant->id,
            'number' => '+40700000000',
            'is_active' => true,
        ]);
        $call = Call::create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'phone_number_id' => $phoneNumber->id,
            'direction' => 'inbound',
            'status' => 'completed',
            'caller_number' => '+40711111111',
        ]);

        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->delete("/dashboard/apeluri/{$call->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('calls', ['id' => $call->id]);
    }

    public function test_admin_can_delete_call(): void
    {
        $phoneNumber = PhoneNumber::create([
            'tenant_id' => $this->tenant->id,
            'number' => '+40700000001',
            'is_active' => true,
        ]);
        $call = Call::create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'phone_number_id' => $phoneNumber->id,
            'direction' => 'inbound',
            'status' => 'completed',
            'caller_number' => '+40711111111',
        ]);

        $response = $this->actingAs($this->userWithRole('tenant_admin'))
            ->delete("/dashboard/apeluri/{$call->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('calls', ['id' => $call->id]);
    }

    public function test_viewer_cannot_create_bot(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->post('/dashboard/agenti', [
                'name' => 'Unauthorized Bot',
                'language' => 'ro',
                'voice' => 'alloy',
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('bots', ['name' => 'Unauthorized Bot']);
    }
}
