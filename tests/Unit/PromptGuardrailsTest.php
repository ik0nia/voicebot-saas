<?php

namespace Tests\Unit;

use App\Services\PromptGuardrails;
use Tests\TestCase;

class PromptGuardrailsTest extends TestCase
{
    public function test_apply_adds_anti_hallucination(): void
    {
        $result = PromptGuardrails::apply('Base prompt.');

        $this->assertStringContainsString('REGULI OBLIGATORII', $result);
        // Service wording changed from "NU inventa prețuri" to the
        // more-complete "NU inventa date, prețuri, termene sau
        // specificații". Test follows the service rather than the
        // reverse — match on the common "NU inventa" prefix.
        $this->assertStringContainsString('NU inventa', $result);
        $this->assertStringStartsWith('Base prompt.', $result);
    }

    public function test_apply_voice_adds_voice_rules(): void
    {
        $result = PromptGuardrails::apply('Base prompt.', isVoice: true);

        $this->assertStringContainsString('REGULI OBLIGATORII', $result);
        $this->assertStringContainsString('REGULI VOCALE', $result);
        $this->assertStringContainsString('propoziții', $result);
    }

    public function test_apply_non_voice_skips_voice_rules(): void
    {
        $result = PromptGuardrails::apply('Base prompt.', isVoice: false);

        $this->assertStringContainsString('REGULI OBLIGATORII', $result);
        $this->assertStringNotContainsString('REGULI VOCALE', $result);
    }

    public function test_guardrails_are_at_end_of_prompt(): void
    {
        $base = 'System prompt with knowledge context and all the data.';
        $result = PromptGuardrails::apply($base);

        // Base comes first, guardrails at end
        $basePos = strpos($result, $base);
        $guardrailPos = strpos($result, 'REGULI OBLIGATORII');

        $this->assertEquals(0, $basePos);
        $this->assertGreaterThan(strlen($base), $guardrailPos);
    }

    public function test_guardrails_with_empty_prompt(): void
    {
        $result = PromptGuardrails::apply('');

        $this->assertStringContainsString('REGULI OBLIGATORII', $result);
    }
}
