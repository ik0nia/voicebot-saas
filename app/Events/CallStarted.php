<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Call $call) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->call->tenant_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->call->id,
            'bot_name' => $this->call->bot?->name,
            'caller_number' => $this->call->caller_number,
            'direction' => $this->call->direction,
            'status' => $this->call->status,
            'started_at' => $this->call->started_at?->toIso8601String(),
        ];
    }
}
