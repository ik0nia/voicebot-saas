<?php

namespace Tests\Unit\Services;

use App\Services\ChatModelRouter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatModelRouterTest extends TestCase
{
    private ChatModelRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new ChatModelRouter();

        // Ensure config is loaded for routing tiers
        config([
            'routing.tiers.fast' => [
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'max_tokens' => 500,
                'temperature' => 0.6,
            ],
            'routing.tiers.smart' => [
                'provider' => 'anthropic',
                'model' => 'claude-sonnet-4-5-20241022',
                'max_tokens' => 800,
                'temperature' => 0.5,
            ],
            'routing.cost_budget_cents' => 15,
            'routing.short_message_threshold' => 8,
            'routing.long_conversation_threshold' => 10,
            'routing.long_conversation_word_min' => 15,
            'routing.word_count_threshold' => 30,
            'routing.circuit_breaker' => [
                'window_minutes' => 5,
                'min_requests' => 5,
                'fail_rate_threshold' => 0.8,
            ],
            'routing.patterns.ro' => [
                '/recomand|suger|sfatu|ce.*alegi|ce.*iei|ce.*trebui|ce.*potrivit/u',
                '/compar|diferent|versus|sau.*mai bun|care.*mai/u',
                '/proiect|renovez|construi|izol|termoizol|amenaj/u',
                '/cum.*fac|cum.*aplic|cum.*montez|cum.*instalez/u',
                '/\?.*\?/',
            ],
            'services.anthropic.api_key' => 'test-key',
        ]);
    }

    public function test_default_routing_returns_fast_tier(): void
    {
        $result = $this->router->route('salut');

        $this->assertEquals('openai', $result['provider']);
        $this->assertEquals('gpt-4o-mini', $result['model']);
    }

    public function test_complex_message_routes_to_smart_tier(): void
    {
        $result = $this->router->route('Ce imi recomanzi pentru proiectul meu de renovare?');

        $this->assertEquals('anthropic', $result['provider']);
        $this->assertEquals('claude-sonnet-4-5-20241022', $result['model']);
    }

    public function test_short_continuation_stays_on_fast(): void
    {
        // Short message (< 8 words) with history > 4 should stay on fast
        $result = $this->router->route('da, ok', historyCount: 6);

        $this->assertEquals('openai', $result['provider']);
        $this->assertEquals('gpt-4o-mini', $result['model']);
    }

    public function test_cost_budget_exceeded_forces_fast(): void
    {
        $result = $this->router->route(
            'Ce imi recomanzi pentru proiectul meu de renovare?',
            conversationCostCents: 20,
        );

        $this->assertEquals('openai', $result['provider']);
        $this->assertEquals('gpt-4o-mini', $result['model']);
    }

    public function test_voice_channel_bias_toward_fast(): void
    {
        // Simple message on voice channel should stay fast
        $result = $this->router->route('vreau un produs', isVoiceChannel: true);

        $this->assertEquals('openai', $result['provider']);
        $this->assertEquals('gpt-4o-mini', $result['model']);
    }

    public function test_voice_channel_allows_smart_for_complex_query(): void
    {
        $result = $this->router->route(
            'Ce imi recomanzi pentru proiectul meu de renovare a casei? Vreau sa compar materialele.',
            isVoiceChannel: true,
        );

        $this->assertEquals('anthropic', $result['provider']);
    }

    public function test_circuit_breaker_switches_provider(): void
    {
        // Simulate circuit open: many failures, few successes
        Cache::put('routing_cb_anthropic_fail', 10, now()->addMinutes(5));
        Cache::put('routing_cb_anthropic_ok', 1, now()->addMinutes(5));

        // Even a complex message should fall back to fast when circuit is open
        $result = $this->router->route('Ce imi recomanzi pentru proiectul meu de renovare?');

        $this->assertEquals('openai', $result['provider']);
        $this->assertEquals('gpt-4o-mini', $result['model']);
    }

    public function test_circuit_breaker_stays_closed_below_min_requests(): void
    {
        // Below min_requests (5), circuit should stay closed
        Cache::put('routing_cb_anthropic_fail', 3, now()->addMinutes(5));
        Cache::put('routing_cb_anthropic_ok', 0, now()->addMinutes(5));

        $result = $this->router->route('Ce imi recomanzi pentru proiectul meu de renovare?');

        $this->assertEquals('anthropic', $result['provider']);
    }

    public function test_long_conversation_with_moderate_message_routes_to_smart(): void
    {
        // historyCount > 10 and wordCount > 15 should route to smart
        $longMessage = 'Vreau sa stiu mai multe detalii despre acest produs care este listat pe site la un pret foarte bun si pare interesant';
        $result = $this->router->route($longMessage, historyCount: 12);

        $this->assertEquals('anthropic', $result['provider']);
    }

    public function test_record_success_increments_cache(): void
    {
        Cache::forget('routing_cb_openai_ok');

        $this->router->recordSuccess('openai');

        $this->assertGreaterThanOrEqual(1, (int) Cache::get('routing_cb_openai_ok', 0));
    }

    public function test_record_failure_increments_cache(): void
    {
        Cache::forget('routing_cb_openai_fail');

        $this->router->recordFailure('openai');

        $this->assertGreaterThanOrEqual(1, (int) Cache::get('routing_cb_openai_fail', 0));
    }
}
