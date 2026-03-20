<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_shows_correct_metrics(): void
    {
        $bot = Bot::factory()->create(['tenant_id' => $this->tenant->id]);

        Call::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $bot->id,
            'status' => 'completed',
        ]);

        Call::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $bot->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        // Verify the response contains expected data (total calls = 7)
        $response->assertSee('7');
    }

    /**
     * @dataProvider dashboardPagesProvider
     */
    public function test_dashboard_pages_return_200_for_authenticated_user(string $uri): void
    {
        $response = $this->actingAs($this->user)->get($uri);

        $response->assertStatus(200);
    }

    public static function dashboardPagesProvider(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'bots' => ['/dashboard/boti'],
            'calls' => ['/dashboard/apeluri'],
            'analytics' => ['/dashboard/analiza'],
            'phone numbers' => ['/dashboard/numere'],
            'team' => ['/dashboard/echipa'],
            'billing' => ['/dashboard/facturare'],
            'settings' => ['/dashboard/setari'],
        ];
    }
}
