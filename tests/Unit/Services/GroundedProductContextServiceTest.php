<?php

namespace Tests\Unit\Services;

use App\Services\GroundedProductContextService;
use Tests\TestCase;

class GroundedProductContextServiceTest extends TestCase
{
    private GroundedProductContextService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GroundedProductContextService();
        // Ensure default config for tests
        config([
            'product_search.grounded_context.enabled' => true,
            'product_search.grounded_context.max_products' => 5,
            'product_search.grounded_context.max_name_length' => 120,
        ]);
    }

    /**
     * Helper to build a product card shape identical to ProductSearchService::toCardArray.
     */
    private function card(string $name, float|string|null $price = null, string $stock = 'instock', array $extra = []): array
    {
        return array_merge([
            'id' => rand(1, 9999),
            'name' => $name,
            'price' => $price,
            'regular_price' => $extra['regular_price'] ?? $price,
            'sale_price' => $extra['sale_price'] ?? null,
            'currency' => $extra['currency'] ?? 'RON',
            'image_url' => '',
            'short_description' => '',
            'permalink' => '',
            'stock_status' => $stock,
            'add_to_cart_url' => '',
        ], $extra);
    }

    // ─────────────────────────────────────────────────────────────
    // Enable / disable behavior
    // ─────────────────────────────────────────────────────────────

    public function test_returns_empty_when_disabled_globally(): void
    {
        config(['product_search.grounded_context.enabled' => false]);
        $out = $this->service->build([$this->card('Polistiren Eps 80', 150)]);
        $this->assertSame('', $out);
    }

    public function test_per_bot_setting_overrides_global(): void
    {
        config(['product_search.grounded_context.enabled' => true]);
        // Bot disables grounded context
        $out = $this->service->build([$this->card('Polistiren Eps 80', 150)], ['grounded_context' => false]);
        $this->assertSame('', $out);

        // Bot enables grounded context while global is off
        config(['product_search.grounded_context.enabled' => false]);
        $out = $this->service->build([$this->card('Polistiren Eps 80', 150)], ['grounded_context' => true]);
        $this->assertNotSame('', $out);
    }

    public function test_returns_empty_for_empty_product_list(): void
    {
        $this->assertSame('', $this->service->build([]));
    }

    public function test_skips_malformed_entries(): void
    {
        $out = $this->service->build([
            ['name' => ''],            // no name → skipped
            'not an array',            // wrong shape → skipped
            $this->card('Real Product', 100),
        ]);
        $this->assertStringContainsString('Real Product', $out);
        // Exactly one valid product → header shows "1 produse reale"
        $this->assertStringContainsString('1 produse reale', $out);
    }

    public function test_returns_empty_when_all_entries_malformed(): void
    {
        $out = $this->service->build([
            ['name' => ''],
            'garbage',
            ['no_name' => 'x'],
        ]);
        $this->assertSame('', $out);
    }

    // ─────────────────────────────────────────────────────────────
    // Format correctness
    // ─────────────────────────────────────────────────────────────

    public function test_includes_carduri_produse_marker_for_backward_compat(): void
    {
        // Existing prompt rules grep for "[CARDURI PRODUSE" to decide what to say.
        // Marker must remain present when grounded output is used.
        $out = $this->service->build([$this->card('Test Product', 50)]);
        $this->assertStringContainsString('[CARDURI PRODUSE', $out);
    }

    public function test_includes_all_product_names_in_output(): void
    {
        $products = [
            $this->card('Polistiren Eps 80 10 Cm Austrotherm', 150.00),
            $this->card('Polistiren Eps 80 8 Cm Austrotherm', 120.00),
            $this->card('Polistiren Eps 80 5 Cm Austrotherm', 80.00),
        ];
        $out = $this->service->build($products);

        $this->assertStringContainsString('Polistiren Eps 80 10 Cm Austrotherm', $out);
        $this->assertStringContainsString('Polistiren Eps 80 8 Cm Austrotherm', $out);
        $this->assertStringContainsString('Polistiren Eps 80 5 Cm Austrotherm', $out);
    }

    public function test_includes_prices_in_output(): void
    {
        $out = $this->service->build([
            $this->card('Product A', 150.00),
            $this->card('Product B', 120.50),
        ]);
        $this->assertStringContainsString('150 RON', $out);
        $this->assertStringContainsString('120.50 RON', $out);
    }

    public function test_integer_prices_rendered_without_decimals(): void
    {
        $out = $this->service->build([$this->card('X', 100)]);
        $this->assertStringContainsString('100 RON', $out);
        $this->assertStringNotContainsString('100.00', $out);
    }

    public function test_shows_sale_price_annotation(): void
    {
        $out = $this->service->build([
            $this->card('Sale Product', null, 'instock', [
                'price' => 75,
                'regular_price' => 100,
                'sale_price' => 75,
            ]),
        ]);
        $this->assertStringContainsString('75 RON', $out);
        $this->assertStringContainsString('redus de la 100', $out);
    }

    public function test_missing_price_shows_fallback(): void
    {
        $out = $this->service->build([
            $this->card('No Price Product', null),
        ]);
        $this->assertStringContainsString('No Price Product', $out);
        $this->assertStringContainsString('preț la cerere', $out);
    }

    public function test_respects_max_products_cap(): void
    {
        config(['product_search.grounded_context.max_products' => 2]);
        $out = $this->service->build([
            $this->card('P1', 10),
            $this->card('P2', 20),
            $this->card('P3', 30),
            $this->card('P4', 40),
        ]);
        $this->assertStringContainsString('P1', $out);
        $this->assertStringContainsString('P2', $out);
        $this->assertStringNotContainsString('P3', $out);
        $this->assertStringNotContainsString('P4', $out);
        $this->assertStringContainsString('2 produse reale', $out);
    }

    public function test_truncates_overlong_name(): void
    {
        config(['product_search.grounded_context.max_name_length' => 30]);
        $longName = str_repeat('A', 100);
        $out = $this->service->build([$this->card($longName, 10)]);
        // Truncated with ellipsis
        $this->assertStringContainsString('…', $out);
        // Original length is NOT present
        $this->assertStringNotContainsString(str_repeat('A', 100), $out);
    }

    // ─────────────────────────────────────────────────────────────
    // Stock handling
    // ─────────────────────────────────────────────────────────────

    public function test_all_in_stock_header_and_rules(): void
    {
        $out = $this->service->build([
            $this->card('A', 10, 'instock'),
            $this->card('B', 20, 'instock'),
        ]);
        $this->assertStringContainsString('în stoc', $out);
        $this->assertStringNotContainsString('EPUIZAT', $out);
        $this->assertStringNotContainsString('pe comandă', $out);
    }

    public function test_all_out_of_stock_header_warns_explicitly(): void
    {
        $out = $this->service->build([
            $this->card('A', 10, 'outofstock'),
            $this->card('B', 20, 'outofstock'),
        ]);
        $this->assertStringContainsString('TOATE sunt EPUIZATE', $out);
        $this->assertStringContainsString('EPUIZAT', $out);
        // Rules should instruct honest out-of-stock messaging
        $this->assertStringContainsString('alternative', $out);
    }

    public function test_mixed_stock_header_shows_counts(): void
    {
        $out = $this->service->build([
            $this->card('A', 10, 'instock'),
            $this->card('B', 20, 'outofstock'),
            $this->card('C', 30, 'instock'),
        ]);
        $this->assertStringContainsString('2 în stoc, 1 epuizate', $out);
        // Per-product labels
        $this->assertStringContainsString('în stoc', $out);
        $this->assertStringContainsString('EPUIZAT', $out);
    }

    public function test_backorder_header_mentions_delivery_delay(): void
    {
        $out = $this->service->build([
            $this->card('A', 10, 'onbackorder'),
            $this->card('B', 20, 'onbackorder'),
        ]);
        $this->assertStringContainsString('pe comandă', $out);
    }

    // ─────────────────────────────────────────────────────────────
    // Grounding rules (LLM instructions)
    // ─────────────────────────────────────────────────────────────

    public function test_output_instructs_llm_on_mismatch(): void
    {
        // Key regression protection: the rule telling the LLM to honestly report
        // "I didn't find something matching" when retrieval returns unrelated
        // products must be present.
        $out = $this->service->build([$this->card('Întrerupător Dublu', 20)]);
        $this->assertMatchesRegularExpression('/GROUNDING CRITIC|nu corespund|n-?am\s+găsit|SPUNE EXPLICIT/iu', $out);
    }

    public function test_output_instructs_stop_after_intro(): void
    {
        $out = $this->service->build([$this->card('Test', 10)]);
        // Positive framing — the LLM is told to STOP after the intro rather
        // than being told "don't enumerate" (LLMs ignore negatives).
        $this->assertStringContainsString('STOP', $out);
    }

    public function test_output_includes_format_examples(): void
    {
        $out = $this->service->build([$this->card('Test', 10)]);
        // Good and bad examples must both be present so the model has a clear
        // target shape to match.
        $this->assertMatchesRegularExpression('/Exemple bune|Exemplu/iu', $out);
        $this->assertMatchesRegularExpression('/REFUZATE|nu scrie|nu rescrie/iu', $out);
    }

    public function test_output_enables_comparison_answers(): void
    {
        $out = $this->service->build([
            $this->card('A', 10),
            $this->card('B', 20),
        ]);
        $this->assertMatchesRegularExpression('/comparație|care e mai ieftin|preț/iu', $out);
        // Must include a concrete comparison example showing the model what
        // shape the grounded answer should take.
        $this->assertStringContainsString('Cel mai ieftin', $out);
    }

    // ─────────────────────────────────────────────────────────────
    // Safety — injection-like inputs
    // ─────────────────────────────────────────────────────────────

    public function test_special_characters_in_name_are_preserved_not_escaped_or_stripped(): void
    {
        $name = 'Vopsea "Premium" & Co (5L)';
        $out = $this->service->build([$this->card($name, 50)]);
        $this->assertStringContainsString($name, $out);
    }

    public function test_large_product_list_is_capped_and_does_not_explode(): void
    {
        $products = array_map(fn ($i) => $this->card("Product $i", $i), range(1, 50));
        $out = $this->service->build($products);
        // Default cap = 5
        $this->assertStringContainsString('5 produse reale', $out);
        $this->assertStringContainsString('Product 1', $out);
        $this->assertStringContainsString('Product 5', $out);
        $this->assertStringNotContainsString('Product 6', $out);
    }

    public function test_output_length_is_bounded_for_typical_input(): void
    {
        // 5 products with reasonable names should produce <2500 chars context —
        // roughly ~500 tokens, well under any real chat budget (~2800-3000
        // total system prompt). Soft guardrail against prompt bloat.
        $products = array_map(
            fn ($i) => $this->card("Polistiren Eps 80 {$i} Cm Austrotherm Premium", 100 + $i * 10),
            range(1, 5)
        );
        $out = $this->service->build($products);
        $this->assertLessThan(2500, mb_strlen($out));
    }

    // ─────────────────────────────────────────────────────────────
    // Empty context helper (no results)
    // ─────────────────────────────────────────────────────────────

    public function test_build_empty_context_has_zero_marker(): void
    {
        $out = $this->service->buildEmptyContext();
        $this->assertStringContainsString('[CARDURI PRODUSE: 0', $out);
        $this->assertStringContainsString('NU spune că ai găsit', $out);
    }
}
