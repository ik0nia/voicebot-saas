<?php

namespace Tests\Unit;

use App\Services\FillingMessageService;
use PHPUnit\Framework\TestCase;

/**
 * Edge-case unit tests for FillingMessageService::detectIntent().
 * No DB, no Laravel bootstrap.
 */
class FillingMessageIntentTest extends TestCase
{
    private FillingMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FillingMessageService();
    }

    // 1. Mixed intent: transcript with both price and brand keywords - highest scoring wins
    public function test_mixed_intent_picks_highest_scoring(): void
    {
        // "cat costa produsele de la Adeplast" has price keywords (pret/costa -> "costa")
        // and brand keywords ("de la"). Price keywords should score higher overall.
        $transcript = 'cat costa produsele de la Adeplast';
        $intent = $this->service->detectIntent($transcript, true);

        // Price keywords: "costa" (5) + "cat costa" would match "cat costa" (9)
        // Brand keywords: "de la" (5)
        // Price should win because it accumulates more total keyword length
        $this->assertContains($intent, [
            FillingMessageService::INTENT_PRICE_CHECK,
            FillingMessageService::INTENT_BRAND_LOOKUP,
        ], "Mixed price+brand transcript should resolve to one of these intents, got: {$intent}");

        // Specifically, price keywords should outscore brand
        $this->assertEquals(
            FillingMessageService::INTENT_PRICE_CHECK,
            $intent,
            "Price keywords should outscore brand keywords in 'cat costa produsele de la Adeplast'"
        );
    }

    // 2a. Romanian diacritics: "preț" (with diacritics) should match PRICE_CHECK
    public function test_diacritics_pret_with_cedilla(): void
    {
        $intent = $this->service->detectIntent('care e prețul', true);
        $this->assertEquals(FillingMessageService::INTENT_PRICE_CHECK, $intent);
    }

    // 2b. Romanian diacritics: "pret" (without diacritics) should also match PRICE_CHECK
    public function test_diacritics_pret_without_cedilla(): void
    {
        $intent = $this->service->detectIntent('care e pretul', true);
        $this->assertEquals(FillingMessageService::INTENT_PRICE_CHECK, $intent);
    }

    // 2c. Romanian diacritics: "comandă" (with diacritics) should match ORDER_STATUS
    public function test_diacritics_comanda_with_breve(): void
    {
        $intent = $this->service->detectIntent('vreau să văd comanda', false);
        $this->assertEquals(FillingMessageService::INTENT_ORDER_STATUS, $intent);
    }

    // 2d. Romanian diacritics: "comanda" (without diacritics) should also match ORDER_STATUS
    public function test_diacritics_comanda_without_breve(): void
    {
        $intent = $this->service->detectIntent('vreau sa vad comanda', false);
        $this->assertEquals(FillingMessageService::INTENT_ORDER_STATUS, $intent);
    }

    // 3. Very short transcript (1-2 chars) returns GENERAL
    public function test_very_short_transcript_returns_general(): void
    {
        $this->assertEquals(FillingMessageService::INTENT_GENERAL, $this->service->detectIntent('ab', true));
        $this->assertEquals(FillingMessageService::INTENT_GENERAL, $this->service->detectIntent('x', false));
        $this->assertEquals(FillingMessageService::INTENT_GENERAL, $this->service->detectIntent('da', true));
    }

    // 4. Empty transcript returns GENERAL
    public function test_empty_transcript_returns_general(): void
    {
        $this->assertEquals(FillingMessageService::INTENT_GENERAL, $this->service->detectIntent('', false));
        $this->assertEquals(FillingMessageService::INTENT_GENERAL, $this->service->detectIntent('', true));
    }

    // 5. Multiple stock keywords should return STOCK_CHECK
    public function test_multiple_stock_keywords_returns_stock_check(): void
    {
        $transcript = 'mai aveti pe stoc si e disponibil produsul';
        $intent = $this->service->detectIntent($transcript, true);
        $this->assertEquals(FillingMessageService::INTENT_STOCK_CHECK, $intent);
    }

    // 6. Order with AWB number detection
    public function test_order_with_awb_number(): void
    {
        $transcript = 'am un awb 1234567890 si vreau sa stiu unde e coletul';
        $intent = $this->service->detectIntent($transcript, false);
        $this->assertEquals(FillingMessageService::INTENT_ORDER_STATUS, $intent);
    }

    // 7. Technical query with dimensions
    public function test_technical_query_with_dimensions(): void
    {
        $transcript = 'ce dimensiuni are si cat de greu e greutatea';
        $intent = $this->service->detectIntent($transcript, true);
        $this->assertEquals(FillingMessageService::INTENT_TECHNICAL, $intent);
    }

    // 8. Category browse with "ce tipuri" pattern
    public function test_category_browse_ce_tipuri(): void
    {
        $transcript = 'ce tipuri de produse aveti in gama';
        $intent = $this->service->detectIntent($transcript, true);
        $this->assertEquals(FillingMessageService::INTENT_CATEGORY_BROWSE, $intent);
    }

    // 9. Brand query with "de la" pattern
    public function test_brand_query_de_la(): void
    {
        $transcript = 'aratami produsele de la Samsung';
        $intent = $this->service->detectIntent($transcript, true);
        $this->assertEquals(FillingMessageService::INTENT_BRAND_LOOKUP, $intent);
    }

    // 10. Product search fallback when hasProducts=true but no keyword match
    public function test_product_search_fallback_with_has_products(): void
    {
        // A transcript with no intent keywords but longer than 3 chars, with hasProducts=true
        $transcript = 'vreau ceva frumos pentru living';
        $intent = $this->service->detectIntent($transcript, true);
        $this->assertEquals(FillingMessageService::INTENT_PRODUCT_SEARCH, $intent);
    }

    // 10b. Same transcript without hasProducts should return GENERAL
    public function test_no_product_search_fallback_without_has_products(): void
    {
        $transcript = 'vreau ceva frumos pentru living';
        $intent = $this->service->detectIntent($transcript, false);
        $this->assertEquals(FillingMessageService::INTENT_GENERAL, $intent);
    }
}
