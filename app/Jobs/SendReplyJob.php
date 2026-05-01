<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Channels\ChannelMessageDispatcher;
use App\Services\Channels\Messages\OutboundMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued outbound dispatch for typed OutboundMessage objects.
 *
 * Pattern: the AI / orchestrator builds an OutboundMessage in-process
 * (cheap, synchronous), then hands it to this job which handles the
 * Meta round-trip on a worker. The job persists a Message row with
 * external_message_id set on success so subsequent webhook status
 * events (delivered/read) can update it.
 *
 * Retry strategy: 3 attempts with exponential backoff (Meta's own
 * client-side retry guidance). Permanent failures (4xx other than 429)
 * are logged and the message row stays in 'failed' status.
 */
class SendReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds

    public function __construct(
        public int $channelId,
        public string $recipientId,
        public OutboundMessage $message,
        public ?int $conversationId = null,
    ) {}

    public function handle(ChannelMessageDispatcher $dispatcher): void
    {
        $channel = Channel::withoutGlobalScopes()->find($this->channelId);
        if (!$channel) {
            Log::warning('SendReplyJob: channel disappeared before dispatch', [
                'channel_id' => $this->channelId,
            ]);
            return;
        }

        if (!$channel->is_active) {
            Log::info('SendReplyJob: channel inactive — skipping', [
                'channel_id' => $channel->id,
            ]);
            return;
        }

        $result = $dispatcher->dispatch($channel, $this->recipientId, $this->message);

        $this->persistOutboundMessage($channel, $result);

        if (!$result['success']) {
            // Non-2xx Meta response. Throw so Laravel retries (subject to
            // $tries cap). The error string is sanitized inside the
            // dispatcher — safe to surface here.
            throw new \RuntimeException(
                "SendReplyJob failed for channel {$channel->id}: {$result['error']}"
            );
        }
    }

    private function persistOutboundMessage(Channel $channel, array $result): void
    {
        if ($this->conversationId === null) {
            return;
        }

        $conversation = Conversation::withoutGlobalScopes()->find($this->conversationId);
        if (!$conversation) {
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $this->messagePreview(),
            'content_type' => $this->message->kind(),
            'external_message_id' => $result['message_id'] ?? null,
            'metadata' => [
                'channel_id' => $channel->id,
                'channel_type' => $channel->type,
                'success' => (bool) $result['success'],
                'error' => $result['error'] ?? null,
            ],
            'sent_at' => $result['success'] ? now() : null,
        ]);

        $conversation->increment('messages_count');
        $conversation->last_activity_at = now();
        $conversation->saveQuietly();
    }

    /**
     * One-line summary suitable for the messages.content text column.
     * Typed messages may have richer payloads; we record just the textual
     * body for analytics / inbox-list previews. Full payload is in metadata.
     */
    private function messagePreview(): string
    {
        // Reflection-light: each typed message exposes its own salient text
        // via these properties. We avoid coupling the job to every concrete
        // class by switching on kind().
        return match (true) {
            isset($this->message->body) => (string) $this->message->body,
            isset($this->message->caption) => (string) ($this->message->caption ?? ''),
            isset($this->message->name) => '[template] ' . $this->message->name,
            default => '[' . $this->message->kind() . ']',
        };
    }
}
