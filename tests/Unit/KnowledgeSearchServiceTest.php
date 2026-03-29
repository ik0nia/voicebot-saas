<?php

namespace Tests\Unit;

use App\Services\KnowledgeSearchService;
use Tests\TestCase;

class KnowledgeSearchServiceTest extends TestCase
{
    private KnowledgeSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KnowledgeSearchService();
    }

    public function test_expand_query_with_synonyms(): void
    {
        $method = new \ReflectionMethod($this->service, 'expandQuery');
        $method->setAccessible(true);

        // 'pret' (exact dictionary key) triggers synonym expansion
        $result = $method->invoke($this->service, 'care este pret');
        $this->assertStringContainsString('cost', $result);
        $this->assertStringContainsString('tarif', $result);

        // 'pretul' does NOT match — synonym dict uses stems, FTS handles inflections
        $result2 = $method->invoke($this->service, 'care este pretul');
        $this->assertStringNotContainsString('cost', $result2);
    }

    public function test_expand_query_normalizes_diacritics(): void
    {
        $method = new \ReflectionMethod($this->service, 'expandQuery');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'livrare rapidă');
        $this->assertStringContainsString('transport', $result);
    }

    public function test_expand_query_skips_short_words(): void
    {
        $method = new \ReflectionMethod($this->service, 'expandQuery');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'la ce pret este');
        // 'la', 'ce' should be skipped (<=3 chars)
        $this->assertStringNotContainsString('telefon', $result);
    }

    public function test_remove_similar_content_filters_duplicates(): void
    {
        $method = new \ReflectionMethod($this->service, 'removeSimilarContent');
        $method->setAccessible(true);

        $results = [
            (object) ['id' => 1, 'content' => 'Livrarea se face în 24 ore prin curier rapid la adresa indicată'],
            (object) ['id' => 2, 'content' => 'Livrarea se face în 24 ore prin curier rapid la adresa clientului'],
            (object) ['id' => 3, 'content' => 'Returul produselor se poate face în 14 zile de la primire'],
        ];

        $filtered = $method->invoke($this->service, $results);

        $this->assertCount(2, $filtered);
        $this->assertEquals(1, $filtered[0]->id);
        $this->assertEquals(3, $filtered[1]->id);
    }

    public function test_remove_similar_content_keeps_distinct(): void
    {
        $method = new \ReflectionMethod($this->service, 'removeSimilarContent');
        $method->setAccessible(true);

        $results = [
            (object) ['id' => 1, 'content' => 'Informații despre livrare și transport'],
            (object) ['id' => 2, 'content' => 'Politica de retur și garanție produse'],
            (object) ['id' => 3, 'content' => 'Program de funcționare și contact'],
        ];

        $filtered = $method->invoke($this->service, $results);
        $this->assertCount(3, $filtered);
    }

    public function test_remove_similar_content_handles_empty(): void
    {
        $method = new \ReflectionMethod($this->service, 'removeSimilarContent');
        $method->setAccessible(true);

        $this->assertEmpty($method->invoke($this->service, []));
    }

    public function test_remove_similar_content_handles_single(): void
    {
        $method = new \ReflectionMethod($this->service, 'removeSimilarContent');
        $method->setAccessible(true);

        $results = [(object) ['id' => 1, 'content' => 'test content']];
        $filtered = $method->invoke($this->service, $results);
        $this->assertCount(1, $filtered);
    }

    public function test_search_returns_empty_for_nonexistent_bot(): void
    {
        $results = $this->service->search(999999, 'test query');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function test_build_context_returns_empty_for_nonexistent_bot(): void
    {
        $context = $this->service->buildContext(999999, 'test query');
        $this->assertEquals('', $context);
    }

    public function test_validate_document_tokens_accepts_normal(): void
    {
        $this->assertTrue($this->service->validateDocumentTokens(str_repeat('a ', 1000)));
    }

    public function test_validate_document_tokens_rejects_huge(): void
    {
        $this->assertFalse($this->service->validateDocumentTokens(str_repeat('a', 500000)));
    }
}
