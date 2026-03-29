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

    public function test_legacy_pipeline_runs_when_flag_off(): void
    {
        // No v2_orchestrator setting = flag OFF
        $this->assertEmpty($this->bot->settings['v2_orchestrator'] ?? null);

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'salut',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['response', 'session_id', 'session_token']);

        // detected_intents should be null (legacy path doesn't set them)
        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound);
        $this->assertNull($outbound->detected_intents);
        $this->assertNull($outbound->pipelines_executed);
    }

    public function test_orchestrator_runs_when_flag_on(): void
    {
        // Enable orchestrator
        $this->bot->update(['settings' => ['v2_orchestrator' => true]]);

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'salut',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['response', 'session_id', 'session_token']);

        // detected_intents should be populated
        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound);
        $this->assertNotNull($outbound->detected_intents);
        $this->assertIsArray($outbound->detected_intents);
        // Greeting intent should be detected
        $intentNames = array_column($outbound->detected_intents, 'name');
        $this->assertContains('greeting', $intentNames);
    }

    public function test_orchestrator_fallback_on_error(): void
    {
        // Enable orchestrator
        $this->bot->update(['settings' => ['v2_orchestrator' => true]]);

        // Bind a broken orchestrator to trigger fallback
        $this->app->bind(\App\Services\IntentOrchestratorService::class, function () {
            return new class {
                public function plan(...$args) { throw new \RuntimeException('Boom'); }
            };
        });

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'test fallback',
        ]);

        // Should still succeed — falls back to legacy pipeline
        $response->assertOk();
        $response->assertJsonStructure(['response']);

        // detected_intents should be null (fell back to legacy)
        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNull($outbound->detected_intents);
    }

    public function test_orchestrator_detects_multiple_intents(): void
    {
        $this->bot->update(['settings' => ['v2_orchestrator' => true]]);

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
        $this->bot->update(['settings' => ['v2_orchestrator' => true]]);

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'ce silicon aveti',
        ]);

        $response->assertOk();

        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound->pipelines_executed);
        $this->assertIsArray($outbound->pipelines_executed);
    }
}
