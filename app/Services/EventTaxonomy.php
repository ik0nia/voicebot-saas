<?php

namespace App\Services;

/**
 * Canonical event names for the analytics system.
 * All event tracking MUST use these constants — never raw strings.
 */
class EventTaxonomy
{
    // ─── Session lifecycle ───
    public const SESSION_STARTED = 'session_started';
    public const SESSION_RESUMED = 'session_resumed';
    public const SESSION_ENDED = 'session_ended';

    // ─── Messages ───
    public const MESSAGE_SENT = 'message_sent';
    public const MESSAGE_REPLIED = 'message_replied';

    // ─── Intents & Pipelines ───
    public const INTENT_DETECTED = 'intent_detected';
    public const PIPELINE_EXECUTED = 'pipeline_executed';

    // ─── Products ───
    public const PRODUCTS_RETURNED = 'products_returned';
    public const PRODUCT_IMPRESSION = 'product_impression';
    public const PRODUCT_CLICK = 'product_click';

    // ─── Cart ───
    public const ADD_TO_CART_CLICK = 'add_to_cart_click';
    public const ADD_TO_CART_SUCCESS = 'add_to_cart_success';
    public const ADD_TO_CART_FAILURE = 'add_to_cart_failure';
    public const VARIATION_REQUIRED_REDIRECT = 'variation_required_redirect';
    public const REDIRECTED_TO_PRODUCT_PAGE = 'redirected_to_product_page';

    // ─── Checkout & Purchase ───
    public const CHECKOUT_STARTED = 'checkout_started';
    public const PURCHASE_COMPLETED = 'purchase_completed';

    // ─── Lead ───
    public const LEAD_PROMPT_SHOWN = 'lead_prompt_shown';
    public const LEAD_PROMPT_ACCEPTED = 'lead_prompt_accepted';
    public const LEAD_PROMPT_DISMISSED = 'lead_prompt_dismissed';
    public const LEAD_FIELD_SUBMITTED = 'lead_field_submitted';
    public const LEAD_COMPLETED = 'lead_completed';
    public const LEAD_SENT_TO_CRM = 'lead_sent_to_crm';

    // ─── Handoff ───
    public const HANDOFF_OFFERED = 'handoff_offered';
    public const HANDOFF_ACCEPTED = 'handoff_accepted';
    public const HANDOFF_DECLINED = 'handoff_declined';
    public const HANDOFF_SENT = 'handoff_sent';
    public const HANDOFF_RESOLVED = 'handoff_resolved';

    // ─── Fallback / Errors ───
    public const FALLBACK_TRIGGERED = 'fallback_triggered';
    public const NO_RESULTS = 'no_results';

    // ─── Recommendation ───
    public const CLARIFICATION_ASKED = 'clarification_asked';
    public const CLARIFICATION_ANSWERED = 'clarification_answered';

    // ─── Event sources ───
    public const SOURCE_WIDGET = 'widget';
    public const SOURCE_BACKEND = 'backend';
    public const SOURCE_WEBHOOK = 'webhook';
    public const SOURCE_WOOCOMMERCE = 'woocommerce';
    public const SOURCE_VOICE = 'voice';

    /**
     * All valid event names for validation.
     */
    public static function validEvents(): array
    {
        return [
            self::SESSION_STARTED, self::SESSION_RESUMED, self::SESSION_ENDED,
            self::MESSAGE_SENT, self::MESSAGE_REPLIED,
            self::INTENT_DETECTED, self::PIPELINE_EXECUTED,
            self::PRODUCTS_RETURNED, self::PRODUCT_IMPRESSION, self::PRODUCT_CLICK,
            self::ADD_TO_CART_CLICK, self::ADD_TO_CART_SUCCESS, self::ADD_TO_CART_FAILURE,
            self::VARIATION_REQUIRED_REDIRECT, self::REDIRECTED_TO_PRODUCT_PAGE,
            self::CHECKOUT_STARTED, self::PURCHASE_COMPLETED,
            self::LEAD_PROMPT_SHOWN, self::LEAD_PROMPT_ACCEPTED, self::LEAD_PROMPT_DISMISSED,
            self::LEAD_FIELD_SUBMITTED, self::LEAD_COMPLETED, self::LEAD_SENT_TO_CRM,
            self::HANDOFF_OFFERED, self::HANDOFF_ACCEPTED, self::HANDOFF_DECLINED,
            self::HANDOFF_SENT, self::HANDOFF_RESOLVED,
            self::FALLBACK_TRIGGERED, self::NO_RESULTS,
            self::CLARIFICATION_ASKED, self::CLARIFICATION_ANSWERED,
        ];
    }

    public static function validSources(): array
    {
        return [self::SOURCE_WIDGET, self::SOURCE_BACKEND, self::SOURCE_WEBHOOK, self::SOURCE_WOOCOMMERCE, self::SOURCE_VOICE];
    }

    public static function isValid(string $eventName): bool
    {
        return in_array($eventName, self::validEvents(), true);
    }
}
