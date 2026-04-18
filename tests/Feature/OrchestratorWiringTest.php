<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrchestratorWiringTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Bot $bot;
    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Test', 'slug' => 'test', 'plan' => 'pro', 'plan_slug' => 'pro']);
        $this->bot = Bot::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Test', 'slug' => 'test-bot',
            'is_active' => true, 'system_prompt' => 'Test bot.',
        ]);
        $this->channel = Channel::create([
            'bot_id' => $this->bot->id, 'type' => 'web_chatbot',
            'is_active' => true, 'status' => 'connected',
        ]);
    }

    public function test_orchestrator_runs_for_every_message(): void
    {
        // Orchestrator is the only pipeline now — no flag to toggle.
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'salut',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['response', 'session_id', 'session_token']);

        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound);
        $this->assertNotNull($outbound->detected_intents);
        $this->assertIsArray($outbound->detected_intents);
        $intentNames = array_column($outbound->detected_intents, 'name');
        $this->assertContains('greeting', $intentNames);
    }

    public function test_orchestrator_failure_serves_degraded_turn(): void
    {
        // When the orchestrator throws (DB/Redis outage, etc.) the
        // turn still completes — the user gets a reply assembled from
        // the system prompt + their message alone, no retrieved
        // products/knowledge. detected_intents / pipelines_executed
        // stay null so analytics can tell this path apart.
        $this->app->bind(\App\Services\IntentOrchestratorService::class, function () {
            return new class {
                public function plan(...$args) { throw new \RuntimeException('Boom'); }
            };
        });

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'test degraded',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['response']);

        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNull($outbound->detected_intents);
        $this->assertNull($outbound->pipelines_executed);
    }

    public function test_orchestrator_detects_multiple_intents(): void
    {
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'caut adeziv pentru gresie si cat costa livrarea',
        ]);

        $response->assertOk();

        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound->detected_intents);
        $this->assertIsArray($outbound->detected_intents);
        // Should detect product_search AND knowledge_query at minimum
        $intentNames = array_column($outbound->detected_intents, 'name');
        $this->assertTrue(
            count($intentNames) >= 2,
            'Expected at least 2 intents, got: ' . implode(', ', $intentNames)
        );
    }

    public function test_pipelines_executed_is_stored(): void
    {
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'ce silicon aveti',
        ]);

        $response->assertOk();

        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound->pipelines_executed);
        $this->assertIsArray($outbound->pipelines_executed);
    }
}
