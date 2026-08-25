<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Who is on the other end of a tool call.
 *
 * ToolRegistry::execute() is deliberately narrow — `(name, botId, params)` —
 * and everything a handler knows beyond that has to arrive in `params`, which
 * means the language model has to supply it. That works for a lookup ("find
 * me a soup") and fails for anything with continuity, because the model has
 * no idea which conversation it is in and will not invent a correct id.
 *
 * The existing symptom: `reservations.conversation_id` is in the schema and
 * on the fillable list, but HospitalityToolDispatcher reads it from
 * `$params['conversation_id']` — a key no model has ever sent. Every
 * reservation ever taken by a bot has a null conversation_id.
 *
 * A food order cannot be built that way at all. It spans turns ("adaugă și o
 * cola", two minutes later "cât face?"), and each turn is a separate tool
 * call, so the basket needs a stable key that the caller — not the model —
 * owns. That key is this object.
 *
 * Request-scoped singleton, bound in AppServiceProvider, mirroring
 * AnalyticsConfig. PHP-FPM gives one container per request, so "singleton"
 * here means "for this HTTP request", which is exactly the lifetime wanted:
 * one chat turn, or one voice tool-call webhook. It is populated at the two
 * dispatch sites (ChatbotApiController for chat, MediaStreamEventController
 * for voice) and read by handlers that need continuity.
 *
 * Under a persistent worker (Octane, a queue worker looping) the container is
 * NOT per-request and this would leak between jobs. Nothing dispatches tools
 * from a queue today; if that changes, reset() must be called at job
 * boundaries.
 */
class ToolContext
{
    public const CHANNEL_CHAT  = 'chat';
    public const CHANNEL_VOICE = 'voice';

    private ?string $channel = null;
    private ?int $conversationId = null;
    private ?int $callId = null;
    private ?string $customerPhone = null;
    private ?string $customerName = null;

    /**
     * Bind a web-chat turn.
     *
     * The phone is optional and usually absent on chat — a widget visitor is
     * anonymous until they say so, and the order flow asks for it explicitly
     * rather than guessing.
     */
    public function forChat(int $conversationId, ?string $customerPhone = null, ?string $customerName = null): void
    {
        $this->channel = self::CHANNEL_CHAT;
        $this->conversationId = $conversationId;
        $this->callId = null;
        $this->customerPhone = self::normalisePhone($customerPhone);
        $this->customerName = $customerName;
    }

    /**
     * Bind a voice call.
     *
     * The caller's number comes from telephony, not from the transcript, so
     * it is trustworthy in a way a spoken-and-transcribed number is not —
     * "zero șapte doi patru" survives ASR badly. Handlers prefer this over
     * anything the model reports, and the order flow only asks the caller to
     * dictate a number when telephony gave us none (withheld caller ID).
     */
    public function forVoice(int $callId, ?string $callerNumber = null, ?int $conversationId = null): void
    {
        $this->channel = self::CHANNEL_VOICE;
        $this->callId = $callId;
        $this->conversationId = $conversationId;
        $this->customerPhone = self::normalisePhone($callerNumber);
        $this->customerName = null;
    }

    /**
     * Stable key for state that must survive across tool calls within one
     * conversation — the open food order, principally.
     *
     * Voice is keyed on the call and chat on the conversation, never on the
     * phone number: two people ordering from the same household line, or one
     * person calling twice, must not land in the same basket. Returns null
     * when nothing bound the context, and callers treat that as "no
     * continuity available" rather than falling back to something shared.
     */
    public function sessionRef(): ?string
    {
        if ($this->callId !== null) {
            return 'call:' . $this->callId;
        }
        if ($this->conversationId !== null) {
            return 'conv:' . $this->conversationId;
        }
        return null;
    }

    public function channel(): ?string
    {
        return $this->channel;
    }

    public function isVoice(): bool
    {
        return $this->channel === self::CHANNEL_VOICE;
    }

    public function conversationId(): ?int
    {
        return $this->conversationId;
    }

    public function callId(): ?int
    {
        return $this->callId;
    }

    public function customerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function customerName(): ?string
    {
        return $this->customerName;
    }

    /** Whether anything bound this context at all. */
    public function isBound(): bool
    {
        return $this->channel !== null;
    }

    /** Clear the context — only needed where the container outlives a request. */
    public function reset(): void
    {
        $this->channel = null;
        $this->conversationId = null;
        $this->callId = null;
        $this->customerPhone = null;
        $this->customerName = null;
    }

    /**
     * Keep digits and a leading +, drop the formatting telephony providers
     * disagree about. Not a validator: an unparseable number is still better
     * recorded than discarded, because a human reading the order can usually
     * make sense of it.
     *
     * Public and static because handlers compare a number the customer
     * dictated against the one telephony reported, and both have to be folded
     * the same way first — "+40 752 144 855" and "0752144855" are not the
     * same string but are the same person.
     */
    public static function normalisePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '' || $phone === 'anonymous' || $phone === 'unknown') {
            return null;
        }

        $cleaned = preg_replace('/(?!^\+)[^0-9]/', '', $phone) ?? '';

        return $cleaned !== '' && $cleaned !== '+' ? $cleaned : null;
    }
}
