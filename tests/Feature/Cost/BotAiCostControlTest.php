<?php

namespace Tests\Feature\Cost;

use App\Exceptions\FullProfileDailyCapException;
use App\Models\AiApiMetric;
use App\Models\Bot;
use App\Models\ModelPricing;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BotAiGenerationService;
use App\Services\Cost\BnrExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Iteration B — cost-control guardrails around
 * `BotAiGenerationService`:
 *   1. per-bot daily cap on `full_profile`
 *   2. Redis response cache for deterministic targets
 *
 * Both guard the "✨ Completează tot cu AI" button from runaway spend
 * without breaking the existing generation flow.
 */
class BotAiCostControlTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Bot $bot;
    private int $claudeCallCount = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->bot = Bot::factory()->create([
            'tenant_id' => $this->tenant->id,
            'niche_slug' => 'stomatologie',
        ]);

        ModelPricing::updateOrCreate(
            ['model_id' => BotAiGenerationService::MODEL],
            [
                'provider' => 'anthropic',
                'input_cost' => 1.00,
                'output_cost' => 5.00,
                'max_context_tokens' => 200000,
                'is_active' => true,
            ]
        );

        $this->app->singleton(BnrExchangeRate::class, function () {
            $m = Mockery::mock(BnrExchangeRate::class);
            $m->shouldReceive('usdToRon')->andReturn(4.50);
            $m->shouldReceive('convert')->andReturnUsing(fn ($usd) => round($usd * 4.50, 4));
            return $m;
        });

        $this->claudeCallCount = 0;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Stub the Anthropic client and count invocations. */
    private function mockClaude(string $text, int $inputTokens = 100, int $outputTokens = 50): void
    {
        $callCounter = function () { $this->claudeCallCount++; };

        $this->app->singleton(\Anthropic\Client::class, function () use ($text, $inputTokens, $outputTokens, $callCounter) {
            $usage = new class($inputTokens, $outputTokens) {
                public int $inputTokens;
                public int $outputTokens;
                public ?int $cacheReadInputTokens = 0;
                public ?int $cacheCreationInputTokens = 0;
                public function __construct(int $in, int $out)
                {
                    $this->inputTokens = $in;
                    $this->outputTokens = $out;
                }
                public function toArray(): array
                {
                    return [
                        'input_tokens' => $this->inputTokens,
                        'output_tokens' => $this->outputTokens,
                        'cache_read_input_tokens' => 0,
                        'cache_creation_input_tokens' => 0,
                    ];
                }
            };
            $textBlock = new class($text) {
                public string $text;
                public function __construct(string $t) { $this->text = $t; }
            };
            $message = new class($textBlock, $usage) {
                public array $content;
                public object $usage;
                public function __construct($tb, $u) { $this->content = [$tb]; $this->usage = $u; }
            };
            $messagesService = new class($message, $callCounter) {
                public object $msg;
                public $cb;
                public function __construct($m, $cb) { $this->msg = $m; $this->cb = $cb; }
                public function create(...$args) { ($this->cb)(); return $this->msg; }
            };
            return new class($messagesService) {
                public object $messages;
                public function __construct($svc) { $this->messages = $svc; }
            };
        });
    }

    private function seedFullProfileMetrics(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            AiApiMetric::create([
                'provider' => 'anthropic',
                'model' => BotAiGenerationService::MODEL,
                'input_tokens' => 500,
                'output_tokens' => 300,
                'cost_cents' => 1.0,
                'response_time_ms' => 500,
                'status' => 'success',
                'bot_id' => $this->bot->id,
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'purpose' => BotAiGenerationService::PURPOSE,
                'metadata' => [
                    'target' => 'full_profile',
                    'count' => 1,
                    'niche_slug' => 'stomatologie',
                ],
            ]);
        }
    }

    public function test_full_profile_daily_cap_blocks_generation_over_limit(): void
    {
        config(['bot_ai.max_full_profile_per_bot_per_day' => 3]);
        $this->seedFullProfileMetrics(3);
        $this->mockClaude(json_encode(['business_info' => 'x', 'faqs' => [], 'rules' => [], 'tone' => []]));

        $service = app(BotAiGenerationService::class);
        $this->expectException(FullProfileDailyCapException::class);
        $service->generate(
            bot: $this->bot,
            target: 'full_profile',
            hint: null,
            userId: $this->user->id,
        );

        // No Claude call should have happened.
        $this->assertSame(0, $this->claudeCallCount);
    }

    public function test_full_profile_cap_allows_under_limit(): void
    {
        config(['bot_ai.max_full_profile_per_bot_per_day' => 3]);
        $this->seedFullProfileMetrics(2);
        $this->mockClaude(json_encode(['business_info' => 'x', 'faqs' => [], 'rules' => [], 'tone' => []]));

        $service = app(BotAiGenerationService::class);
        $result = $service->generate(
            bot: $this->bot,
            target: 'full_profile',
            hint: null,
            userId: $this->user->id,
        );

        $this->assertArrayHasKey('generated', $result);
        $this->assertSame(1, $this->claudeCallCount);
    }

    public function test_full_profile_cap_is_per_bot_not_per_tenant(): void
    {
        config(['bot_ai.max_full_profile_per_bot_per_day' => 2]);
        $this->seedFullProfileMetrics(2); // On $this->bot.

        // Create a second bot in the same tenant — its cap should be fresh.
        $otherBot = Bot::factory()->create([
            'tenant_id' => $this->tenant->id,
            'niche_slug' => 'stomatologie',
        ]);
        $this->mockClaude(json_encode(['business_info' => 'x', 'faqs' => [], 'rules' => [], 'tone' => []]));

        $service = app(BotAiGenerationService::class);
        $result = $service->generate(
            bot: $otherBot,
            target: 'full_profile',
            hint: null,
            userId: $this->user->id,
        );

        $this->assertArrayHasKey('generated', $result);
    }

    public function test_redis_cache_hits_skip_the_real_llm_call(): void
    {
        Cache::flush();
        config(['bot_ai.response_cache_ttl_seconds' => 1800]);
        $json = json_encode(['rules' => ['Nu da diagnostic.', 'Redirecționează urgențele.']]);
        $this->mockClaude($json);

        $service = app(BotAiGenerationService::class);

        // First call — real LLM call.
        $first = $service->generate(
            bot: $this->bot,
            target: 'rules_suggest',
            hint: 'fii prudent',
            userId: $this->user->id,
        );
        $this->assertSame(1, $this->claudeCallCount);
        $this->assertArrayHasKey('generated', $first);

        // Second identical call — should come from cache, no LLM hit.
        $second = $service->generate(
            bot: $this->bot,
            target: 'rules_suggest',
            hint: 'fii prudent',
            userId: $this->user->id,
        );
        $this->assertSame(1, $this->claudeCallCount, 'Second call should hit Redis cache, not the LLM');
        $this->assertTrue($second['from_cache'] ?? false);
        $this->assertSame(0, $second['tokens_in']);
        $this->assertSame(0, $second['tokens_out']);
        $this->assertSame(0.0, $second['cost_ron']);
    }

    public function test_cache_hit_still_records_metric_with_from_cache_flag(): void
    {
        Cache::flush();
        config(['bot_ai.response_cache_ttl_seconds' => 1800]);
        $this->mockClaude(json_encode(['faqs' => [['question' => 'Q?', 'answer' => 'A.']]]));

        $service = app(BotAiGenerationService::class);

        // Prime the cache.
        $service->generate(bot: $this->bot, target: 'faq_bulk', hint: null, count: 1, userId: $this->user->id);

        // Trigger cache hit.
        $service->generate(bot: $this->bot, target: 'faq_bulk', hint: null, count: 1, userId: $this->user->id);

        $cacheRow = AiApiMetric::where('bot_id', $this->bot->id)
            ->where('purpose', BotAiGenerationService::PURPOSE)
            ->get()
            ->first(fn ($m) => ($m->metadata['from_cache'] ?? false) === true);

        $this->assertNotNull($cacheRow, 'Expected a from_cache=true metric row');
        $this->assertSame(0.0, (float) $cacheRow->cost_cents);
    }

    public function test_full_profile_is_not_cached(): void
    {
        Cache::flush();
        $this->mockClaude(json_encode(['business_info' => 'x', 'faqs' => [], 'rules' => [], 'tone' => []]));
        $service = app(BotAiGenerationService::class);

        $service->generate(bot: $this->bot, target: 'full_profile', hint: null, userId: $this->user->id);
        $service->generate(bot: $this->bot, target: 'full_profile', hint: null, userId: $this->user->id);

        // Both calls hit the LLM — full_profile is in the not-cached list.
        $this->assertSame(2, $this->claudeCallCount);
    }
}
