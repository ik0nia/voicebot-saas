<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\PlanLimit;
use App\Models\Tenant;
use App\Services\Chat\ChatResponderInterface;
use App\Services\ChatCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\Support\Fakes\FakeChatCompletionService;
use Tests\Support\Fakes\FakeChatResponder;
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
    protected FakeChatResponder $responderFake;

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

        // Two fakes bound in parallel: the ChatCompletionService fake
        // catches the legacy sync path (anything that still calls
        // ChatCompletionService directly — e.g. generateAIResponse's
        // cascading fallback), and the ChatResponder fake catches the
        // unified sync+stream path that runs through the refactored
        // service. Both record calls for snapshot assertions.
        $this->chatFake = new FakeChatCompletionService();
        $this->app->instance(ChatCompletionService::class, $this->chatFake);

        $this->responderFake = new FakeChatResponder();
        $this->app->instance(ChatResponderInterface::class, $this->responderFake);
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
     * Queue a fake LLM reply for the next /message or /message-stream
     * round-trip. Feeds both the ChatResponder fake (new unified path)
     * and the ChatCompletionService fake (legacy direct callers like
     * the cascading fallback in generateAIResponse) so the first
     * available consumer picks it up regardless of which path runs.
     */
    protected function queueReply(string $content, array $tokens = []): void
    {
        $this->responderFake->queueCompleteReply($content, $tokens);
        $this->responderFake->queueStreamReply($content, $tokens);
        $this->chatFake->queueReply($content, $tokens);
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
     * POST /api/v1/chatbot/{channel}/message-stream. Consumes the
     * streamed body as part of the call so the StreamedResponse
     * closure actually runs — without streamedContent() the closure
     * is deferred until someone reads the body, which means DB
     * assertions from the caller would observe only the pre-stream
     * rows (greeting + user message) and miss the bot reply insert.
     */
    protected function sendMessageStream(Channel $channel, string $text, array $extra = []): TestResponse
    {
        $payload = array_merge([
            'message' => $text,
        ], $extra);

        $response = $this->postJson("/api/v1/chatbot/{$channel->id}/message-stream", $payload, [
            'Origin' => 'https://example.test',
            'User-Agent' => 'phpunit',
            'Accept' => 'text/event-stream',
        ]);

        // Force the StreamedResponse closure to execute now.
        $response->streamedContent();

        return $response;
    }

    /**
     * Parse a collected SSE body into a flat list of
     * ['type' => ..., 'data' => [...]] entries. Handles only the
     * `data: <json>\n\n` framing the widget emits — no `event:` lines.
     *
     * @return list<array{type: string, data: array}>
     */
    protected function parseSseEvents(string $body): array
    {
        $events = [];
        foreach (preg_split('/\r?\n\r?\n/', trim($body)) as $chunk) {
            if (!str_starts_with($chunk, 'data:')) {
                continue;
            }
            $json = trim(substr($chunk, 5));
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                continue;
            }
            $events[] = [
                'type' => (string) ($decoded['type'] ?? 'unknown'),
                'data' => $decoded,
            ];
        }
        return $events;
    }

    /**
     * Convenience: the LLM messages array recorded from the most recent
     * /message request. Useful for byte-exact prompt snapshots.
     *
     * Checks the ChatResponder fake first (the refactored sync +
     * stream path flows through it), falls back to the
     * ChatCompletionService fake (for any legacy caller that reaches
     * the completion service directly).
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function recordedLlmMessages(): array
    {
        $last = $this->responderFake->lastCompleteCall()
            ?? $this->responderFake->lastStreamCall()
            ?? $this->chatFake->lastCall();
        if ($last === null) {
            $this->fail('No ChatResponder / ChatCompletionService call was recorded.');
        }
        return $last['messages'];
    }

    /**
     * Convenience: the LLM messages array recorded from the most
     * recent stream() call on the ChatResponder fake. Mirrors
     * {@see recordedLlmMessages()} for the SSE path.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function recordedStreamLlmMessages(): array
    {
        $last = $this->responderFake->lastStreamCall();
        if ($last === null) {
            $this->fail('No ChatResponder::stream() call was recorded.');
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
