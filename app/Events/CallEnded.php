<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
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
            'status' => $this->call->status,
            'duration_seconds' => $this->call->duration_seconds,
            'cost_cents' => $this->call->cost_cents,
            'ended_at' => $this->call->ended_at?->toIso8601String(),
        ];
    }
}
