<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Translates Meta status webhook payloads into messages.delivered_at /
 * read_at updates.
 *
 * WhatsApp and Messenger/Instagram use different shapes:
 *  - WA: entry[].changes[].value.statuses[].{id, status, timestamp}
 *  - FB/IG: entry[].messaging[].{delivery: {mids, watermark}, read: {watermark}}
 *
 * Lookups are by external_message_id which we set in SendReplyJob on
 * successful outbound. If the lookup fails (rare — happens when an old
 * message status arrives after the messages row has been pruned), we
 * log and move on — never throw, webhooks must always 200.
 */
class MessageStatusProcessor
{
    /** WhatsApp Cloud API status events. */
    public function processWhatsAppStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $kind = $status['status'] ?? null;
            $timestampUnix = isset($status['timestamp']) ? (int) $status['timestamp'] : null;

            if ($messageId === null || $kind === null) {
                continue;
            }
            $when = $timestampUnix ? Carbon::createFromTimestamp($timestampUnix) : now();

            $this->markStatus($messageId, $kind, $when);
        }
    }

    /**
     * Facebook Messenger / Instagram delivery+read events.
     *
     * Both providers use a watermark timestamp meaning "everything sent at
     * or before this time has been delivered/read", with mids[] only on
     * delivery. We treat read like a watermark sweep (find all outbound
     * messages on this conversation/recipient with sent_at <= watermark
     * and read_at = null, set read_at = watermark).
     */
    public function processMessengerEvent(int $channelId, string $senderPsid, array $event): void
    {
        if (isset($event['delivery']['mids'])) {
            $watermark = isset($event['delivery']['watermark'])
                ? Carbon::createFromTimestampMs((int) $event['delivery']['watermark'])
                : now();
            foreach ($event['delivery']['mids'] as $mid) {
                $this->markStatus($mid, 'delivered', $watermark);
            }
        }

        if (isset($event['read']['watermark'])) {
            $watermark = Carbon::createFromTimestampMs((int) $event['read']['watermark']);
            // Watermark sweep: mark all outbound messages to this PSID that
            // were sent at or before the watermark as read. Limit by
            // channel_id via the conversation join to avoid cross-tenant
            // contamination.
            $count = Message::query()
                ->where('direction', 'outbound')
                ->whereNull('read_at')
                ->where('sent_at', '<=', $watermark)
                ->whereExists(function ($q) use ($channelId, $senderPsid) {
                    $q->selectRaw(1)
                        ->from('conversations')
                        ->whereColumn('conversations.id', 'messages.conversation_id')
                        ->where('conversations.channel_id', $channelId)
                        ->where('conversations.contact_identifier', $senderPsid);
                })
                ->update(['read_at' => $watermark]);

            if ($count > 0) {
                Log::info('MessageStatusProcessor: read watermark applied', [
                    'channel_id' => $channelId,
                    'count' => $count,
                ]);
            }
        }
    }

    private function markStatus(string $externalMessageId, string $kind, Carbon $when): void
    {
        $message = Message::query()
            ->where('external_message_id', $externalMessageId)
            ->first();

        if (!$message) {
            // Status arrived for a message we don't know about. Either:
            //  - inbound message status (we did not send it)
            //  - we sent it but the row was pruned (months later)
            //  - external_message_id was never recorded (legacy outbound)
            // Quiet log — too noisy for warning level.
            return;
        }

        $field = match ($kind) {
            'sent' => 'sent_at',
            'delivered' => 'delivered_at',
            'read' => 'read_at',
            'failed' => null, // failure is recorded in metadata, not its own column
            default => null,
        };

        if ($field === null) {
            // Track failed status in metadata for the inbox UI
            if ($kind === 'failed') {
                $meta = $message->metadata ?? [];
                $meta['delivery_failed_at'] = $when->toIso8601String();
                $message->metadata = $meta;
                $message->save();
            }
            return;
        }

        if ($message->{$field} !== null) {
            // Already stamped — Meta retransmits status events sometimes;
            // first-write-wins to keep timestamps stable.
            return;
        }

        $message->{$field} = $when;
        $message->save();
    }
}
