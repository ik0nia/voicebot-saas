<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\PlanLimit;
use App\Models\Tenant;
use App\Services\ChatCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\Support\Fakes\FakeChatCompletionService;
use Tests\TestCase;

/**
 * Base class for every test that exercises {@see \App\Http\Controllers\Api\ChatbotApiController}
 * end-to-end. Guarantees:
 *
 *   - a fresh test DB (RefreshDatabase) — prod is protected by bootstrap + TestCase guards
 *   - a FakeChatCompletionService bound into the container, so the LLM
 *     call is intercepted at the service boundary (no Anthropic /
 *     OpenAI network traffic, no keys required)
 *   - a minimal 'free' PlanLimit seeded so PlanLimitService checks pass
 *   - spatie roles created — BotPolicy etc. reference them during web flows
 *
 * Concrete tests should build their own Tenant → Bot → Channel graph via
 * factories and then call the helpers {@see sendMessage()} /
 * {@see sendMessageStream()}.
 */
abstract class ChatbotCharacterizationTestCase extends TestCase
{
    use RefreshDatabase;

    protected FakeChatCompletionService $chatFake;

    protected function setUp(): void
    {
        parent::setUp();

        // The array cache store survives across tests in the same PHPUnit
        // process (RefreshDatabase resets the DB but not the container-
        // scoped cache). Flushing it here guarantees each test sees a
        // clean slate — critical for byte-exact prompt snapshots, where
        // a leaked `bot_system_prompt_{id}_legacy` entry from a prior run
        // would silently override factory data.
        Cache::flush();

        foreach (['tenant_admin', 'tenant_manager', 'tenant_viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->seedFreePlan();

        $this->chatFake = new FakeChatCompletionService();
        $this->app->instance(ChatCompletionService::class, $this->chatFake);
    }

    /**
     * Build a minimally valid {@see Channel} (widget type) tied to a
     * fresh Tenant + Bot. Convenience wrapper — tests that need
     * niche-specific or structured-prompt bots should call factories
     * directly instead.
     */
    protected function makeWidgetChannel(array $botOverrides = [], array $channelOverrides = []): Channel
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->state($botOverrides)
            ->create();

        return Channel::factory()
            ->for($bot)
            ->widget()
            ->state($channelOverrides)
            ->create();
    }

    /**
     * POST /api/v1/chatbot/{channel}/message with a user utterance.
     * Returns the raw response so the caller can run its own assertions.
     */
    protected function sendMessage(Channel $channel, string $text, array $extra = []): TestResponse
    {
        $payload = array_merge([
            'message' => $text,
        ], $extra);

        return $this->postJson("/api/v1/chatbot/{$channel->id}/message", $payload, [
            'Origin' => 'https://example.test',
            'User-Agent' => 'phpunit',
        ]);
    }

    /**
     * Convenience: the LLM messages array recorded from the most recent
     * /message request. Useful for byte-exact prompt snapshots.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function recordedLlmMessages(): array
    {
        $last = $this->chatFake->lastCall();
        if ($last === null) {
            $this->fail('No ChatCompletionService::complete() call was recorded.');
        }
        return $last['messages'];
    }

    private function seedFreePlan(): void
    {
        if (PlanLimit::where('slug', 'free')->exists()) {
            return;
        }

        PlanLimit::create([
            'slug' => 'free',
            'name' => 'Free',
            'price_monthly' => 0,
            'limits' => [
                'max_bots' => 1,
                'max_messages_per_month' => 100_000,
                'max_tokens_per_month' => 10_000_000,
            ],
            'features' => [],
            'allowed_agents' => [],
            'allowed_file_formats' => ['text', 'txt', 'url'],
            'max_upload_size_kb' => 2_048,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
