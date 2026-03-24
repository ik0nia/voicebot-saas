<?php

namespace Tests\Unit\Services;

use App\Services\ProductSearchService;
use ReflectionClass;
use Tests\TestCase;

class ProductSearchServiceTest extends TestCase
{
    private ProductSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductSearchService();
    }

    /**
     * Helper to call private methods via reflection.
     */
    private function callPrivateMethod(string $method, array $args = []): mixed
    {
        $reflection = new ReflectionClass(ProductSearchService::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->service, $args);
    }

    public function test_extract_search_words_removes_stopwords(): void
    {
        $words = $this->callPrivateMethod('extractSearchWords', ['vreau pentru casa un produs']);

        // "vreau", "pentru", "un" are stopwords and should be removed
        $this->assertNotContains('vreau', $words);
        $this->assertNotContains('pentru', $words);
        $this->assertNotContains('un', $words);
    }

    public function test_extract_search_words_keeps_meaningful_words(): void
    {
        $words = $this->callPrivateMethod('extractSearchWords', ['laptop gaming performant']);

        $this->assertContains('laptop', $words);
        $this->assertContains('gaming', $words);
        $this->assertContains('performant', $words);
    }

    public function test_extract_search_words_keeps_short_alphanumeric(): void
    {
        // 2-char words that are alphanumeric should be kept (e.g. product codes)
        $words = $this->callPrivateMethod('extractSearchWords', ['produs a5 beton']);

        $this->assertContains('a5', $words);
    }

    public function test_extract_search_words_removes_color_stopwords(): void
    {
        $words = $this->callPrivateMethod('extractSearchWords', ['tricou rosu negru']);

        $this->assertNotContains('rosu', $words);
        $this->assertNotContains('negru', $words);
        $this->assertContains('tricou', $words);
    }

    public function test_extract_search_words_removes_size_stopwords(): void
    {
        $words = $this->callPrivateMethod('extractSearchWords', ['cutie mare produs']);

        $this->assertNotContains('mare', $words);
        $this->assertNotContains('cutie', $words); // "cutie" is also a stopword
        $this->assertContains('produs', $words);
    }

    public function test_extract_search_words_empty_query_returns_empty(): void
    {
        $words = $this->callPrivateMethod('extractSearchWords', ['de la pe in']);

        $this->assertEmpty($words);
    }

    public function test_build_name_pattern_plain_word(): void
    {
        $pattern = $this->callPrivateMethod('buildNamePattern', ['laptop']);

        $this->assertEquals('%laptop%', $pattern);
    }

    public function test_build_name_pattern_handles_product_code_letters_then_digits(): void
    {
        // "abc123" should become "%abc%123%"
        $pattern = $this->callPrivateMethod('buildNamePattern', ['abc123']);

        $this->assertEquals('%abc%123%', $pattern);
    }

    public function test_build_name_pattern_handles_product_code_digits_then_letters(): void
    {
        // "123abc" should become "%123%abc%"
        $pattern = $this->callPrivateMethod('buildNamePattern', ['123abc']);

        $this->assertEquals('%123%abc%', $pattern);
    }

    public function test_build_name_pattern_mixed_format_no_match(): void
    {
        // "a1b2" doesn't match either pattern, should return plain
        $pattern = $this->callPrivateMethod('buildNamePattern', ['a1b2']);

        $this->assertEquals('%a1b2%', $pattern);
    }

    public function test_to_card_array_returns_expected_keys(): void
    {
        $product = (object) [
            'wc_product_id' => 42,
            'name' => 'Test Product',
            'price' => '99.99',
            'regular_price' => '129.99',
            'sale_price' => '99.99',
            'currency' => 'RON',
            'image_url' => 'https://example.com/img.jpg',
            'short_description' => 'A test product',
            'permalink' => 'https://example.com/product/test',
            'stock_status' => 'instock',
            'site_url' => 'https://example.com',
        ];

        $card = $this->service->toCardArray($product);

        $this->assertEquals(42, $card['id']);
        $this->assertEquals('Test Product', $card['name']);
        $this->assertEquals('99.99', $card['price']);
        $this->assertEquals('RON', $card['currency']);
        $this->assertEquals('https://example.com/?add-to-cart=42', $card['add_to_cart_url']);
    }

    public function test_to_card_array_handles_empty_site_url(): void
    {
        $product = (object) [
            'wc_product_id' => 1,
            'name' => 'Product',
            'price' => '10',
            'regular_price' => '10',
            'sale_price' => '',
            'currency' => 'RON',
            'image_url' => '',
            'short_description' => '',
            'permalink' => '',
            'stock_status' => 'instock',
            'site_url' => '',
        ];

        $card = $this->service->toCardArray($product);

        $this->assertEquals('', $card['add_to_cart_url']);
    }

    public function test_search_returns_empty_for_very_short_query(): void
    {
        // Query shorter than 2 chars should return empty
        $results = $this->service->search(1, 'a');

        $this->assertEmpty($results);
    }
}
