<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Services\ChannelMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChannelMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 120];

    public function __construct(
        public int $channelId,
        public string $contactId,
        public string $contactName,
        public string $messageText,
    ) {}

    public function handle(ChannelMessageService $messageService): void
    {
        $channel = Channel::find($this->channelId);
        if (!$channel || !$channel->is_active) {
            Log::warning('ProcessChannelMessage: channel not found or inactive', ['channel_id' => $this->channelId]);
            return;
        }

        $messageService->processIncomingMessage(
            $channel,
            $this->contactId,
            $this->contactName,
            $this->messageText,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessChannelMessage: failed after all retries', [
            'channel_id' => $this->channelId,
            'contact_id' => $this->contactId,
            'error' => $exception->getMessage(),
        ]);
    }
}
