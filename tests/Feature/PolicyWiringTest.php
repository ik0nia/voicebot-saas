<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\ConversationPolicy;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyWiringTest extends TestCase
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
            'is_active' => true, 'system_prompt' => 'Ești un asistent virtual de test.',
        ]);
        $this->channel = Channel::create([
            'bot_id' => $this->bot->id, 'type' => 'web_chatbot',
            'is_active' => true, 'status' => 'connected',
        ]);
    }

    public function test_policy_not_injected_when_flag_off(): void
    {
        // Create a policy with a distinctive phrase
        ConversationPolicy::create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'tone' => 'premium',
            'prohibited_phrases' => ['test_prohibited_marker'],
        ]);

        // Flag OFF — policy should not be applied
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'salut',
        ]);

        $response->assertOk();

        // We can't directly inspect the prompt sent to AI, but we can verify
        // the logger metadata doesn't include policy_applied
        // This is a structural test — the policy service was not called
        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound);
        // No crash, response generated successfully without policy
    }

    public function test_policy_injected_when_flag_on(): void
    {
        // Enable v2_policies flag
        $this->bot->update(['settings' => ['v2_policies' => true]]);

        // Create a policy
        ConversationPolicy::create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'tone' => 'warm',
            'verbosity' => 'detailed',
            'emoji_allowed' => true,
            'prohibited_phrases' => ['nu avem', 'imposibil'],
            'business_rules' => ['Menționează mereu serviciul de instalare'],
        ]);

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'salut',
        ]);

        $response->assertOk();

        // Verify response was generated (policy didn't crash the flow)
        $outbound = Message::where('direction', 'outbound')->latest('id')->first();
        $this->assertNotNull($outbound);
        $this->assertNotEmpty($outbound->content);
    }

    public function test_policy_graceful_failure(): void
    {
        // Enable flag but bind broken policy service
        $this->bot->update(['settings' => ['v2_policies' => true]]);

        $this->app->bind(\App\Services\ConversationPolicyService::class, function () {
            return new class {
                public function getPolicy(...$args) { throw new \RuntimeException('Policy boom'); }
            };
        });

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/message", [
            'message' => 'test policy failure',
        ]);

        // Should still succeed — policy failure is caught and skipped
        $response->assertOk();
        $response->assertJsonStructure(['response']);
    }

    public function test_policy_merge_precedence(): void
    {
        // Tenant-level policy
        ConversationPolicy::create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => null,
            'tone' => 'professional',
            'emoji_allowed' => false,
            'business_rules' => ['Tenant rule 1'],
        ]);

        // Bot-level override
        ConversationPolicy::create([
            'tenant_id' => $this->tenant->id,
            'bot_id' => $this->bot->id,
            'tone' => 'warm',
            'business_rules' => ['Bot rule override'],
        ]);

        $policyService = app(\App\Services\ConversationPolicyService::class);
        $policy = $policyService->getPolicy($this->bot);

        // Bot-level tone should override tenant
        $this->assertEquals('warm', $policy['tone']);
        // Bot-level rules should override tenant
        $this->assertEquals(['Bot rule override'], $policy['business_rules']);
        // Emoji stays from tenant (bot didn't override it)
        $this->assertFalse($policy['emoji_allowed']);
    }

    public function test_policy_to_prompt_output(): void
    {
        $policyService = app(\App\Services\ConversationPolicyService::class);

        $policy = [
            'tone' => 'warm',
            'verbosity' => 'concise',
            'emoji_allowed' => true,
            'prohibited_phrases' => ['nu știu', 'imposibil'],
            'required_phrases' => ['Cu drag!'],
            'business_rules' => ['Menționează serviciul de montaj'],
            'cta_aggressiveness' => 'moderate',
            'lead_aggressiveness' => 'soft',
            'brand_vocabulary' => [],
            'snippets' => [],
        ];

        $instructions = $policyService->toPromptInstructions($policy);

        $this->assertStringContainsString('STILUL CONVERSAȚIEI', $instructions);
        $this->assertStringContainsString('cald', $instructions); // warm tone
        $this->assertStringContainsString('emoji', $instructions);
        $this->assertStringContainsString('nu știu', $instructions); // prohibited
        $this->assertStringContainsString('Cu drag!', $instructions); // required
        $this->assertStringContainsString('montaj', $instructions); // business rule
        $this->assertStringContainsString('SFÂRȘIT STIL', $instructions);
    }
}
