<?php

namespace Tests\Feature\Dashboard;

use App\Models\Appointment;
use App\Models\Bot;
use App\Models\ServiceType;
use App\Models\StaffMember;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Coverage for the booking admin UI introduced in iter E. Every mutation
 * path and the advanced-mode toggle are exercised; cross-tenant access
 * is rejected. The controller lives at
 * /dashboard/agenti/{bot}/programari/*.
 */
class BookingAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Bot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'tenant_admin', 'tenant_manager', 'tenant_viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->assignRole('tenant_admin');

        $this->bot = Bot::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'engine_type' => 'booking',
            'niche_slug'  => 'stomatologie',
        ]);
    }

    public function test_non_booking_bot_redirects_away(): void
    {
        $leadBot = Bot::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'engine_type' => 'lead',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/dashboard/agenti/{$leadBot->id}/programari");

        $response->assertRedirect("/dashboard/agenti/{$leadBot->id}/editare");
        $response->assertSessionHas('info');
    }

    public function test_booking_bot_renders_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/dashboard/agenti/{$this->bot->id}/programari");

        $response->assertStatus(200);
        $response->assertSee('Programări', false);
        $response->assertSee('Servicii', false);
        $response->assertSee('Personal', false);
        $response->assertSee('Program de lucru', false);
    }

    public function test_store_service_creates_row(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/dashboard/agenti/{$this->bot->id}/programari/servicii", [
                'name'             => 'Consultație test',
                'duration_minutes' => 45,
                'price'            => 150,
                'is_urgent'        => 1,
                'is_active'        => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_types', [
            'bot_id' => $this->bot->id,
            'name'   => 'Consultație test',
            'duration_minutes' => 45,
            'is_urgent' => true,
        ]);
    }

    public function test_update_service_modifies_row(): void
    {
        $service = ServiceType::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'Original',
            'slug'      => 'original',
            'duration_minutes' => 30,
        ]);

        $response = $this->actingAs($this->user)
            ->patch("/dashboard/agenti/{$this->bot->id}/programari/servicii/{$service->id}", [
                'name'             => 'Editat',
                'duration_minutes' => 60,
                'price'            => 200,
                'is_urgent'        => 1,
                'is_active'        => 1,
            ]);

        $response->assertRedirect();
        $service->refresh();
        $this->assertSame('Editat', $service->name);
        $this->assertSame(60, $service->duration_minutes);
        $this->assertTrue((bool) $service->is_urgent);
    }

    public function test_destroy_service_with_no_history_hard_deletes(): void
    {
        $service = ServiceType::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'Temp',
            'slug'      => 'temp',
            'duration_minutes' => 30,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/dashboard/agenti/{$this->bot->id}/programari/servicii/{$service->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('service_types', ['id' => $service->id]);
    }

    public function test_destroy_service_with_history_soft_deactivates(): void
    {
        $service = ServiceType::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'Istoric',
            'slug'      => 'istoric',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $staff = StaffMember::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'Cabinet',
        ]);
        Appointment::create([
            'tenant_id'        => $this->tenant->id,
            'bot_id'           => $this->bot->id,
            'service_type_id'  => $service->id,
            'staff_member_id'  => $staff->id,
            'customer_name'    => 'Ion',
            'starts_at'        => now()->subDays(5),
            'ends_at'          => now()->subDays(5)->addMinutes(30),
            'status'           => Appointment::STATUS_COMPLETED,
            'source'           => 'chat',
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/dashboard/agenti/{$this->bot->id}/programari/servicii/{$service->id}");

        $response->assertRedirect();
        $service->refresh();
        $this->assertFalse((bool) $service->is_active);
        $this->assertDatabaseHas('service_types', ['id' => $service->id]);
    }

    public function test_store_and_update_staff(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/dashboard/agenti/{$this->bot->id}/programari/personal", [
                'name' => 'Dr. Popescu',
                'role' => 'Medic dentist',
                'is_active' => 1,
            ]);
        $response->assertRedirect();

        $staff = StaffMember::where('bot_id', $this->bot->id)->where('name', 'Dr. Popescu')->first();
        $this->assertNotNull($staff);

        $this->actingAs($this->user)
            ->patch("/dashboard/agenti/{$this->bot->id}/programari/personal/{$staff->id}", [
                'name' => 'Dr. Ionescu',
                'is_active' => 1,
            ])->assertRedirect();

        $staff->refresh();
        $this->assertSame('Dr. Ionescu', $staff->name);
    }

    public function test_destroy_staff_with_future_appointments_requires_force(): void
    {
        $staff = StaffMember::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'Dr. Temp',
        ]);
        $service = ServiceType::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'X',
            'slug'      => 'x',
            'duration_minutes' => 30,
        ]);
        Appointment::create([
            'tenant_id'        => $this->tenant->id,
            'bot_id'           => $this->bot->id,
            'service_type_id'  => $service->id,
            'staff_member_id'  => $staff->id,
            'customer_name'    => 'Viitor',
            'starts_at'        => now()->addDays(2),
            'ends_at'          => now()->addDays(2)->addMinutes(30),
            'status'           => Appointment::STATUS_CONFIRMED,
            'source'           => 'chat',
        ]);

        // Without force → refused.
        $this->actingAs($this->user)
            ->delete("/dashboard/agenti/{$this->bot->id}/programari/personal/{$staff->id}")
            ->assertRedirect();
        $this->assertDatabaseHas('staff_members', ['id' => $staff->id]);

        // With force=1 → deleted.
        $this->actingAs($this->user)
            ->delete("/dashboard/agenti/{$this->bot->id}/programari/personal/{$staff->id}?force=1")
            ->assertRedirect();
        $this->assertDatabaseMissing('staff_members', ['id' => $staff->id]);
    }

    public function test_update_hours_persists_grid(): void
    {
        $staff = StaffMember::create([
            'tenant_id' => $this->tenant->id,
            'bot_id'    => $this->bot->id,
            'name'      => 'Cabinet',
        ]);

        $payload = [
            'staff' => [
                (string) $staff->id => [
                    '1' => ['start' => '09:00', 'end' => '18:00', 'closed' => 0],
                    '2' => ['start' => '09:00', 'end' => '18:00', 'closed' => 0],
                    '6' => ['closed' => 1],
                    '7' => ['closed' => 1],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put("/dashboard/agenti/{$this->bot->id}/programari/program", $payload);

        $response->assertRedirect();

        $this->assertSame(2, WorkingHour::where('staff_member_id', $staff->id)->count());
        $this->assertDatabaseHas('working_hours', [
            'staff_member_id' => $staff->id,
            'weekday' => 1,
            'start_time' => '09:00:00',
            'end_time'   => '18:00:00',
        ]);
    }

    public function test_advanced_mode_toggle_persists_on_bot(): void
    {
        $this->actingAs($this->user)
            ->post("/dashboard/agenti/{$this->bot->id}/programari/advanced-mode", [
                'enabled' => 1,
            ])->assertRedirect();

        $this->bot->refresh();
        $this->assertTrue($this->bot->settings['booking']['advanced_mode'] ?? false);

        $this->actingAs($this->user)
            ->post("/dashboard/agenti/{$this->bot->id}/programari/advanced-mode", [
                'enabled' => 0,
            ])->assertRedirect();

        $this->bot->refresh();
        $this->assertFalse($this->bot->settings['booking']['advanced_mode'] ?? true);
    }

    public function test_cross_tenant_access_blocked(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherBot = Bot::factory()->create([
            'tenant_id'   => $otherTenant->id,
            'engine_type' => 'booking',
        ]);

        // Index: route-model binding applies the global scope → 404.
        $status = $this->actingAs($this->user)
            ->get("/dashboard/agenti/{$otherBot->id}/programari")->status();
        $this->assertContains($status, [403, 404]);

        // Service store on another tenant's bot.
        $status = $this->actingAs($this->user)
            ->post("/dashboard/agenti/{$otherBot->id}/programari/servicii", [
                'name' => 'hack',
                'duration_minutes' => 30,
            ])->status();
        $this->assertContains($status, [403, 404]);

        // Advanced mode toggle on another tenant's bot.
        $status = $this->actingAs($this->user)
            ->post("/dashboard/agenti/{$otherBot->id}/programari/advanced-mode", [
                'enabled' => 1,
            ])->status();
        $this->assertContains($status, [403, 404]);

        // Nothing should have leaked into the other tenant's settings.
        $otherBot->refresh();
        $this->assertFalse($otherBot->settings['booking']['advanced_mode'] ?? false);
    }
}
