<?php

namespace Tests\Unit\Services;

use App\Services\IntentDetectionService;
use Tests\TestCase;

class IntentDetectionServiceTest extends TestCase
{
    private IntentDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IntentDetectionService();
    }

    public function test_greeting_detection(): void
    {
        $result = $this->service->detect('Buna ziua!');

        $this->assertTrue($result['is_greeting']);
    }

    public function test_greeting_detection_with_salut(): void
    {
        $result = $this->service->detect('Salut!');

        $this->assertTrue($result['is_greeting']);
    }

    public function test_long_message_is_not_greeting(): void
    {
        $result = $this->service->detect('Buna ziua, as vrea sa stiu despre produsele voastre');

        $this->assertFalse($result['is_greeting']);
    }

    public function test_order_query_detection(): void
    {
        $result = $this->service->detect('Unde este comanda mea?');

        $this->assertTrue($result['is_order_query']);
    }

    public function test_order_query_with_tracking(): void
    {
        $result = $this->service->detect('Vreau sa vad tracking-ul coletului');

        $this->assertTrue($result['is_order_query']);
    }

    public function test_order_query_with_awb(): void
    {
        $result = $this->service->detect('Care este numarul AWB?');

        $this->assertTrue($result['is_order_query']);
    }

    public function test_product_search_detection(): void
    {
        $result = $this->service->detect('Cat costa produsul acesta?');

        $this->assertTrue($result['is_product_search']);
    }

    public function test_product_search_with_stock(): void
    {
        $result = $this->service->detect('Aveti pe stoc acest produs?');

        $this->assertTrue($result['is_product_search']);
    }

    public function test_product_search_with_price(): void
    {
        $result = $this->service->detect('Ce pret are?');

        $this->assertTrue($result['is_product_search']);
    }

    public function test_should_skip_knowledge_for_greeting(): void
    {
        $this->assertTrue($this->service->shouldSkipKnowledge('Salut!'));
    }

    public function test_should_skip_knowledge_for_thanks(): void
    {
        $this->assertTrue($this->service->shouldSkipKnowledge('Multumesc!'));
    }

    public function test_should_skip_knowledge_for_simple_followup(): void
    {
        $this->assertTrue($this->service->shouldSkipKnowledge('Da'));
    }

    public function test_complex_message_not_skipped(): void
    {
        $this->assertFalse($this->service->shouldSkipKnowledge('Vreau sa stiu cat costa produsul X si daca il aveti pe stoc'));
    }

    public function test_complaint_detection(): void
    {
        $result = $this->service->detect('Am o reclamatie despre produs');

        $this->assertTrue($result['is_complaint']);
    }

    public function test_complaint_with_problem(): void
    {
        $result = $this->service->detect('Am o problema cu comanda');

        $this->assertTrue($result['is_complaint']);
    }

    public function test_thanks_detection(): void
    {
        $result = $this->service->detect('Multumesc frumos!');

        $this->assertTrue($result['is_thanks']);
    }

    public function test_detect_returns_all_intent_keys(): void
    {
        $result = $this->service->detect('test message');

        $expectedKeys = ['is_order_query', 'is_product_search', 'is_greeting', 'is_followup', 'is_complaint', 'is_thanks'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result);
            $this->assertIsBool($result[$key]);
        }
    }

    public function test_simple_non_intent_message(): void
    {
        $result = $this->service->detect('Vreau sa stiu mai multe despre proiectul vostru de renovare a apartamentelor din zona centrala');

        // A long non-trivial message should not match greeting/thanks
        $this->assertFalse($result['is_greeting']);
        $this->assertFalse($result['is_thanks']);
    }
}
