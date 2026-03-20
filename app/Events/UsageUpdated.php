<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public float $minutesUsed,
        public float $minutesLimit,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'minutes_used' => $this->minutesUsed,
            'minutes_limit' => $this->minutesLimit,
            'percentage' => $this->minutesLimit > 0
                ? round(($this->minutesUsed / $this->minutesLimit) * 100, 1)
                : 0,
        ];
    }
}
