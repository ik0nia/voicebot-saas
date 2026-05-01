<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Channel;
use App\Models\ContactInbox;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill ContactInbox pivots from existing conversations + contacts state.
 *
 * Iterates conversations in chunks, derives the (channel_id, source_id) pair
 * from each, and uses firstOrCreate so the job is safe to re-run multiple
 * times (e.g. after a partial failure, or after new inbound traffic arrives
 * during the backfill window).
 *
 * Source_id resolution:
 *  - WhatsApp: conversations.contact_identifier (the wa_id phone)
 *  - Facebook Messenger: conversations.contact_identifier (the PSID)
 *  - Instagram DM: conversations.contact_identifier (the IG sender id)
 *  - Web widget: conversations.visitor_id (or external_conversation_id as fallback)
 *  - Voice: conversations.contact_identifier (caller phone)
 *
 * After creating the pivot, also stamps conversations.contact_inbox_id so
 * downstream code can join through the new seam directly.
 */
class BackfillContactInboxes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $sinceId = null,
        public int $chunkSize = 500,
    ) {}

    public function handle(): void
    {
        $stats = ['scanned' => 0, 'created' => 0, 'linked' => 0, 'skipped' => 0];

        Conversation::query()
            ->withoutGlobalScopes()
            ->whereNotNull('channel_id')
            ->whereNotNull('contact_id')
            ->whereNull('contact_inbox_id')
            ->when($this->sinceId, fn ($q) => $q->where('id', '>=', $this->sinceId))
            ->orderBy('id')
            ->chunkById($this->chunkSize, function ($conversations) use (&$stats) {
                foreach ($conversations as $conversation) {
                    $stats['scanned']++;

                    $channel = Channel::withoutGlobalScopes()->find($conversation->channel_id);
                    if (!$channel) {
                        $stats['skipped']++;
                        continue;
                    }

                    $sourceId = $this->resolveSourceId($conversation, $channel);
                    if ($sourceId === null || $sourceId === '') {
                        $stats['skipped']++;
                        continue;
                    }

                    $pivot = DB::transaction(function () use ($conversation, $channel, $sourceId, &$stats) {
                        // Try to find an existing pivot for this (channel, source_id);
                        // if it exists and points to a different contact, leave it
                        // alone — manual reconciliation is required and we don't
                        // silently re-link.
                        $existing = ContactInbox::query()
                            ->where('channel_id', $channel->id)
                            ->where('source_id', $sourceId)
                            ->first();

                        if ($existing) {
                            return $existing;
                        }

                        $stats['created']++;
                        return ContactInbox::create([
                            'contact_id' => $conversation->contact_id,
                            'channel_id' => $channel->id,
                            'source_id' => $sourceId,
                            'source_metadata' => $conversation->contact_name
                                ? ['name' => $conversation->contact_name]
                                : null,
                        ]);
                    });

                    if ($conversation->contact_inbox_id !== $pivot->id) {
                        $conversation->contact_inbox_id = $pivot->id;
                        $conversation->saveQuietly();
                        $stats['linked']++;
                    }
                }
            });

        Log::info('BackfillContactInboxes completed', $stats);
    }

    private function resolveSourceId(Conversation $conversation, Channel $channel): ?string
    {
        $candidate = match ($channel->type) {
            Channel::TYPE_WEB_CHATBOT => $conversation->visitor_id
                ?? $conversation->contact_identifier
                ?? $conversation->external_conversation_id,
            default => $conversation->contact_identifier,
        };

        return $candidate ? (string) $candidate : null;
    }
}
