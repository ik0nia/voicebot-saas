<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Services\StructuredPromptBuilder;
use Tests\TestCase;

/**
 * Iteration C — prompt hygiene guarantees.
 *
 * 1. Anti-hallucination rule appears at most once in the structured
 *    prompt (the niche's REGULI DURE line). The copy in
 *    standard_rules / dont_rules was removed in Iteration C;
 *    PromptGuardrails::antiHallucination() adds a third occurrence
 *    at runtime but that's appended AFTER StructuredPromptBuilder
 *    output by the fluent composer, so it's not counted here.
 *
 * 2. effectiveTone() resolves tone via bot → niche → fallback.
 */
class StructuredPromptBuilderDedupTest extends TestCase
{
    private function makeBot(array $overrides = [], ?array $settings = null): Bot
    {
        $bot = new Bot();
        $bot->id = 1;
        $bot->name = 'Test Bot';
        $bot->slug = 'test-bot';
        $bot->niche_slug = $overrides['niche_slug'] ?? null;
        $bot->system_prompt = $overrides['system_prompt'] ?? null;
        $bot->settings = $settings ?? [];
        return $bot;
    }

    public function test_nu_inventa_appears_at_most_once_for_magazin_online(): void
    {
        // Simulate a freshly-seeded bot per Iteration D: niche defaults
        // copied into structured settings, flag on, no tenant edits yet.
        $niche = config('niches.magazin-online');
        $this->assertIsArray($niche, 'magazin-online niche must exist');

        $bot = $this->makeBot(['niche_slug' => 'magazin-online'], [
            'use_structured_prompt' => true,
            'faqs' => array_values(array_slice($niche['suggested_faqs'] ?? [], 0, 8)),
            'dont_rules' => array_values($niche['standard_rules'] ?? []),
            'tone_guide' => $niche['default_tone'] ?? [],
        ]);

        $out = app(StructuredPromptBuilder::class)->build($bot);

        // "Nu inventa" should appear exactly once — only in the niche
        // prompt_addon REGULI DURE section, not in the tenant-editable
        // standard_rules block (removed in Iteration C).
        $count = substr_count($out, 'Nu inventa');
        $this->assertSame(
            1,
            $count,
            "Expected exactly one 'Nu inventa' occurrence in composed prompt, got {$count}."
        );
    }

    public function test_nu_inventa_removed_from_restaurant_standard_rules(): void
    {
        $rules = config('niches.restaurant.standard_rules', []);
        $this->assertIsArray($rules);

        foreach ($rules as $rule) {
            $this->assertStringNotContainsStringIgnoringCase(
                'nu inventa',
                (string) $rule,
                'restaurant standard_rules must not contain a generic "Nu inventa" rule after Iteration C.'
            );
        }
    }

    public function test_nu_inventa_removed_from_magazin_online_standard_rules(): void
    {
        $rules = config('niches.magazin-online.standard_rules', []);
        $this->assertIsArray($rules);

        foreach ($rules as $rule) {
            $this->assertStringNotContainsStringIgnoringCase(
                'nu inventa',
                (string) $rule,
                'magazin-online standard_rules must not contain a generic "Nu inventa" rule after Iteration C.'
            );
        }
    }

    public function test_effective_tone_returns_niche_default_when_bot_has_none(): void
    {
        config()->set('niches.my-test-niche.default_tone', [
            'length' => 'long',
            'register' => 'dvs',
            'emoji_ok' => false,
            'languages' => ['ro', 'de'],
        ]);

        $bot = $this->makeBot(['niche_slug' => 'my-test-niche'], [
            'use_structured_prompt' => true,
            // no tone_guide set
        ]);

        $tone = app(StructuredPromptBuilder::class)->effectiveTone($bot);
        $this->assertSame('long', $tone['length']);
        $this->assertSame('dvs', $tone['register']);
        $this->assertFalse($tone['emoji_ok']);
        $this->assertSame(['ro', 'de'], $tone['languages']);
    }

    public function test_effective_tone_returns_bot_tone_when_bot_has_one(): void
    {
        config()->set('niches.my-test-niche.default_tone', [
            'length' => 'long',
            'register' => 'dvs',
            'emoji_ok' => false,
            'languages' => ['ro'],
        ]);

        $bot = $this->makeBot(['niche_slug' => 'my-test-niche'], [
            'use_structured_prompt' => true,
            'tone_guide' => [
                'length' => 'short',
                'register' => 'tu',
                'emoji_ok' => true,
                'languages' => ['ro', 'en'],
            ],
        ]);

        $tone = app(StructuredPromptBuilder::class)->effectiveTone($bot);
        $this->assertSame('short', $tone['length']);
        $this->assertSame('tu', $tone['register']);
        $this->assertTrue($tone['emoji_ok']);
        $this->assertSame(['ro', 'en'], $tone['languages']);
    }

    public function test_effective_tone_falls_back_to_hardcoded_when_no_niche_and_no_bot_tone(): void
    {
        $bot = $this->makeBot([], [
            'use_structured_prompt' => true,
        ]);

        $tone = app(StructuredPromptBuilder::class)->effectiveTone($bot);
        // Hardcoded fallback — matches Iteration D defaults.
        $this->assertSame('medium', $tone['length']);
        $this->assertSame('tu', $tone['register']);
        $this->assertFalse($tone['emoji_ok']);
        $this->assertSame(['ro'], $tone['languages']);
    }
}
