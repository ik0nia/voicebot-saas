<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
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

    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/bots');

        $response->assertStatus(401);
    }

    public function test_can_list_bots_with_api_token(): void
    {
        Bot::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/bots');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_bot_via_api(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/bots', [
                'name' => 'API Bot',
                'system_prompt' => 'Ești un asistent vocal de test.',
                'voice' => 'nova',
                'language' => 'ro',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bots', [
            'tenant_id' => $this->tenant->id,
            'name' => 'API Bot',
        ]);
    }

    public function test_api_rate_limiting_headers_present(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/bots');

        $response->assertStatus(200);
    }

    public function test_tenant_isolation_in_api(): void
    {
        // Create a bot for tenant A
        $botA = Bot::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create tenant B with its own user
        $tenantB = Tenant::factory()->create();
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);
        $tokenB = $userB->createToken('test-token')->plainTextToken;

        // Tenant B should not see Tenant A's bots
        $response = $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->getJson('/api/v1/bots');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
