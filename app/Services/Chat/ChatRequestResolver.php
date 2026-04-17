<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Turns an inbound HTTP chat request into a {@see ResolvedChatRequest}
 * (on success) or a {@see ChatRequestRejection} (on failure).
 *
 * Owns every side effect that *must* happen before the LLM sees the
 * turn:
 *   - channel + bot activation check (cached)
 *   - rate limiting (per-IP/channel, 30/min)
 *   - tenant plan-limit check
 *   - session HMAC validation + 10-minute inactivity expiry (with
 *     DeriveConversationOutcomes queued for the closed conversation)
 *   - Conversation creation for fresh sessions, including greeting
 *     Message, session_started event, and increment of messages_count
 *   - user Message persistence + last_activity_at touch
 *   - prechat lead capture when the widget submitted contact info
 *
 * All of the above used to live inline at the top of
 * ChatbotApiController::preprocessMessage. Keeping it behind one
 * service means the rate-limit key, the session contract, and the
 * conversation lifecycle can only diverge in one place.
 */
final class ChatRequestResolver
{
    private const VALIDATION_RULES = [
        'message' => 'required|string|max:2000',
        'session_id' => 'nullable|string|max:255',
        'session_token' => 'nullable|string|max:255',
        'prechat_name' => 'nullable|string|max:255',
        'prechat_email' => 'nullable|string|max:255',
        'prechat_phone' => 'nullable|string|max:255',
        'page_context' => 'nullable|array',
        'page_context.page_url' => 'nullable|string|max:2000',
        'page_context.page_title' => 'nullable|string|max:500',
        'page_context.page_path' => 'nullable|string|max:500',
        'page_context.time_on_page' => 'nullable|integer|min:0',
        'page_context.referrer' => 'nullable|string|max:2000',
        // W3/W4/F-fix: keep these allowlisted or Laravel strips them
        // from $validated and the bot loses product_context awareness.
        'page_context.page_type' => 'nullable|string|max:40',
        'page_context.product_context' => 'nullable|array',
        'page_context.product_context.product_id' => 'nullable|integer',
        'page_context.product_context.variation_id' => 'nullable|integer',
        'page_context.product_context.name' => 'nullable|string|max:500',
        'page_context.product_context.price' => 'nullable|string|max:40',
        'page_context.product_context.currency' => 'nullable|string|max:10',
        'page_context.product_context.categories' => 'nullable|array|max:10',
        'page_context.product_context.categories.*' => 'nullable|string|max:120',
        'page_context.product_context.in_stock' => 'nullable|boolean',
        'page_context.product_context.permalink' => 'nullable|string|max:2000',
        'page_context.cart_context' => 'nullable|array',
        'page_context.cart_context.items_count' => 'nullable|integer|min:0',
        'page_context.cart_context.total' => 'nullable|string|max:100',
        'page_context.cart_context.total_raw' => 'nullable|numeric',
        'page_context.cart_context.currency' => 'nullable|string|max:10',
        'page_context.cart_context.shipping_threshold' => 'nullable|numeric',
        'page_context.cart_context.missing_amount_for_free_shipping' => 'nullable|numeric',
        'page_context.cart_context.items' => 'nullable|array|max:20',
        'page_context.cart_context.items.*.product_id' => 'nullable|integer',
        'page_context.cart_context.items.*.name' => 'nullable|string|max:500',
        'page_context.cart_context.items.*.qty' => 'nullable|integer|min:0',
    ];

    private const RATE_LIMIT_MAX = 30;
    private const RATE_LIMIT_DECAY = 60;
    private const SESSION_INACTIVE_MINUTES = 10;
    private const CHANNEL_CACHE_TTL = 1800;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly ConversationEventService $conversationEventService,
        private readonly PrechatLeadCreator $prechatLeadCreator,
    ) {}

    /**
     * Resolve an active channel by id. Cached for 30 min; the
     * ChannelCacheObserver + BotChannelCacheObserver invalidate the
     * cache when the owning bot or channel toggles is_active, so the
     * cache can never serve a paused widget.
     */
    public function findActiveChannel(int|string $channelId): ?Channel
    {
        $dbQuery = function () use ($channelId): ?Channel {
            $channel = Channel::withoutGlobalScopes()
                ->where('id', $channelId)
                ->where('is_active', true)
                ->first();
            if (!$channel) {
                return null;
            }
            $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
            if (!$bot || !$bot->is_active) {
                return null;
            }
            return $channel;
        };

        try {
            return Cache::remember("channel_{$channelId}", self::CHANNEL_CACHE_TTL, $dbQuery);
        } catch (\Throwable) {
            return $dbQuery();
        }
    }

    /**
     * Run the full pre-LLM pipeline.
     *
     * @return ResolvedChatRequest|ChatRequestRejection
     */
    public function resolve(Request $request, int|string $channelId): ResolvedChatRequest|ChatRequestRejection
    {
        $channel = $this->findActiveChannel($channelId);
        if (!$channel) {
            return new ChatRequestRejection('Canal invalid.', 404);
        }

        $validated = $request->validate(self::VALIDATION_RULES);

        $userMessage = (string) $validated['message'];
        $sessionId = $validated['session_id'] ?? null;
        $sessionToken = $validated['session_token'] ?? null;
        $prechatName = $validated['prechat_name'] ?? null;
        $prechatEmail = $validated['prechat_email'] ?? null;
        $prechatPhone = $validated['prechat_phone'] ?? null;
        $pageContext = $validated['page_context'] ?? null;

        $rateLimitKey = 'chatbot:msg:' . $request->ip() . ':' . $channelId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_MAX)) {
            return new ChatRequestRejection(
                'Prea multe mesaje. Încercați din nou în câteva secunde.',
                429,
            );
        }
        RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot || !$bot->is_active) {
            return new ChatRequestRejection('Bot inactiv.', 403);
        }

        $tenant = Tenant::find($bot->tenant_id);
        if ($tenant) {
            $limitCheck = $this->planLimitService->canSendMessage($tenant, $bot);
            if (!$limitCheck->allowed) {
                return new ChatRequestRejection(
                    'Limita de mesaje a fost atinsă. Contactați administratorul pentru upgrade.',
                    429,
                    ['limit_reached' => true],
                );
            }
        }

        [$conversation, $sessionId, $sessionToken, $sessionExpired] = $this->resolveConversation(
            $channel,
            $bot,
            (string) $channelId,
            $sessionId,
            $sessionToken,
            $request,
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'content' => $userMessage,
            'content_type' => 'text',
            'metadata' => $pageContext ? ['page_context' => $pageContext] : null,
            'sent_at' => now(),
        ]);

        $conversation->increment('messages_count');
        $conversation->update(['last_activity_at' => now()]);

        if ($prechatEmail || $prechatPhone) {
            $this->prechatLeadCreator->create(
                $bot,
                $conversation,
                $prechatName,
                $prechatEmail,
                $prechatPhone,
            );
        }

        return new ResolvedChatRequest(
            channel: $channel,
            bot: $bot,
            tenant: $tenant,
            conversation: $conversation,
            sessionId: $sessionId,
            sessionToken: $sessionToken,
            sessionExpired: $sessionExpired,
            userMessage: $userMessage,
            pageContext: $pageContext,
            prechatName: $prechatName,
            prechatEmail: $prechatEmail,
            prechatPhone: $prechatPhone,
        );
    }

    /**
     * HMAC-validates the caller's session, expires any conversation
     * idle ≥ {@see SESSION_INACTIVE_MINUTES} minutes (firing
     * DeriveConversationOutcomes), then either returns the existing
     * Conversation or creates a fresh one with greeting message +
     * session_started event.
     *
     * @return array{0: Conversation, 1: string, 2: string, 3: bool}
     */
    private function resolveConversation(
        Channel $channel,
        Bot $bot,
        string $channelId,
        ?string $sessionId,
        ?string $sessionToken,
        Request $request,
    ): array {
        $sessionExpired = false;
        $conversation = null;

        if ($sessionId && $sessionToken) {
            $expectedToken = hash_hmac('sha256', $sessionId . $channelId, config('app.key'));
            if (hash_equals($expectedToken, $sessionToken)) {
                $conversation = Conversation::where('channel_id', $channel->id)
                    ->where('external_conversation_id', $sessionId)
                    ->where('status', 'active')
                    ->first();

                if ($conversation) {
                    $lastMessage = $conversation->messages()->latest('id')->first();
                    $lastActivity = $lastMessage ? $lastMessage->created_at : $conversation->created_at;

                    if ($lastActivity->diffInMinutes(now()) >= self::SESSION_INACTIVE_MINUTES) {
                        $expiredConvId = $conversation->id;
                        $conversation->update([
                            'status' => 'completed',
                            'ended_at' => $lastActivity,
                        ]);

                        \App\Jobs\DeriveConversationOutcomes::dispatch($expiredConvId)
                            ->delay(now()->addSeconds(5));

                        $conversation = null;
                        $sessionExpired = true;
                    }
                }
            }
        }

        if ($conversation) {
            return [$conversation, (string) $sessionId, (string) $sessionToken, $sessionExpired];
        }

        $sessionId = Str::uuid()->toString();
        $sessionToken = hash_hmac('sha256', $sessionId . $channelId, config('app.key'));
        $conversation = Conversation::create([
            'tenant_id' => $bot->tenant_id,
            'bot_id' => $bot->id,
            'channel_id' => $channel->id,
            'external_conversation_id' => $sessionId,
            'contact_identifier' => $request->ip(),
            'visitor_id' => $request->input('visitor_id'),
            'status' => 'active',
            'metadata' => [
                'user_agent' => $request->userAgent(),
                'origin' => $request->header('Origin', ''),
            ],
            'started_at' => now(),
        ]);

        $eventCtx = $this->conversationEventService->buildContext(
            $bot->tenant_id,
            $bot->id,
            $channel->id,
            $conversation->id,
            $sessionId,
        );
        $this->conversationEventService->track(
            EventTaxonomy::SESSION_STARTED,
            [
                'visitor_id' => $request->input('visitor_id'),
                'user_agent' => $request->userAgent(),
            ],
            array_merge($eventCtx, [
                'idempotency_key' => $this->conversationEventService->idempotencyKey(
                    (string) $conversation->id,
                    'session_started',
                ),
            ]),
        );

        $channelConfig = $channel->config ?? [];
        $greetingText = $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?';
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $greetingText,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);
        $conversation->increment('messages_count');

        return [$conversation, $sessionId, $sessionToken, $sessionExpired];
    }
}
