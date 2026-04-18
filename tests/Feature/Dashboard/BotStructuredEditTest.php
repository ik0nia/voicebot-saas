<?php

namespace Tests\Feature\Dashboard;

use App\Models\AiApiMetric;
use App\Models\Bot;
use App\Models\ModelPricing;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BotAiGenerationService;
use App\Services\Cost\BnrExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Session-auth (dashboard) coverage for the structured bot editor
 * introduced in iter 21. The Sanctum-gated API counterpart is covered
 * by tests/Feature/BotAiGenerateTest.php; this suite only checks the
 * three proxy routes plus the extended update() payload.
 */
class BotStructuredEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Bot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['tenant_admin', 'tenant_manager', 'tenant_viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->assignRole('tenant_admin');

        $this->bot = Bot::factory()->create([
            'tenant_id' => $this->tenant->id,
            'niche_slug' => 'stomatologie',
        ]);

        ModelPricing::updateOrCreate(
            ['model_id' => BotAiGenerationService::MODEL],
            [
                'provider' => 'anthropic',
                'input_cost_per_million' => 1.00,
                'output_cost_per_million' => 5.00,
                'max_context_tokens' => 200000,
                'is_active' => true,
            ]
        );

        RateLimiter::clear('bot-ai-generate:tenant:' . $this->tenant->id);
        RateLimiter::clear('bot-ai-generate:full_profile:tenant:' . $this->tenant->id);

        $this->app->singleton(BnrExchangeRate::class, function () {
            $m = Mockery::mock(BnrExchangeRate::class);
            $m->shouldReceive('usdToRon')->andReturn(4.50);
            $m->shouldReceive('convert')->andReturnUsing(fn ($usd) => round($usd * 4.50, 4));
            return $m;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Stub the Anthropic client so we don't hit the network. */
    private function mockClaude(string $text, int $inputTokens = 100, int $outputTokens = 50): void
    {
        $this->app->singleton(\Anthropic\Client::class, function () use ($text, $inputTokens, $outputTokens) {
            $usage = new class($inputTokens, $outputTokens) {
                public int $inputTokens;
                public int $outputTokens;
                public ?int $cacheReadInputTokens = 0;
                public ?int $cacheCreationInputTokens = 0;
                public function __construct(int $in, int $out) { $this->inputTokens = $in; $this->outputTokens = $out; }
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
            $block = new class($text) { public string $text; public function __construct(string $t) { $this->text = $t; } };
            $message = new class($block, $usage) {
                public array $content; public object $usage;
                public function __construct($b, $u) { $this->content = [$b]; $this->usage = $u; }
            };
            $svc = new class($message) {
                public object $msg;
                public function __construct($m) { $this->msg = $m; }
                public function create(...$args) { return $this->msg; }
            };
            return new class($svc) {
                public object $messages;
                public function __construct($s) { $this->messages = $s; }
            };
        });
    }

    public function test_edit_page_renders_with_niche_data(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/dashboard/agenti/{$this->bot->id}/editare");

        $response->assertStatus(200);
        // Niche display name appears in the header badge. The badge
        // pulls from config/niches.php, so assert on that source of
        // truth rather than a capitalised slug (stomatologie →
        // "Cabinet stomatologic", not "Stomatologie").
        $response->assertSee('Cabinet stomatologic', false);
        // Tab nav is present.
        $response->assertSee('Informații business', false);
        $response->assertSee('Reguli stricte', false);
        $response->assertSee('Ton &amp; stil', false);
    }

    public function test_update_persists_structured_settings(): void
    {
        $schedule = [
            ['key' => 'mon', 'label' => 'Luni', 'closed' => false, 'open' => '09:00', 'close' => '18:00', 'break_start' => null, 'break_end' => null],
            ['key' => 'sun', 'label' => 'Duminică', 'closed' => true, 'open' => '10:00', 'close' => '14:00', 'break_start' => null, 'break_end' => null],
        ];

        $payload = [
            'name' => $this->bot->name,
            'voice' => 'coral',
            'language' => 'ro',
            'settings' => [
                'use_structured_prompt' => '1',
                'business_info' => [
                    'address' => 'Str. Victoriei 10, București',
                    'phone' => '+40721234567',
                    'email' => 'contact@exemplu.ro',
                    'website' => 'https://exemplu.ro',
                    'extras' => 'Cabinet cu 10 ani experiență.',
                    'hours_schedule' => json_encode($schedule),
                ],
                'faqs' => [
                    ['question' => 'Care e programul?', 'answer' => 'L-V 9-18.'],
                    ['question' => 'Acceptați card?', 'answer' => 'Da, toate tipurile.'],
                ],
                'dont_rules' => [
                    'Nu da diagnostic medical.',
                    'Nu promite prețuri fără confirmare.',
                ],
                'tone_guide' => [
                    'length' => 'short',
                    'register' => 'dvs',
                    'emoji_ok' => '0',
                    'languages' => ['ro', 'en'],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put("/dashboard/agenti/{$this->bot->id}", $payload);

        $response->assertRedirect();

        $this->bot->refresh();
        $settings = $this->bot->settings;

        $this->assertTrue($settings['use_structured_prompt'] ?? false);
        $this->assertSame('Str. Victoriei 10, București', $settings['business_info']['address']);
        $this->assertSame('+40721234567', $settings['business_info']['phone']);
        $this->assertIsArray($settings['business_info']['hours_schedule']);
        $this->assertCount(2, $settings['business_info']['hours_schedule']);
        $this->assertSame('mon', $settings['business_info']['hours_schedule'][0]['key']);
        $this->assertCount(2, $settings['faqs']);
        $this->assertSame('Care e programul?', $settings['faqs'][0]['question']);
        $this->assertCount(2, $settings['dont_rules']);
        $this->assertSame('short', $settings['tone_guide']['length']);
        $this->assertSame('dvs', $settings['tone_guide']['register']);
        $this->assertFalse($settings['tone_guide']['emoji_ok']);
        $this->assertSame(['ro', 'en'], $settings['tone_guide']['languages']);
    }

    public function test_update_merges_without_wiping_unrelated_settings(): void
    {
        // Seed an unrelated key that the form never sends.
        $this->bot->forceFill(['settings' => [
            'vad_threshold' => 0.42,
            'automations' => ['sms_on_missed_call' => true],
        ]])->save();

        $this->actingAs($this->user)
            ->put("/dashboard/agenti/{$this->bot->id}", [
                'name' => $this->bot->name,
                'voice' => 'coral',
                'language' => 'ro',
                'settings' => [
                    'business_info' => ['address' => 'nou'],
                ],
            ])->assertRedirect();

        $this->bot->refresh();
        $this->assertSame(0.42, (float) $this->bot->settings['vad_threshold']);
        $this->assertTrue($this->bot->settings['automations']['sms_on_missed_call']);
        $this->assertSame('nou', $this->bot->settings['business_info']['address']);
    }

    public function test_update_drops_blank_faq_rows(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/dashboard/agenti/{$this->bot->id}", [
                'name' => $this->bot->name,
                'voice' => 'coral',
                'language' => 'ro',
                'settings' => [
                    'faqs' => [
                        ['question' => 'A?', 'answer' => 'B.'],
                        ['question' => '', 'answer' => ''],
                        ['question' => 'C?', 'answer' => 'D.'],
                    ],
                ],
            ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->bot->refresh();
        $this->assertCount(2, $this->bot->settings['faqs']);
        $this->assertSame('A?', $this->bot->settings['faqs'][0]['question']);
        $this->assertSame('C?', $this->bot->settings['faqs'][1]['question']);
    }

    public function test_validation_rejects_more_than_50_faqs(): void
    {
        $faqs = [];
        for ($i = 0; $i < 51; $i++) {
            $faqs[] = ['question' => "Q{$i}?", 'answer' => "A{$i}."];
        }

        $response = $this->actingAs($this->user)
            ->put("/dashboard/agenti/{$this->bot->id}", [
                'name' => $this->bot->name,
                'voice' => 'coral',
                'language' => 'ro',
                'settings' => ['faqs' => $faqs],
            ]);

        $response->assertSessionHasErrors('settings.faqs');
    }

    public function test_ai_generate_route_proxies_service_and_logs_cost(): void
    {
        $this->mockClaude('Aveți program și sâmbăta?', inputTokens: 120, outputTokens: 40);

        $response = $this->actingAs($this->user)
            ->postJson("/dashboard/agenti/{$this->bot->id}/ai-generate", [
                'target' => 'faq_question',
                'hint' => 'program',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['generated', 'cost_ron', 'tokens' => ['in', 'out']]);

        $this->assertDatabaseHas('ai_api_metrics', [
            'bot_id' => $this->bot->id,
            'tenant_id' => $this->tenant->id,
            'purpose' => BotAiGenerationService::PURPOSE,
        ]);
    }

    public function test_ai_generate_rejects_invalid_target(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/dashboard/agenti/{$this->bot->id}/ai-generate", [
                'target' => 'write_malware',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target']);
    }

    public function test_ai_generate_cross_tenant_blocked(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherBot = Bot::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/dashboard/agenti/{$otherBot->id}/ai-generate", [
                'target' => 'faq_question',
            ]);

        // Global scope filters the bot out → 404. Either 403 or 404
        // means access was denied.
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_prompt_preview_returns_structured_prompt(): void
    {
        $this->bot->forceFill(['settings' => [
            'use_structured_prompt' => true,
            'business_info' => ['address' => 'Str. X'],
            'faqs' => [['question' => 'A?', 'answer' => 'B.']],
            'dont_rules' => ['Nu inventa'],
            'tone_guide' => ['length' => 'short'],
        ]])->save();

        $response = $this->actingAs($this->user)
            ->getJson("/dashboard/agenti/{$this->bot->id}/prompt-preview");

        $response->assertStatus(200)
            ->assertJsonStructure(['prompt', 'sections', 'flag_on'])
            ->assertJsonPath('flag_on', true);

        $sections = $response->json('sections');
        $this->assertIsArray($sections);
        $names = array_column($sections, 'name');
        $this->assertContains('faqs', $names);
        $this->assertContains('business_info', $names);
    }

    public function test_ai_cost_today_returns_count_and_cost(): void
    {
        // Seed two metric rows — one for this bot today, one for
        // another bot (should be excluded).
        AiApiMetric::create([
            'provider' => 'anthropic',
            'model' => BotAiGenerationService::MODEL,
            'input_tokens' => 100, 'output_tokens' => 50,
            'cost_cents' => 0.75, 'response_time_ms' => 100,
            'status' => 'success',
            'bot_id' => $this->bot->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'purpose' => BotAiGenerationService::PURPOSE,
            'metadata' => ['target' => 'faq_question'],
        ]);
        AiApiMetric::create([
            'provider' => 'anthropic',
            'model' => BotAiGenerationService::MODEL,
            'input_tokens' => 50, 'output_tokens' => 20,
            'cost_cents' => 0.25, 'response_time_ms' => 80,
            'status' => 'success',
            'bot_id' => $this->bot->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'purpose' => BotAiGenerationService::PURPOSE,
            'metadata' => ['target' => 'rules_suggest'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/dashboard/agenti/{$this->bot->id}/ai-cost-today");

        $response->assertStatus(200)
            ->assertJsonStructure(['count', 'cost_ron'])
            ->assertJsonPath('count', 2);

        // Cost = (0.75 + 0.25) cents / 100 = $0.01 × 4.5 = 0.045 RON.
        $this->assertEqualsWithDelta(0.045, (float) $response->json('cost_ron'), 0.0001);
    }

    public function test_cross_tenant_proxy_routes_blocked(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherBot = Bot::factory()->create(['tenant_id' => $otherTenant->id]);

        foreach ([
            'GET ' . "/dashboard/agenti/{$otherBot->id}/prompt-preview",
            'GET ' . "/dashboard/agenti/{$otherBot->id}/ai-cost-today",
        ] as $endpoint) {
            [$method, $url] = explode(' ', $endpoint, 2);
            $response = $this->actingAs($this->user)->json($method, $url);
            $this->assertContains($response->status(), [403, 404], "Expected block on {$endpoint}, got {$response->status()}");
        }
    }

    public function test_unauthenticated_proxy_routes_redirect(): void
    {
        // Session auth → web guard → unauthenticated redirects to /login.
        $this->get("/dashboard/agenti/{$this->bot->id}/ai-cost-today")->assertStatus(302);
        $this->post("/dashboard/agenti/{$this->bot->id}/ai-generate", ['target' => 'faq_question'])->assertStatus(302);
    }
}
