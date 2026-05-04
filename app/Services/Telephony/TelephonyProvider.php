<?php

namespace App\Services\Telephony;

/**
 * Common interface that every telephony provider must satisfy. Keeping
 * call sites (controllers, jobs, webhooks) bound to the interface means
 * the next provider swap is one new class + one config flag, not a
 * refactor across the codebase.
 *
 * Currently the only implementation is TwilioService.
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
     *                Always has a ->id property (Twilio CallSid).
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
     * Buy a number. Twilio is instant.
     */
    public function purchaseNumber(string $phoneNumber): ?object;

    /**
     * Release a number back to the provider. `$externalId` is whatever
     * we stored on phone_numbers (Twilio IncomingPhoneNumber sid).
     */
    public function releaseNumber(string $externalId): bool;

    /**
     * Attach human-readable metadata (tenant, bot) to a number.
     * Twilio writes to friendly_name.
     */
    public function updateNumberTags(string $phoneNumber, array $tags): bool;

    /**
     * Provider-side status for a number: 'active' | 'pending' | 'not_found'.
     */
    public function getNumberStatus(string $phoneNumber): string;

    /**
     * Status of a purchase order. Twilio always returns 'completed'
     * (orders are synchronous).
     */
    public function getOrderStatus(string $orderId): ?string;

    /**
     * Answer-URL response (TwiML) that bridges the PSTN leg into our
     * OpenAI Realtime WebSocket via <Connect><Stream>.
     */
    public function generateMediaStreamTexml(string $botId, string $callId): string;
}
