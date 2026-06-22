<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Procesează un FB feed comment ca o conversație Inbox.
 * Comment-urile ajung împachetate cu post-ul lor; operatorul poate
 * răspunde sau marca rezolvat de pe pagina detalii conversație.
 *
 * Skip own page comments (echo de la noi).
 */
class IngestFacebookCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    /**
     * @param array<string, mixed> $value
     */
    public function __construct(
        public readonly string $pageId,
        public readonly array $value,
    ) {}

    public function handle(): void
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('type', Channel::TYPE_FACEBOOK_MESSENGER)
            ->where('external_id', $this->pageId)
            ->where('is_active', true)
            ->first();
        if (!$channel) return;

        $fromId = $this->value['from']['id'] ?? null;
        $fromName = $this->value['from']['name'] ?? 'Vizitator Facebook';
        $message = trim((string) ($this->value['message'] ?? ''));
        $commentId = (string) ($this->value['comment_id'] ?? '');
        $postId = (string) ($this->value['post_id'] ?? '');

        if (!$fromId || $fromId === $this->pageId || $message === '') return;

        // Conversație una-pe-post: toate comentariile pe același post merg în
        // aceeași convo, ca operatorul să vadă thread-ul.
        $convo = Conversation::firstOrCreate(
            [
                'channel_id' => $channel->id,
                'contact_identifier' => 'fb_post:' . $postId,
            ],
            [
                'tenant_id' => $channel->tenant_id,
                'bot_id' => $channel->bot_id,
                'contact_name' => 'Comentarii FB · ' . mb_substr($postId, -6),
                'status' => 'active',
                'last_activity_at' => now(),
            ]
        );

        Message::create([
            'conversation_id' => $convo->id,
            'tenant_id' => $channel->tenant_id,
            'direction' => 'inbound',
            'content' => $fromName . ': ' . $message,
            'metadata' => [
                'fb_comment_id' => $commentId,
                'fb_post_id' => $postId,
                'fb_user_id' => $fromId,
                'fb_user_name' => $fromName,
                'source' => 'facebook_comment',
            ],
        ]);

        $convo->update([
            'last_activity_at' => now(),
            'messages_count' => $convo->messages_count + 1,
        ]);

        Log::info('FB comment ingested', ['comment_id' => $commentId, 'conv_id' => $convo->id]);
    }
}
