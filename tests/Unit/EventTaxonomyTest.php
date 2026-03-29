<?php

namespace Tests\Unit;

use App\Services\EventTaxonomy;
use Tests\TestCase;

class EventTaxonomyTest extends TestCase
{
    public function test_valid_events_contains_core_events(): void
    {
        $events = EventTaxonomy::validEvents();

        $this->assertContains('session_started', $events);
        $this->assertContains('message_sent', $events);
        $this->assertContains('product_click', $events);
        $this->assertContains('add_to_cart_success', $events);
        $this->assertContains('lead_completed', $events);
        $this->assertContains('purchase_completed', $events);
        $this->assertContains('handoff_sent', $events);
    }

    public function test_is_valid_returns_true_for_known_events(): void
    {
        $this->assertTrue(EventTaxonomy::isValid('session_started'));
        $this->assertTrue(EventTaxonomy::isValid('product_impression'));
        $this->assertTrue(EventTaxonomy::isValid('lead_completed'));
    }

    public function test_is_valid_returns_false_for_unknown_events(): void
    {
        $this->assertFalse(EventTaxonomy::isValid('invalid_event'));
        $this->assertFalse(EventTaxonomy::isValid(''));
        $this->assertFalse(EventTaxonomy::isValid('session_started_fake'));
    }

    public function test_valid_sources_contains_all_sources(): void
    {
        $sources = EventTaxonomy::validSources();

        $this->assertContains('widget', $sources);
        $this->assertContains('backend', $sources);
        $this->assertContains('webhook', $sources);
        $this->assertContains('woocommerce', $sources);
        $this->assertContains('voice', $sources);
    }

    public function test_constants_match_valid_events(): void
    {
        // Every constant must be in validEvents()
        $this->assertTrue(EventTaxonomy::isValid(EventTaxonomy::SESSION_STARTED));
        $this->assertTrue(EventTaxonomy::isValid(EventTaxonomy::PRODUCT_CLICK));
        $this->assertTrue(EventTaxonomy::isValid(EventTaxonomy::ADD_TO_CART_SUCCESS));
        $this->assertTrue(EventTaxonomy::isValid(EventTaxonomy::PURCHASE_COMPLETED));
        $this->assertTrue(EventTaxonomy::isValid(EventTaxonomy::HANDOFF_RESOLVED));
    }
}
