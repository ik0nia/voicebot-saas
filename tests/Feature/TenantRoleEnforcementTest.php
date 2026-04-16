<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Iter 13 hardens the team and account-destruction endpoints, which
 * before had only same-tenant equality checks — no role gate. Concretely
 * a tenant_viewer could:
 *   - POST /dashboard/echipa/invite to add a new user (any role)
 *   - PATCH /dashboard/echipa/{user}/role to promote themselves to
 *     tenant_admin (full tenant takeover)
 *   - DELETE /dashboard/setari/account to wipe the tenant
 *
 * Iter 12 wired model-level policies; iter 13 gates route-level mutation
 * surfaces that don't have a per-model policy. These tests lock the new
 * tenant.role middleware to its intended behaviour.
 */
class TenantRoleEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'tenant_admin', 'tenant_manager', 'tenant_viewer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        $this->tenant = Tenant::create([
            'name' => 'Acme', 'slug' => 'acme', 'plan' => 'pro', 'plan_slug' => 'pro',
        ]);
    }

    private function userWithRole(string $role, ?int $tenantId = null): User
    {
        $user = User::factory()->create(['tenant_id' => $tenantId ?? $this->tenant->id]);
        $user->assignRole($role);
        return $user;
    }

    public function test_viewer_cannot_invite_team_member(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->post('/dashboard/echipa/invite', [
                'name' => 'Mallory',
                'email' => 'mallory@example.com',
                'role' => 'tenant_admin',
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'mallory@example.com']);
    }

    public function test_manager_cannot_invite_team_member(): void
    {
        // Inviting teammates is an admin-only action. Manager is confined
        // to operational mutations (bots, sites, numbers, ...).
        $response = $this->actingAs($this->userWithRole('tenant_manager'))
            ->post('/dashboard/echipa/invite', [
                'name' => 'Eve',
                'email' => 'eve@example.com',
                'role' => 'tenant_viewer',
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'eve@example.com']);
    }

    public function test_admin_can_invite_team_member(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_admin'))
            ->post('/dashboard/echipa/invite', [
                'name' => 'Teammate',
                'email' => 'teammate@example.com',
                'role' => 'tenant_viewer',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'teammate@example.com']);
    }

    public function test_viewer_cannot_promote_themselves_to_admin(): void
    {
        // Full privilege-escalation scenario — before iter 13 this was
        // a tenant takeover.
        $viewer = $this->userWithRole('tenant_viewer');

        $response = $this->actingAs($viewer)
            ->patch("/dashboard/echipa/{$viewer->id}/role", ['role' => 'tenant_admin']);
        $response->assertStatus(403);

        $viewer->refresh();
        $this->assertTrue($viewer->hasRole('tenant_viewer'));
        $this->assertFalse($viewer->hasRole('tenant_admin'));
    }

    public function test_viewer_cannot_remove_team_member(): void
    {
        $viewer = $this->userWithRole('tenant_viewer');
        $other = $this->userWithRole('tenant_manager');

        $response = $this->actingAs($viewer)
            ->delete("/dashboard/echipa/{$other->id}/remove");
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_viewer_cannot_destroy_tenant_account(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->delete('/dashboard/setari/account', ['confirmation' => 'STERGE']);
        $response->assertStatus(403);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_viewer_cannot_update_company_settings(): void
    {
        $response = $this->actingAs($this->userWithRole('tenant_viewer'))
            ->put('/dashboard/setari/company', [
                'name' => 'Hacked Inc.',
                'settings' => [],
            ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id, 'name' => 'Acme']);
    }

    public function test_super_admin_bypasses_tenant_role_middleware(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other', 'slug' => 'other', 'plan' => 'pro', 'plan_slug' => 'pro',
        ]);
        $superAdmin = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)
            ->post('/dashboard/echipa/invite', [
                'name' => 'Support',
                'email' => 'support@sambla.ro',
                'role' => 'tenant_viewer',
            ]);
        // Not checking redirect exactly — super_admin may land on a tenant
        // without context and the controller might 302 elsewhere — the
        // point is the middleware lets the request through (no 403).
        $this->assertNotSame(403, $response->status(), 'Super admin blocked by tenant.role middleware');
    }
}
