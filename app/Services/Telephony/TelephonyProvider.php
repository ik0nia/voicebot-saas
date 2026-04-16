<?php

namespace App\Services\Telephony;

/**
 * Common interface that every telephony provider (Telnyx, Twilio, …)
 * must satisfy. Introduced in the Telnyx → Twilio migration: keeping
 * call sites (controllers, jobs, webhooks) bound to the interface means
 * the next provider swap is one new class + one config flag, not a
 * refactor across the codebase.
 *
 * Provider-specific concepts that don't map cleanly (Telnyx tags vs
 * Twilio friendly_name, Telnyx number_orders vs Twilio instant
 * provisioning) are smoothed over by each implementation; the interface
 * only exposes the tenant-facing semantics the platform actually uses.
 */
interface TelephonyProvider
{
    /**
     * Short, stable identifier written into phone_numbers.provider.
     */
    public function name(): string;

    /**
     * Place an outbound call that streams into our media-stream WebSocket.
     *
     * @return object Provider-native call descriptor (id, status, etc.).
     *                Always has a ->id property (Telnyx uuid, Twilio CallSid).
     */
    public function makeCall(string $to, string $from, string $webhookUrl): object;

    /**
     * Enumerate numbers available for purchase in a country.
     *
     * @return array<int, array{
     *   number: string,
     *   friendly_name: string,
     *   capabilities: array{voice: bool, sms: bool},
     *   region: array|string,
     *   monthly_cost: float,
     * }>
     */
    public function getAvailableNumbers(string $country = 'RO', string $type = 'local', int $limit = 10): array;

    /**
     * Buy a number. Twilio is instant; Telnyx creates a number_order
     * that may sit in "pending" until regulatory info is approved —
     * getOrderStatus() lets callers poll.
     */
    public function purchaseNumber(string $phoneNumber): ?object;

    /**
     * Release a number back to the provider. `$externalId` is whatever
     * we stored on phone_numbers (Telnyx phone_number_id, Twilio
     * IncomingPhoneNumber sid).
     */
    public function releaseNumber(string $externalId): bool;

    /**
     * Attach human-readable metadata (tenant, bot) to a number.
     * Telnyx supports tags natively; Twilio writes to friendly_name.
     */
    public function updateNumberTags(string $phoneNumber, array $tags): bool;

    /**
     * Provider-side status for a number: 'active' | 'pending' | 'not_found'.
     */
    public function getNumberStatus(string $phoneNumber): string;

    /**
     * Status of a purchase order. Twilio always returns 'completed'
     * (orders are synchronous); Telnyx can return pending / completed /
     * failed / null.
     */
    public function getOrderStatus(string $orderId): ?string;

    /**
     * Answer-URL response that bridges the PSTN leg into our OpenAI
     * Realtime WebSocket. Telnyx calls this TeXML, Twilio calls it
     * TwiML — grammar is close but `<Connect><Stream>` attributes
     * differ slightly.
     */
    public function generateMediaStreamTexml(string $botId, string $callId): string;
}
